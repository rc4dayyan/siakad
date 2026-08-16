<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KelasAcademicPeriodTest extends TestCase
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

    public function test_class_scope_only_returns_the_selected_period(): void
    {
        $activeClass = $this->kelas('AKTIF-A', $this->activePeriod);
        $this->kelas('LAMA-A', $this->closedPeriod);

        $classes = Kelas::query()->forAcademicPeriod($this->activePeriod)->get();

        $this->assertCount(1, $classes);
        $this->assertTrue($classes->first()->is($activeClass));
    }

    public function test_store_always_uses_the_selected_period(): void
    {
        $programStudiId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Agama Islam',
            'code' => 'PAI',
        ]);

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.master.kelas-store'), [
                'name' => 'Kelas A',
                'code' => 'PAI-2026-A',
                'capacity' => 30,
                'pstudi_id' => $programStudiId,
                'taka_id' => $this->closedPeriod->id,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('kelas', [
            'code' => 'PAI-2026-A',
            'taka_id' => $this->activePeriod->id,
        ]);
        $this->assertDatabaseMissing('kelas', [
            'code' => 'PAI-2026-A',
            'taka_id' => $this->closedPeriod->id,
        ]);
    }

    public function test_class_can_be_created_in_a_draft_period_when_no_period_is_active(): void
    {
        $this->activePeriod->update([
            'status' => TahunAkademik::STATUS_CLOSED,
            'is_active' => false,
        ]);
        $draftPeriod = $this->period('2027-GANJIL', TahunAkademik::STATUS_DRAFT);
        $programStudiId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Agama Islam',
            'code' => 'PAI-DRAFT',
        ]);

        $response = $this
            ->actingAs($this->academicUser())
            ->post(route('academic.master.kelas-store'), [
                'name' => 'Kelas Draft A',
                'code' => 'PAI-2027-A',
                'capacity' => 30,
                'pstudi_id' => $programStudiId,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('kelas', [
            'code' => 'PAI-2027-A',
            'taka_id' => $draftPeriod->id,
        ]);
    }

    public function test_class_capacity_accepts_100_and_rejects_values_above_100(): void
    {
        $programStudiId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Agama Islam',
            'code' => 'PAI-KAPASITAS',
        ]);
        $user = $this->academicUser();

        $accepted = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($user)
            ->post(route('academic.master.kelas-store'), [
                'name' => 'Kelas Kapasitas 100',
                'code' => 'KAPASITAS-100',
                'capacity' => 100,
                'pstudi_id' => $programStudiId,
            ]);

        $accepted->assertSessionHasNoErrors();
        $this->assertDatabaseHas('kelas', [
            'code' => 'KAPASITAS-100',
            'capacity' => 100,
        ]);

        $rejected = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($user)
            ->post(route('academic.master.kelas-store'), [
                'name' => 'Kelas Kapasitas 101',
                'code' => 'KAPASITAS-101',
                'capacity' => 101,
                'pstudi_id' => $programStudiId,
            ]);

        $rejected->assertSessionHasErrors('capacity');
        $this->assertDatabaseMissing('kelas', ['code' => 'KAPASITAS-101']);
    }

    public function test_class_from_another_period_cannot_be_updated(): void
    {
        $class = $this->kelas('LAMA-B', $this->closedPeriod);
        $programStudiId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Bahasa Arab',
            'code' => 'PBA',
        ]);

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->patch(route('academic.master.kelas-update', $class->code), [
                'name' => 'Nama Disusupi',
                'code' => $class->code,
                'capacity' => 25,
                'pstudi_id' => $programStudiId,
            ]);

        $response->assertRedirect(route('error.notfound'));
        $this->assertDatabaseHas('kelas', [
            'id' => $class->id,
            'name' => 'Kelas '.$class->code,
            'taka_id' => $this->closedPeriod->id,
        ]);
    }

    public function test_closed_period_is_read_only_for_class_mutations(): void
    {
        $programStudiId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Bahasa Arab',
            'code' => 'PBA-LOCKED',
        ]);

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->closedPeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.master.kelas-store'), [
                'name' => 'Kelas Tidak Boleh Dibuat',
                'code' => 'LOCKED-CLASS',
                'capacity' => 25,
                'pstudi_id' => $programStudiId,
            ]);

        $response->assertSessionHasErrors('academic_period');
        $this->assertDatabaseMissing('kelas', ['code' => 'LOCKED-CLASS']);
    }

    public function test_program_kuliah_must_belong_to_the_selected_period_and_program_studi(): void
    {
        $programStudiId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Guru MI',
            'code' => 'PGMI',
        ]);
        $oldProgramId = DB::table('program_kuliahs')->insertGetId([
            'taka_id' => $this->closedPeriod->id,
            'pstudi_id' => $programStudiId,
            'name' => 'Reguler Lama',
            'code' => 'REG-LAMA',
        ]);

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.master.kelas-store'), [
                'name' => 'Kelas B',
                'code' => 'PGMI-2026-B',
                'capacity' => 30,
                'pstudi_id' => $programStudiId,
                'proku_id' => $oldProgramId,
            ]);

        $response->assertSessionHasErrors('proku_id');
        $this->assertDatabaseMissing('kelas', ['code' => 'PGMI-2026-B']);
    }

    public function test_import_rejects_a_row_for_another_period(): void
    {
        DB::table('program_studis')->insert([
            'name' => 'Pendidikan Agama Islam',
            'code' => 'PAI',
        ]);
        $csv = implode("\n", [
            'Kode Kelas,Nama Kelas,Kapasitas,Kode Tahun Akademik,Kode Program Studi,Kode Program Kuliah,NIDN Wali Dosen',
            'PAI-LAMA-A,Kelas Lama A,30,'.$this->closedPeriod->code.',PAI,,',
        ]);

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->post(route('academic.services.convert.import-kelas'), [
                'import' => UploadedFile::fake()->createWithContent('kelas.csv', $csv),
                'dry_run' => 1,
            ]);

        $response->assertSessionHasErrors('import');
        $this->assertDatabaseMissing('kelas', ['code' => 'PAI-LAMA-A']);
    }

    public function test_export_only_contains_classes_from_the_selected_period(): void
    {
        $this->kelas('AKTIF-EXPORT', $this->activePeriod);
        $this->kelas('LAMA-EXPORT', $this->closedPeriod);

        $response = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->activePeriod->id])
            ->actingAs($this->academicUser())
            ->get(route('academic.services.convert.export-kelas'));

        $response->assertOk();
        $this->assertStringContainsString($this->activePeriod->code, (string) $response->headers->get('content-disposition'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('AKTIF-EXPORT', $content);
        $this->assertStringNotContainsString('LAMA-EXPORT', $content);
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
        (require database_path('migrations/2024_04_27_041303_create_kelas_table.php'))->up();

        Schema::create('program_studis', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
        });
        Schema::create('program_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('pstudi_id');
            $table->string('name');
            $table->string('code')->unique();
        });
        Schema::create('dosens', function (Blueprint $table): void {
            $table->id();
            $table->string('dsn_nidn')->unique();
            $table->string('dsn_name');
        });
        Schema::create('mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id')->nullable();
        });
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

    private function kelas(string $code, TahunAkademik $period): Kelas
    {
        return Kelas::create([
            'name' => 'Kelas '.$code,
            'code' => $code,
            'capacity' => 30,
            'taka_id' => $period->id,
            'pstudi_id' => 1,
        ]);
    }

    private function academicUser(): User
    {
        return User::create([
            'type' => 3,
            'code' => 'ACADEMIC',
            'name' => 'Academic User',
            'user' => 'academic',
            'phone' => '081234567890',
            'email' => 'academic@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
