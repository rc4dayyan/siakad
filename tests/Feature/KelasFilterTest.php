<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\ProgramKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KelasFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        (require database_path('migrations/2024_06_26_050556_create_web_settings_table.php'))->up();
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        Schema::create('program_studis', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
        });
        (require database_path('migrations/2024_04_26_061235_create_program_kuliahs_table.php'))->up();
        Schema::create('dosens', function ($table): void {
            $table->id();
            $table->string('dsn_name');
        });
        (require database_path('migrations/2024_04_27_041303_create_kelas_table.php'))->up();
        Schema::create('registrasi_mahasiswas', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->unsignedBigInteger('taka_id');
        });
        Schema::create('notifications', function ($table): void {
            $table->id();
            $table->timestamps();
        });
        Schema::create('ticket_supports', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('dept_id')->nullable();
            $table->unsignedBigInteger('users_id')->nullable();
            $table->timestamps();
        });

        DB::table('web_settings')->insert([
            'school_apps' => 'SIAKAD',
            'school_name' => 'Kampus Test',
            'school_head' => 'Ketua Test',
            'school_link' => 'https://example.test',
            'school_desc' => 'Kampus untuk pengujian.',
            'school_email' => 'kampus@example.test',
            'school_phone' => '0800000000',
            'social_fb' => '-',
            'social_ig' => '-',
            'social_in' => '-',
            'social_tw' => '-',
        ]);
        DB::table('program_studis')->insert([
            ['id' => 1, 'name' => 'Pendidikan Agama Islam', 'code' => 'PAI'],
            ['id' => 2, 'name' => 'Pendidikan Bahasa Arab', 'code' => 'PBA'],
        ]);
        DB::table('dosens')->insert([
            ['id' => 1, 'dsn_name' => 'Dr. Ahmad'],
            ['id' => 2, 'dsn_name' => 'Dr. Fatimah'],
        ]);
    }

    public function test_classes_can_be_filtered_by_keyword_study_program_program_and_lecturer(): void
    {
        $period = $this->activePeriod();
        $program = $this->program($period, 1, 'Reguler Pagi');
        $otherProgram = $this->program($period, 2, 'Reguler Sore');
        $matching = $this->kelas($period, $program, 1, 1, 'Kelas Alpha', 'PAI-A');
        $this->kelas($period, $otherProgram, 2, 2, 'Kelas Beta', 'PBA-B');

        $response = $this
            ->actingAs($this->webAdmin())
            ->get(route('web-admin.master.kelas-index', [
                'q' => 'Alpha',
                'pstudi_id' => 1,
                'proku_id' => $program->id,
                'dosen_id' => 1,
            ]));

        $response->assertOk();
        $response->assertViewHas('kelas', fn ($classes): bool => $classes->modelKeys() === [$matching->id]);
        $response->assertSee('Filter Data Kelas');
        $response->assertSee('1 data ditemukan');
        $response->assertSee('Lihat Mahasiswa');
        $response->assertSee('Edit Data');
        $response->assertSee('Hapus Data');
    }

    public function test_program_filter_from_another_period_is_rejected(): void
    {
        $this->activePeriod();
        $otherPeriod = TahunAkademik::factory()->create([
            'name' => '2025/2026 Ganjil',
            'code' => '2025-GANJIL',
            'year_start' => 2025,
            'year_end' => 2026,
        ]);
        $otherProgram = $this->program($otherPeriod, 1, 'Program Lama');

        $response = $this
            ->actingAs($this->webAdmin())
            ->get(route('web-admin.master.kelas-index', ['proku_id' => $otherProgram->id]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('proku_id');
    }

    private function activePeriod(): TahunAkademik
    {
        return TahunAkademik::factory()->create([
            'name' => '2026/2027 Ganjil',
            'code' => '2026-GANJIL',
            'year_start' => 2026,
            'year_end' => 2027,
            'status' => TahunAkademik::STATUS_ACTIVE,
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    private function program(TahunAkademik $period, int $studyProgramId, string $name): ProgramKuliah
    {
        return ProgramKuliah::create([
            'taka_id' => $period->id,
            'pstudi_id' => $studyProgramId,
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)).'-'.$period->id,
            'wave' => 'Gelombang I',
            'wave_start' => $period->year_start.'-01-01',
            'wave_ended' => $period->year_start.'-03-31',
        ]);
    }

    private function kelas(
        TahunAkademik $period,
        ProgramKuliah $program,
        int $studyProgramId,
        int $lecturerId,
        string $name,
        string $code
    ): Kelas {
        return Kelas::create([
            'taka_id' => $period->id,
            'pstudi_id' => $studyProgramId,
            'proku_id' => $program->id,
            'dosen_id' => $lecturerId,
            'capacity' => 30,
            'name' => $name,
            'code' => $code,
        ]);
    }

    private function webAdmin(): User
    {
        return User::create([
            'type' => 0,
            'code' => 'ADM-KELAS',
            'name' => 'Admin Kelas',
            'user' => 'admin.kelas',
            'phone' => '0800000002',
            'email' => 'admin.kelas@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
