<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\JadwalMingguan;
use App\Models\KalenderAkademik;
use App\Models\Krs;
use App\Models\KrsItem;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\PenawaranMataKuliah;
use App\Models\PertemuanKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\User;
use App\Services\Academic\AttendanceEligibilityService;
use App\Services\Academic\KrsService;
use App\Services\Academic\MeetingGeneratorService;
use App\Services\Academic\ScheduleConflictService;
use App\Services\Academic\ScheduleNotificationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class KrsWorkflowTest extends TestCase
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
            '2024_04_27_041303_create_kelas_table.php',
            '2024_04_28_063053_create_kurikulums_table.php',
            '2024_04_28_035926_create_gedungs_table.php',
            '2024_04_28_052322_create_ruangs_table.php',
            '2024_04_30_032644_create_mata_kuliahs_table.php',
            '2024_04_30_055648_create_jadwal_kuliahs_table.php',
            '2024_04_30_102751_create_absensi_mahasiswas_table.php',
            '2024_05_30_004205_create_notifications_table.php',
            '2025_07_05_091153_create_nilai_mahasiswas_table.php',
            '2025_07_05_112548_add_matakuliah_kelas_id.php',
            '2026_07_17_000001_create_master_mata_kuliahs_table.php',
            '2026_07_17_000002_add_mid_to_mata_kuliahs_table.php',
            '2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php',
            '2026_07_17_000004_link_grades_and_study_results_to_academic_periods.php',
            '2026_07_17_000005_create_registrasi_mahasiswas_table.php',
        ] as $migration) {
            if ($migration === '2026_07_17_000004_link_grades_and_study_results_to_academic_periods.php') {
                // Migration tersebut juga mengubah hasil_studis; bagian ini tidak dibutuhkan untuk workflow KRS.
                DB::statement('ALTER TABLE nilai_mahasiswas ADD COLUMN taka_id INTEGER NULL');

                continue;
            }
            (require database_path('migrations/'.$migration))->up();
        }

        (require database_path('migrations/2026_07_17_000008_create_course_offerings_and_krs_tables.php'))->up();
        (require database_path('migrations/2026_07_17_000009_create_weekly_schedules_and_course_meetings.php'))->up();
    }

    public function test_migration_prevents_duplicate_course_offering_combination(): void
    {
        $data = $this->academicData();
        $attributes = $this->offeringAttributes($data);
        PenawaranMataKuliah::create($attributes);

        $this->expectException(UniqueConstraintViolationException::class);
        PenawaranMataKuliah::create([...$attributes, 'code' => 'OF-DUPLICATE']);
    }

    public function test_student_submits_and_advisor_approves_locked_krs_with_notification(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->forRegistration($data['registration']);

        $service->add($krs, $offering);
        $this->assertSame(3, $krs->fresh()->total_sks);
        $service->submit($krs->fresh(), 'Mohon persetujuan.');
        $approved = $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, 'Disetujui.');

        $this->assertSame(Krs::STATUS_APPROVED, $approved->status);
        $this->assertNotNull($approved->diputuskan_at);
        $this->assertSame($data['advisor']->id, $approved->diputuskan_oleh);
        $this->assertDatabaseHas('notifications', ['student_id' => $data['student']->id, 'type' => 'krs']);
        $this->assertTrue($offering->pesertaDisetujui()->whereKey($data['student']->id)->exists());

        $this->expectException(LogicException::class);
        KrsItem::create(['krs_id' => $approved->id, 'penawaran_mata_kuliah_id' => $offering->id, 'sks' => 3]);
    }

    public function test_krs_rejects_closed_window_and_credit_limit(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->forRegistration($data['registration']);
        KalenderAkademik::query()->update(['selesai_at' => now()->subMinute()]);

        try {
            $service->add($krs, $offering);
            $this->fail('KRS di luar jadwal seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('krs', $exception->errors());
        }

        KalenderAkademik::query()->update(['selesai_at' => now()->addDay()]);
        $data['registration']->update(['batas_sks' => 2]);

        $this->expectException(ValidationException::class);
        $service->add($krs->fresh(), $offering);
    }

    public function test_only_assigned_advisor_can_decide_submitted_krs(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $other = $this->lecturer('9999', 'Dosen Lain');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->decide($krs->fresh(), $other, Krs::STATUS_APPROVED, null);
    }

    public function test_weekly_schedule_detects_lecturer_class_room_conflicts_and_capacity(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $roomId = $this->room(40);
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id, 'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id, 'ruang_id' => $roomId, 'hari' => 1,
            'mulai' => '08:00', 'selesai' => '09:40',
        ];
        JadwalMingguan::create([...$attributes, 'code' => 'JMG-ONE', 'fingerprint' => JadwalMingguan::fingerprint($attributes)]);

        try {
            app(ScheduleConflictService::class)->validate($offering, [...$attributes, 'mulai' => '09:00', 'selesai' => '10:00']);
            $this->fail('Semua bentrok sumber daya seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('dosen_id', $exception->errors());
            $this->assertArrayHasKey('kelas_id', $exception->errors());
            $this->assertArrayHasKey('ruang_id', $exception->errors());
        }

        $administrator = User::create([
            'type' => 0, 'code' => 'WEBADMIN', 'name' => 'Web Administrator', 'user' => 'webadmin',
            'phone' => '0812000000', 'email' => 'webadmin@example.test', 'password' => 'secret', 'status' => 1,
        ]);
        $override = app(ScheduleConflictService::class)->validate(
            $offering,
            [...$attributes, 'mulai' => '09:00', 'selesai' => '10:00'],
            actor: $administrator,
            override: true,
            reason: 'Kegiatan akademik khusus yang telah disetujui pimpinan.'
        );
        $this->assertSame($administrator->id, $override['pengecualian_oleh']);

        $smallRoom = $this->room(2, 'R002');
        $this->expectException(ValidationException::class);
        app(ScheduleConflictService::class)->validate($offering, [...$attributes, 'ruang_id' => $smallRoom, 'hari' => 2]);
    }

    public function test_meeting_generator_skips_holiday_and_is_idempotent(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $roomId = $this->room(40);
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id, 'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id, 'ruang_id' => $roomId, 'hari' => 1,
            'mulai' => '08:00', 'selesai' => '09:40',
        ];
        $schedule = JadwalMingguan::create([...$attributes, 'code' => 'JMG-GEN', 'fingerprint' => JadwalMingguan::fingerprint($attributes)]);
        KalenderAkademik::create([
            'taka_id' => $data['periodId'], 'kategori' => 'libur', 'nama' => 'Hari Libur',
            'mulai_at' => '2026-08-03 00:00:00', 'selesai_at' => '2026-08-03 23:59:59', 'dipublikasikan' => true,
        ]);
        $generator = app(MeetingGeneratorService::class);
        $preview = $generator->preview($schedule, '2026-08-01', '2026-08-31', 2);

        $this->assertSame(['2026-08-10', '2026-08-17'], $preview->pluck('tanggal')->all());
        $this->assertSame(['created' => 2, 'skipped' => 0], $generator->generate($schedule, '2026-08-01', '2026-08-31', 2));
        $this->assertSame(['created' => 0, 'skipped' => 2], $generator->generate($schedule, '2026-08-01', '2026-08-31', 2));
        $this->assertSame(2, PertemuanKuliah::where('jadwal_mingguan_id', $schedule->id)->count());
        $this->assertDatabaseCount('jadwal_kuliahs', 2);
    }

    public function test_attendance_requires_approved_krs_participant(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $krsService = app(KrsService::class);
        $krs = $krsService->add($krsService->forRegistration($data['registration']), $offering);
        $krsService->submit($krs);
        $krsService->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id, 'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id, 'ruang_id' => $this->room(40), 'hari' => 1,
            'mulai' => '08:00', 'selesai' => '09:40',
        ];
        $schedule = JadwalMingguan::create([...$attributes, 'code' => 'JMG-ABS', 'fingerprint' => JadwalMingguan::fingerprint($attributes)]);
        app(MeetingGeneratorService::class)->generate($schedule, '2026-08-01', '2026-08-31', 1);
        $meeting = $schedule->pertemuans()->firstOrFail();

        $item = app(AttendanceEligibilityService::class)->eligibleKrsItem($meeting, $data['student']);
        $this->assertSame($offering->id, $item->penawaran_mata_kuliah_id);
        $this->assertSame(1, app(ScheduleNotificationService::class)->changed($schedule, 'Jadwal kuliah berubah.'));
        $this->assertDatabaseHas('notifications', [
            'student_id' => $data['student']->id, 'type' => 'jadwal', 'desc' => 'Jadwal kuliah berubah.',
        ]);

        $outsider = Mahasiswa::create([
            'mhs_nim' => '26999', 'mhs_name' => 'Mahasiswa Luar', 'mhs_code' => 'MHS999',
            'mhs_user' => 'mhs999', 'password' => 'secret', 'mhs_mail' => 'mhs999@example.test', 'mhs_phone' => '0899999999',
        ]);
        $this->expectException(ValidationException::class);
        app(AttendanceEligibilityService::class)->eligibleKrsItem($meeting, $outsider);
    }

    private function academicData(): array
    {
        $advisor = $this->lecturer('1001', 'Dosen Wali');
        $periodId = DB::table('tahun_akademiks')->insertGetId([
            'name' => '2026/2027 Ganjil', 'code' => '20261', 'semester' => 1, 'year_start' => 2026,
            'year_end' => 2027, 'term' => 'ganjil', 'status' => 'active', 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $programId = DB::table('program_studis')->insertGetId([
            'faku_id' => 1, 'name' => 'Pendidikan Agama Islam', 'cnim' => '01', 'code' => 'PAI',
            'slug' => 'pai', 'head_id' => $advisor->id, 'title' => 'S.Pd', 'level' => 'S1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $periodId, 'pstudi_id' => $programId, 'dosen_id' => $advisor->id,
            'capacity' => 40, 'name' => 'PAI 1A', 'code' => 'PAI-1A-2026', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $curriculumId = DB::table('kurikulums')->insertGetId([
            'name' => 'Kurikulum 2026', 'code' => 'K26', 'desc' => '-', 'year_start' => 2026,
            'year_ended' => 2030, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = Mahasiswa::create([
            'taka_id' => $periodId, 'class_id' => $classId, 'mhs_stat' => 1, 'mhs_nim' => '26001',
            'mhs_name' => 'Mahasiswa Uji', 'mhs_code' => 'MHS001', 'mhs_user' => 'mhs001', 'password' => 'secret',
            'mhs_mail' => 'mhs001@example.test', 'mhs_phone' => '0811111111',
        ]);
        $registration = RegistrasiMahasiswa::create([
            'mahasiswa_id' => $student->id, 'taka_id' => $periodId, 'semester_mahasiswa' => 1,
            'status_akademik' => 'aktif', 'status_registrasi' => 'terdaftar', 'kelas_id' => $classId,
            'dosen_wali_id' => $advisor->id, 'batas_sks' => 24,
        ]);
        $master = MasterMataKuliah::create(['program_studi' => 'PAI', 'semester' => 1, 'name' => 'Pengantar Studi Islam', 'sks' => 3]);
        KalenderAkademik::create([
            'taka_id' => $periodId, 'kategori' => 'krs', 'nama' => 'Pengisian KRS',
            'mulai_at' => now()->subDay(), 'selesai_at' => now()->addDay(), 'dipublikasikan' => true,
        ]);

        return compact('advisor', 'student', 'registration', 'master', 'periodId', 'programId', 'classId', 'curriculumId');
    }

    private function offeringAttributes(array $data): array
    {
        return [
            'master_mata_kuliah_id' => $data['master']->id, 'taka_id' => $data['periodId'],
            'pstudi_id' => $data['programId'], 'kuri_id' => $data['curriculumId'], 'kelas_id' => $data['classId'],
            'dosen_utama_id' => $data['advisor']->id, 'code' => 'OF-'.uniqid(), 'sks' => 3, 'kapasitas' => 40,
        ];
    }

    private function lecturer(string $nidn, string $name): Dosen
    {
        return Dosen::create([
            'dsn_stat' => 1, 'dsn_nidn' => $nidn, 'dsn_name' => $name, 'dsn_code' => 'D'.$nidn,
            'dsn_user' => 'd'.$nidn, 'password' => 'secret', 'dsn_mail' => $nidn.'@example.test',
            'dsn_phone' => '08'.$nidn,
        ]);
    }

    private function room(int $capacity, string $code = 'R001'): int
    {
        $buildingId = DB::table('gedungs')->insertGetId([
            'name' => 'Gedung '.$code, 'code' => 'G'.$code,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('ruangs')->insertGetId([
            'gedu_id' => $buildingId, 'type' => 0, 'floor' => 1, 'kapasitas' => $capacity,
            'name' => 'Ruang '.$code, 'code' => $code, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
