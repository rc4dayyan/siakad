<?php

namespace Tests\Feature;

use App\Http\Controllers\Mahasiswa\Pages\StudentNilaiController;
use App\Models\Dosen;
use App\Models\HasilStudi;
use App\Models\Mahasiswa;
use App\Models\NilaiMahasiswa;
use App\Models\TahunAkademik;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\StudentAcademicContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
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

    public function test_student_active_view_is_period_filtered_but_transcript_combines_periods(): void
    {
        $ungradedCourseId = $this->course('MK-BELUM-DINILAI', $this->activePeriod, $this->activeClassId);
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

        $this->assertCount(2, $activeView->getData()['nilai']);
        $this->assertTrue($activeView->getData()['nilai']->contains(fn (array $row) => $row['kode_mata_kuliah'] === 'MK-BELUM-DINILAI' && $row['nilai'] === null
        ));
        $this->assertCount(3, $transcriptView->getData()['nilai']);
        $this->assertDatabaseMissing('nilai_mahasiswas', [
            'mata_kuliah_id' => $ungradedCourseId,
            'mahasiswa_id' => $this->student->id,
        ]);
    }

    public function test_student_can_download_own_transcript_as_landscape_pdf(): void
    {
        DB::table('program_studis')->insert([
            'id' => 1,
            'name' => 'Pendidikan Agama Islam',
            'level' => 'S-1',
            'head_id' => $this->lecturerId,
        ]);
        DB::table('registrasi_mahasiswas')->insert([
            [
                'mahasiswa_id' => $this->student->id,
                'taka_id' => $this->closedPeriod->id,
                'semester_mahasiswa' => 1,
                'kelas_id' => $this->oldClassId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mahasiswa_id' => $this->student->id,
                'taka_id' => $this->activePeriod->id,
                'semester_mahasiswa' => 2,
                'kelas_id' => $this->activeClassId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        NilaiMahasiswa::create([
            'mahasiswa_id' => $this->student->id,
            'taka_id' => $this->activePeriod->id,
            'mata_kuliah_id' => $this->activeCourseId,
            'kelas_id' => $this->activeClassId,
            'dosen_id' => $this->lecturerId,
            'nilai' => 'A',
        ]);
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data): bool {
                $this->assertSame('base.cetak.cetak-transkrip-mahasiswa', $view);
                $this->assertSame('Pendidikan Agama Islam', $data['program']->name);
                $this->assertSame([1, 2], $data['semesters']->pluck('number')->all());
                $this->assertSame(4, $data['totalCredits']);
                $this->assertEqualsWithDelta(3.5, $data['ipk'], 0.001);

                return true;
            })
            ->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')
            ->once()
            ->with('Transkrip-Nilai-Sementara-MHS-001.pdf')
            ->andReturn(response('pdf'));

        $this->actingAs($this->student, 'mahasiswa');
        $response = app(StudentNilaiController::class)->printTranscript(
            app(AcademicPeriodContext::class),
            app(StudentAcademicContext::class)
        );

        $this->assertSame('pdf', $response->getContent());
    }

    public function test_student_grade_page_uses_only_approved_krs_courses_when_available(): void
    {
        $this->createKrsTables();
        $registrationId = DB::table('registrasi_mahasiswas')->insertGetId([
            'mahasiswa_id' => $this->student->id,
            'taka_id' => $this->activePeriod->id,
            'semester_mahasiswa' => 1,
            'kelas_id' => $this->activeClassId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $selectedMasterId = DB::table('master_mata_kuliahs')->insertGetId([
            'name' => 'Mata Kuliah Dipilih',
        ]);
        $unselectedMasterId = DB::table('master_mata_kuliahs')->insertGetId([
            'name' => 'Mata Kuliah Tidak Dipilih',
        ]);
        $selectedOfferingId = $this->offering($selectedMasterId, 'PENAWARAN-DIPILIH');
        $this->offering($unselectedMasterId, 'PENAWARAN-TIDAK-DIPILIH');
        $krsId = DB::table('krs')->insertGetId([
            'registrasi_mahasiswa_id' => $registrationId,
            'status' => 'approved',
        ]);
        DB::table('krs_items')->insert([
            'krs_id' => $krsId,
            'penawaran_mata_kuliah_id' => $selectedOfferingId,
            'sks' => 2,
        ]);

        $this->actingAs($this->student, 'mahasiswa');
        $view = app(StudentNilaiController::class)->index(
            Request::create('/nilai-kuliah'),
            app(AcademicPeriodContext::class),
            app(StudentAcademicContext::class)
        );

        $this->assertCount(1, $view->getData()['nilai']);
        $this->assertSame('Mata Kuliah Dipilih', $view->getData()['nilai']->first()['mata_kuliah']);
        $this->assertNull($view->getData()['nilai']->first()['nilai']);
        $this->assertSame('KRS Disetujui', $view->getData()['nilai']->first()['status']);
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
        Schema::create('program_studis', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('level')->nullable();
            $table->unsignedBigInteger('head_id')->nullable();
        });
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
            $table->string('mhs_nim')->unique();
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
            $table->unsignedTinyInteger('bsks')->nullable();
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

    private function createKrsTables(): void
    {
        Schema::create('master_mata_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('penawaran_mata_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_mata_kuliah_id');
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('dosen_utama_id');
            $table->unsignedBigInteger('legacy_mata_kuliah_id')->nullable();
            $table->string('code');
        });
        Schema::create('krs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('registrasi_mahasiswa_id');
            $table->string('status');
        });
        Schema::create('krs_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('krs_id');
            $table->unsignedBigInteger('penawaran_mata_kuliah_id');
            $table->unsignedTinyInteger('sks');
        });
    }

    private function offering(int $masterId, string $code): int
    {
        return DB::table('penawaran_mata_kuliahs')->insertGetId([
            'master_mata_kuliah_id' => $masterId,
            'taka_id' => $this->activePeriod->id,
            'kelas_id' => $this->activeClassId,
            'dosen_utama_id' => $this->lecturerId,
            'code' => $code,
        ]);
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
            'bsks' => 2,
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
            'mhs_nim' => $code,
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
}
