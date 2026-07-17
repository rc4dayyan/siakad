<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrasiMahasiswaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        (require database_path('migrations/2024_03_09_024013_create_mahasiswas_table.php'))->up();
        (require database_path('migrations/2024_03_09_024021_create_dosens_table.php'))->up();
        (require database_path('migrations/2024_04_27_041303_create_kelas_table.php'))->up();
        (require database_path('migrations/2026_07_17_000005_create_registrasi_mahasiswas_table.php'))->up();
    }

    public function test_factory_creates_registration_with_safe_defaults(): void
    {
        $registration = RegistrasiMahasiswa::factory()->create();

        $this->assertInstanceOf(Mahasiswa::class, $registration->mahasiswa);
        $this->assertInstanceOf(TahunAkademik::class, $registration->taka);
        $this->assertSame(RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF, $registration->status_akademik);
        $this->assertSame(RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR, $registration->status_registrasi);
        $this->assertSame(24, $registration->batas_sks);
        $this->assertIsInt($registration->semester_mahasiswa);
    }

    public function test_student_can_only_have_one_registration_per_period(): void
    {
        $student = Mahasiswa::factory()->create();
        $period = TahunAkademik::factory()->create();
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
        ]);
    }

    public function test_registration_relations_and_period_scope_are_correct(): void
    {
        $student = Mahasiswa::factory()->create();
        $currentPeriod = TahunAkademik::factory()->create();
        $otherPeriod = TahunAkademik::factory()->create();
        $lecturer = $this->lecturer();
        $class = $this->kelas($currentPeriod, $lecturer);
        $current = RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $currentPeriod->id,
            'kelas_id' => $class->id,
            'dosen_wali_id' => $lecturer->id,
        ]);
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $otherPeriod->id,
        ]);

        $registrations = RegistrasiMahasiswa::query()->forAcademicPeriod($currentPeriod)->get();

        $this->assertCount(1, $registrations);
        $this->assertTrue($registrations->first()->is($current));
        $this->assertTrue($current->kelas->is($class));
        $this->assertTrue($current->dosenWali->is($lecturer));
        $this->assertTrue($student->registrasiAkademik->contains($current));
        $this->assertTrue($currentPeriod->registrasiMahasiswas->contains($current));
        $this->assertTrue($class->registrasiMahasiswas->contains($current));
        $this->assertTrue($lecturer->mahasiswaWaliRegistrations->contains($current));
    }

    public function test_legacy_class_changes_do_not_overwrite_registration_history(): void
    {
        $student = Mahasiswa::factory()->create();
        $firstPeriod = TahunAkademik::factory()->create();
        $secondPeriod = TahunAkademik::factory()->create();
        $firstClass = $this->kelas($firstPeriod);
        $secondClass = $this->kelas($secondPeriod);
        $firstRegistration = RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $firstPeriod->id,
            'kelas_id' => $firstClass->id,
            'semester_mahasiswa' => 1,
        ]);
        $secondRegistration = RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $secondPeriod->id,
            'kelas_id' => $secondClass->id,
            'semester_mahasiswa' => 2,
        ]);

        $student->update(['taka_id' => $secondPeriod->id, 'class_id' => $secondClass->id]);

        $this->assertSame($firstClass->id, $firstRegistration->fresh()->kelas_id);
        $this->assertSame(1, $firstRegistration->fresh()->semester_mahasiswa);
        $this->assertSame($secondClass->id, $secondRegistration->fresh()->kelas_id);
        $this->assertSame(2, $secondRegistration->fresh()->semester_mahasiswa);
    }

    public function test_period_with_registration_is_recognized_as_having_academic_data(): void
    {
        $registration = RegistrasiMahasiswa::factory()->create();

        $this->assertTrue($registration->taka->hasAcademicData());
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

    private function kelas(TahunAkademik $period, ?Dosen $lecturer = null): Kelas
    {
        return Kelas::create([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'dosen_id' => $lecturer?->id,
            'capacity' => 30,
            'name' => 'Kelas '.fake()->unique()->word(),
            'code' => fake()->unique()->bothify('KLS-####'),
        ]);
    }
}
