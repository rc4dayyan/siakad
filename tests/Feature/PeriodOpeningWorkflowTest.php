<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\HistoryTagihan;
use App\Models\JadwalMingguan;
use App\Models\KalenderAkademik;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\PenawaranMataKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\TemplateTagihan;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AcademicStatusService;
use App\Services\Academic\KrsService;
use App\Services\Academic\PeriodConfigurationCopyService;
use App\Services\Academic\PeriodPublicationService;
use App\Services\Academic\PeriodReadinessService;
use App\Services\Finance\BulkBillingService;
use App\Services\Finance\FinancialEligibilityService;
use Carbon\CarbonImmutable;
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
        $master = MasterMataKuliah::create(['program_studi' => 'PAI', 'semester' => 1, 'name' => 'Mata Kuliah Siap', 'sks' => 3]);
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
