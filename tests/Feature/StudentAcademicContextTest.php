<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Services\Academic\StudentAcademicContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentAcademicContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', fn (Blueprint $table) => $table->id());
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        (require database_path('migrations/2024_03_09_024013_create_mahasiswas_table.php'))->up();
        (require database_path('migrations/2024_03_09_024021_create_dosens_table.php'))->up();
        (require database_path('migrations/2024_04_27_041303_create_kelas_table.php'))->up();
        (require database_path('migrations/2026_07_17_000005_create_registrasi_mahasiswas_table.php'))->up();
    }

    public function test_registration_class_takes_precedence_over_stale_legacy_class(): void
    {
        $period = TahunAkademik::factory()->create();
        $legacyClass = $this->kelas($period, 'LEGACY');
        $registeredClass = $this->kelas($period, 'REGISTERED');
        $student = Mahasiswa::factory()->create([
            'taka_id' => $period->id,
            'class_id' => $legacyClass->id,
        ]);
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
            'kelas_id' => $registeredClass->id,
        ]);

        $resolved = app(StudentAcademicContext::class)->classFor($student, $period);

        $this->assertTrue($resolved->is($registeredClass));
    }

    public function test_legacy_class_is_used_only_when_registration_is_missing_for_same_period(): void
    {
        $period = TahunAkademik::factory()->create();
        $legacyClass = $this->kelas($period, 'LEGACY');
        $student = Mahasiswa::factory()->create([
            'taka_id' => $period->id,
            'class_id' => $legacyClass->id,
        ]);

        $resolved = app(StudentAcademicContext::class)->classFor($student, $period);

        $this->assertTrue($resolved->is($legacyClass));
    }

    public function test_legacy_class_from_another_period_is_not_leaked(): void
    {
        $activePeriod = TahunAkademik::factory()->create();
        $oldPeriod = TahunAkademik::factory()->create();
        $oldClass = $this->kelas($oldPeriod, 'OLD');
        $student = Mahasiswa::factory()->create([
            'taka_id' => $oldPeriod->id,
            'class_id' => $oldClass->id,
        ]);

        $this->assertNull(app(StudentAcademicContext::class)->classFor($student, $activePeriod));
    }

    public function test_class_scope_combines_registration_and_safe_legacy_fallback(): void
    {
        $period = TahunAkademik::factory()->create();
        $class = $this->kelas($period, 'TARGET');
        $otherClass = $this->kelas($period, 'OTHER');
        $registered = Mahasiswa::factory()->create(['class_id' => $otherClass->id, 'taka_id' => $period->id]);
        $fallback = Mahasiswa::factory()->create(['class_id' => $class->id, 'taka_id' => $period->id]);
        $stale = Mahasiswa::factory()->create(['class_id' => $class->id, 'taka_id' => $period->id]);
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $registered->id,
            'taka_id' => $period->id,
            'kelas_id' => $class->id,
        ]);
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $stale->id,
            'taka_id' => $period->id,
            'kelas_id' => $otherClass->id,
        ]);

        $studentIds = Mahasiswa::query()->forAcademicClass($period, $class->id)->pluck('id');

        $this->assertTrue($studentIds->contains($registered->id));
        $this->assertTrue($studentIds->contains($fallback->id));
        $this->assertFalse($studentIds->contains($stale->id));
    }

    public function test_profile_uses_latest_registration_when_active_period_has_none(): void
    {
        $oldPeriod = TahunAkademik::factory()->create();
        $activePeriod = TahunAkademik::factory()->create();
        $student = Mahasiswa::factory()->create();
        $registration = RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $oldPeriod->id,
            'kelas_id' => $this->kelas($oldPeriod, 'LATEST')->id,
        ]);

        $resolved = app(StudentAcademicContext::class)->profileRegistration($student, $activePeriod);

        $this->assertTrue($resolved->is($registration));
    }

    private function kelas(TahunAkademik $period, string $code): Kelas
    {
        return Kelas::create([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'capacity' => 30,
            'name' => 'Kelas '.$code,
            'code' => $code,
        ]);
    }
}
