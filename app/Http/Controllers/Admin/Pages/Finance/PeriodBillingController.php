<?php

namespace App\Http\Controllers\Admin\Pages\Finance;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\PenerbitanTagihanBatch;
use App\Models\ProgramKuliah;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\Settings\webSettings;
use App\Models\TagihanKuliah;
use App\Models\TemplateTagihan;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Finance\BillingTargetService;
use App\Services\Finance\BulkBillingService;
use App\Services\Finance\FinancialEligibilityService;
use App\Services\Finance\FinancialReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodBillingController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $periods, FinancialReportService $reports): View
    {
        $period = $periods->requireCurrent($request->user());

        return view('user.finance.pages.period-billing-index', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'templates' => TemplateTagihan::forAcademicPeriod($period)->latest()->get(),
            'tagihans' => TagihanKuliah::forAcademicPeriod($period)->latest()->limit(100)->get(),
            'batches' => PenerbitanTagihanBatch::query()->where('taka_id', $period->id)->with('template')->latest()->get(),
            'report' => $reports->forPeriod($period),
            'mahasiswas' => Mahasiswa::query()->whereHas('registrasiAkademik', fn ($query) => $query->where('taka_id', $period->id))->orderBy('mhs_name')->get(),
            'prodis' => ProgramStudi::query()->orderBy('name')->get(),
            'prokus' => ProgramKuliah::query()->where('taka_id', $period->id)->orderBy('name')->get(),
            'academicStatuses' => RegistrasiMahasiswa::academicStatusLabels(),
            'registrations' => RegistrasiMahasiswa::forAcademicPeriod($period)->with('mahasiswa')->orderBy('id')->get(),
            'preview' => null,
        ]);
    }

    public function store(Request $request, AcademicPeriodContext $periods, BillingTargetService $targets): RedirectResponse
    {
        $period = $periods->requireWritableCurrent($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:40'],
            'nominal' => ['required', 'integer', 'min:1'],
            'tanggal_terbit' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:tanggal_terbit'],
            'wajib_lunas_krs' => ['nullable', 'boolean'],
            'target_type' => ['required', 'in:'.implode(',', BillingTargetService::TARGET_TYPES)],
            'target_mahasiswa_id' => ['nullable', 'integer', 'exists:mahasiswas,id'],
            'target_prodi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'target_proku_id' => ['nullable', 'integer', 'exists:program_kuliahs,id'],
            'kelompok_target' => ['nullable', 'string', 'max:40'],
        ]);
        $template = TemplateTagihan::create($data + [
            'taka_id' => $period->id,
            'created_by' => $request->user()->id,
            'wajib_lunas_krs' => $request->boolean('wajib_lunas_krs'),
        ]);

        try {
            $targets->validate($template);
        } catch (\Throwable $exception) {
            $template->delete();
            throw $exception;
        }

        return back()->with('success', 'Template tagihan berhasil dibuat. Gunakan pratinjau sebelum menerbitkan.');
    }

    public function preview(Request $request, TemplateTagihan $template, AcademicPeriodContext $periods, BulkBillingService $billing, FinancialReportService $reports): View
    {
        $period = $periods->requireCurrent($request->user());
        abort_unless($template->taka_id === $period->id, 404);
        $data = $this->index($request, $periods, $reports)->getData();
        $data['preview'] = $billing->preview($template);
        $data['previewTemplate'] = $template;

        return view('user.finance.pages.period-billing-index', $data);
    }

    public function issue(Request $request, TemplateTagihan $template, AcademicPeriodContext $periods, BulkBillingService $billing): RedirectResponse
    {
        $period = $periods->requireWritableCurrent($request->user());
        abort_unless($template->taka_id === $period->id, 404);
        $batch = $billing->issue($template, $request->user());

        return back()->with('success', $batch->ringkasan);
    }

    public function override(Request $request, RegistrasiMahasiswa $registration, AcademicPeriodContext $periods, FinancialEligibilityService $financial): RedirectResponse
    {
        $period = $periods->requireWritableCurrent($request->user());
        abort_unless($registration->taka_id === $period->id, 404);
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $financial->createOverride($registration, $request->user(), $data['alasan'], $data['berlaku_sampai'] ?? null);

        return back()->with('success', 'Override KRS tercatat dalam audit keuangan.');
    }
}
