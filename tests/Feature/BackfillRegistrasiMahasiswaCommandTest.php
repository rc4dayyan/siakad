<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillRegistrasiMahasiswaCommandTest extends TestCase
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

    public function test_dry_run_reports_valid_data_without_writing(): void
    {
        $period = TahunAkademik::factory()->create(['semester' => 2]);
        $class = $this->kelas($period);
        $student = Mahasiswa::factory()->create([
            'taka_id' => $period->id,
            'class_id' => $class->id,
        ]);

        $this->artisan('academic:backfill-registrations', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain('1 registrasi dapat dibuat')
            ->assertSuccessful();

        $this->assertDatabaseMissing('registrasi_mahasiswas', ['mahasiswa_id' => $student->id]);
    }

    public function test_valid_legacy_student_is_backfilled_with_class_period_and_status(): void
    {
        $period = TahunAkademik::factory()->create(['semester' => 2]);
        $class = $this->kelas($period);
        $student = Mahasiswa::factory()->create([
            'taka_id' => $period->id,
            'class_id' => $class->id,
            'mhs_stat' => 1,
        ]);

        $this->artisan('academic:backfill-registrations')->assertSuccessful();

        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
            'kelas_id' => $class->id,
            'semester_mahasiswa' => 2,
            'status_akademik' => 'aktif',
            'status_registrasi' => 'terdaftar',
            'batas_sks' => 24,
        ]);
    }

    public function test_incomplete_and_mismatched_legacy_data_are_reported_as_failed(): void
    {
        $studentWithoutReferences = Mahasiswa::factory()->create(['taka_id' => 0, 'class_id' => 0]);
        $studentPeriod = TahunAkademik::factory()->create();
        $otherPeriod = TahunAkademik::factory()->create();
        $otherClass = $this->kelas($otherPeriod);
        $mismatchedStudent = Mahasiswa::factory()->create([
            'taka_id' => $studentPeriod->id,
            'class_id' => $otherClass->id,
        ]);

        $this->artisan('academic:backfill-registrations')
            ->expectsOutputToContain('tahun akademik lama tidak ditemukan')
            ->expectsOutputToContain('kelas lama tidak berasal dari tahun akademik mahasiswa')
            ->assertFailed();

        $this->assertDatabaseMissing('registrasi_mahasiswas', ['mahasiswa_id' => $studentWithoutReferences->id]);
        $this->assertDatabaseMissing('registrasi_mahasiswas', ['mahasiswa_id' => $mismatchedStudent->id]);
    }

    public function test_rerunning_command_skips_existing_registration_without_duplicates(): void
    {
        $period = TahunAkademik::factory()->create();
        $class = $this->kelas($period);
        $student = Mahasiswa::factory()->create(['taka_id' => $period->id, 'class_id' => $class->id]);

        $this->artisan('academic:backfill-registrations')->assertSuccessful();
        $this->artisan('academic:backfill-registrations')->assertSuccessful();

        $this->assertSame(1, DB::table('registrasi_mahasiswas')->where('mahasiswa_id', $student->id)->count());
    }

    public function test_invalid_chunk_option_fails_before_processing(): void
    {
        $this->artisan('academic:backfill-registrations', ['--chunk' => 0])
            ->expectsOutputToContain('antara 1 sampai 5000')
            ->assertFailed();
    }

    private function kelas(TahunAkademik $period): Kelas
    {
        return Kelas::create([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'capacity' => 30,
            'name' => 'Kelas '.fake()->unique()->word(),
            'code' => fake()->unique()->bothify('KLS-####'),
        ]);
    }
}
