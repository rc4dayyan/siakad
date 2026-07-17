<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use Database\Seeders\DemoDuaTahunAkademikSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DemoDuaTahunAkademikSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('type')->default(0);
            $table->string('code')->unique();
            $table->string('name');
            $table->string('user');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->tinyInteger('status')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        foreach ($this->migrationFiles() as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    public function test_seeder_creates_complete_two_year_flow_and_is_idempotent(): void
    {
        $this->seed(DemoDuaTahunAkademikSeeder::class);
        $this->seed(DemoDuaTahunAkademikSeeder::class);

        $this->assertSame(4, TahunAkademik::query()->whereIn('code', [
            '242501',
            '242502',
            '252601',
            '252602',
        ])->count());
        $this->assertDatabaseHas('tahun_akademiks', [
            'code' => '252602',
            'status' => TahunAkademik::STATUS_ACTIVE,
            'is_active' => 1,
        ]);
        $this->assertSame(1, TahunAkademik::query()->where('is_active', true)->count());

        $this->assertSame(2, DB::table('dosens')->where('dsn_code', 'like', 'DEMO-%')->count());
        $this->assertSame(2, Mahasiswa::query()->where('mhs_code', 'like', 'DEMO-%')->count());
        $this->assertSame(4, DB::table('kelas')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(8, DB::table('mata_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(8, DB::table('jadwal_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(16, DB::table('absensi_mahasiswas')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(8, DB::table('hasil_studis')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(4, DB::table('student_tasks')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(2, DB::table('users')->where('code', 'like', 'DEMO-STAFF-%')->count());
        $this->assertSame(16, DB::table('kalender_akademiks')->where('nama', 'like', '% Demo')->count());
        $this->assertSame(8, DB::table('penawaran_mata_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(8, DB::table('jadwal_mingguans')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(32, DB::table('pertemuan_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(8, DB::table('krs')->count());
        $this->assertSame(16, DB::table('krs_items')->count());
        $this->assertSame(4, DB::table('template_tagihans')->where('name', 'like', 'UKT Demo %')->count());
        $this->assertSame(8, DB::table('tagihan_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(8, DB::table('history_tagihans')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(4, DB::table('penerbitan_tagihan_batches')->count());
        $this->assertSame(4, DB::table('period_readiness_snapshots')->where('purpose', 'demo_seed')->count());
        $this->assertSame(4, DB::table('period_publications')->count());
        $this->assertSame(4, DB::table('academic_workflow_audits')->where('event', 'period.demo_seeded')->count());

        $demoStudentIds = Mahasiswa::query()
            ->where('mhs_code', 'like', 'DEMO-%')
            ->pluck('id');
        $this->assertSame(8, RegistrasiMahasiswa::query()->whereIn('mahasiswa_id', $demoStudentIds)->count());
        $this->assertSame(16, DB::table('nilai_mahasiswas')->whereIn('mahasiswa_id', $demoStudentIds)->count());
        $this->assertSame(8, DB::table('student_scores')->whereIn('student_id', $demoStudentIds)->count());

        $firstStudent = Mahasiswa::query()->where('mhs_code', 'DEMO-MHS-01')->firstOrFail();
        $this->assertSame(
            [1, 2, 3, 4],
            $firstStudent->registrasiAkademik()->orderBy('semester_mahasiswa')->pluck('semester_mahasiswa')->all()
        );
        $this->assertSame('DEMO-PAI-S4', DB::table('kelas')->where('id', $firstStudent->class_id)->value('code'));
        $this->assertDatabaseHas('tahun_akademiks', ['code' => '252602', 'is_published' => 1]);
        $this->assertDatabaseHas('krs', ['registrasi_mahasiswa_id' => $firstStudent->registrasiAkademik()->where('taka_id', TahunAkademik::where('code', '252602')->value('id'))->value('id'), 'status' => 'approved']);
        $this->assertDatabaseHas('history_tagihans', ['code' => 'DEMO-BYR-252602-2', 'status' => 'pending']);
    }

    private function migrationFiles(): array
    {
        return [
            '2024_04_26_060533_create_tahun_akademiks_table.php',
            '2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php',
            '2024_03_09_024013_create_mahasiswas_table.php',
            '2024_03_09_024021_create_dosens_table.php',
            '2024_04_25_082451_create_fakultas_table.php',
            '2024_04_25_082531_create_program_studis_table.php',
            '2024_04_26_061235_create_program_kuliahs_table.php',
            '2024_04_27_041303_create_kelas_table.php',
            '2024_04_28_035926_create_gedungs_table.php',
            '2024_04_28_052322_create_ruangs_table.php',
            '2024_04_28_063053_create_kurikulums_table.php',
            '2026_07_17_000001_create_master_mata_kuliahs_table.php',
            '2024_04_30_032644_create_mata_kuliahs_table.php',
            '2026_07_17_000002_add_mid_to_mata_kuliahs_table.php',
            '2025_07_05_112548_add_matakuliah_kelas_id.php',
            '2024_04_30_055648_create_jadwal_kuliahs_table.php',
            '2024_05_10_080721_create_tagihan_kuliahs_table.php',
            '2024_05_10_081438_create_history_tagihans_table.php',
            '2024_04_30_102751_create_absensi_mahasiswas_table.php',
            '2024_06_16_033935_create_hasil_studis_table.php',
            '2025_07_05_091153_create_nilai_mahasiswas_table.php',
            '2026_07_17_000004_link_grades_and_study_results_to_academic_periods.php',
            '2026_07_17_000005_create_registrasi_mahasiswas_table.php',
            '2024_06_13_085258_create_student_tasks_table.php',
            '2024_06_14_102445_create_student_scores_table.php',
            '2026_07_17_000008_create_course_offerings_and_krs_tables.php',
            '2026_07_17_000009_create_weekly_schedules_and_course_meetings.php',
            '2026_07_17_000010_normalize_period_billing_and_financial_krs_policy.php',
            '2026_07_17_000011_create_period_opening_workflow_and_audit.php',
        ];
    }
}
