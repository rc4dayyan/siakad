<?php

namespace Tests\Feature;

use App\Models\MasterMataKuliah;
use App\Models\MataKuliah;
use App\Models\User;
use Database\Seeders\MasterMataKuliahSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        (require database_path('migrations/2026_07_17_000001_create_master_mata_kuliahs_table.php'))->up();
        (require database_path('migrations/2026_07_17_000002_add_mid_to_mata_kuliahs_table.php'))->up();
    }

    public function test_seeder_imports_all_courses_from_the_source_document(): void
    {
        $this->seed(MasterMataKuliahSeeder::class);

        $this->assertDatabaseCount('master_mata_kuliahs', 246);
        $this->assertSame(62, MasterMataKuliah::where('program_studi', 'PAI')->count());
        $this->assertSame(62, MasterMataKuliah::where('program_studi', 'RA')->count());
        $this->assertSame(62, MasterMataKuliah::where('program_studi', 'MI')->count());
        $this->assertSame(60, MasterMataKuliah::where('program_studi', 'PBA')->count());
    }

    public function test_mata_kuliah_and_master_mata_kuliah_have_bidirectional_relations(): void
    {
        $master = MasterMataKuliah::create([
            'program_studi' => 'PAI',
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
            'program_studi' => 'PAI',
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
            'program_studi' => 'PAI',
            'semester' => 1,
            'name' => 'Pend. Kewarganegaraan',
            'sks' => 2,
        ]);
        $secondMaster = MasterMataKuliah::create([
            'program_studi' => 'PAI',
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
