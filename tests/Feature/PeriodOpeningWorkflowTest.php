<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\HistoryTagihan;
use App\Models\JadwalMingguan;
use App\Models\KalenderAkademik;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\Notification;
use App\Models\PenawaranMataKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\TemplateTagihan;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AcademicStatusService;
use App\Services\Academic\AdminKrsManagementService;
use App\Services\Academic\KrsBulkImportService;
use App\Services\Academic\KrsService;
use App\Services\Academic\PeriodConfigurationCopyService;
use App\Services\Academic\PeriodPublicationService;
use App\Services\Academic\PeriodReadinessService;
use App\Services\Finance\BulkBillingService;
use App\Services\Finance\FinancialEligibilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PeriodOpeningWorkflowTest extends TestCase
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
            '2024_04_28_063053_create_kurikulums_table.php',
            '2024_04_28_035926_create_gedungs_table.php',
            '2024_04_28_052322_create_ruangs_table.php',
            '2024_04_30_032644_create_mata_kuliahs_table.php',
            '2024_04_30_055648_create_jadwal_kuliahs_table.php',
            '2024_04_30_102751_create_absensi_mahasiswas_table.php',
            '2024_05_10_080721_create_tagihan_kuliahs_table.php',
            '2024_05_10_081438_create_history_tagihans_table.php',
            '2024_05_23_095204_create_ticket_supports_table.php',
            '2024_05_30_004205_create_notifications_table.php',
            '2024_06_26_050556_create_web_settings_table.php',
            '2025_07_05_091153_create_nilai_mahasiswas_table.php',
            '2025_07_05_112548_add_matakuliah_kelas_id.php',
            '2026_07_17_000001_create_master_mata_kuliahs_table.php',
            '2026_07_17_000002_add_mid_to_mata_kuliahs_table.php',
            '2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php',
            '2026_07_17_000005_create_registrasi_mahasiswas_table.php',
            '2026_07_17_000006_create_riwayat_status_akademik_mahasiswas_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::statement('ALTER TABLE nilai_mahasiswas ADD COLUMN taka_id INTEGER NULL');
        (require database_path('migrations/2026_07_17_000008_create_course_offerings_and_krs_tables.php'))->up();
        (require database_path('migrations/2026_07_17_000009_create_weekly_schedules_and_course_meetings.php'))->up();
        (require database_path('migrations/2026_07_17_000010_normalize_period_billing_and_financial_krs_policy.php'))->up();
        (require database_path('migrations/2026_07_17_000011_create_period_opening_workflow_and_audit.php'))->up();
        DB::table('web_settings')->insert([
            'school_apps' => 'SIAKAD', 'school_name' => 'Kampus Uji', 'school_head' => 'Ketua',
            'school_link' => 'https://example.test', 'school_desc' => '-', 'school_email' => 'info@example.test',
            'school_phone' => '0800', 'social_fb' => '-', 'social_ig' => '-', 'social_in' => '-', 'social_tw' => '-',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_readiness_fails_and_publication_rolls_back_when_required_data_is_missing(): void
    {
        $actor = $this->actor();
        $period = $this->period('EMPTY', TahunAkademik::STATUS_ACTIVE, true);
        $result = app(PeriodReadinessService::class)->check($period);

        $this->assertSame('gagal', $result['status']);
        $this->assertGreaterThan(0, $result['counts']['gagal']);

        try {
            app(PeriodPublicationService::class)->publish($period, $actor);
            $this->fail('Publikasi periode yang belum siap harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('publication', $exception->errors());
        }

        $this->assertFalse($period->fresh()->is_published);
        $this->assertDatabaseCount('period_publications', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseHas('period_readiness_snapshots', ['taka_id' => $period->id, 'status' => 'gagal']);
    }

    public function test_end_to_end_period_opening_publication_krs_and_close_are_isolated(): void
    {
        $data = $this->readyPeriod();
        $context = app(AcademicPeriodContext::class);
        $this->assertNull($context->published());
        $this->assertSame('siap', app(PeriodReadinessService::class)->check($data['period'])['status']);

        app(PeriodPublicationService::class)->publish($data['period'], $data['actor']);
        $this->assertTrue($data['period']->fresh()->is_published);
        $this->assertSame($data['period']->id, $context->published()?->id);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'period.published', 'actor_id' => $data['actor']->id]);

        $krsService = app(KrsService::class);
        $krs = $krsService->forRegistration($data['registration']);
        $krsService->add($krs, $data['offering']);
        $krsService->submit($krs->fresh());
        $approved = $krsService->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, 'Disetujui.');
        $this->assertSame(Krs::STATUS_APPROVED, $approved->status);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'krs.decided', 'actor_reference_id' => $data['advisor']->id]);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'billing.batch_issued', 'actor_id' => $data['actor']->id]);
        app(FinancialEligibilityService::class)->createOverride(
            $data['registration'], $data['actor'], 'Rekonsiliasi pembayaran telah disetujui pimpinan.'
        );
        app(AcademicStatusService::class)->change(
            $data['registration']->fresh(), RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI,
            'Cuti akademik atas permohonan mahasiswa.', CarbonImmutable::today(), $data['actor']
        );
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'krs.financial_override_created', 'actor_id' => $data['actor']->id]);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'student.academic_status_changed', 'actor_id' => $data['actor']->id]);

        $otherStudent = $this->student('SECOND');
        $otherRegistration = RegistrasiMahasiswa::create([
            'mahasiswa_id' => $otherStudent->id, 'taka_id' => $data['period']->id, 'semester_mahasiswa' => 1,
            'status_akademik' => 'aktif', 'status_registrasi' => 'terdaftar', 'kelas_id' => $data['classId'],
            'dosen_wali_id' => $data['advisor']->id, 'batas_sks' => 24,
        ]);
        $draft = $krsService->forRegistration($otherRegistration);
        $data['period']->update(['status' => TahunAkademik::STATUS_CLOSED, 'is_active' => false, 'is_published' => false]);
        $this->assertNull($context->published());

        $this->expectException(ValidationException::class);
        $krsService->add($draft, $data['offering']);
    }

    public function test_configuration_copy_is_selective_transaction_safe_and_idempotent(): void
    {
        $actor = $this->actor();
        $source = $this->period('SRC', TahunAkademik::STATUS_CLOSED);
        $target = $this->period('DST', TahunAkademik::STATUS_DRAFT);
        $advisor = $this->advisor();
        $programId = $this->program();
        $sourceProku = $this->programKuliah($source, $programId, 'Reguler');
        $this->programKuliah($target, $programId, 'Reguler');
        DB::table('kelas')->insert([
            'taka_id' => $source->id, 'pstudi_id' => $programId, 'proku_id' => $sourceProku,
            'dosen_id' => $advisor->id, 'capacity' => 30, 'name' => 'Kelas Salin', 'code' => 'SRC-CLASS',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        TemplateTagihan::create([
            'taka_id' => $source->id, 'name' => 'Registrasi Ulang', 'jenis' => 'registrasi', 'nominal' => 250000,
            'tanggal_terbit' => $source->starts_at, 'jatuh_tempo' => $source->starts_at->copy()->addMonth(),
            'wajib_lunas_krs' => false, 'target_type' => 'kelompok', 'kelompok_target' => 'semua', 'created_by' => $actor->id,
        ]);
        $service = app(PeriodConfigurationCopyService::class);
        $preview = $service->preview($source, $target, ['kelas', 'dosen', 'template_tagihan']);
        $this->assertSame(['KRS', 'nilai', 'pembayaran', 'presensi'], $preview['excluded']);

        $first = $service->execute($source, $target, ['kelas', 'dosen', 'template_tagihan'], $actor);
        $second = $service->execute($source, $target, ['kelas', 'dosen', 'template_tagihan'], $actor);
        $this->assertSame(2, $first->created_count);
        $this->assertSame(0, $second->created_count);
        $this->assertGreaterThanOrEqual(2, $second->skipped_count);
        $this->assertDatabaseCount('krs', 0);
        $this->assertDatabaseCount('history_tagihans', 0);
    }

    public function test_dashboard_is_filtered_for_finance_and_forbidden_for_support(): void
    {
        $period = $this->period('DASH', TahunAkademik::STATUS_DRAFT);
        $finance = User::create([
            'type' => 1, 'code' => 'FIN', 'name' => 'Finance', 'user' => 'finance', 'phone' => '08111',
            'email' => 'finance@example.test', 'password' => 'secret', 'status' => 1,
        ]);
        $this->actingAs($finance)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->get(route('finance.period-opening.index'))
            ->assertOk()
            ->assertSee('Tagihan wajib')
            ->assertDontSee('Jadwal dan bentrok');

        $support = User::create([
            'type' => 5, 'code' => 'SUP', 'name' => 'Support', 'user' => 'support', 'phone' => '08222',
            'email' => 'support@example.test', 'password' => 'secret', 'status' => 1,
        ]);
        $this->actingAs($support)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->get(route('support.period-opening.index'))
            ->assertForbidden();
    }

    public function test_period_opening_wizard_is_available_only_to_web_administrator(): void
    {
        $period = $this->period('WIZARD', TahunAkademik::STATUS_DRAFT);
        $admin = $this->actor();

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->get(route('web-admin.period-opening.wizard'))
            ->assertOk()
            ->assertSee('Wizard Aktivasi Periode Baru')
            ->assertSee('Akademik: registrasi dan kelas')
            ->assertSee('Keuangan: tagihan periode');

        $finance = User::create([
            'type' => 1, 'code' => 'FIN-WIZ', 'name' => 'Finance', 'user' => 'finance-wiz', 'phone' => '08112',
            'email' => 'finance-wiz@example.test', 'password' => 'secret', 'status' => 1,
        ]);

        $this->actingAs($finance)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->get(route('finance.period-opening.wizard'))
            ->assertForbidden();
    }

    public function test_administrator_can_reopen_and_change_krs_with_audit_and_notifications(): void
    {
        $data = $this->readyPeriod();
        $service = app(KrsService::class);
        $management = app(AdminKrsManagementService::class);
        $krs = $service->forRegistration($data['registration']);
        $management->add($krs, $data['offering'], $data['actor'], 'Penyesuaian rencana studi mahasiswa.');
        $service->submit($krs->fresh());
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, 'Disetujui dosen wali.');

        $reopened = $management->reopen($krs->fresh(), $data['actor'], 'Koreksi mata kuliah berdasarkan hasil konsultasi.');
        $this->assertSame(Krs::STATUS_REJECTED, $reopened->status);
        $item = $reopened->items()->firstOrFail();
        $updated = $management->remove($reopened, $item->id, $data['actor'], 'Mata kuliah tidak sesuai rencana semester.');

        $this->assertSame(0, $updated->total_sks);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'krs.admin_item_added', 'actor_id' => $data['actor']->id]);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'krs.admin_reopened', 'actor_id' => $data['actor']->id]);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'krs.admin_item_removed', 'actor_id' => $data['actor']->id]);
        $this->assertSame(6, Notification::query()->where('auth_id', $data['actor']->id)->where('type', 'krs')->count());

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.krs-management.index', ['registration' => $data['registration']->id]))
            ->assertOk()
            ->assertSee($data['student']->mhs_name)
            ->assertSee('Tambahkan mata kuliah');
    }

    public function test_web_administrator_can_add_multiple_courses_to_student_krs_in_one_request(): void
    {
        $data = $this->readyPeriod();
        $secondOffering = $this->additionalOffering($data, 'BULK-ADD', 2);

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.krs-management.index', ['registration' => $data['registration']->id]))
            ->assertOk()
            ->assertSee('name="penawaran_ids[]"', false)
            ->assertSee('Pilih semua penawaran mata kuliah');

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.krs-management.add-many', $data['registration']), [
                'penawaran_ids' => [$data['offering']->id, $secondOffering->id],
                'alasan_penambahan' => 'Penyesuaian paket mata kuliah berdasarkan kurikulum semester.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 mata kuliah berhasil ditambahkan ke KRS dan perubahan telah diaudit.');

        $krs = Krs::where('registrasi_mahasiswa_id', $data['registration']->id)->firstOrFail();
        $this->assertSame($data['offering']->sks + $secondOffering->sks, $krs->total_sks);
        $this->assertEqualsCanonicalizing(
            [$data['offering']->id, $secondOffering->id],
            $krs->items()->pluck('penawaran_mata_kuliah_id')->all()
        );
        $this->assertDatabaseHas('academic_workflow_audits', [
            'event' => 'krs.admin_items_added',
            'actor_id' => $data['actor']->id,
            'subject_id' => $krs->id,
        ]);
        $this->assertSame(2, Notification::query()
            ->where('auth_id', $data['actor']->id)
            ->where('name', 'KRS diubah oleh Akademik')
            ->count());
    }

    public function test_multiple_admin_course_addition_rolls_back_when_one_course_exceeds_credit_limit(): void
    {
        $data = $this->readyPeriod();
        $data['registration']->update(['batas_sks' => 4]);
        $secondOffering = $this->additionalOffering($data, 'BULK-ROLLBACK', 2);

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->from(route('web-admin.krs-management.index', ['registration' => $data['registration']->id]))
            ->post(route('web-admin.krs-management.add-many', $data['registration']), [
                'penawaran_ids' => [$data['offering']->id, $secondOffering->id],
                'alasan_penambahan' => 'Pengujian transaksi penambahan mata kuliah secara menyeluruh.',
            ])
            ->assertRedirect(route('web-admin.krs-management.index', ['registration' => $data['registration']->id]))
            ->assertSessionHasErrors('penawaran');

        $krs = Krs::where('registrasi_mahasiswa_id', $data['registration']->id)->firstOrFail();
        $this->assertSame(0, $krs->total_sks);
        $this->assertDatabaseCount('krs_items', 0);
        $this->assertDatabaseMissing('academic_workflow_audits', [
            'event' => 'krs.admin_items_added',
            'actor_id' => $data['actor']->id,
            'subject_id' => $krs->id,
        ]);
    }

    public function test_krs_management_rejects_unauthorized_role(): void
    {
        $data = $this->readyPeriod();
        $finance = User::create([
            'type' => 1, 'code' => 'FIN-KRS', 'name' => 'Finance KRS', 'user' => 'finance-krs', 'phone' => '08113',
            'email' => 'finance-krs@example.test', 'password' => 'secret', 'status' => 1,
        ]);

        $this->actingAs($finance)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.krs-management.index'))
            ->assertRedirect();
    }

    public function test_academic_department_can_open_krs_management(): void
    {
        $data = $this->readyPeriod();
        $academic = User::create([
            'type' => 3, 'code' => 'ACA-KRS', 'name' => 'Academic KRS', 'user' => 'academic-krs', 'phone' => '08114',
            'email' => 'academic-krs@example.test', 'password' => 'secret', 'status' => 1,
        ]);

        $this->actingAs($academic)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('academic.krs-management.index', ['registration' => $data['registration']->id]))
            ->assertOk()
            ->assertSee($data['student']->mhs_name);
    }

    public function test_academic_department_can_submit_krs_on_behalf_with_audit_and_notifications(): void
    {
        $data = $this->readyPeriod();
        $academic = User::create([
            'type' => 3, 'code' => 'ACA-SUBMIT', 'name' => 'Academic Submit', 'user' => 'academic-submit', 'phone' => '081141',
            'email' => 'academic-submit@example.test', 'password' => 'secret', 'status' => 1,
        ]);
        $krs = app(KrsService::class)->forRegistration($data['registration']);
        app(AdminKrsManagementService::class)->add(
            $krs,
            $data['offering'],
            $academic,
            'Penyusunan KRS berdasarkan hasil konsultasi akademik.'
        );
        KalenderAkademik::query()->where('taka_id', $data['period']->id)->update([
            'selesai_at' => now()->subMinute(),
        ]);

        $this->actingAs($academic)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('academic.krs-management.index', ['registration' => $data['registration']->id]))
            ->assertOk()
            ->assertSee('Ajukan atas nama mahasiswa');

        $this->actingAs($academic)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->patch(route('academic.krs-management.submit-on-behalf', $krs), [
                'alasan_pengajuan' => 'Mahasiswa meminta bantuan pengajuan melalui bagian akademik.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Krs::STATUS_SUBMITTED, $krs->fresh()->status);
        $this->assertNotNull($krs->fresh()->diajukan_at);
        $this->assertStringContainsString('Diajukan oleh administrator', $krs->fresh()->catatan_mahasiswa);
        $this->assertDatabaseHas('academic_workflow_audits', [
            'event' => 'krs.admin_submitted',
            'actor_id' => $academic->id,
            'subject_id' => $krs->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'auth_id' => $academic->id,
            'student_id' => $data['student']->id,
            'name' => 'KRS diajukan oleh administrator',
        ]);
        $this->assertDatabaseHas('notifications', [
            'auth_id' => $academic->id,
            'lecture_id' => $data['advisor']->id,
            'name' => 'KRS diajukan oleh administrator',
        ]);
    }

    public function test_approval_only_administrator_cannot_submit_krs_on_behalf(): void
    {
        $data = $this->readyPeriod();
        $krs = app(KrsService::class)->forRegistration($data['registration']);
        app(AdminKrsManagementService::class)->add(
            $krs,
            $data['offering'],
            $data['actor'],
            'Persiapan KRS untuk pemeriksaan pembatasan akses.'
        );
        $admin = User::create([
            'type' => 4, 'code' => 'ADM-NO-SUBMIT', 'name' => 'Admin No Submit', 'user' => 'admin-no-submit', 'phone' => '081142',
            'email' => 'admin-no-submit@example.test', 'password' => 'secret', 'status' => 1,
        ]);

        try {
            app(AdminKrsManagementService::class)->submit(
                $krs,
                $admin,
                'Percobaan pengajuan dari role yang tidak diizinkan.'
            );
            $this->fail('Administrator persetujuan tidak boleh mengajukan KRS atas nama mahasiswa.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(Krs::STATUS_DRAFT, $krs->fresh()->status);
        $this->assertDatabaseMissing('academic_workflow_audits', [
            'event' => 'krs.admin_submitted',
            'actor_id' => $admin->id,
            'subject_id' => $krs->id,
        ]);
    }

    public function test_department_admin_can_approve_submitted_krs_with_audit_and_notifications(): void
    {
        $data = $this->readyPeriod();
        $service = app(KrsService::class);
        $krs = $service->forRegistration($data['registration']);
        app(AdminKrsManagementService::class)->add($krs, $data['offering'], $data['actor'], 'Persiapan KRS untuk pengujian persetujuan.');
        $service->submit($krs->fresh(), 'Mohon persetujuan administrator.');
        $admin = User::create([
            'type' => 4, 'code' => 'ADM-KRS', 'name' => 'Admin KRS', 'user' => 'admin-krs', 'phone' => '08115',
            'email' => 'admin-krs@example.test', 'password' => 'secret', 'status' => 1,
        ]);

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('admin.krs-management.index', ['registration' => $data['registration']->id]))
            ->assertOk()
            ->assertSee('Setujui KRS')
            ->assertDontSee('Tambahkan mata kuliah');

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->patch(route('admin.krs-management.approve', $krs), ['catatan' => 'Disetujui setelah pemeriksaan administrasi.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Krs::STATUS_APPROVED, $krs->fresh()->status);
        $this->assertNull($krs->fresh()->diputuskan_oleh);
        $this->assertDatabaseHas('academic_workflow_audits', [
            'event' => 'krs.admin_approved',
            'actor_id' => $admin->id,
            'subject_id' => $krs->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'auth_id' => $admin->id,
            'student_id' => $data['student']->id,
            'type' => 'krs',
            'name' => 'KRS disetujui oleh administrator',
        ]);
    }

    public function test_department_admin_cannot_approve_krs_that_has_not_been_submitted(): void
    {
        $data = $this->readyPeriod();
        $krs = app(KrsService::class)->forRegistration($data['registration']);
        $admin = User::create([
            'type' => 4, 'code' => 'ADM-DRAFT', 'name' => 'Admin Draft', 'user' => 'admin-draft', 'phone' => '08116',
            'email' => 'admin-draft@example.test', 'password' => 'secret', 'status' => 1,
        ]);

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->from(route('admin.krs-management.index', ['registration' => $data['registration']->id]))
            ->patch(route('admin.krs-management.approve', $krs))
            ->assertRedirect(route('admin.krs-management.index', ['registration' => $data['registration']->id]))
            ->assertSessionHasErrors('krs');

        $this->assertSame(Krs::STATUS_DRAFT, $krs->fresh()->status);
        $this->assertDatabaseMissing('academic_workflow_audits', [
            'event' => 'krs.admin_approved',
            'actor_id' => $admin->id,
            'subject_id' => $krs->id,
        ]);
    }

    public function test_department_admin_can_bulk_approve_selected_submitted_krs(): void
    {
        $data = $this->readyPeriod();
        $service = app(KrsService::class);
        $firstKrs = $service->forRegistration($data['registration']);
        app(AdminKrsManagementService::class)->add($firstKrs, $data['offering'], $data['actor'], 'Persiapan persetujuan KRS massal.');
        $service->submit($firstKrs->fresh());
        $secondKrs = $this->additionalKrs($data, 'BULK-APPROVE', Krs::STATUS_SUBMITTED);
        $admin = User::create([
            'type' => 4, 'code' => 'ADM-BULK', 'name' => 'Admin Bulk', 'user' => 'admin-bulk', 'phone' => '08117',
            'email' => 'admin-bulk@example.test', 'password' => 'secret', 'status' => 1,
        ]);

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->patch(route('admin.krs-management.bulk'), [
                'action' => 'approve',
                'krs_ids' => [$firstKrs->id, $secondKrs->id],
                'catatan' => 'Disetujui melalui pemeriksaan KRS massal.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 KRS berhasil disetujui dan dikunci.');

        $this->assertSame(Krs::STATUS_APPROVED, $firstKrs->fresh()->status);
        $this->assertSame(Krs::STATUS_APPROVED, $secondKrs->fresh()->status);
        $this->assertSame(2, DB::table('academic_workflow_audits')
            ->where('event', 'krs.admin_approved')->where('actor_id', $admin->id)->count());
        $this->assertSame(2, Notification::query()
            ->where('name', 'KRS disetujui oleh administrator')->where('auth_id', $admin->id)
            ->whereNotNull('student_id')->count());
    }

    public function test_bulk_approval_is_atomic_when_a_selected_krs_is_not_submitted(): void
    {
        $data = $this->readyPeriod();
        $submittedKrs = $this->additionalKrs($data, 'BULK-SUBMITTED', Krs::STATUS_SUBMITTED);
        $draftKrs = app(KrsService::class)->forRegistration($data['registration']);
        $admin = User::create([
            'type' => 4, 'code' => 'ADM-ATOMIC', 'name' => 'Admin Atomic', 'user' => 'admin-atomic', 'phone' => '08118',
            'email' => 'admin-atomic@example.test', 'password' => 'secret', 'status' => 1,
        ]);

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->patch(route('admin.krs-management.bulk'), [
                'action' => 'approve',
                'krs_ids' => [$submittedKrs->id, $draftKrs->id],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('krs');

        $this->assertSame(Krs::STATUS_SUBMITTED, $submittedKrs->fresh()->status);
        $this->assertSame(Krs::STATUS_DRAFT, $draftKrs->fresh()->status);
        $this->assertDatabaseMissing('academic_workflow_audits', [
            'event' => 'krs.admin_approved',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_web_administrator_can_bulk_reopen_locked_krs(): void
    {
        $data = $this->readyPeriod();
        $firstKrs = $this->additionalKrs($data, 'BULK-REOPEN-1', Krs::STATUS_APPROVED);
        $secondKrs = $this->additionalKrs($data, 'BULK-REOPEN-2', Krs::STATUS_LOCKED);

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->patch(route('web-admin.krs-management.bulk'), [
                'action' => 'reopen',
                'krs_ids' => [$firstKrs->id, $secondKrs->id],
                'catatan' => 'Koreksi kurikulum untuk seluruh mahasiswa terpilih.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 KRS berhasil dibuka kembali.');

        $this->assertSame(Krs::STATUS_REJECTED, $firstKrs->fresh()->status);
        $this->assertSame(Krs::STATUS_REJECTED, $secondKrs->fresh()->status);
        $this->assertSame(2, DB::table('academic_workflow_audits')
            ->where('event', 'krs.admin_reopened')->where('actor_id', $data['actor']->id)->count());
    }

    public function test_krs_management_checklists_follow_the_action_and_student_pagination_is_isolated(): void
    {
        $data = $this->readyPeriod();
        $submitted = null;

        for ($index = 1; $index <= 25; $index++) {
            $submitted = $this->additionalKrs($data, 'PAGE-'.$index, Krs::STATUS_SUBMITTED);
        }

        $response = $this->actingAs($data['actor'])
            ->withSession([
                AcademicPeriodContext::SESSION_KEY => $data['period']->id,
                '_old_input' => [
                    'action' => 'approve',
                    'krs_ids' => [$submitted->id],
                    'catatan' => 'Catatan pilihan lama.',
                ],
            ])
            ->get(route('web-admin.krs-management.index', ['students_page' => 2]));

        $registration = $submitted->registrasiMahasiswa;
        $response->assertOk()
            ->assertSee($registration->mahasiswa->mhs_name)
            ->assertSee('Menampilkan 26–26 dari 26 mahasiswa')
            ->assertSee('data-can-approve="1"', false)
            ->assertSee('data-can-reopen="1"', false)
            ->assertSee('id="krs-bulk-action"', false)
            ->assertSee('option value="approve" selected', false)
            ->assertSee('value="'.$submitted->id.'"', false)
            ->assertSee('students_page=1', false)
            ->assertSee(route('web-admin.krs-management.index', [
                'registration' => $registration->id,
                'q' => '',
                'status' => '',
                'students_page' => 2,
            ]));

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.krs-management.index'))
            ->assertOk()
            ->assertSee('data-can-approve="1" data-can-reopen="1"', false)
            ->assertSee('Mahasiswa belum memiliki KRS.', false)
            ->assertSee("return checkbox.dataset.canApprove === '1' || checkbox.dataset.canReopen === '1';", false);
    }

    public function test_krs_management_student_list_can_be_filtered_by_current_period_class(): void
    {
        $data = $this->readyPeriod();
        $sourceClass = DB::table('kelas')->where('id', $data['classId'])->first();
        $otherClassId = DB::table('kelas')->insertGetId([
            'taka_id' => $data['period']->id,
            'pstudi_id' => $sourceClass->pstudi_id,
            'proku_id' => $sourceClass->proku_id,
            'dosen_id' => $data['advisor']->id,
            'capacity' => 30,
            'name' => 'Kelas Filter Lain',
            'code' => 'FILTER-OTHER-CLASS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherStudent = $this->student('FILTER-OTHER');
        RegistrasiMahasiswa::create([
            'mahasiswa_id' => $otherStudent->id,
            'taka_id' => $data['period']->id,
            'semester_mahasiswa' => 1,
            'status_akademik' => 'aktif',
            'status_registrasi' => 'terdaftar',
            'kelas_id' => $otherClassId,
            'dosen_wali_id' => $data['advisor']->id,
            'batas_sks' => 24,
        ]);

        $filteredTemplateUrl = route('web-admin.krs-management.import-template', [
            'kelas_id' => $data['classId'],
        ]);
        $detailUrl = route('web-admin.krs-management.index', [
            'registration' => $data['registration']->id,
            'q' => '',
            'status' => '',
            'kelas_id' => $data['classId'],
            'students_page' => 1,
        ]);

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.krs-management.index', ['kelas_id' => $data['classId']]))
            ->assertOk()
            ->assertSee('Filter Daftar Mahasiswa')
            ->assertSee('name="kelas_id"', false)
            ->assertSee($data['student']->mhs_name)
            ->assertDontSee($otherStudent->mhs_name)
            ->assertSee('1 mahasiswa')
            ->assertSee($filteredTemplateUrl)
            ->assertSee($detailUrl);
    }

    public function test_department_admin_can_preview_and_execute_krs_excel_import(): void
    {
        $data = $this->readyPeriod();
        $service = app(KrsService::class);
        $firstKrs = $service->forRegistration($data['registration']);
        app(AdminKrsManagementService::class)->add($firstKrs, $data['offering'], $data['actor'], 'Persiapan import Excel KRS.');
        $service->submit($firstKrs->fresh());
        $secondKrs = $this->additionalKrs($data, 'EXCEL-APPROVE', Krs::STATUS_SUBMITTED);
        $secondStudent = $secondKrs->registrasiMahasiswa->mahasiswa;
        $admin = User::create([
            'type' => 4, 'code' => 'ADM-EXCEL', 'name' => 'Admin Excel', 'user' => 'admin-excel', 'phone' => '08119',
            'email' => 'admin-excel@example.test', 'password' => 'secret', 'status' => 1,
        ]);
        $file = UploadedFile::fake()->createWithContent('update-krs.csv', implode("\n", [
            'NIM,Nama,Aksi,Keterangan Aksi',
            "{$data['student']->mhs_nim},{$data['student']->mhs_name},setujui,Menyetujui KRS",
            "{$secondStudent->mhs_nim},{$secondStudent->mhs_name},setujui,Menyetujui KRS",
            'NIM-DIABAIKAN,Nama Diabaikan,,Baris kosong diabaikan',
        ]));

        $filteredTemplateUrl = route('admin.krs-management.import-template', [
            'q' => $secondStudent->mhs_nim,
            'status' => Krs::STATUS_SUBMITTED,
        ]);
        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('admin.krs-management.index', [
                'q' => $secondStudent->mhs_nim,
                'status' => Krs::STATUS_SUBMITTED,
            ]))
            ->assertOk()
            ->assertSee($filteredTemplateUrl)
            ->assertSee('Menyetujui dan mengunci KRS');

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get($filteredTemplateUrl)
            ->assertOk()
            ->assertDownload('template-update-krs-'.$data['period']->code.'.xlsx');

        $previewResponse = $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('admin.krs-management.import-preview'), ['import' => $file])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('krs_bulk_import');
        $preview = $previewResponse->getSession()->get('krs_bulk_import');

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('admin.krs-management.import-execute'), [
                'token' => $preview['token'],
                'catatan' => 'Disetujui melalui import Excel.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Import selesai: 2 KRS disetujui dan 0 KRS dibuka kembali.');

        $this->assertSame(Krs::STATUS_APPROVED, $firstKrs->fresh()->status);
        $this->assertSame(Krs::STATUS_APPROVED, $secondKrs->fresh()->status);
        $this->assertSame(2, DB::table('academic_workflow_audits')
            ->where('event', 'krs.admin_approved')->where('actor_id', $admin->id)->count());
    }

    public function test_web_administrator_can_prepare_submit_and_approve_all_available_courses_through_excel(): void
    {
        $data = $this->readyPeriod();
        $secondOffering = $this->additionalOffering($data, 'EXCEL-PREPARE', 2);
        $file = UploadedFile::fake()->createWithContent('ajukan-setujui-krs.csv', implode("\n", [
            'NIM,Nama,Aksi,Keterangan Aksi',
            "{$data['student']->mhs_nim},{$data['student']->mhs_name},ajukan_setujui,Isi seluruh penawaran dan setujui",
        ]));

        $previewResponse = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.krs-management.import-preview'), ['import' => $file])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('krs_bulk_import');
        $preview = $previewResponse->getSession()->get('krs_bulk_import');
        $this->assertSame(KrsBulkImportService::ACTION_PREPARE_APPROVE, $preview['prepared'][0]['aksi']);
        $this->assertStringContainsString('Isi 2 penawaran', $preview['prepared'][0]['aksi_label']);

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.krs-management.import-execute'), [
                'token' => $preview['token'],
                'catatan' => 'Pengajuan dan persetujuan dilakukan berdasarkan paket semester.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Import selesai: 0 KRS disetujui, 0 KRS dibuka kembali, dan 1 KRS diisi seluruh penawarannya, diajukan, serta disetujui.');

        $krs = Krs::where('registrasi_mahasiswa_id', $data['registration']->id)->firstOrFail();
        $this->assertSame(Krs::STATUS_APPROVED, $krs->status);
        $this->assertSame($data['offering']->sks + $secondOffering->sks, $krs->total_sks);
        $this->assertEqualsCanonicalizing(
            [$data['offering']->id, $secondOffering->id],
            $krs->items()->pluck('penawaran_mata_kuliah_id')->all()
        );
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'krs.admin_items_added', 'subject_id' => $krs->id]);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'krs.admin_submitted', 'subject_id' => $krs->id]);
        $this->assertDatabaseHas('academic_workflow_audits', ['event' => 'krs.admin_approved', 'subject_id' => $krs->id]);
    }

    public function test_academic_department_cannot_use_prepare_and_approve_excel_action(): void
    {
        $data = $this->readyPeriod();
        $academic = User::create([
            'type' => 3, 'code' => 'ACA-EXCEL-PREPARE', 'name' => 'Academic Excel Prepare', 'user' => 'academic-excel-prepare', 'phone' => '081191',
            'email' => 'academic-excel-prepare@example.test', 'password' => 'secret', 'status' => 1,
        ]);
        $file = UploadedFile::fake()->createWithContent('ajukan-setujui-krs.csv', implode("\n", [
            'NIM,Nama,Aksi',
            "{$data['student']->mhs_nim},{$data['student']->mhs_name},ajukan_setujui",
        ]));

        $this->actingAs($academic)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('academic.krs-management.import-preview'), ['import' => $file])
            ->assertRedirect()
            ->assertSessionHasErrors('import')
            ->assertSessionMissing('krs_bulk_import');

        $this->assertDatabaseMissing('krs', ['registrasi_mahasiswa_id' => $data['registration']->id]);
    }

    public function test_prepare_and_approve_excel_action_rolls_back_all_students_when_one_fails(): void
    {
        $data = $this->readyPeriod();
        $secondStudent = $this->student('EXCEL-PREPARE-ROLLBACK');
        $secondRegistration = RegistrasiMahasiswa::create([
            'mahasiswa_id' => $secondStudent->id,
            'taka_id' => $data['period']->id,
            'semester_mahasiswa' => 1,
            'status_akademik' => 'aktif',
            'status_registrasi' => 'terdaftar',
            'kelas_id' => $data['classId'],
            'dosen_wali_id' => $data['advisor']->id,
            'batas_sks' => 1,
        ]);
        $file = UploadedFile::fake()->createWithContent('ajukan-setujui-rollback.csv', implode("\n", [
            'NIM,Nama,Aksi',
            "{$data['student']->mhs_nim},{$data['student']->mhs_name},ajukan_setujui",
            "{$secondStudent->mhs_nim},{$secondStudent->mhs_name},ajukan_setujui",
        ]));

        $previewResponse = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.krs-management.import-preview'), ['import' => $file])
            ->assertRedirect()
            ->assertSessionHas('krs_bulk_import');
        $preview = $previewResponse->getSession()->get('krs_bulk_import');

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.krs-management.import-execute'), [
                'token' => $preview['token'],
                'catatan' => 'Pengujian rollback seluruh pengajuan dan persetujuan Excel.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('penawaran');

        $this->assertDatabaseMissing('krs', ['registrasi_mahasiswa_id' => $data['registration']->id]);
        $this->assertDatabaseMissing('krs', ['registrasi_mahasiswa_id' => $secondRegistration->id]);
        $this->assertDatabaseMissing('academic_workflow_audits', [
            'event' => 'krs.admin_approved',
            'actor_id' => $data['actor']->id,
        ]);
    }

    public function test_krs_excel_preview_rejects_name_that_does_not_match_nim(): void
    {
        $data = $this->readyPeriod();
        $krs = $this->additionalKrs($data, 'EXCEL-MISMATCH', Krs::STATUS_SUBMITTED);
        $student = $krs->registrasiMahasiswa->mahasiswa;
        $admin = User::create([
            'type' => 4, 'code' => 'ADM-MISMATCH', 'name' => 'Admin Mismatch', 'user' => 'admin-mismatch', 'phone' => '08120',
            'email' => 'admin-mismatch@example.test', 'password' => 'secret', 'status' => 1,
        ]);
        $file = UploadedFile::fake()->createWithContent('update-krs.csv', implode("\n", [
            'nim,nama,aksi',
            "{$student->mhs_nim},Nama Mahasiswa Lain,setujui",
        ]));

        $this->actingAs($admin)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('admin.krs-management.import-preview'), ['import' => $file])
            ->assertRedirect()
            ->assertSessionHasErrors('import')
            ->assertSessionMissing('krs_bulk_import');

        $this->assertSame(Krs::STATUS_SUBMITTED, $krs->fresh()->status);
        $this->assertDatabaseMissing('academic_workflow_audits', [
            'event' => 'krs.admin_approved',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_offering_import_accepts_legacy_master_code_for_matching_feeder_study_program(): void
    {
        $actor = $this->actor();
        $period = $this->period('LEGACY-MASTER', TahunAkademik::STATUS_DRAFT);
        $advisor = $this->advisor();
        $programId = DB::table('program_studis')->insertGetId([
            'faku_id' => 1,
            'name' => 'Pendidikan Guru Pendidikan Agama Islam',
            'cnim' => '86208',
            'code' => '86208',
            'slug' => 'pendidikan-guru-pendidikan-agama-islam',
            'head_id' => 0,
            'title' => 'S.Pd',
            'level' => 'S1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $programKuliahId = $this->programKuliah($period, $programId, 'Reguler');
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'proku_id' => $programKuliahId,
            'dosen_id' => $advisor->id,
            'capacity' => 30,
            'name' => 'PAI A',
            'code' => 'PAI-LEGACY-A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $curriculumId = DB::table('kurikulums')->insertGetId([
            'name' => 'Kurikulum 2020',
            'code' => 'K20-LEGACY',
            'desc' => '-',
            'year_start' => 2020,
            'year_ended' => 2028,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $master = MasterMataKuliah::create([
            'program_studi' => 'PGPAI',
            'semester' => 2,
            'name' => 'Bahasa Arab 1',
            'sks' => 2,
        ]);
        $prerequisite = MasterMataKuliah::create([
            'program_studi' => 'PGPAI',
            'semester' => 1,
            'name' => 'Pengantar Studi Islam',
            'sks' => 2,
        ]);

        $headers = [
            'Kode Penawaran', 'Kode Periode', 'Kode Program Studi', 'Kode Kurikulum', 'Kode Kelas',
            'Semester Mata Kuliah', 'Nama Mata Kuliah', 'NIDN Dosen Utama', 'NIDN Dosen Pendamping 1',
            'NIDN Dosen Pendamping 2', 'Semester Prasyarat', 'Nama Mata Kuliah Prasyarat',
            'Kapasitas', 'Deskripsi',
        ];
        $values = [
            'OFF-IMPORT-PAI', $period->code, '86208', 'K20-LEGACY', 'PAI-LEGACY-A',
            'II', 'Bahasa Arab 1', $advisor->dsn_nidn, '', '', 'I', 'Pengantar Studi Islam', '40', 'Hasil import',
        ];
        $file = UploadedFile::fake()->createWithContent(
            'penawaran.csv',
            implode(',', $headers)."\n".implode(',', $values)
        );

        $response = $this->actingAs($actor)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->post(route('web-admin.master.penawaran-import'), [
                '_form' => 'import-offerings',
                'import' => $file,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('penawaran_mata_kuliahs', [
            'master_mata_kuliah_id' => $master->id,
            'prasyarat_master_id' => $prerequisite->id,
            'pstudi_id' => $programId,
            'kelas_id' => $classId,
        ]);

        PenawaranMataKuliah::query()->delete();
        $invalidValues = $values;
        $invalidValues[0] = 'OFF-IMPORT-INVALID';
        $invalidValues[4] = 'KELAS-TIDAK-ADA';
        $invalidFile = UploadedFile::fake()->createWithContent(
            'penawaran-invalid.csv',
            implode(',', $headers)."\n".implode(',', $values)."\n".implode(',', $invalidValues)
        );

        $this->actingAs($actor)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->post(route('web-admin.master.penawaran-import'), [
                '_form' => 'import-offerings',
                'import' => $invalidFile,
            ])
            ->assertSessionHasErrors('import');

        $this->assertDatabaseCount('penawaran_mata_kuliahs', 0);
    }

    public function test_offerings_can_be_exported_for_the_selected_period(): void
    {
        $data = $this->readyPeriod();
        $originalSchedule = JadwalMingguan::query()->where('penawaran_mata_kuliah_id', $data['offering']->id)->firstOrFail();

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.master.penawaran-export'));

        $response->assertOk();
        $this->assertStringContainsString(
            'penawaran-mata-kuliah-'.$data['period']->code,
            (string) $response->headers->get('content-disposition')
        );
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $file = UploadedFile::fake()->createWithContent('penawaran.xlsx', $content);
        JadwalMingguan::query()->delete();
        PenawaranMataKuliah::query()->delete();

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.master.penawaran-import'), [
                '_form' => 'import-offerings',
                'import' => $file,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('penawaran_mata_kuliahs', [
            'code' => 'OFF-READY',
            'taka_id' => $data['period']->id,
        ]);
        $this->assertDatabaseCount('penawaran_mata_kuliahs', 1);
        $restoredOffering = PenawaranMataKuliah::query()->where('code', 'OFF-READY')->firstOrFail();
        $this->assertDatabaseHas('jadwal_mingguans', [
            'penawaran_mata_kuliah_id' => $restoredOffering->id,
            'ruang_id' => $originalSchedule->ruang_id,
            'hari' => $originalSchedule->hari,
            'mulai' => $originalSchedule->mulai,
            'selesai' => $originalSchedule->selesai,
        ]);
    }

    public function test_web_administrator_can_create_an_offering_and_weekly_schedule_atomically(): void
    {
        $data = $this->readyPeriod();
        $master = MasterMataKuliah::create([
            'program_studi' => $data['offering']->masterMataKuliah->program_studi,
            'semester' => 2,
            'name' => 'Penawaran dan Jadwal Baru',
            'sks' => 2,
        ]);
        $roomId = JadwalMingguan::query()->value('ruang_id');

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.master.penawaran-store'), [
                '_form' => 'create-offering',
                'master_mata_kuliah_id' => $master->id,
                'pstudi_id' => $data['offering']->pstudi_id,
                'kuri_id' => $data['offering']->kuri_id,
                'kelas_ids' => [$data['classId']],
                'dosen_utama_id' => $data['advisor']->id,
                'kapasitas' => 30,
                'buat_jadwal' => 1,
                'jadwal_ruang_id' => $roomId,
                'jadwal_hari' => 2,
                'jadwal_mulai' => '10:00',
                'jadwal_selesai' => '11:40',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Jadwal mingguan juga berhasil dibuat'));
        $offering = PenawaranMataKuliah::query()->where('master_mata_kuliah_id', $master->id)->firstOrFail();
        $this->assertDatabaseHas('jadwal_mingguans', [
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $roomId,
            'hari' => 2,
            'mulai' => '10:00',
            'selesai' => '11:40',
        ]);
    }

    public function test_offering_creation_rolls_back_when_inline_schedule_conflicts(): void
    {
        $data = $this->readyPeriod();
        $master = MasterMataKuliah::create([
            'program_studi' => $data['offering']->masterMataKuliah->program_studi,
            'semester' => 2,
            'name' => 'Penawaran Jadwal Bentrok',
            'sks' => 2,
        ]);
        $existingSchedule = JadwalMingguan::query()->firstOrFail();

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.master.penawaran-store'), [
                '_form' => 'create-offering',
                'master_mata_kuliah_id' => $master->id,
                'pstudi_id' => $data['offering']->pstudi_id,
                'kuri_id' => $data['offering']->kuri_id,
                'kelas_ids' => [$data['classId']],
                'dosen_utama_id' => $data['advisor']->id,
                'kapasitas' => 30,
                'buat_jadwal' => 1,
                'jadwal_ruang_id' => $existingSchedule->ruang_id,
                'jadwal_hari' => $existingSchedule->hari,
                'jadwal_mulai' => substr($existingSchedule->mulai, 0, 5),
                'jadwal_selesai' => substr($existingSchedule->selesai, 0, 5),
            ])
            ->assertSessionHasErrors(['dosen_id', 'kelas_id', 'ruang_id']);

        $this->assertDatabaseMissing('penawaran_mata_kuliahs', [
            'master_mata_kuliah_id' => $master->id,
            'taka_id' => $data['period']->id,
        ]);
    }

    public function test_inline_schedule_requires_exactly_one_selected_class(): void
    {
        $data = $this->readyPeriod();
        $master = MasterMataKuliah::create([
            'program_studi' => $data['offering']->masterMataKuliah->program_studi,
            'semester' => 2,
            'name' => 'Penawaran Jadwal Banyak Kelas',
            'sks' => 2,
        ]);
        $sourceClass = DB::table('kelas')->where('id', $data['classId'])->first();
        $secondClassId = DB::table('kelas')->insertGetId([
            ...collect((array) $sourceClass)->except(['id', 'name', 'code', 'created_at', 'updated_at'])->all(),
            'name' => 'Kelas Siap Kedua',
            'code' => 'READY-CLASS-SECOND',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.master.penawaran-store'), [
                '_form' => 'create-offering',
                'master_mata_kuliah_id' => $master->id,
                'pstudi_id' => $data['offering']->pstudi_id,
                'kuri_id' => $data['offering']->kuri_id,
                'kelas_ids' => [$data['classId'], $secondClassId],
                'dosen_utama_id' => $data['advisor']->id,
                'kapasitas' => 30,
                'buat_jadwal' => 1,
                'jadwal_ruang_id' => JadwalMingguan::query()->value('ruang_id'),
                'jadwal_hari' => 2,
                'jadwal_mulai' => '10:00',
                'jadwal_selesai' => '11:40',
            ])
            ->assertSessionHasErrors('kelas_ids');

        $this->assertDatabaseMissing('penawaran_mata_kuliahs', [
            'master_mata_kuliah_id' => $master->id,
            'taka_id' => $data['period']->id,
        ]);
    }

    public function test_unscheduled_offerings_page_only_lists_offerings_without_a_weekly_schedule(): void
    {
        $data = $this->readyPeriod();
        $master = MasterMataKuliah::create([
            'program_studi' => $data['offering']->masterMataKuliah->program_studi,
            'semester' => 1,
            'name' => 'Mata Kuliah Belum Dijadwalkan',
            'sks' => 2,
        ]);
        $unscheduled = PenawaranMataKuliah::create([
            ...$data['offering']->only(['taka_id', 'pstudi_id', 'kuri_id', 'kelas_id', 'dosen_utama_id', 'kapasitas']),
            'master_mata_kuliah_id' => $master->id,
            'code' => 'OFF-BELUM-JADWAL',
            'sks' => 2,
        ]);

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.master.penawaran-unscheduled'));

        $response->assertOk()
            ->assertSee('Penawaran Belum Dijadwalkan')
            ->assertSee($unscheduled->code)
            ->assertDontSee($data['offering']->code)
            ->assertSee('Jadwalkan');

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.master.penawaran-index', ['status_jadwal' => 'belum']))
            ->assertOk()
            ->assertSee($unscheduled->code)
            ->assertDontSee($data['offering']->code)
            ->assertSee('Belum dijadwalkan')
            ->assertSee('Jadwalkan');

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.master.penawaran-index', ['status_jadwal' => 'sudah']))
            ->assertOk()
            ->assertSee($data['offering']->code)
            ->assertDontSee($unscheduled->code)
            ->assertSee('Sudah dijadwalkan');

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.master.jadwal-mingguan-index', [
                'penawaran_id' => $unscheduled->id,
                'buat' => 1,
            ]))
            ->assertOk()
            ->assertViewHas('preferredOffering', fn ($offering) => $offering?->is($unscheduled))
            ->assertViewHas('openCreateModal', true);
    }

    public function test_weekly_schedules_can_be_exported_and_imported_for_the_selected_period(): void
    {
        $data = $this->readyPeriod();

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.master.jadwal-mingguan-index'))
            ->assertOk()
            ->assertSee('Import Jadwal')
            ->assertSee('Export');

        $response = $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->get(route('web-admin.master.jadwal-mingguan-export'));

        $response->assertOk();
        $this->assertStringContainsString(
            'jadwal-mingguan-'.$data['period']->code,
            (string) $response->headers->get('content-disposition')
        );
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        JadwalMingguan::query()->delete();
        $file = UploadedFile::fake()->createWithContent('jadwal-mingguan.xlsx', $content);

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.master.jadwal-mingguan-import'), [
                '_form' => 'import-weekly-schedules',
                'import' => $file,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Import selesai: 1 jadwal dibuat dan 0 duplikat dilewati.');

        $this->assertDatabaseHas('jadwal_mingguans', [
            'code' => 'SCH-READY',
            'penawaran_mata_kuliah_id' => $data['offering']->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:40',
        ]);
    }

    public function test_weekly_schedule_import_rolls_back_all_rows_when_a_schedule_conflicts(): void
    {
        $data = $this->readyPeriod();
        JadwalMingguan::query()->delete();
        $class = DB::table('kelas')->find($data['classId']);
        $courseName = $data['offering']->masterMataKuliah->name;
        $headers = [
            'Kode Jadwal', 'Kode Periode', 'Kode Penawaran', 'Nama Mata Kuliah',
            'Kode Kelas', 'Nama Kelas', 'NIDN Dosen', 'Nama Dosen', 'Kode Ruang',
            'Nama Ruang', 'Hari', 'Jam Mulai', 'Jam Selesai', 'Alasan Pengecualian',
        ];
        $first = [
            'SCH-IMPORT-1', $data['period']->code, $data['offering']->code, $courseName,
            $class->code, $class->name, $data['advisor']->dsn_nidn, $data['advisor']->dsn_name,
            'R01', 'Ruang', 'Senin', '08:00', '09:40', '',
        ];
        $second = [
            'SCH-IMPORT-2', $data['period']->code, $data['offering']->code, $courseName,
            $class->code, $class->name, $data['advisor']->dsn_nidn, $data['advisor']->dsn_name,
            'R01', 'Ruang', 'Senin', '09:00', '10:30', '',
        ];
        $file = UploadedFile::fake()->createWithContent(
            'jadwal-mingguan.csv',
            implode(',', $headers)."\n".implode(',', $first)."\n".implode(',', $second)
        );

        $this->actingAs($data['actor'])
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['period']->id])
            ->post(route('web-admin.master.jadwal-mingguan-import'), [
                '_form' => 'import-weekly-schedules',
                'import' => $file,
            ])
            ->assertSessionHasErrors(['dosen_id', 'kelas_id', 'ruang_id']);

        $this->assertDatabaseCount('jadwal_mingguans', 0);
    }

    private function readyPeriod(): array
    {
        $actor = $this->actor();
        $period = $this->period('READY', TahunAkademik::STATUS_ACTIVE, true);
        $advisor = $this->advisor();
        $programId = $this->program();
        $prokuId = $this->programKuliah($period, $programId, 'Reguler Siap');
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $period->id, 'pstudi_id' => $programId, 'proku_id' => $prokuId,
            'dosen_id' => $advisor->id, 'capacity' => 30, 'name' => 'Kelas Siap', 'code' => 'READY-CLASS',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $curriculumId = DB::table('kurikulums')->insertGetId([
            'name' => 'Kurikulum Siap', 'code' => 'KUR-READY', 'desc' => '-', 'year_start' => 2026,
            'year_ended' => 2030, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $master = MasterMataKuliah::create([
            'program_studi' => DB::table('program_studis')->where('id', $programId)->value('code'),
            'semester' => 1,
            'name' => 'Mata Kuliah Siap',
            'sks' => 3,
        ]);
        $student = $this->student('READY');
        $registration = RegistrasiMahasiswa::create([
            'mahasiswa_id' => $student->id, 'taka_id' => $period->id, 'semester_mahasiswa' => 1,
            'status_akademik' => 'aktif', 'status_registrasi' => 'terdaftar', 'kelas_id' => $classId,
            'dosen_wali_id' => $advisor->id, 'batas_sks' => 24,
        ]);
        $offering = PenawaranMataKuliah::create([
            'master_mata_kuliah_id' => $master->id, 'taka_id' => $period->id, 'pstudi_id' => $programId,
            'kuri_id' => $curriculumId, 'kelas_id' => $classId, 'dosen_utama_id' => $advisor->id,
            'code' => 'OFF-READY', 'sks' => 3, 'kapasitas' => 30,
        ]);
        $roomId = $this->room();
        $scheduleAttributes = [
            'penawaran_mata_kuliah_id' => $offering->id, 'kelas_id' => $classId, 'dosen_id' => $advisor->id,
            'ruang_id' => $roomId, 'hari' => 1, 'mulai' => '08:00', 'selesai' => '09:40',
        ];
        JadwalMingguan::create($scheduleAttributes + ['code' => 'SCH-READY', 'fingerprint' => JadwalMingguan::fingerprint($scheduleAttributes)]);
        KalenderAkademik::create([
            'taka_id' => $period->id, 'kategori' => KalenderAkademik::KATEGORI_KRS, 'nama' => 'Pengisian KRS',
            'mulai_at' => now()->subDay(), 'selesai_at' => now()->addDay(), 'dipublikasikan' => true,
        ]);
        $template = TemplateTagihan::create([
            'taka_id' => $period->id, 'name' => 'UKT Siap', 'jenis' => 'ukt', 'nominal' => 500000,
            'tanggal_terbit' => $period->starts_at, 'jatuh_tempo' => $period->starts_at->copy()->addMonth(),
            'wajib_lunas_krs' => true, 'target_type' => 'mahasiswa', 'target_mahasiswa_id' => $student->id,
            'created_by' => $actor->id,
        ]);
        app(BulkBillingService::class)->issue($template, $actor);
        $bill = $template->tagihans()->firstOrFail();
        HistoryTagihan::create([
            'users_id' => $student->id, 'stat' => 1, 'tagihan_code' => $bill->code, 'desc' => 'Lunas',
            'code' => 'PAY-READY', 'tagihan_kuliah_id' => $bill->id, 'taka_id' => $period->id,
            'nominal' => $bill->nominal, 'status' => 'lunas', 'dibayar_at' => now(),
        ]);

        return compact('actor', 'period', 'advisor', 'classId', 'student', 'registration', 'offering');
    }

    private function additionalKrs(array $data, string $suffix, string $status): Krs
    {
        $student = $this->student($suffix);
        $registration = RegistrasiMahasiswa::create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $data['period']->id,
            'semester_mahasiswa' => 1,
            'status_akademik' => 'aktif',
            'status_registrasi' => 'terdaftar',
            'kelas_id' => $data['classId'],
            'dosen_wali_id' => $data['advisor']->id,
            'batas_sks' => 24,
        ]);
        $krs = Krs::create([
            'registrasi_mahasiswa_id' => $registration->id,
            'status' => Krs::STATUS_DRAFT,
            'total_sks' => $data['offering']->sks,
        ]);
        $krs->items()->create([
            'penawaran_mata_kuliah_id' => $data['offering']->id,
            'sks' => $data['offering']->sks,
        ]);
        $krs->update([
            'status' => $status,
            'diajukan_at' => in_array($status, [Krs::STATUS_SUBMITTED, Krs::STATUS_APPROVED, Krs::STATUS_LOCKED], true) ? now() : null,
        ]);

        return $krs->load('registrasiMahasiswa.taka');
    }

    private function additionalOffering(array $data, string $suffix, int $sks): PenawaranMataKuliah
    {
        $master = MasterMataKuliah::create([
            'program_studi' => 'PAI',
            'semester' => 1,
            'name' => 'Mata Kuliah '.$suffix,
            'sks' => $sks,
        ]);
        $source = $data['offering'];

        return PenawaranMataKuliah::create([
            'master_mata_kuliah_id' => $master->id,
            'taka_id' => $source->taka_id,
            'pstudi_id' => $source->pstudi_id,
            'kuri_id' => $source->kuri_id,
            'kelas_id' => $source->kelas_id,
            'dosen_utama_id' => $source->dosen_utama_id,
            'code' => 'OF-'.$suffix,
            'sks' => $sks,
            'kapasitas' => 40,
        ]);
    }

    private function actor(): User
    {
        return User::firstOrCreate(['email' => 'root@example.test'], [
            'type' => 0, 'code' => 'ROOT', 'name' => 'Root', 'user' => 'root', 'phone' => '0800000000',
            'password' => 'secret', 'status' => 1,
        ]);
    }

    private function period(string $code, string $status, bool $active = false): TahunAkademik
    {
        return TahunAkademik::create([
            'name' => 'Periode '.$code, 'code' => $code, 'semester' => 1, 'year_start' => 2026,
            'year_end' => 2027, 'term' => 'ganjil', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30',
            'status' => $status, 'is_active' => $active, 'is_published' => false,
        ]);
    }

    private function advisor(): Dosen
    {
        return Dosen::firstOrCreate(['dsn_nidn' => '99001'], [
            'dsn_stat' => 1, 'dsn_name' => 'Dosen Siap', 'dsn_code' => 'DSN-READY', 'dsn_user' => 'dosen.ready',
            'dsn_mail' => 'dosen@example.test', 'dsn_phone' => '08990001', 'password' => 'secret',
        ]);
    }

    private function program(): int
    {
        return DB::table('program_studis')->insertGetId([
            'faku_id' => 1, 'name' => 'PAI '.uniqid(), 'cnim' => uniqid(), 'code' => 'PAI'.uniqid(),
            'slug' => 'pai-'.uniqid(), 'head_id' => 0, 'title' => 'S.Pd', 'level' => 'S1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function programKuliah(TahunAkademik $period, int $programId, string $name): int
    {
        return DB::table('program_kuliahs')->insertGetId([
            'taka_id' => $period->id, 'pstudi_id' => $programId, 'name' => $name,
            'code' => 'PROKU-'.$period->code.'-'.Str::random(4), 'wave' => 'I',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function student(string $suffix): Mahasiswa
    {
        return Mahasiswa::create([
            'mhs_nim' => 'NIM-'.$suffix, 'mhs_name' => 'Mahasiswa '.$suffix, 'mhs_code' => 'MHS-'.$suffix,
            'mhs_user' => 'mhs-'.strtolower($suffix), 'password' => 'secret', 'mhs_mail' => strtolower($suffix).'@example.test',
            'mhs_phone' => '08'.abs(crc32($suffix)),
        ]);
    }

    private function room(): int
    {
        $building = DB::table('gedungs')->insertGetId(['name' => 'Gedung', 'code' => 'GDG', 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('ruangs')->insertGetId([
            'gedu_id' => $building, 'type' => 0, 'floor' => 1, 'name' => 'Ruang', 'code' => 'R01',
            'kapasitas' => 40, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
