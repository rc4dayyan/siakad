<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RuangBulkCreateTest extends TestCase
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
            $table->string('password');
            $table->tinyInteger('status')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('gedungs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('ruangs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('gedu_id');
            $table->integer('type');
            $table->integer('floor');
            $table->unsignedSmallInteger('kapasitas')->default(40);
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    public function test_web_administrator_can_create_multiple_numbered_rooms(): void
    {
        $buildingId = DB::table('gedungs')->insertGetId([
            'name' => 'Gedung Utama',
            'code' => 'GU',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->webAdministrator())->post(route('web-admin.inventory.ruang-store'), [
            '_form' => 'create-ruang',
            'gedu_id' => $buildingId,
            'type' => 0,
            'floor' => 1,
            'kapasitas' => 40,
            'name' => 'Ruang Kelas',
            'code' => 'r',
            'jumlah' => 3,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('web-admin.inventory.ruang-index'));

        $this->assertDatabaseCount('ruangs', 3);
        $this->assertDatabaseHas('ruangs', ['name' => 'Ruang Kelas 01', 'code' => 'R01']);
        $this->assertDatabaseHas('ruangs', ['name' => 'Ruang Kelas 03', 'code' => 'R03']);
    }

    public function test_single_create_keeps_the_original_name_and_code(): void
    {
        $buildingId = DB::table('gedungs')->insertGetId([
            'name' => 'Gedung Utama',
            'code' => 'GU',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->webAdministrator())->post(route('web-admin.inventory.ruang-store'), [
            '_form' => 'create-ruang',
            'gedu_id' => $buildingId,
            'type' => 1,
            'floor' => 2,
            'kapasitas' => 24,
            'name' => 'Laboratorium Komputer',
            'code' => 'lab',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ruangs', [
            'name' => 'Laboratorium Komputer',
            'code' => 'LAB',
        ]);
    }

    public function test_bulk_create_rejects_a_code_collision_without_partial_writes(): void
    {
        $buildingId = DB::table('gedungs')->insertGetId([
            'name' => 'Gedung Utama',
            'code' => 'GU',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('ruangs')->insert([
            'gedu_id' => $buildingId,
            'type' => 0,
            'floor' => 1,
            'kapasitas' => 40,
            'name' => 'Ruang Lama',
            'code' => 'R02',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->webAdministrator())->post(route('web-admin.inventory.ruang-store'), [
            '_form' => 'create-ruang',
            'gedu_id' => $buildingId,
            'type' => 0,
            'floor' => 1,
            'kapasitas' => 40,
            'name' => 'Ruang Kelas',
            'code' => 'R',
            'jumlah' => 3,
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseCount('ruangs', 1);
        $this->assertDatabaseMissing('ruangs', ['code' => 'R01']);
        $this->assertDatabaseMissing('ruangs', ['code' => 'R03']);
    }

    public function test_bulk_create_rejects_a_base_code_that_cannot_fit_the_sequence(): void
    {
        $buildingId = DB::table('gedungs')->insertGetId([
            'name' => 'Gedung Utama',
            'code' => 'GU',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->webAdministrator())->post(route('web-admin.inventory.ruang-store'), [
            '_form' => 'create-ruang',
            'gedu_id' => $buildingId,
            'type' => 0,
            'floor' => 1,
            'kapasitas' => 40,
            'name' => 'Ruang Kelas',
            'code' => 'ROOM',
            'jumlah' => 10,
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseCount('ruangs', 0);
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
}
