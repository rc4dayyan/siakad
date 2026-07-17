<?php

namespace Tests\Feature;

use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MataKuliahAcademicPeriodTest extends TestCase
{
    private TahunAkademik $activePeriod;

    private TahunAkademik $closedPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createTables();
        $this->activePeriod = $this->period('2026-GANJIL', TahunAkademik::STATUS_ACTIVE, true);
        $this->closedPeriod = $this->period('2025-GENAP', TahunAkademik::STATUS_CLOSED);
    }

    public function test_scope_only_returns_courses_from_selected_period(): void
    {
        $activeCourse = $this->mataKuliah('AKTIF-01', $this->activePeriod);
        $this->mataKuliah('LAMA-01', $this->closedPeriod);

        $courses = MataKuliah::query()->forAcademicPeriod($this->activePeriod)->get();

        $this->assertCount(1, $courses);
        $this->assertTrue($courses->first()->is($activeCourse));
    }

    public function test_store_ignores_submitted_period_and_uses_session_period(): void
    {
        $references = $this->references();

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.master.matkul-store'), [
                ...$references,
                'code' => 'PAI-101',
                'bsks' => 3,
                'desc' => 'Pengantar PAI',
                'taka_id' => $this->closedPeriod->id,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mata_kuliahs', [
            'code' => 'PAI-101',
            'taka_id' => $this->activePeriod->id,
            'name' => 'Pengantar Studi Islam',
        ]);
        $this->assertDatabaseMissing('mata_kuliahs', [
            'code' => 'PAI-101',
            'taka_id' => $this->closedPeriod->id,
        ]);
    }

    public function test_course_from_another_period_cannot_be_updated(): void
    {
        $course = $this->mataKuliah('LAMA-02', $this->closedPeriod);
        $references = $this->references();

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->patch(route('academic.master.matkul-update', $course->code), [
                ...$references,
                'code' => $course->code,
                'bsks' => 4,
                'desc' => 'Tidak boleh berubah',
            ]);

        $response->assertRedirect(route('error.notfound'));
        $this->assertDatabaseHas('mata_kuliahs', [
            'id' => $course->id,
            'desc' => 'Mata kuliah '.$course->code,
            'taka_id' => $this->closedPeriod->id,
        ]);
    }

    public function test_prerequisite_must_belong_to_selected_period(): void
    {
        $oldPrerequisite = $this->mataKuliah('LAMA-SYARAT', $this->closedPeriod);
        $references = $this->references();

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.master.matkul-store'), [
                ...$references,
                'code' => 'PAI-102',
                'bsks' => 3,
                'desc' => 'Mata kuliah baru',
                'requ_id' => $oldPrerequisite->id,
            ]);

        $response->assertSessionHasErrors('requ_id');
        $this->assertDatabaseMissing('mata_kuliahs', ['code' => 'PAI-102']);
    }

    public function test_closed_period_is_read_only_for_course_mutations(): void
    {
        $references = $this->references();

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->closedPeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.master.matkul-store'), [
                ...$references,
                'code' => 'LOCKED-COURSE',
                'bsks' => 3,
                'desc' => 'Tidak boleh dibuat',
            ]);

        $response->assertSessionHasErrors('academic_period');
        $this->assertDatabaseMissing('mata_kuliahs', ['code' => 'LOCKED-COURSE']);
    }

    public function test_import_uses_selected_period_and_master_course_data(): void
    {
        $references = $this->references();
        $this->activePeriod->update(['status' => TahunAkademik::STATUS_DRAFT, 'is_active' => false]);
        $csv = "Nama,Kode,Kode Tahun Akademik\nPengantar Studi Islam,PAI-IMPORT,{$this->activePeriod->code}";

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.services.convert.import-matkul'), [
                'import' => UploadedFile::fake()->createWithContent('mata-kuliah.csv', $csv),
                'pstudi_id' => $references['pstudi_id'],
                'kuri_id' => $references['kuri_id'],
                'dosen_1' => $references['dosen_1'],
                'taka_id' => $this->closedPeriod->id,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mata_kuliahs', [
            'code' => 'PAI-IMPORT',
            'name' => 'Pengantar Studi Islam',
            'bsks' => '3',
            'taka_id' => $this->activePeriod->id,
            'mid' => $references['mid'],
        ]);
    }

    public function test_export_only_contains_courses_from_selected_period(): void
    {
        $this->mataKuliah('AKTIF-EXPORT', $this->activePeriod);
        $this->mataKuliah('LAMA-EXPORT', $this->closedPeriod);

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->get(route('academic.services.convert.export-matkul'));

        $response->assertOk();
        $this->assertStringContainsString($this->activePeriod->code, (string) $response->headers->get('content-disposition'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('AKTIF-EXPORT', $content);
        $this->assertStringNotContainsString('LAMA-EXPORT', $content);
    }

    public function test_active_period_import_requires_dry_run_and_does_not_write(): void
    {
        $references = $this->references();
        $csv = "Nama,Kode,Kode Tahun Akademik\nPengantar Studi Islam,PAI-DRY,{$this->activePeriod->code}";
        $payload = [
            'import' => UploadedFile::fake()->createWithContent('mata-kuliah.csv', $csv),
            'pstudi_id' => $references['pstudi_id'],
            'kuri_id' => $references['kuri_id'],
            'dosen_1' => $references['dosen_1'],
        ];

        $this->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.services.convert.import-matkul'), $payload)
            ->assertSessionHasErrors('academic_period');

        $payload['import'] = UploadedFile::fake()->createWithContent('mata-kuliah.csv', $csv);
        $payload['dry_run'] = 1;
        $this->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.services.convert.import-matkul'), $payload)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('mata_kuliahs', ['code' => 'PAI-DRY']);
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
        (require database_path('migrations/2026_07_17_000001_create_master_mata_kuliahs_table.php'))->up();
        (require database_path('migrations/2024_04_30_032644_create_mata_kuliahs_table.php'))->up();
        (require database_path('migrations/2026_07_17_000002_add_mid_to_mata_kuliahs_table.php'))->up();

        Schema::create('program_studis', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
        });
        Schema::create('kurikulums', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
        });
        Schema::create('dosens', function (Blueprint $table): void {
            $table->id();
            $table->string('dsn_nidn')->unique();
            $table->string('dsn_name');
        });
    }

    private function references(): array
    {
        $programStudiId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Agama Islam',
            'code' => 'PAI',
        ]);
        $kurikulumId = DB::table('kurikulums')->insertGetId([
            'name' => 'Kurikulum 2026',
            'code' => 'K-2026',
        ]);
        $dosenId = DB::table('dosens')->insertGetId([
            'dsn_nidn' => '0123456789',
            'dsn_name' => 'Dosen PAI',
        ]);
        $masterId = DB::table('master_mata_kuliahs')->insertGetId([
            'program_studi' => 'PAI',
            'semester' => 1,
            'name' => 'Pengantar Studi Islam',
            'sks' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'mid' => $masterId,
            'pstudi_id' => $programStudiId,
            'kuri_id' => $kurikulumId,
            'dosen_1' => $dosenId,
        ];
    }

    private function mataKuliah(string $code, TahunAkademik $period): MataKuliah
    {
        return MataKuliah::create([
            'name' => 'Mata kuliah '.$code,
            'code' => $code,
            'bsks' => 3,
            'desc' => 'Mata kuliah '.$code,
            'kuri_id' => 1,
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'dosen_1' => 1,
        ]);
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
            'starts_at' => '2026-08-01',
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
