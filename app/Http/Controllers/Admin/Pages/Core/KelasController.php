<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\ProgramKuliah;
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    use roleTrait;

    public function index(AcademicPeriodContext $context): View
    {
        $period = $context->current(auth()->user());

        return view('user.admin.master.admin-kelas-index', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'selectedPeriod' => $period,
            'canManageClasses' => $period?->isWritable() ?? false,
            'kelas' => Kelas::query()
                ->forAcademicPeriod($period)
                ->with(['taka', 'pstudi', 'proku', 'dosen'])
                ->withCount(['registrasiMahasiswas as mahasiswas_count' => fn ($query) => $query->where('taka_id', $period?->id)])
                ->orderBy('name')
                ->get(),
            'pstudi' => ProgramStudi::query()->orderBy('name')->get(),
            'proku' => ProgramKuliah::query()
                ->when($period, fn ($query) => $query->where('taka_id', $period->id))
                ->when(! $period, fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('name')
                ->get(),
            'dosen' => Dosen::query()->orderBy('dsn_name')->get(),
        ]);
    }

    public function viewMahasiswa(string $code, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();

        return view('user.admin.master.admin-kelas-view-mahasiswa', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'kelas' => $kelas,
            'mahasiswa' => Mahasiswa::query()->forAcademicClass($period, $kelas->id)->get(),
        ]);
    }

    public function cetakMahasiswa(string $code, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();

        return view('base.cetak.cetak-data-kehadiran', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'kelas' => $kelas,
            'mahasiswa' => Mahasiswa::query()->forAcademicClass($period, $kelas->id)->get(),
        ]);
    }

    public function store(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $validated = $this->validateKelas($request, $period->id);

        Kelas::create([
            ...$validated,
            'taka_id' => $period->id,
        ]);

        Alert::success('Berhasil', 'Data kelas berhasil disimpan.');

        return back();
    }

    public function update(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();
        $validated = $this->validateKelas($request, $period->id, $kelas);

        $kelas->update($validated);

        Alert::success('Berhasil', 'Data kelas berhasil diperbarui.');

        return back();
    }

    public function destroy(string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent(auth()->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();

        $kelas->delete();

        Alert::success('Berhasil', 'Data kelas berhasil dihapus.');

        return back();
    }

    private function validateKelas(Request $request, int $periodId, ?Kelas $kelas = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', Rule::unique('kelas', 'code')->ignore($kelas?->id)],
            'capacity' => ['required', 'integer', 'between:1,35'],
            'pstudi_id' => ['required', 'integer', 'exists:program_studis,id'],
            'proku_id' => [
                'nullable',
                'integer',
                Rule::exists('program_kuliahs', 'id')->where(fn ($query) => $query
                    ->where('taka_id', $periodId)
                    ->where('pstudi_id', $request->integer('pstudi_id'))),
            ],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
        ], [
            'proku_id.exists' => 'Program kuliah tidak tersedia pada periode dan program studi yang dipilih.',
        ]);
    }
}
