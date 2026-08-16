<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DosenOpenFeederImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', true);
        DB::purge('sqlite');
        Storage::fake('local');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('type')->default(0);
            $table->string('code')->unique();
            $table->string('name');
            $table->string('user')->unique();
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('status')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('dosens', function (Blueprint $table): void {
            $table->id();
            $table->integer('dsn_stat')->default(0);
            $table->string('dsn_nidn')->unique();
            $table->string('dsn_name');
            $table->string('dsn_code');
            $table->string('dsn_image')->default('default/default-profile.jpg');
            $table->string('dsn_birthplace')->nullable();
            $table->date('dsn_birthdate')->nullable();
            $table->string('dsn_gend')->nullable();
            $table->string('dsn_user')->unique();
            $table->string('password');
            $table->string('dsn_mail')->unique();
            $table->string('dsn_phone')->unique();
            $table->string('verify_token')->nullable();
            $table->timestamp('token_created_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_imports_each_nidn_once_and_uses_it_for_initial_credentials(): void
    {
        $file = UploadedFile::fake()->createWithContent('dosen.csv', implode("\n", [
            'Semester,NIDN,Nama Dosen,Kode Matakuliah',
            '20241,2008017601,NIDA NURJUNAEDAH,PGBA57',
            '20241,2008017601,NIDA NURJUNAEDAH,PGPIAUD37',
            '20241,2102119602,MUHAMMAD ILHAM KHALID AL-FARABY,PAI01',
        ]));

        $response = $this->actingAs($this->webAdministrator())->post(
            route('web-admin.workers.lecture-import'),
            ['import' => $file],
        );

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('dosens', 2);
        $this->assertDatabaseHas('dosens', [
            'dsn_nidn' => '2008017601',
            'dsn_name' => 'NIDA NURJUNAEDAH',
            'dsn_user' => '2008017601',
            'dsn_mail' => '2008017601@import.invalid',
            'dsn_phone' => '2008017601',
            'dsn_stat' => 1,
        ]);
        $this->assertTrue(Hash::check('2008017601', Dosen::where('dsn_nidn', '2008017601')->firstOrFail()->password));
    }

    public function test_it_rejects_an_invalid_nidn_without_importing_any_rows(): void
    {
        $file = UploadedFile::fake()->createWithContent('dosen.csv', implode("\n", [
            'NIDN,Nama Dosen',
            '2008017601,Dosen Valid',
            'ABC,Dosen Tidak Valid',
        ]));

        $response = $this->from(route('web-admin.workers.lecture-index'))
            ->actingAs($this->webAdministrator())
            ->post(route('web-admin.workers.lecture-import'), ['import' => $file]);

        $response->assertRedirect(route('web-admin.workers.lecture-index'))
            ->assertSessionHasErrors('import');
        $this->assertDatabaseCount('dosens', 0);
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
