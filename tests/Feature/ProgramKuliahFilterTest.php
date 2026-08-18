<?php

namespace Tests\Feature;

use App\Models\ProgramKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgramKuliahFilterTest extends TestCase
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
    }

    public function test_programs_can_be_filtered_by_period_study_program_and_wave(): void
    {
        $currentPeriod = $this->period('2026-GANJIL', 2026);
        $oldPeriod = $this->period('2025-GANJIL', 2025);
        $matching = $this->program($currentPeriod, 1, 'Reguler Pagi', 'Gelombang I');
        $this->program($currentPeriod, 2, 'Reguler Sore', 'Gelombang I');
        $this->program($oldPeriod, 1, 'Reguler Lama', 'Gelombang II');

        $response = $this
            ->actingAs($this->webAdmin())
            ->get(route('web-admin.master.proku-index', [
                'taka_id' => $currentPeriod->id,
                'pstudi_id' => 1,
                'wave' => 'Gelombang I',
            ]));

        $response->assertOk();
        $response->assertViewHas('proku', function ($programs) use ($matching): bool {
            return $programs->modelKeys() === [$matching->id];
        });
        $response->assertSee('Filter Program Kuliah');
        $response->assertSee('2026-GANJIL');
        $response->assertSee('1 data ditemukan');
    }

    public function test_invalid_period_filter_is_rejected(): void
    {
        $response = $this
            ->actingAs($this->webAdmin())
            ->get(route('web-admin.master.proku-index', ['taka_id' => 999]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('taka_id');
    }

    private function period(string $code, int $year): TahunAkademik
    {
        return TahunAkademik::factory()->create([
            'name' => $code,
            'code' => $code,
            'year_start' => $year,
            'year_end' => $year + 1,
        ]);
    }

    private function program(
        TahunAkademik $period,
        int $studyProgramId,
        string $name,
        string $wave
    ): ProgramKuliah {
        return ProgramKuliah::create([
            'taka_id' => $period->id,
            'pstudi_id' => $studyProgramId,
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)).'-'.$period->id,
            'wave' => $wave,
            'wave_start' => $period->year_start.'-01-01',
            'wave_ended' => $period->year_start.'-03-31',
        ]);
    }

    private function webAdmin(): User
    {
        return User::create([
            'type' => 0,
            'code' => 'ADM-PROKU',
            'name' => 'Admin Program Kuliah',
            'user' => 'admin.proku',
            'phone' => '0800000001',
            'email' => 'admin.proku@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
