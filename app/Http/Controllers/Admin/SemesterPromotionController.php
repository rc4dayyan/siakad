<?php

namespace App\Http\Controllers\Admin;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\ProsesKenaikanSemester;
use App\Models\Settings\webSettings;
use App\Models\TahunAkademik;
use App\Services\Academic\SemesterPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SemesterPromotionController extends Controller
{
    use roleTrait;

    public function index(): View
    {
        return view('user.admin.pages.student-promotion-index', $this->pageData());
    }

    public function preview(Request $request, SemesterPromotionService $service): View
    {
        $configuration = $this->configuration($request);
        $preview = $service->preview(
            $configuration['sourcePeriod'],
            $configuration['targetPeriod'],
            $configuration['sourceClass'],
            $configuration['targetClass'],
            $configuration['leaveAction'],
            $configuration['inactiveAction']
        );

        return view('user.admin.pages.student-promotion-index', $this->pageData([
            'preview' => $preview,
            'selection' => $request->only([
                'periode_sumber',
                'periode_tujuan',
                'kelas_sumber',
                'kelas_tujuan',
                'dosen_wali',
                'keputusan_cuti',
                'keputusan_nonaktif',
            ]),
        ]));
    }

    public function execute(Request $request, SemesterPromotionService $service): RedirectResponse
    {
        $configuration = $this->configuration($request);
        $run = $service->execute(
            $configuration['sourcePeriod'],
            $configuration['targetPeriod'],
            $configuration['sourceClass'],
            $configuration['targetClass'],
            $configuration['advisor'],
            $configuration['leaveAction'],
            $configuration['inactiveAction'],
            $request->user()
        );

        Alert::success(
            'Kenaikan semester selesai',
            "Berhasil {$run->jumlah_berhasil}, dilewati {$run->jumlah_dilewati}, gagal {$run->jumlah_gagal}."
        );

        return redirect()->route($this->setPrefix().'workers.student-promotion-index');
    }

    private function configuration(Request $request): array
    {
        $validated = $request->validate([
            'periode_sumber' => ['required', 'string', 'exists:tahun_akademiks,code', 'different:periode_tujuan'],
            'periode_tujuan' => ['required', 'string', 'exists:tahun_akademiks,code'],
            'kelas_sumber' => ['required', 'string', 'exists:kelas,code', 'different:kelas_tujuan'],
            'kelas_tujuan' => ['required', 'string', 'exists:kelas,code'],
            'dosen_wali' => ['required', 'string', 'exists:dosens,dsn_code'],
            'keputusan_cuti' => ['required', Rule::in(SemesterPromotionService::statusActions())],
            'keputusan_nonaktif' => ['required', Rule::in(SemesterPromotionService::statusActions())],
        ], [
            'periode_sumber.required' => 'Periode sumber wajib dipilih.',
            'periode_tujuan.required' => 'Periode tujuan wajib dipilih.',
            'periode_sumber.different' => 'Periode sumber dan tujuan harus berbeda.',
            'kelas_sumber.required' => 'Kelas sumber wajib dipilih.',
            'kelas_tujuan.required' => 'Kelas tujuan wajib dipilih.',
            'kelas_sumber.different' => 'Kelas sumber dan tujuan harus berbeda.',
            'dosen_wali.required' => 'Dosen wali tujuan wajib dipilih.',
            'keputusan_cuti.required' => 'Keputusan mahasiswa cuti wajib dipilih.',
            'keputusan_nonaktif.required' => 'Keputusan mahasiswa nonaktif wajib dipilih.',
        ]);

        return [
            'sourcePeriod' => TahunAkademik::where('code', $validated['periode_sumber'])->firstOrFail(),
            'targetPeriod' => TahunAkademik::where('code', $validated['periode_tujuan'])->firstOrFail(),
            'sourceClass' => Kelas::where('code', $validated['kelas_sumber'])->firstOrFail(),
            'targetClass' => Kelas::where('code', $validated['kelas_tujuan'])->firstOrFail(),
            'advisor' => Dosen::where('dsn_code', $validated['dosen_wali'])->firstOrFail(),
            'leaveAction' => $validated['keputusan_cuti'],
            'inactiveAction' => $validated['keputusan_nonaktif'],
        ];
    }

    private function pageData(array $additional = []): array
    {
        return array_merge([
            'prefix' => $this->setPrefix(),
            'web' => webSettings::where('id', 1)->first(),
            'periods' => TahunAkademik::query()->with('kelas')->orderByDesc('starts_at')->get(),
            'advisors' => Dosen::query()->where('dsn_stat', 1)->orderBy('dsn_name')->get(),
            'actions' => SemesterPromotionService::statusActions(),
            'preview' => null,
            'selection' => [],
            'runs' => ProsesKenaikanSemester::query()
                ->with(['periodeSumber', 'periodeTujuan', 'kelasSumber', 'kelasTujuan', 'diprosesOleh'])
                ->latest()
                ->limit(10)
                ->get(),
        ], $additional);
    }
}
