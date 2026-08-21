<?php

namespace App\Http\Controllers\Admin\Pages\Finance;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\AcademicWorkflowAudit;
use App\Models\Mahasiswa;
use App\Models\ProgramKuliah;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\Settings\webSettings;
use App\Models\TagihanKuliah;
use App\Models\TemplateTagihan;
use App\Services\Academic\AcademicAuditService;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Finance\BillingTargetService;
use App\Services\Finance\BulkBillingService;
use App\Services\Finance\FinancialEligibilityService;
use App\Services\Finance\FinancialReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PeriodBillingController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $periods, FinancialReportService $reports): View
    {
        $period = $periods->requireCurrent($request->user());
        $templatePerPage = (int) $request->input('template_per_page', 10);
        if (! in_array($templatePerPage, [10, 25, 50], true)) {
            $templatePerPage = 10;
        }

        return view('user.finance.pages.period-billing-index', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'templates' => TemplateTagihan::forAcademicPeriod($period)
                ->with(['targetMahasiswa', 'targetProdi', 'targetProku'])
                ->withCount(['tagihans', 'penerbitanBatches'])
                ->latest()
                ->paginate($templatePerPage, ['*'], 'template_page')
                ->withQueryString(),
            'templatePerPage' => $templatePerPage,
            'tagihans' => TagihanKuliah::forAcademicPeriod($period)->latest()->limit(100)->get(),
            'report' => $reports->forPeriod($period),
            'mahasiswas' => Mahasiswa::query()->whereHas('registrasiAkademik', fn ($query) => $query->where('taka_id', $period->id))->orderBy('mhs_name')->get(),
            'prodis' => ProgramStudi::query()->orderBy('name')->get(),
            'prokus' => ProgramKuliah::query()->where('taka_id', $period->id)->orderBy('name')->get(),
            'academicStatuses' => RegistrasiMahasiswa::academicStatusLabels(),
            'registrations' => RegistrasiMahasiswa::forAcademicPeriod($period)->with('mahasiswa')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, AcademicPeriodContext $periods, BillingTargetService $targets): RedirectResponse
    {
        $period = $periods->requireWritableCurrent($request->user());
        $data = $this->validateTemplate($request);
        $data['wajib_lunas_krs'] = $request->boolean('wajib_lunas_krs');
        $template = TemplateTagihan::create($data + [
            'taka_id' => $period->id,
            'created_by' => $request->user()->id,
        ]);

        try {
            $targets->validate($template);
        } catch (\Throwable $exception) {
            $template->delete();
            throw $exception;
        }

        return back()->with('success', 'Template tagihan berhasil dibuat. Gunakan pratinjau sebelum menerbitkan.');
    }

    public function update(
        Request $request,
        TemplateTagihan $template,
        AcademicPeriodContext $periods,
        BillingTargetService $targets,
        AcademicAuditService $audit
    ): RedirectResponse {
        $period = $periods->requireWritableCurrent($request->user());
        abort_unless($template->taka_id === $period->id, 404);
        $data = $this->validateTemplate($request);
        $data['wajib_lunas_krs'] = $request->boolean('wajib_lunas_krs');

        DB::transaction(function () use ($request, $template, $period, $targets, $audit, $data): void {
            $lockedTemplate = TemplateTagihan::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();

            if ($lockedTemplate->tagihans()->exists() || $lockedTemplate->penerbitanBatches()->exists()) {
                throw ValidationException::withMessages([
                    'template' => 'Template yang sudah diterbitkan tidak dapat diubah karena diperlukan untuk audit.',
                ]);
            }

            $before = $lockedTemplate->only(array_keys($data));
            $lockedTemplate->fill($data);
            $targets->validate($lockedTemplate);
            $lockedTemplate->save();

            $audit->record(
                'billing.template_updated',
                $lockedTemplate,
                $period->id,
                $request->user(),
                $before,
                $lockedTemplate->only(array_keys($data))
            );
        });

        return redirect()->route($this->setPrefix().'billing-period.index')
            ->with('success', 'Template tagihan berhasil diperbarui. Periksa kembali pratinjau sebelum menerbitkan.');
    }

    public function preview(
        Request $request,
        TemplateTagihan $template,
        AcademicPeriodContext $periods,
        BulkBillingService $billing,
        BillingTargetService $targets
    ): View {
        $period = $periods->requireCurrent($request->user());
        abort_unless($template->taka_id === $period->id, 404);

        return view('user.finance.pages.period-billing-preview', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'template' => $template->load(['targetMahasiswa', 'targetProdi', 'targetProku']),
            'preview' => $billing->preview($template),
            'registrations' => $targets->candidateQuery($template)->paginate(25)->withQueryString(),
        ]);
    }

    public function issue(Request $request, TemplateTagihan $template, AcademicPeriodContext $periods, BulkBillingService $billing): RedirectResponse
    {
        $period = $periods->requireWritableCurrent($request->user());
        abort_unless($template->taka_id === $period->id, 404);
        $batch = $billing->issue($template, $request->user());

        return back()->with('success', $batch->ringkasan);
    }

    public function destroy(
        Request $request,
        TemplateTagihan $template,
        AcademicPeriodContext $periods,
        AcademicAuditService $audit
    ): RedirectResponse {
        $period = $periods->requireWritableCurrent($request->user());
        abort_unless($template->taka_id === $period->id, 404);

        $restoredDraft = DB::transaction(function () use ($request, $template, $period, $audit): ?TagihanKuliah {
            $lockedTemplate = TemplateTagihan::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();

            if ($lockedTemplate->tagihans()->exists() || $lockedTemplate->penerbitanBatches()->exists()) {
                throw ValidationException::withMessages([
                    'template' => 'Template yang sudah digunakan untuk penerbitan tidak dapat dihapus karena diperlukan untuk audit.',
                ]);
            }

            $preparationAudit = AcademicWorkflowAudit::query()
                ->where('event', 'billing.draft_prepared')
                ->where('metadata->template_id', $lockedTemplate->id)
                ->latest()
                ->first();
            $draft = $preparationAudit?->subject_type === TagihanKuliah::class
                ? TagihanKuliah::query()->whereKey($preparationAudit->subject_id)->lockForUpdate()->first()
                : null;

            if ($draft?->status === TagihanKuliah::STATUS_DIBATALKAN) {
                $draft->update(['status' => TagihanKuliah::STATUS_DRAFT]);
            } else {
                $draft = null;
            }

            $audit->record(
                'billing.template_deleted',
                $lockedTemplate,
                $period->id,
                $request->user(),
                ['name' => $lockedTemplate->name, 'jenis' => $lockedTemplate->jenis],
                null,
                ['restored_draft_id' => $draft?->id]
            );
            $lockedTemplate->delete();

            return $draft;
        });

        $message = 'Template yang belum digunakan berhasil dihapus.';
        if ($restoredDraft) {
            $message .= ' Draft sumber dipulihkan dan dapat diproses kembali.';
        }

        return redirect()->route($this->setPrefix().'billing-period.index')->with('success', $message);
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

    private function validateTemplate(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(array_keys(TemplateTagihan::JENIS_LABELS))],
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

        foreach ([
            'mahasiswa' => 'target_mahasiswa_id',
            'prodi' => 'target_prodi_id',
            'proku' => 'target_proku_id',
            'kelompok' => 'kelompok_target',
        ] as $targetType => $field) {
            $data[$field] = $data['target_type'] === $targetType ? ($data[$field] ?? null) : null;
        }

        return $data;
    }
}
