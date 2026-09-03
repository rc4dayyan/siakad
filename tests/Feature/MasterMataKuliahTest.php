<?php

namespace Tests\Feature;

use App\Models\MasterMataKuliah;
use App\Models\MataKuliah;
use App\Models\User;
use Database\Seeders\MasterMataKuliahSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class MasterMataKuliahTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', true);

        DB::purge('sqlite');

        Schema::create('mata_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->integer('kuri_id');
            $table->integer('taka_id');
            $table->integer('requ_id')->nullable();
            $table->integer('pstudi_id');
            $table->integer('dosen_1');
            $table->integer('dosen_2')->nullable();
            $table->integer('dosen_3')->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('bsks');
            $table->longText('desc');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->default(0);
            $table->string('code')->unique();
            $table->string('name');
            $table->string('user');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('status')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tahun_akademiks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('semester');
            $table->integer('year_start');
            $table->integer('year_end')->nullable();
            $table->string('term')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('status')->default('draft');
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('program_studis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });
        Schema::create('kurikulums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('dosens', function (Blueprint $table) {
            $table->id();
            $table->string('dsn_name');
            $table->timestamps();
        });

        DB::table('tahun_akademiks')->insert([
            'id' => 1, 'name' => '2026/2027 Ganjil', 'code' => '20261', 'semester' => 1,
            'year_start' => 2026, 'year_end' => 2027, 'term' => 'ganjil', 'is_active' => 1,
            'status' => 'active', 'activated_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('program_studis')->insert(['id' => 1, 'name' => 'PAI', 'code' => '86208', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('kurikulums')->insert(['id' => 1, 'name' => 'Kurikulum', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('dosens')->insert(['id' => 1, 'dsn_name' => 'Dosen', 'created_at' => now(), 'updated_at' => now()]);

        (require database_path('migrations/2026_07_17_000001_create_master_mata_kuliahs_table.php'))->up();
        (require database_path('migrations/2026_08_17_000001_add_code_to_master_mata_kuliahs_table.php'))->up();
        (require database_path('migrations/2026_07_17_000002_add_mid_to_mata_kuliahs_table.php'))->up();
    }

    public function test_seeder_imports_all_courses_from_the_source_document(): void
    {
        $this->seed(MasterMataKuliahSeeder::class);

        $this->assertDatabaseCount('master_mata_kuliahs', 186);
        $this->assertSame(62, MasterMataKuliah::where('program_studi', '86208')->count());
        $this->assertSame(62, MasterMataKuliah::where('program_studi', '86233')->count());
        $this->assertSame(62, MasterMataKuliah::where('program_studi', '88204')->count());
        $this->assertDatabaseHas('master_mata_kuliahs', [
            'program_studi' => '88204',
            'code' => 'PGBA01',
            'name' => 'Pengantar Studi Islam',
            'sks' => 2,
            'semester' => 1,
        ]);
    }

    public function test_import_accepts_reference_headers_and_roman_semesters(): void
    {
        MasterMataKuliah::create([
            'program_studi' => '86208',
            'semester' => 1,
            'name' => 'PPKN',
            'sks' => 3,
        ]);

        $file = $this->masterMataKuliahFile([
            ['86208', 'PAI.01', 'PPKN', 2, 'I'],
            ['86208', 'PAI.10', 'Bahasa Indonesia', 2, 'II'],
        ]);

        try {
            $response = $this->actingAs($this->webAdministrator())->post(
                route('web-admin.master.master-matkul-import'),
                ['_form' => 'import-master-matkul', 'import' => $file],
            );
        } finally {
            @unlink($file->getPathname());
        }

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('master_mata_kuliahs', 2);
        $this->assertDatabaseHas('master_mata_kuliahs', [
            'program_studi' => '86208',
            'code' => 'PAI.01',
            'semester' => 1,
            'name' => 'PPKN',
            'sks' => 2,
        ]);
        $this->assertDatabaseHas('master_mata_kuliahs', [
            'code' => 'PAI.10',
            'semester' => 2,
            'name' => 'Bahasa Indonesia',
        ]);
    }

    public function test_import_rejects_duplicate_codes_without_saving_partial_data(): void
    {
        $file = $this->masterMataKuliahFile([
            ['86208', 'PAI.01', 'PPKN', 2, 'I'],
            ['86208', 'PAI.01', 'Bahasa Inggris', 2, 'I'],
        ]);

        try {
            $response = $this->actingAs($this->webAdministrator())->from('/web-admin/master/master-matkul')->post(
                route('web-admin.master.master-matkul-import'),
                ['_form' => 'import-master-matkul', 'import' => $file],
            );
        } finally {
            @unlink($file->getPathname());
        }

        $response->assertRedirect('/web-admin/master/master-matkul');
        $response->assertSessionHasErrors('import');
        $this->assertDatabaseCount('master_mata_kuliahs', 0);
    }

    public function test_export_uses_reference_column_order_and_roman_semester(): void
    {
        MasterMataKuliah::create([
            'program_studi' => '86208',
            'code' => 'PAI.19',
            'semester' => 3,
            'name' => 'Bahasa Inggris 3',
            'sks' => 2,
        ]);

        $response = $this->actingAs($this->webAdministrator())->get(
            route('web-admin.master.master-matkul-export'),
        );

        $response->assertOk();
        $this->assertStringContainsString('master-mata-kuliah-', (string) $response->headers->get('content-disposition'));

        $path = tempnam(sys_get_temp_dir(), 'master-matkul-export-').'.xlsx';

        try {
            file_put_contents($path, $response->streamedContent());
            $rows = IOFactory::load($path)->getActiveSheet()->rangeToArray('A1:E2');
        } finally {
            @unlink($path);
        }

        $this->assertSame(['Program Studi', 'Kode', 'Nama Mata Kuliah', 'SKS', 'Semester'], $rows[0]);
        $this->assertSame(['86208', 'PAI.19', 'Bahasa Inggris 3', '2', 'III'], $rows[1]);
    }

    public function test_mata_kuliah_and_master_mata_kuliah_have_bidirectional_relations(): void
    {
        $master = MasterMataKuliah::create([
            'program_studi' => '86208',
            'semester' => 1,
            'name' => 'Pend. Kewarganegaraan',
            'sks' => 2,
        ]);

        $mataKuliah = MataKuliah::create([
            'mid' => $master->id,
            'kuri_id' => 1,
            'taka_id' => 1,
            'pstudi_id' => 1,
            'dosen_1' => 1,
            'name' => 'Pend. Kewarganegaraan',
            'code' => 'PKN-01',
            'bsks' => '2',
            'desc' => 'Mata kuliah Pendidikan Kewarganegaraan.',
        ]);

        $this->assertTrue($mataKuliah->masterMataKuliah->is($master));
        $this->assertTrue($master->mataKuliahs->first()->is($mataKuliah));
    }

    public function test_mid_rejects_an_unknown_master_mata_kuliah(): void
    {
        $this->expectException(QueryException::class);

        MataKuliah::create([
            'mid' => 999999,
            'kuri_id' => 1,
            'taka_id' => 1,
            'pstudi_id' => 1,
            'dosen_1' => 1,
            'name' => 'Mata Kuliah Tidak Valid',
            'code' => 'INVALID-MID',
            'bsks' => '2',
            'desc' => 'Data untuk pengujian foreign key.',
        ]);
    }

    public function test_store_uses_the_selected_master_id_and_name(): void
    {
        $master = MasterMataKuliah::create([
            'program_studi' => '86208',
            'semester' => 1,
            'name' => 'Pend. Kewarganegaraan',
            'sks' => 2,
        ]);

        $response = $this->actingAs($this->webAdministrator())->post(
            route('web-admin.master.matkul-store'),
            $this->mataKuliahPayload($master, ['name' => 'Nama dari browser tidak boleh digunakan']),
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mata_kuliahs', [
            'mid' => $master->id,
            'name' => $master->name,
            'code' => 'TEST-MATKUL',
        ]);
        $this->assertDatabaseMissing('mata_kuliahs', [
            'name' => 'Nama dari browser tidak boleh digunakan',
        ]);
    }

    public function test_update_changes_mid_and_name_to_the_selected_master(): void
    {
        $firstMaster = MasterMataKuliah::create([
            'program_studi' => '86208',
            'semester' => 1,
            'name' => 'Pend. Kewarganegaraan',
            'sks' => 2,
        ]);
        $secondMaster = MasterMataKuliah::create([
            'program_studi' => '86208',
            'semester' => 2,
            'name' => 'Bahasa Indonesia',
            'sks' => 2,
        ]);
        $mataKuliah = MataKuliah::create($this->mataKuliahPayload($firstMaster));

        $response = $this->actingAs($this->webAdministrator())->patch(
            route('web-admin.master.matkul-update', $mataKuliah->code),
            $this->mataKuliahPayload($secondMaster),
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mata_kuliahs', [
            'id' => $mataKuliah->id,
            'mid' => $secondMaster->id,
            'name' => $secondMaster->name,
        ]);
    }

    public function test_store_rejects_an_unknown_master_id(): void
    {
        $master = new MasterMataKuliah(['id' => 999999]);

        $response = $this->actingAs($this->webAdministrator())->post(
            route('web-admin.master.matkul-store'),
            $this->mataKuliahPayload($master),
        );

        $response->assertSessionHasErrors('mid');
        $this->assertDatabaseCount('mata_kuliahs', 0);
    }

    private function webAdministrator(): User
    {
        return User::create([
            'type' => 0,
            'code' => 'WEBADMIN',
            'name' => 'Web Administrator',
            'user' => 'webadmin',
            'phone' => '081234567890',
            'email' => 'webadmin@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }

    private function masterMataKuliahFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Program Studi', 'Kode', 'Nama Mata Kuliah', 'SKS', 'Semester'],
            ...$rows,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'master-matkul-import-').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return new UploadedFile(
            $path,
            'master-mata-kuliah-All.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function mataKuliahPayload(MasterMataKuliah $master, array $overrides = []): array
    {
        return array_merge([
            'mid' => $master->id,
            'kuri_id' => 1,
            'taka_id' => 1,
            'pstudi_id' => 1,
            'dosen_1' => 1,
            'name' => $master->name,
            'code' => 'TEST-MATKUL',
            'bsks' => '2',
            'desc' => 'Mata kuliah untuk pengujian.',
        ], $overrides);
    }
}
