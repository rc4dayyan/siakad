<?php

namespace Tests\Feature;

use App\Models\HistoryTagihan;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TagihanKuliah;
use App\Models\TemplateTagihan;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\KrsEligibilityService;
use App\Services\Finance\BillingTargetService;
use App\Services\Finance\BulkBillingService;
use App\Services\Finance\FinancialEligibilityService;
use App\Services\Finance\FinancialReportService;
use App\Services\Finance\ManualPaymentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinanceAcademicPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        foreach ([
            '2014_10_12_000000_create_users_table.php',
            '2024_03_09_024013_create_mahasiswas_table.php',
            '2024_03_09_024021_create_dosens_table.php',
            '2024_04_25_082531_create_program_studis_table.php',
            '2024_04_26_060533_create_tahun_akademiks_table.php',
            '2024_04_26_061235_create_program_kuliahs_table.php',
            '2024_04_27_041303_create_kelas_table.php',
            '2024_05_10_080721_create_tagihan_kuliahs_table.php',
            '2024_05_10_081438_create_history_tagihans_table.php',
            '2024_05_14_092028_create_balances_table.php',
            '2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php',
            '2026_07_17_000005_create_registrasi_mahasiswas_table.php',
            '2026_07_17_000010_normalize_period_billing_and_financial_krs_policy.php',
            '2026_07_17_000011_create_period_opening_workflow_and_audit.php',
            '2026_07_18_000001_add_manual_verification_to_history_tagihans.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    public function test_target_must_be_unique_and_belong_to_template_period(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
            'target_prodi_id' => $data['programId'],
        ]);

        $this->expectException(ValidationException::class);
        app(BillingTargetService::class)->validate($template);
    }

    public function test_bulk_preview_issue_and_rerun_are_safe(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, ['target_type' => 'kelompok', 'kelompok_target' => 'aktif']);
        $service = app(BulkBillingService::class);

        $preview = $service->preview($template);
        $this->assertSame(1, $preview['calon']);
        $this->assertSame(1, $preview['siap']);
        $this->assertSame(0, $preview['dilewati']);
        $this->assertSame(500000, $preview['total_nominal']);
        $first = $service->issue($template, $data['actor']);
        $repeatedPreview = $service->preview($template);
        $second = $service->issue($template, $data['actor']);

        $this->assertSame(1, $first->berhasil);
        $this->assertSame(0, $first->gagal);
        $this->assertSame(0, $repeatedPreview['siap']);
        $this->assertSame(1, $repeatedPreview['dilewati']);
        $this->assertSame(0, $repeatedPreview['total_nominal']);
        $this->assertSame(0, $second->berhasil);
        $this->assertSame(1, $second->dilewati);
        $this->assertDatabaseCount('tagihan_kuliahs', 1);
    }

    public function test_required_unpaid_bill_blocks_krs_until_paid_or_overridden(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
            'wajib_lunas_krs' => true,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);
        $service = app(FinancialEligibilityService::class);

        $this->assertFalse($service->isEligible($data['registration']));
        $this->assertFalse(app(KrsEligibilityService::class)->isEligible($data['registration']));
        $bill = TagihanKuliah::firstOrFail();
        HistoryTagihan::create([
            'users_id' => $data['student']->id,
            'stat' => 1,
            'tagihan_code' => $bill->code,
            'desc' => 'Pembayaran tes',
            'code' => 'PAY-ONE',
            'tagihan_kuliah_id' => $bill->id,
            'taka_id' => $data['periodId'],
            'nominal' => $bill->nominal,
            'status' => 'lunas',
            'dibayar_at' => now(),
        ]);
        $this->assertTrue($service->isEligible($data['registration']));

        HistoryTagihan::query()->delete();
        $service->createOverride($data['registration'], $data['actor'], 'Disetujui pimpinan karena pembayaran sedang direkonsiliasi.');
        $this->assertTrue($service->isEligible($data['registration']));
        $this->assertDatabaseHas('override_keuangan_krs', ['actor_id' => $data['actor']->id]);
    }

    public function test_non_required_bill_does_not_block_krs(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
            'wajib_lunas_krs' => false,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);

        $this->assertTrue(app(FinancialEligibilityService::class)->isEligible($data['registration']));
        $this->assertTrue(app(KrsEligibilityService::class)->isEligible($data['registration']));
    }

    public function test_manual_payment_can_be_rejected_resubmitted_and_approved(): void
    {
        Storage::fake('local');
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
            'wajib_lunas_krs' => true,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);
        $bill = TagihanKuliah::firstOrFail();
        $service = app(ManualPaymentService::class);

        $first = $service->submit($bill, $data['student'], UploadedFile::fake()->image('bukti.jpg'), [
            'tanggal_transfer' => '2026-07-10',
            'nama_pengirim' => 'Mahasiswa Pengirim',
            'note' => 'Transfer melalui bank.',
        ]);

        $this->assertSame(HistoryTagihan::STATUS_PENDING, $first->status);
        $this->assertSame(0, $first->stat);
        Storage::disk('local')->assertExists($first->bukti_path);
        $this->assertFalse(app(FinancialEligibilityService::class)->isEligible($data['registration']));

        $rejected = $service->decide($first, $data['actor'], HistoryTagihan::STATUS_REJECTED, 'Bukti transfer tidak terbaca.');
        $this->assertSame(HistoryTagihan::STATUS_REJECTED, $rejected->status);

        $second = $service->submit($bill, $data['student'], UploadedFile::fake()->image('bukti-baru.jpg'), [
            'tanggal_transfer' => '2026-07-10',
            'nama_pengirim' => 'Mahasiswa Pengirim',
        ]);
        $paid = $service->decide($second, $data['actor'], HistoryTagihan::STATUS_PAID, 'Pembayaran sesuai mutasi rekening.');

        $this->assertSame(HistoryTagihan::STATUS_PAID, $paid->status);
        $this->assertSame(1, $paid->stat);
        $this->assertNotNull($paid->dibayar_at);
        $this->assertSame($data['actor']->id, $paid->ditinjau_oleh);
        $this->assertDatabaseCount('balances', 1);
        $this->assertTrue(app(FinancialEligibilityService::class)->isEligible($data['registration']));
    }

    public function test_manual_payment_rejects_duplicate_pending_submission(): void
    {
        Storage::fake('local');
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);
        $bill = TagihanKuliah::firstOrFail();
        $service = app(ManualPaymentService::class);
        $submission = [
            'tanggal_transfer' => '2026-07-10',
            'nama_pengirim' => 'Mahasiswa Pengirim',
        ];
        $service->submit($bill, $data['student'], UploadedFile::fake()->image('bukti.jpg'), $submission);

        $this->expectException(ValidationException::class);
        $service->submit($bill, $data['student'], UploadedFile::fake()->image('duplikat.jpg'), $submission);
    }

    public function test_manual_payment_rejects_a_bill_owned_by_another_student(): void
    {
        Storage::fake('local');
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);
        $otherStudent = Mahasiswa::create([
            'mhs_nim' => 'NIM-LAIN', 'mhs_name' => 'Mahasiswa Lain', 'mhs_code' => 'MHS-LAIN',
            'mhs_user' => 'mhs-lain', 'password' => 'secret', 'mhs_mail' => 'lain@example.test',
            'mhs_phone' => '081299999999',
        ]);

        $this->expectException(ValidationException::class);
        app(ManualPaymentService::class)->submit(
            TagihanKuliah::firstOrFail(),
            $otherStudent,
            UploadedFile::fake()->image('bukti.jpg'),
            ['tanggal_transfer' => '2026-07-10', 'nama_pengirim' => 'Mahasiswa Lain']
        );
    }

    public function test_report_is_isolated_and_old_period_total_does_not_change(): void
    {
        $old = $this->academicData('2025/2026', '20251');
        $template = $this->template($old, ['target_type' => 'mahasiswa', 'target_mahasiswa_id' => $old['student']->id]);
        app(BulkBillingService::class)->issue($template, $old['actor']);
        $reports = app(FinancialReportService::class);
        $before = $reports->forPeriod($old['periodId'])['ringkasan'];

        $new = $this->academicData('2026/2027', '20261');
        $newTemplate = $this->template($new, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $new['student']->id,
            'jenis' => 'registrasi',
            'nominal' => 750000,
        ]);
        app(BulkBillingService::class)->issue($newTemplate, $new['actor']);

        $this->assertSame($before, $reports->forPeriod($old['periodId'])['ringkasan']);
        $this->assertSame(500000, $before['total_tagihan']);
        $this->assertSame(750000, $reports->forPeriod($new['periodId'])['ringkasan']['total_tagihan']);
    }

    public function test_finance_can_prepare_a_legacy_draft_for_preview_safely(): void
    {
        $data = $this->academicData();
        $draft = TagihanKuliah::create([
            'author_id' => (string) $data['actor']->id,
            'proku_id' => '0',
            'prodi_id' => '0',
            'users_id' => (string) $data['student']->id,
            'name' => 'UKT Draft',
            'code' => 'UKT-DRAFT-ONE',
            'price' => '650000',
            'status' => TagihanKuliah::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->post(route('finance.finance.tagihan-prepare', $draft->code), [
                '_form' => 'prepare-billing-'.$draft->id,
                'jenis' => 'ukt',
                'nominal' => 650000,
                'tanggal_terbit' => '2026-07-01',
                'jatuh_tempo' => '2026-08-01',
                'wajib_lunas_krs' => '1',
            ]);

        $template = TemplateTagihan::where('name', 'UKT Draft')->firstOrFail();

        $response->assertRedirect(route('finance.billing-period.preview', $template));
        $this->assertSame(TagihanKuliah::STATUS_DIBATALKAN, $draft->fresh()->status);
        $this->assertSame($data['periodId'], $template->taka_id);
        $this->assertSame($data['student']->id, $template->target_mahasiswa_id);
        $this->assertSame(650000, $template->nominal);
        $this->assertTrue($template->wajib_lunas_krs);
        $this->assertDatabaseHas('academic_workflow_audits', [
            'event' => 'billing.draft_prepared',
            'subject_id' => $draft->id,
            'actor_id' => $data['actor']->id,
        ]);
    }

    public function test_prepared_draft_cannot_be_processed_twice(): void
    {
        $data = $this->academicData();
        $draft = TagihanKuliah::create([
            'author_id' => (string) $data['actor']->id,
            'proku_id' => '0',
            'prodi_id' => '0',
            'users_id' => (string) $data['student']->id,
            'name' => 'Draft Sudah Diproses',
            'code' => 'UKT-DRAFT-TWO',
            'price' => '500000',
            'status' => TagihanKuliah::STATUS_DIBATALKAN,
        ]);

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->from(route('finance.finance.tagihan-index'))
            ->post(route('finance.finance.tagihan-prepare', $draft->code), [
                '_form' => 'prepare-billing-'.$draft->id,
                'jenis' => 'ukt',
                'nominal' => 500000,
                'tanggal_terbit' => '2026-07-01',
                'jatuh_tempo' => '2026-08-01',
                'wajib_lunas_krs' => '0',
            ]);

        $response->assertRedirect(route('finance.finance.tagihan-index'));
        $response->assertSessionHasErrors('status');
        $this->assertDatabaseCount('template_tagihans', 0);
    }

    public function test_prepare_rejects_a_non_standard_billing_type_without_changing_the_draft(): void
    {
        $data = $this->academicData();
        $draft = TagihanKuliah::create([
            'author_id' => (string) $data['actor']->id,
            'proku_id' => '0',
            'prodi_id' => '0',
            'users_id' => (string) $data['student']->id,
            'name' => 'Draft Jenis Tidak Valid',
            'code' => 'UKT-DRAFT-INVALID-TYPE',
            'price' => '500000',
            'status' => TagihanKuliah::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->from(route('finance.finance.tagihan-index'))
            ->post(route('finance.finance.tagihan-prepare', $draft->code), [
                '_form' => 'prepare-billing-'.$draft->id,
                'jenis' => 'jenis-bebas',
                'nominal' => 500000,
                'tanggal_terbit' => '2026-07-01',
                'jatuh_tempo' => '2026-08-01',
                'wajib_lunas_krs' => '0',
            ]);

        $response->assertRedirect(route('finance.finance.tagihan-index'));
        $response->assertSessionHasErrors('jenis');
        $this->assertSame(TagihanKuliah::STATUS_DRAFT, $draft->fresh()->status);
        $this->assertDatabaseCount('template_tagihans', 0);
    }

    public function test_unused_prepared_template_can_be_deleted_and_restores_its_source_draft(): void
    {
        $data = $this->academicData();
        $draft = TagihanKuliah::create([
            'author_id' => (string) $data['actor']->id,
            'proku_id' => '0',
            'prodi_id' => '0',
            'users_id' => (string) $data['student']->id,
            'name' => 'Draft Dapat Dipulihkan',
            'code' => 'UKT-DRAFT-RESTORE',
            'price' => '500000',
            'status' => TagihanKuliah::STATUS_DRAFT,
        ]);
        $session = [AcademicPeriodContext::SESSION_KEY => $data['periodId']];

        $this->actingAs($data['actor'])->withSession($session)
            ->post(route('finance.finance.tagihan-prepare', $draft->code), [
                '_form' => 'prepare-billing-'.$draft->id,
                'jenis' => 'ukt',
                'nominal' => 500000,
                'tanggal_terbit' => '2026-07-01',
                'jatuh_tempo' => '2026-08-01',
                'wajib_lunas_krs' => '0',
            ]);
        $template = TemplateTagihan::where('name', 'Draft Dapat Dipulihkan')->firstOrFail();

        $response = $this->actingAs($data['actor'])->withSession($session)
            ->delete(route('finance.billing-period.destroy', $template));

        $response->assertRedirect(route('finance.billing-period.index'));
        $this->assertDatabaseMissing('template_tagihans', ['id' => $template->id]);
        $this->assertSame(TagihanKuliah::STATUS_DRAFT, $draft->fresh()->status);
        $this->assertDatabaseHas('academic_workflow_audits', [
            'event' => 'billing.template_deleted',
            'subject_id' => $template->id,
        ]);
    }

    public function test_issued_template_cannot_be_deleted(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->from(route('finance.billing-period.index'))
            ->delete(route('finance.billing-period.destroy', $template));

        $response->assertRedirect(route('finance.billing-period.index'));
        $response->assertSessionHasErrors('template');
        $this->assertDatabaseHas('template_tagihans', ['id' => $template->id]);
        $this->assertDatabaseCount('tagihan_kuliahs', 1);
    }

    public function test_unused_template_can_be_updated(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
        ]);

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->patch(route('finance.billing-period.update', $template), [
                '_form' => 'edit-billing-template-'.$template->id,
                'name' => 'Registrasi Semester Ganjil',
                'jenis' => 'registrasi',
                'nominal' => 725000,
                'tanggal_terbit' => '2026-07-05',
                'jatuh_tempo' => '2026-08-05',
                'wajib_lunas_krs' => '1',
                'target_type' => 'kelompok',
                'target_mahasiswa_id' => $data['student']->id,
                'target_prodi_id' => null,
                'target_proku_id' => null,
                'kelompok_target' => 'aktif',
            ]);

        $response->assertRedirect(route('finance.billing-period.index'));
        $template->refresh();
        $this->assertSame('Registrasi Semester Ganjil', $template->name);
        $this->assertSame('registrasi', $template->jenis);
        $this->assertSame(725000, $template->nominal);
        $this->assertTrue($template->wajib_lunas_krs);
        $this->assertSame('kelompok', $template->target_type);
        $this->assertSame('aktif', $template->kelompok_target);
        $this->assertNull($template->target_mahasiswa_id);
        $this->assertDatabaseHas('academic_workflow_audits', [
            'event' => 'billing.template_updated',
            'subject_id' => $template->id,
            'actor_id' => $data['actor']->id,
        ]);
    }

    public function test_issued_template_cannot_be_updated(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->from(route('finance.billing-period.index'))
            ->patch(route('finance.billing-period.update', $template), [
                '_form' => 'edit-billing-template-'.$template->id,
                'name' => 'Nama Tidak Boleh Berubah',
                'jenis' => 'ukt',
                'nominal' => 900000,
                'tanggal_terbit' => '2026-07-01',
                'jatuh_tempo' => '2026-08-01',
                'wajib_lunas_krs' => '0',
                'target_type' => 'mahasiswa',
                'target_mahasiswa_id' => $data['student']->id,
                'target_prodi_id' => null,
                'target_proku_id' => null,
                'kelompok_target' => null,
            ]);

        $response->assertRedirect(route('finance.billing-period.index'));
        $response->assertSessionHasErrors('template');
        $this->assertSame('UKT Semester', $template->fresh()->name);
        $this->assertSame(500000, $template->fresh()->nominal);
        $this->assertDatabaseMissing('academic_workflow_audits', [
            'event' => 'billing.template_updated',
            'subject_id' => $template->id,
        ]);
    }

    private function academicData(string $name = '2026/2027', string $code = '20261'): array
    {
        $actor = User::firstOrCreate(['email' => 'finance@example.test'], [
            'type' => 1, 'code' => 'FINANCE', 'name' => 'Staf Finance', 'user' => 'finance',
            'phone' => '0812000000', 'password' => 'secret', 'status' => 1,
        ]);
        $periodId = DB::table('tahun_akademiks')->insertGetId([
            'name' => $name, 'code' => $code, 'semester' => 1,
            'year_start' => (int) substr($code, 0, 4), 'year_end' => (int) substr($code, 0, 4) + 1,
            'term' => 'ganjil', 'status' => 'active', 'is_active' => 1,
            'starts_at' => substr($code, 0, 4).'-07-01', 'ends_at' => ((int) substr($code, 0, 4) + 1).'-06-30',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $programId = DB::table('program_studis')->insertGetId([
            'faku_id' => 1, 'name' => 'PAI '.$code, 'cnim' => $code, 'code' => 'PAI'.$code,
            'slug' => 'pai-'.$code, 'head_id' => 0, 'title' => 'S.Pd', 'level' => 'S1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $prokuId = DB::table('program_kuliahs')->insertGetId([
            'taka_id' => $periodId, 'pstudi_id' => $programId, 'name' => 'Reguler '.$code,
            'code' => 'REG-'.$code, 'wave' => 'I', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $periodId, 'pstudi_id' => $programId, 'proku_id' => $prokuId,
            'name' => 'Kelas '.$code, 'code' => 'KLS-'.$code, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = Mahasiswa::create([
            'mhs_nim' => 'NIM'.$code, 'mhs_name' => 'Mahasiswa '.$code, 'mhs_code' => 'MHS'.$code,
            'mhs_user' => 'mhs'.$code, 'password' => 'secret', 'mhs_mail' => 'mhs'.$code.'@example.test',
            'mhs_phone' => '08'.$code.'1111',
        ]);
        $registration = RegistrasiMahasiswa::create([
            'taka_id' => $periodId, 'mahasiswa_id' => $student->id, 'kelas_id' => $classId,
            'semester_mahasiswa' => 1, 'status_akademik' => 'aktif', 'status_registrasi' => 'terdaftar', 'batas_sks' => 24,
        ]);

        return compact('actor', 'periodId', 'programId', 'prokuId', 'classId', 'student', 'registration');
    }

    private function template(array $data, array $overrides = []): TemplateTagihan
    {
        return TemplateTagihan::create(array_merge([
            'taka_id' => $data['periodId'], 'name' => 'UKT Semester', 'jenis' => 'ukt', 'nominal' => 500000,
            'tanggal_terbit' => '2026-07-01', 'jatuh_tempo' => '2026-08-01', 'wajib_lunas_krs' => false,
            'target_type' => 'mahasiswa', 'target_mahasiswa_id' => null, 'target_prodi_id' => null,
            'target_proku_id' => null, 'kelompok_target' => null, 'created_by' => $data['actor']->id,
        ], $overrides));
    }
}
