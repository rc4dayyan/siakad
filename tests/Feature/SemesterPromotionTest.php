<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\ProsesKenaikanSemester;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\SemesterPromotionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SemesterPromotionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        (require database_path('migrations/2024_03_09_024013_create_mahasiswas_table.php'))->up();
        (require database_path('migrations/2024_03_09_024021_create_dosens_table.php'))->up();
        (require database_path('migrations/2024_04_27_041303_create_kelas_table.php'))->up();
        (require database_path('migrations/2026_07_17_000005_create_registrasi_mahasiswas_table.php'))->up();
        (require database_path('migrations/2026_07_17_000007_create_proses_kenaikan_semesters_table.php'))->up();
    }

    public function test_preview_marks_terminal_duplicate_and_status_decisions_without_writing(): void
    {
        [$sourcePeriod, $targetPeriod, $sourceClass, $targetClass, $advisor] = $this->configuration();
        $active = $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'aktif');
        $leave = $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'cuti');
        $inactive = $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'nonaktif');
        $graduate = $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'lulus');
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $active->mahasiswa_id,
            'taka_id' => $targetPeriod->id,
            'kelas_id' => $targetClass->id,
            'dosen_wali_id' => $advisor->id,
            'semester_mahasiswa' => 2,
        ]);

        $preview = app(SemesterPromotionService::class)->preview(
            $sourcePeriod,
            $targetPeriod,
            $sourceClass,
            $targetClass,
            SemesterPromotionService::ACTION_PRESERVE,
            SemesterPromotionService::ACTION_SKIP
        )->keyBy('student_code');

        $this->assertSame('lewati', $preview[$active->mahasiswa->mhs_code]['action']);
        $this->assertSame('cuti', $preview[$leave->mahasiswa->mhs_code]['target_status']);
        $this->assertSame('lewati', $preview[$inactive->mahasiswa->mhs_code]['action']);
        $this->assertSame('lewati', $preview[$graduate->mahasiswa->mhs_code]['action']);
        $this->assertDatabaseCount('proses_kenaikan_semesters', 0);
        $this->assertSame(5, RegistrasiMahasiswa::count());
    }

    public function test_bulk_process_creates_registrations_and_audit_summary(): void
    {
        [$sourcePeriod, $targetPeriod, $sourceClass, $targetClass, $advisor] = $this->configuration();
        $active = $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'aktif', 2);
        $leave = $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'cuti', 2);
        $inactive = $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'nonaktif', 2);
        $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'lulus', 2);

        $run = app(SemesterPromotionService::class)->execute(
            $sourcePeriod,
            $targetPeriod,
            $sourceClass,
            $targetClass,
            $advisor,
            SemesterPromotionService::ACTION_PRESERVE,
            SemesterPromotionService::ACTION_ACTIVATE,
            $this->webAdmin(),
            2
        );

        $this->assertSame(4, $run->jumlah_sumber);
        $this->assertSame(3, $run->jumlah_berhasil);
        $this->assertSame(1, $run->jumlah_dilewati);
        $this->assertSame(0, $run->jumlah_gagal);
        $this->assertCount(3, $run->ringkasan['berhasil']);
        $this->assertCount(1, $run->ringkasan['dilewati']);
        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'mahasiswa_id' => $active->mahasiswa_id,
            'taka_id' => $targetPeriod->id,
            'semester_mahasiswa' => 3,
            'status_akademik' => 'aktif',
        ]);
        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'mahasiswa_id' => $leave->mahasiswa_id,
            'taka_id' => $targetPeriod->id,
            'status_akademik' => 'cuti',
        ]);
        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'mahasiswa_id' => $inactive->mahasiswa_id,
            'taka_id' => $targetPeriod->id,
            'status_akademik' => 'aktif',
        ]);
        $this->assertSame($targetClass->id, $active->mahasiswa->fresh()->class_id);
    }

    public function test_rerun_is_safe_and_reports_existing_targets_as_skipped(): void
    {
        [$sourcePeriod, $targetPeriod, $sourceClass, $targetClass, $advisor] = $this->configuration();
        $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'aktif');
        $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'cuti');
        $service = app(SemesterPromotionService::class);
        $actor = $this->webAdmin();

        $service->execute(
            $sourcePeriod,
            $targetPeriod,
            $sourceClass,
            $targetClass,
            $advisor,
            'aktifkan',
            'aktifkan',
            $actor
        );
        $rerun = $service->execute(
            $sourcePeriod,
            $targetPeriod,
            $sourceClass,
            $targetClass,
            $advisor,
            'aktifkan',
            'aktifkan',
            $actor
        );

        $this->assertSame(0, $rerun->jumlah_berhasil);
        $this->assertSame(2, $rerun->jumlah_dilewati);
        $this->assertSame(4, RegistrasiMahasiswa::count());
        $this->assertSame(2, ProsesKenaikanSemester::count());
    }

    public function test_invalid_configuration_rolls_back_before_any_registration_or_audit_is_written(): void
    {
        [$sourcePeriod, $targetPeriod, $sourceClass, , $advisor] = $this->configuration();
        $this->sourceRegistration($sourcePeriod, $sourceClass, $advisor, 'aktif');

        try {
            app(SemesterPromotionService::class)->execute(
                $sourcePeriod,
                $targetPeriod,
                $sourceClass,
                $sourceClass,
                $advisor,
                'aktifkan',
                'aktifkan',
                $this->webAdmin()
            );
            $this->fail('Kelas tujuan lintas periode seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('kelas_tujuan', $exception->errors());
        }

        $this->assertSame(1, RegistrasiMahasiswa::count());
        $this->assertDatabaseCount('proses_kenaikan_semesters', 0);
    }

    private function configuration(): array
    {
        $sourcePeriod = $this->period('2025-GANJIL', TahunAkademik::STATUS_CLOSED, '2025-08-01');
        $targetPeriod = $this->period('2026-GENAP', TahunAkademik::STATUS_ACTIVE, '2026-02-01');
        $advisor = $this->lecturer();

        return [
            $sourcePeriod,
            $targetPeriod,
            $this->kelas($sourcePeriod, $advisor, 'KELAS-SUMBER'),
            $this->kelas($targetPeriod, $advisor, 'KELAS-TUJUAN'),
            $advisor,
        ];
    }

    private function sourceRegistration(
        TahunAkademik $period,
        Kelas $class,
        Dosen $advisor,
        string $status,
        int $semester = 1
    ): RegistrasiMahasiswa {
        return RegistrasiMahasiswa::factory()->create([
            'taka_id' => $period->id,
            'kelas_id' => $class->id,
            'dosen_wali_id' => $advisor->id,
            'semester_mahasiswa' => $semester,
            'status_akademik' => $status,
        ]);
    }

    private function period(string $code, string $status, string $startsAt): TahunAkademik
    {
        return TahunAkademik::factory()->create([
            'name' => $code,
            'code' => $code,
            'status' => $status,
            'is_active' => $status === TahunAkademik::STATUS_ACTIVE,
            'starts_at' => $startsAt,
            'ends_at' => date('Y-m-d', strtotime($startsAt.' +5 months')),
        ]);
    }

    private function lecturer(): Dosen
    {
        return Dosen::create([
            'dsn_stat' => 1,
            'dsn_nidn' => fake()->unique()->numerify('##########'),
            'dsn_name' => fake()->name(),
            'dsn_code' => fake()->unique()->bothify('DSN-####'),
            'dsn_user' => fake()->unique()->userName(),
            'password' => 'password',
            'dsn_mail' => fake()->unique()->safeEmail(),
            'dsn_phone' => fake()->unique()->numerify('08##########'),
        ]);
    }

    private function kelas(TahunAkademik $period, Dosen $advisor, string $code): Kelas
    {
        return Kelas::create([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'dosen_id' => $advisor->id,
            'capacity' => 30,
            'name' => $code,
            'code' => $code,
        ]);
    }

    private function webAdmin(): User
    {
        return User::create([
            'type' => 0,
            'code' => fake()->unique()->bothify('ADM-####'),
            'name' => fake()->name(),
            'user' => fake()->unique()->userName(),
            'phone' => fake()->unique()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
