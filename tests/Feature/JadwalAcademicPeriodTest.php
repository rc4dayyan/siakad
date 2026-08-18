<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\JadwalKuliah;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JadwalAcademicPeriodTest extends TestCase
{
    private TahunAkademik $activePeriod;

    private TahunAkademik $closedPeriod;

    private int $activeClassId;

    private int $oldClassId;

    private int $activeCourseId;

    private int $oldCourseId;

    private int $lecturerId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createTables();
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
        $this->activeCourseId = $this->course('MK-AKTIF', $this->activePeriod);
        $this->oldCourseId = $this->course('MK-LAMA', $this->closedPeriod);
    }

    public function test_schedule_scope_requires_course_and_class_from_selected_period(): void
    {
        $active = $this->schedule('JADWAL-AKTIF', $this->activeCourseId, $this->activeClassId);
        $this->schedule('JADWAL-LAMA', $this->oldCourseId, $this->oldClassId);
        $this->schedule('JADWAL-CAMPUR', $this->activeCourseId, $this->oldClassId);

        $schedules = JadwalKuliah::query()->forAcademicPeriod($this->activePeriod)->get();

        $this->assertCount(1, $schedules);
        $this->assertTrue($schedules->first()->is($active));
    }

    public function test_staff_cannot_create_schedule_with_class_from_another_period(): void
    {
        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.master.jadkul-store'), $this->schedulePayload($this->activeCourseId, $this->oldClassId));

        $response->assertSessionHasErrors('kelas_id');
        $this->assertDatabaseMissing('jadwal_kuliahs', ['kelas_id' => $this->oldClassId, 'makul_id' => $this->activeCourseId]);
    }

    public function test_staff_can_update_schedule_when_database_time_contains_seconds(): void
    {
        $schedule = $this->schedule('JADWAL-EDIT-WAKTU', $this->activeCourseId, $this->activeClassId);
        $payload = $this->schedulePayload($this->activeCourseId, $this->activeClassId);
        $payload['start'] = '08:15:00';
        $payload['ended'] = '10:15:00';

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->patch(route('academic.master.jadkul-update', $schedule->code), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('jadwal_kuliahs', [
            'id' => $schedule->id,
            'start' => '08:15',
            'ended' => '10:15',
        ]);
    }

    public function test_staff_direct_access_to_old_schedule_and_attendance_is_rejected(): void
    {
        $old = $this->schedule('JADWAL-LAMA-AKSES', $this->oldCourseId, $this->oldClassId);
        DB::table('absensi_mahasiswas')->insert([
            'jadkul_code' => $old->code,
            'author_id' => 1,
            'absen_type' => 'H',
            'absen_proof' => 'proof.png',
            'code' => 'ABSEN-LAMA',
            'absen_date' => now()->toDateString(),
            'absen_time' => now()->format('H:i:s'),
        ]);

        $user = $this->academicUser();
        $viewResponse = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($user)
            ->get(route('academic.master.jadkul-absen-view', $old->code));
        $updateResponse = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($user)
            ->patch(route('academic.master.jadkul-absen-update', 'ABSEN-LAMA'), ['absen_desc' => 'Disusupi']);

        $viewResponse->assertRedirect(route('error.notfound'));
        $updateResponse->assertRedirect(route('error.notfound'));
        $this->assertDatabaseMissing('absensi_mahasiswas', ['code' => 'ABSEN-LAMA', 'absen_desc' => 'Disusupi']);
    }

    public function test_closed_period_is_read_only_for_schedule_mutations(): void
    {
        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->closedPeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.master.jadkul-store'), $this->schedulePayload($this->oldCourseId, $this->oldClassId));

        $response->assertSessionHasErrors('academic_period');
    }

    public function test_export_only_contains_selected_period_schedules(): void
    {
        $this->schedule('JADWAL-EXPORT-AKTIF', $this->activeCourseId, $this->activeClassId);
        $this->schedule('JADWAL-EXPORT-LAMA', $this->oldCourseId, $this->oldClassId);

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->get(route('academic.services.convert.export-jadkul'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('JADWAL-EXPORT-AKTIF', $content);
        $this->assertStringNotContainsString('JADWAL-EXPORT-LAMA', $content);
    }

    public function test_lecturer_cannot_open_an_old_or_unowned_schedule(): void
    {
        $old = $this->schedule('JADWAL-DOSEN-LAMA', $this->oldCourseId, $this->oldClassId);
        $lecturer = Dosen::findOrFail($this->lecturerId);

        $response = $this
            ->actingAs($lecturer, 'dosen')
            ->get(route('dosen.akademik.jadwal-view-absen', $old->code));

        $response->assertRedirect(route('error.notfound'));
    }

    public function test_student_cannot_open_schedule_from_another_class_or_period(): void
    {
        $old = $this->schedule('JADWAL-MHS-LAMA', $this->oldCourseId, $this->oldClassId);
        $student = Mahasiswa::create([
            'taka_id' => $this->activePeriod->id,
            'mhs_stat' => 1,
            'mhs_code' => 'MHS-001',
            'mhs_name' => 'Mahasiswa Aktif',
            'class_id' => $this->activeClassId,
            'password' => 'password',
        ]);

        $response = $this
            ->actingAs($student, 'mahasiswa')
            ->get(route('mahasiswa.home-jadkul-absen', $old->code));

        $response->assertRedirect(route('error.notfound'));
    }

    private function createTables(): void
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

        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('pstudi_id');
            $table->string('name');
            $table->string('code')->unique();
        });
        Schema::create('mata_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('pstudi_id');
            $table->unsignedBigInteger('dosen_1');
            $table->unsignedBigInteger('dosen_2')->nullable();
            $table->unsignedBigInteger('dosen_3')->nullable();
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
        Schema::create('ruangs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
        });
        (require database_path('migrations/2024_04_30_055648_create_jadwal_kuliahs_table.php'))->up();
        (require database_path('migrations/2024_04_30_102751_create_absensi_mahasiswas_table.php'))->up();
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
    }

    private function kelas(string $code, TahunAkademik $period): int
    {
        return DB::table('kelas')->insertGetId([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'name' => 'Kelas '.$code,
            'code' => $code,
        ]);
    }

    private function course(string $code, TahunAkademik $period): int
    {
        return DB::table('mata_kuliahs')->insertGetId([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'dosen_1' => $this->lecturerId,
            'name' => 'Mata Kuliah '.$code,
            'code' => $code,
        ]);
    }

    private function schedule(string $code, int $courseId, int $classId): JadwalKuliah
    {
        return JadwalKuliah::create([
            'code' => $code,
            ...$this->schedulePayload($courseId, $classId),
        ]);
    }

    private function schedulePayload(int $courseId, int $classId): array
    {
        $roomId = DB::table('ruangs')->value('id') ?: DB::table('ruangs')->insertGetId(['name' => 'Ruang 1', 'code' => 'R-1']);

        return [
            'makul_id' => $courseId,
            'kelas_id' => $classId,
            'dosen_id' => $this->lecturerId,
            'ruang_id' => $roomId,
            'pert_id' => 1,
            'meth_id' => 0,
            'days_id' => 1,
            'bsks' => 2,
            'date' => now()->toDateString(),
            'start' => '08:00',
            'ended' => '10:00',
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

    private function academicUser(): User
    {
        return User::create([
            'type' => 3,
            'code' => 'ACADEMIC-'.uniqid(),
            'name' => 'Academic User',
            'user' => 'academic-'.uniqid(),
            'phone' => '08'.random_int(1000000000, 9999999999),
            'email' => uniqid().'@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
