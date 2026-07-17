<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\PeriodCopyRun;
use App\Models\PeriodReadinessSnapshot;
use App\Models\Settings\webSettings;
use App\Models\TahunAkademik;
use App\Services\Academic\AcademicIntegrityService;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\PeriodConfigurationCopyService;
use App\Services\Academic\PeriodPublicationService;
use App\Services\Academic\PeriodReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodOpeningController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $periods, PeriodReadinessService $readiness, AcademicIntegrityService $integrity): View
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 1, 3, 4], true), 403);
        $period = $periods->requireCurrent($request->user());
        $result = $readiness->check($period);
        $allowedKeys = match ((int) $request->user()->raw_type) {
            1 => ['tagihan'],
            3, 4 => ['identitas', 'kurikulum_prodi', 'registrasi_kelas', 'penawaran_dosen', 'jadwal'],
            default => collect($result['checks'])->pluck('key')->all(),
        };
        $visible = collect($result['checks'])->whereIn('key', $allowedKeys)->values();
        $prefix = $this->setPrefix();

        return view('user.admin.period-opening-index', [
            'web' => webSettings::find(1),
            'prefix' => $prefix,
            'period' => $period,
            'result' => $result,
            'checks' => $visible,
            'canPublish' => (int) $request->user()->raw_type === 0,
            'canCopy' => in_array((int) $request->user()->raw_type, [0, 3], true),
            'periods' => TahunAkademik::query()->orderByDesc('year_start')->get(),
            'snapshots' => PeriodReadinessSnapshot::where('taka_id', $period->id)->latest('checked_at')->limit(10)->get(),
            'copyRuns' => PeriodCopyRun::where('target_period_id', $period->id)->latest()->limit(10)->get(),
            'preview' => session('copy_preview'),
            'integrity' => (int) $request->user()->raw_type === 0 ? $integrity->report() : null,
        ]);
    }

    public function inspect(Request $request, AcademicPeriodContext $periods, PeriodReadinessService $readiness): RedirectResponse
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 1, 3, 4], true), 403);
        $snapshot = $readiness->snapshot($periods->requireCurrent($request->user()), $request->user());

        return back()->with('success', "Pemeriksaan disimpan: {$snapshot->ready_count} siap, {$snapshot->warning_count} peringatan, {$snapshot->failed_count} gagal.");
    }

    public function publish(Request $request, AcademicPeriodContext $periods, PeriodPublicationService $publication): RedirectResponse
    {
        $period = $periods->requireCurrent($request->user());
        $publication->publish($period, $request->user());

        return back()->with('success', 'Periode berhasil dipublikasikan ke portal dosen dan mahasiswa.');
    }

    public function copyPreview(Request $request, PeriodConfigurationCopyService $copy): RedirectResponse
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 3], true), 403);
        $data = $this->validateCopy($request);
        $source = TahunAkademik::findOrFail($data['source_period_id']);
        $target = TahunAkademik::findOrFail($data['target_period_id']);

        return back()->withInput()->with('copy_preview', $copy->preview($source, $target, $data['selections']));
    }

    public function copyExecute(Request $request, PeriodConfigurationCopyService $copy): RedirectResponse
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 3], true), 403);
        $data = $this->validateCopy($request);
        $run = $copy->execute(
            TahunAkademik::findOrFail($data['source_period_id']),
            TahunAkademik::findOrFail($data['target_period_id']),
            $data['selections'],
            $request->user()
        );

        return back()->with('success', "Salin konfigurasi selesai: {$run->created_count} dibuat, {$run->skipped_count} dilewati, {$run->conflict_count} konflik.");
    }

    private function validateCopy(Request $request): array
    {
        return $request->validate([
            'source_period_id' => ['required', 'integer', 'different:target_period_id', 'exists:tahun_akademiks,id'],
            'target_period_id' => ['required', 'integer', 'exists:tahun_akademiks,id'],
            'selections' => ['required', 'array', 'min:1'],
            'selections.*' => ['string', Rule::in(PeriodConfigurationCopyService::SELECTIONS)],
        ]);
    }
}
