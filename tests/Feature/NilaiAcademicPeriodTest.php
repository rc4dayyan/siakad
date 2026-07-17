<?php

namespace Tests\Feature;

use App\Http\Controllers\Mahasiswa\Pages\StudentNilaiController;
use App\Models\Dosen;
use App\Models\HasilStudi;
use App\Models\Mahasiswa;
use App\Models\NilaiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\StudentAcademicContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NilaiAcademicPeriodTest extends TestCase
{
    private TahunAkademik $activePeriod;

    private TahunAkademik $closedPeriod;

    private int $activeClassId;

    private int $oldClassId;

    private int $activeCourseId;

    private int $oldCourseId;

    private int $lecturerId;

    private Mahasiswa $student;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createBaseTables();
        $this->activePeriod = $this->period('2026-GANJIL', TahunAkademik::STATUS_ACTIVE, true);
        $this->closedPeriod = $this->period('2025-GENAP', TahunAkademik::STATUS_CLOSED);
        $this->lecturerId = DB::table('dosens')->insertGetId([
            'dsn_stat' => 1,
            'dsn_nidn' => '0123456789',
            'dsn_name' => 'Dosen Aktif',
            'password' => 'password',
        ]);
        $this->activeClassId = $this->kelas('KELAS-AKTIF', $this->activePeriod);
        $this->oldClassId = $this->kelas('KELAS-LAMA', $this->closedPeriod);
        $this->activeCourseId = $this->course('MK-AKTIF', $this->activePeriod, $this->activeClassId);
        $this->oldCourseId = $this->course('MK-LAMA', $this->closedPeriod, $this->oldClassId);
        $this->student = $this->student('MHS-001', $this->activeClassId);

        DB::table('nilai_mahasiswas')->insert([
            'mahasiswa_id' => $this->student->id,
            'mata_kuliah_id' => $this->oldCourseId,
            'kelas_id' => $this->oldClassId,
            'dosen_id' => $this->lecturerId,
            'nilai' => 'B',
        ]);

        (require database_path('migrations/2026_07_17_000004_link_grades_and_study_results_to_academic_periods.php'))->up();
    }

    public function test_migration_backfills_period_from_course_offering(): void
    {
        $this->assertDatabaseHas('nilai_mahasiswas', [
            'mata_kuliah_id' => $this->oldCourseId,
            'taka_id' => $this->closedPeriod->id,
        ]);
    }

    public function test_staff_grade_input_sets_selected_period_and_rejects_cross_class_student(): void
    {
        $otherStudent = $this->student('MHS-002', $this->oldClassId);
        $user = $this->webAdmin();

        $invalid = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($user)
            ->post(route('web-admin.master.matkul-storenilai'), [
                'mata_kuliah_id' => $this->activeCourseId,
                'nilai' => [['mahasiswa_id' => $otherStudent->id, 'nilai' => 'A']],
            ]);
        $invalid->assertSessionHasErrors('nilai.0.mahasiswa_id');

        $valid = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($user)
            ->post(route('web-admin.master.matkul-storenilai'), [
                'mata_kuliah_id' => $this->activeCourseId,
                'nilai' => [['mahasiswa_id' => $this->student->id, 'nilai' => 'A']],
            ]);

        $valid->assertSessionHasNoErrors();
        $this->assertDatabaseHas('nilai_mahasiswas', [
            'mahasiswa_id' => $this->student->id,
            'mata_kuliah_id' => $this->activeCourseId,
            'taka_id' => $this->activePeriod->id,
            'nilai' => 'A',
        ]);
    }

    public function test_grade_cannot_be_changed_in_closed_period(): void
    {
        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->closedPeriod->id])
            ->actingAs($this->webAdmin())
            ->post(route('web-admin.master.matkul-storenilai'), [
                'mata_kuliah_id' => $this->oldCourseId,
                'nilai' => [['mahasiswa_id' => $this->student->id, 'nilai' => 'A']],
            ]);

        $response->assertSessionHasErrors('academic_period');
        $this->assertDatabaseHas('nilai_mahasiswas', [
            'mata_kuliah_id' => $this->oldCourseId,
            'nilai' => 'B',
        ]);
    }

    public function test_student_active_view_is_period_filtered_but_transcript_combines_periods(): void
    {
        NilaiMahasiswa::create([
            'mahasiswa_id' => $this->student->id,
            'taka_id' => $this->activePeriod->id,
            'mata_kuliah_id' => $this->activeCourseId,
            'kelas_id' => $this->activeClassId,
            'dosen_id' => $this->lecturerId,
            'nilai' => 'A',
        ]);
        $this->actingAs($this->student, 'mahasiswa');
        $controller = app(StudentNilaiController::class);
        $context = app(AcademicPeriodContext::class);
        $studentContext = app(StudentAcademicContext::class);

        $activeView = $controller->index(Request::create('/nilai-kuliah'), $context, $studentContext);
        $transcriptView = $controller->index(
            Request::create('/nilai-kuliah', 'GET', ['transkrip' => 1]),
            $context,
            $studentContext
        );

        $this->assertCount(1, $activeView->getData()['nilai']);
        $this->assertCount(2, $transcriptView->getData()['nilai']);
    }

    public function test_lecturer_can_score_active_task_once_and_old_task_is_inaccessible(): void
    {
        $activeSchedule = $this->schedule('JADWAL-AKTIF', $this->activeCourseId, $this->activeClassId);
        $oldSchedule = $this->schedule('JADWAL-LAMA', $this->oldCourseId, $this->oldClassId);
        $activeTask = DB::table('student_tasks')->insertGetId($this->taskPayload('TASK-AKTIF', $activeSchedule));
        $oldTask = DB::table('student_tasks')->insertGetId($this->taskPayload('TASK-LAMA', $oldSchedule));
        DB::table('student_scores')->insert([
            ['stask_id' => $activeTask, 'student_id' => $this->student->id, 'score' => null, 'desc' => 'Jawaban', 'code' => 100001],
            ['stask_id' => $oldTask, 'student_id' => $this->student->id, 'score' => null, 'desc' => 'Jawaban lama', 'code' => 100002],
        ]);
        $lecturer = Dosen::findOrFail($this->lecturerId);

        $activeResponse = $this
            ->actingAs($lecturer, 'dosen')
            ->patch(route('dosen.akademik.stask-update-score', 100001), ['score' => 8]);
        $activeResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hasil_studis', [
            'student_id' => $this->student->id,
            'taka_id' => $this->activePeriod->id,
            'score_tugas' => 8,
            'max_tugas' => 1,
        ]);

        $secondResponse = $this
            ->actingAs($lecturer, 'dosen')
            ->patch(route('dosen.akademik.stask-update-score', 100001), ['score' => 9]);
        $secondResponse->assertSessionHasErrors('score');

        $oldResponse = $this
            ->actingAs($lecturer, 'dosen')
            ->patch(route('dosen.akademik.stask-update-score', 100002), ['score' => 8]);
        $oldResponse->assertRedirect(route('error.notfound'));
        $this->assertDatabaseHas('hasil_studis', ['student_id' => $this->student->id, 'score_tugas' => 8, 'max_tugas' => 1]);
    }

    public function test_study_result_is_unique_per_student_and_period(): void
    {
        HasilStudi::create([
            'student_id' => $this->student->id,
            'taka_id' => $this->activePeriod->id,
            'smt_id' => 1,
            'code' => 'KHS-1',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        HasilStudi::create([
            'student_id' => $this->student->id,
            'taka_id' => $this->activePeriod->id,
            'smt_id' => 2,
            'code' => 'KHS-2',
        ]);
    }

    private function createBaseTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('type');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('user');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('status')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        Schema::create('web_settings', fn (Blueprint $table) => $table->id());
        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('pstudi_id');
            $table->string('name');
            $table->string('code')->unique();
        });
        Schema::create('dosens', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('dsn_stat');
            $table->string('dsn_nidn')->unique();
            $table->string('dsn_name');
            $table->string('password');
            $table->rememberToken();
        });
        Schema::create('mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id')->default(0);
            $table->unsignedBigInteger('years_id')->default(0);
            $table->tinyInteger('mhs_stat');
            $table->string('mhs_code')->unique();
            $table->string('mhs_name');
            $table->unsignedBigInteger('class_id');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        (require database_path('migrations/2026_07_17_000005_create_registrasi_mahasiswas_table.php'))->up();
        Schema::create('mata_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('pstudi_id');
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->unsignedBigInteger('dosen_1');
            $table->unsignedBigInteger('dosen_2')->nullable();
            $table->unsignedBigInteger('dosen_3')->nullable();
            $table->string('name');
            $table->string('code')->unique();
        });
        (require database_path('migrations/2025_07_05_091153_create_nilai_mahasiswas_table.php'))->up();
        (require database_path('migrations/2024_06_16_033935_create_hasil_studis_table.php'))->up();
        Schema::create('jadwal_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('makul_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('dosen_id');
            $table->string('code')->unique();
        });
        (require database_path('migrations/2024_06_13_085258_create_student_tasks_table.php'))->up();
        (require database_path('migrations/2024_06_14_102445_create_student_scores_table.php'))->up();
    }

    private function kelas(string $code, TahunAkademik $period): int
    {
        return DB::table('kelas')->insertGetId(['taka_id' => $period->id, 'pstudi_id' => 1, 'name' => $code, 'code' => $code]);
    }

    private function course(string $code, TahunAkademik $period, int $classId): int
    {
        return DB::table('mata_kuliahs')->insertGetId([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'kelas_id' => $classId,
            'dosen_1' => $this->lecturerId,
            'name' => $code,
            'code' => $code,
        ]);
    }

    private function student(string $code, int $classId): Mahasiswa
    {
        return Mahasiswa::create([
            'taka_id' => DB::table('kelas')->where('id', $classId)->value('taka_id'),
            'mhs_stat' => 1,
            'mhs_code' => $code,
            'mhs_name' => $code,
            'class_id' => $classId,
            'password' => 'password',
        ]);
    }

    private function schedule(string $code, int $courseId, int $classId): int
    {
        return DB::table('jadwal_kuliahs')->insertGetId([
            'makul_id' => $courseId,
            'kelas_id' => $classId,
            'dosen_id' => $this->lecturerId,
            'code' => $code,
        ]);
    }

    private function taskPayload(string $code, int $scheduleId): array
    {
        return [
            'dosen_id' => $this->lecturerId,
            'jadkul_id' => $scheduleId,
            'code' => $code,
            'title' => $code,
            'detail_task' => 'Detail',
            'exp_date' => now()->toDateString(),
            'exp_time' => '23:59:00',
        ];
    }

    private function period(string $code, string $status, bool $active = false): TahunAkademik
    {
        return TahunAkademik::create([
            'name' => 'Periode '.$code,
            'code' => $code,
            'semester' => str_contains($code, 'GENAP') ? 2 : 1,
            'year_start' => (int) substr($code, 0, 4),
            'year_end' => (int) substr($code, 0, 4) + 1,
            'term' => str_contains($code, 'GENAP') ? TahunAkademik::TERM_GENAP : TahunAkademik::TERM_GANJIL,
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
            'status' => $status,
            'is_active' => $active,
        ]);
    }

    private function webAdmin(): User
    {
        return User::create([
            'type' => 0,
            'code' => 'WEB-ADMIN-'.uniqid(),
            'name' => 'Web Administrator',
            'user' => 'web-admin-'.uniqid(),
            'phone' => '08'.random_int(1000000000, 9999999999),
            'email' => uniqid().'@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
