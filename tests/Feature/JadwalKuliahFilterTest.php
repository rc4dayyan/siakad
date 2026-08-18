<?php

namespace Tests\Feature;

use App\Models\JadwalKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JadwalKuliahFilterTest extends TestCase
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
        (require database_path('migrations/2024_04_25_082451_create_fakultas_table.php'))->up();
        (require database_path('migrations/2024_04_25_082531_create_program_studis_table.php'))->up();
        Schema::create('dosens', function (Blueprint $table): void {
            $table->id();
            $table->string('dsn_name');
        });
        (require database_path('migrations/2024_04_28_063053_create_kurikulums_table.php'))->up();
        (require database_path('migrations/2024_04_30_032644_create_mata_kuliahs_table.php'))->up();
        (require database_path('migrations/2024_04_27_041303_create_kelas_table.php'))->up();
        (require database_path('migrations/2024_04_28_035926_create_gedungs_table.php'))->up();
        (require database_path('migrations/2024_04_28_052322_create_ruangs_table.php'))->up();
        (require database_path('migrations/2024_04_30_055648_create_jadwal_kuliahs_table.php'))->up();
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
        Schema::create('ticket_supports', function (Blueprint $table): void {
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
        DB::table('fakultas')->insert(['id' => 1, 'name' => 'Tarbiyah', 'code' => 'FT', 'head_id' => 1]);
        DB::table('program_studis')->insert([
            ['id' => 1, 'faku_id' => 1, 'name' => 'Pendidikan Agama Islam', 'cnim' => 'PAI', 'code' => 'PAI', 'slug' => 'pai', 'head_id' => 1, 'title' => 'S.Pd.', 'level' => 'S1'],
            ['id' => 2, 'faku_id' => 1, 'name' => 'Pendidikan Bahasa Arab', 'cnim' => 'PBA', 'code' => 'PBA', 'slug' => 'pba', 'head_id' => 2, 'title' => 'S.Pd.', 'level' => 'S1'],
        ]);
        DB::table('dosens')->insert([
            ['id' => 1, 'dsn_name' => 'Dr. Ahmad'],
            ['id' => 2, 'dsn_name' => 'Dr. Fatimah'],
        ]);
        DB::table('kurikulums')->insert([
            'id' => 1,
            'name' => 'Kurikulum 2026',
            'code' => 'KUR-2026',
            'desc' => '-',
            'year_start' => 2026,
            'year_ended' => 2030,
        ]);
        DB::table('gedungs')->insert(['id' => 1, 'name' => 'Gedung Utama', 'code' => 'GU']);
        DB::table('ruangs')->insert([
            ['id' => 1, 'gedu_id' => 1, 'type' => 0, 'floor' => 1, 'name' => 'Ruang A', 'code' => 'RA'],
            ['id' => 2, 'gedu_id' => 1, 'type' => 0, 'floor' => 1, 'name' => 'Ruang B', 'code' => 'RB'],
        ]);
    }

    public function test_schedules_can_be_filtered_from_the_professional_filter_panel(): void
    {
        $period = $this->period('2026-GANJIL', true);
        $matchingClass = $this->kelas($period, 1, 'Kelas Alpha', 'PAI-A');
        $otherClass = $this->kelas($period, 2, 'Kelas Beta', 'PBA-B');
        $matchingCourse = $this->course($period, 1, 1, 'Studi Islam', 'PAI-101');
        $otherCourse = $this->course($period, 2, 2, 'Bahasa Arab', 'PBA-101');
        $matching = $this->schedule('JAD-ALPHA', $matchingCourse, $matchingClass, 1, 1, 0, 1, '2026-09-07');
        $this->schedule('JAD-BETA', $otherCourse, $otherClass, 2, 2, 1, 2, '2026-09-08');

        $response = $this
            ->actingAs($this->webAdmin())
            ->get(route('web-admin.master.jadkul-index', [
                'q' => 'Studi Islam',
                'pstudi_id' => 1,
                'kelas_id' => $matchingClass,
                'dosen_id' => 1,
                'ruang_id' => 1,
                'meth_id' => 0,
                'days_id' => 1,
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-07',
            ]));

        $response->assertOk();
        $response->assertViewHas('jadkul', fn ($schedules): bool => $schedules->modelKeys() === [$matching->id]);
        $response->assertSee('Filter Jadwal Kuliah');
        $response->assertSee('1 data ditemukan');
        $response->assertSee('Lihat Absensi');
        $response->assertSee('Edit Jadwal');
        $response->assertSee('Hapus Jadwal');
    }

    public function test_class_filter_from_another_period_is_rejected(): void
    {
        $this->period('2026-GANJIL', true);
        $oldPeriod = $this->period('2025-GANJIL');
        $oldClass = $this->kelas($oldPeriod, 1, 'Kelas Lama', 'PAI-LAMA');

        $response = $this
            ->actingAs($this->webAdmin())
            ->get(route('web-admin.master.jadkul-index', ['kelas_id' => $oldClass]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('kelas_id');
    }

    private function period(string $code, bool $active = false): TahunAkademik
    {
        return TahunAkademik::factory()->create([
            'name' => $code,
            'code' => $code,
            'year_start' => (int) substr($code, 0, 4),
            'year_end' => (int) substr($code, 0, 4) + 1,
            'status' => $active ? TahunAkademik::STATUS_ACTIVE : TahunAkademik::STATUS_CLOSED,
            'is_active' => $active,
            'activated_at' => $active ? now() : null,
        ]);
    }

    private function kelas(TahunAkademik $period, int $studyProgramId, string $name, string $code): int
    {
        return DB::table('kelas')->insertGetId([
            'taka_id' => $period->id,
            'pstudi_id' => $studyProgramId,
            'dosen_id' => 1,
            'capacity' => 30,
            'name' => $name,
            'code' => $code,
        ]);
    }

    private function course(TahunAkademik $period, int $studyProgramId, int $lecturerId, string $name, string $code): int
    {
        return DB::table('mata_kuliahs')->insertGetId([
            'kuri_id' => 1,
            'taka_id' => $period->id,
            'pstudi_id' => $studyProgramId,
            'dosen_1' => $lecturerId,
            'name' => $name,
            'code' => $code,
            'bsks' => 2,
            'desc' => '-',
        ]);
    }

    private function schedule(
        string $code,
        int $courseId,
        int $classId,
        int $lecturerId,
        int $roomId,
        int $methodId,
        int $dayId,
        string $date
    ): JadwalKuliah {
        return JadwalKuliah::create([
            'makul_id' => $courseId,
            'kelas_id' => $classId,
            'dosen_id' => $lecturerId,
            'ruang_id' => $roomId,
            'pert_id' => 1,
            'meth_id' => $methodId,
            'days_id' => $dayId,
            'bsks' => 2,
            'date' => $date,
            'start' => '08:00',
            'ended' => '10:00',
            'code' => $code,
        ]);
    }

    private function webAdmin(): User
    {
        return User::create([
            'type' => 0,
            'code' => 'ADM-JADWAL',
            'name' => 'Admin Jadwal',
            'user' => 'admin.jadwal',
            'phone' => '0800000003',
            'email' => 'admin.jadwal@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
