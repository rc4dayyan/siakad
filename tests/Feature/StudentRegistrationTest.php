<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
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
    }

    public function test_staff_can_register_student_to_selected_period(): void
    {
        $period = $this->period('2026-GENAP', TahunAkademik::STATUS_ACTIVE, '2026-02-01');
        $student = Mahasiswa::factory()->create();
        $advisor = $this->lecturer();
        $class = $this->kelas($period, $advisor);

        $response = $this->registerRequest($student->mhs_code, $period, $class, $advisor, [
            'semester_mahasiswa' => 4,
            'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
            'batas_sks' => 22,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
            'semester_mahasiswa' => 4,
            'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
            'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
            'kelas_id' => $class->id,
            'dosen_wali_id' => $advisor->id,
            'batas_sks' => 22,
        ]);
        $this->assertSame($period->id, $student->fresh()->taka_id);
        $this->assertSame($class->id, $student->fresh()->class_id);
    }

    public function test_duplicate_registration_is_rejected_without_overwriting_history(): void
    {
        $period = $this->period('2026-GENAP', TahunAkademik::STATUS_ACTIVE, '2026-02-01');
        $student = Mahasiswa::factory()->create();
        $advisor = $this->lecturer();
        $class = $this->kelas($period, $advisor);
        $existing = RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
            'semester_mahasiswa' => 3,
            'kelas_id' => $class->id,
            'dosen_wali_id' => $advisor->id,
        ]);

        $response = $this->registerRequest($student->mhs_code, $period, $class, $advisor, [
            'semester_mahasiswa' => 4,
        ]);

        $response->assertSessionHasErrors('registration');
        $this->assertSame(1, RegistrasiMahasiswa::query()->where('mahasiswa_id', $student->id)->count());
        $this->assertSame(3, $existing->fresh()->semester_mahasiswa);
    }

    public function test_unknown_student_returns_not_found(): void
    {
        $period = $this->period('2026-GENAP', TahunAkademik::STATUS_ACTIVE, '2026-02-01');
        $advisor = $this->lecturer();
        $class = $this->kelas($period, $advisor);

        $response = $this->registerRequest('MHS-TIDAK-ADA', $period, $class, $advisor);

        $response->assertRedirect(route('error.notfound'));
        $this->assertDatabaseCount('registrasi_mahasiswas', 0);
    }

    public function test_class_from_another_period_is_rejected(): void
    {
        $targetPeriod = $this->period('2026-GENAP', TahunAkademik::STATUS_ACTIVE, '2026-02-01');
        $otherPeriod = $this->period('2025-GANJIL', TahunAkademik::STATUS_CLOSED, '2025-08-01');
        $student = Mahasiswa::factory()->create();
        $advisor = $this->lecturer();
        $otherClass = $this->kelas($otherPeriod, $advisor);

        $response = $this->registerRequest($student->mhs_code, $targetPeriod, $otherClass, $advisor);

        $response->assertSessionHasErrors('kelas_id');
        $this->assertDatabaseCount('registrasi_mahasiswas', 0);
    }

    public function test_student_with_terminal_latest_status_cannot_be_registered_again(): void
    {
        $oldPeriod = $this->period('2025-GANJIL', TahunAkademik::STATUS_CLOSED, '2025-08-01');
        $targetPeriod = $this->period('2026-GENAP', TahunAkademik::STATUS_ACTIVE, '2026-02-01');
        $student = Mahasiswa::factory()->create();
        $advisor = $this->lecturer();
        $oldClass = $this->kelas($oldPeriod, $advisor);
        $targetClass = $this->kelas($targetPeriod, $advisor);
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $oldPeriod->id,
            'kelas_id' => $oldClass->id,
            'dosen_wali_id' => $advisor->id,
            'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_LULUS,
        ]);

        $response = $this->registerRequest($student->mhs_code, $targetPeriod, $targetClass, $advisor);

        $response->assertSessionHasErrors('status_akademik');
        $this->assertSame(1, RegistrasiMahasiswa::query()->where('mahasiswa_id', $student->id)->count());
    }

    public function test_registration_relations_preserve_history_across_periods(): void
    {
        $firstPeriod = $this->period('2025-GANJIL', TahunAkademik::STATUS_CLOSED, '2025-08-01');
        $secondPeriod = $this->period('2026-GENAP', TahunAkademik::STATUS_ACTIVE, '2026-02-01');
        $student = Mahasiswa::factory()->create();
        $advisor = $this->lecturer();
        $firstClass = $this->kelas($firstPeriod, $advisor);
        $secondClass = $this->kelas($secondPeriod, $advisor);
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $firstPeriod->id,
            'semester_mahasiswa' => 1,
            'kelas_id' => $firstClass->id,
            'dosen_wali_id' => $advisor->id,
        ]);

        $this->registerRequest($student->mhs_code, $secondPeriod, $secondClass, $advisor, [
            'semester_mahasiswa' => 2,
        ])->assertSessionHasNoErrors();

        $history = $student->registrasiAkademik()->with(['taka', 'kelas', 'dosenWali'])->orderBy('semester_mahasiswa')->get();

        $this->assertCount(2, $history);
        $this->assertSame(['2025-GANJIL', '2026-GENAP'], $history->pluck('taka.code')->all());
        $this->assertSame([$firstClass->id, $secondClass->id], $history->pluck('kelas.id')->all());
        $this->assertTrue($history->every(fn (RegistrasiMahasiswa $registration) => $registration->dosenWali->is($advisor)));
    }

    private function registerRequest(
        string $studentCode,
        TahunAkademik $period,
        Kelas $class,
        Dosen $advisor,
        array $overrides = []
    ) {
        return $this
            ->actingAs($this->webAdmin())
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->post(route('web-admin.workers.student-registration-store', $studentCode), array_merge([
                'semester_mahasiswa' => 1,
                'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
                'kelas_id' => $class->id,
                'dosen_wali_id' => $advisor->id,
                'batas_sks' => 24,
            ], $overrides));
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

    private function kelas(TahunAkademik $period, Dosen $advisor): Kelas
    {
        return Kelas::create([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'dosen_id' => $advisor->id,
            'capacity' => 30,
            'name' => 'Kelas '.fake()->unique()->word(),
            'code' => fake()->unique()->bothify('KLS-####'),
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
