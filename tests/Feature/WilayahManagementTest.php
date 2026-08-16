<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class WilayahManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', true);

        DB::purge('sqlite');

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

        (require database_path('migrations/2026_08_16_000002_create_wilayahs_table.php'))->up();
    }

    public function test_web_administrator_can_create_update_and_delete_wilayah(): void
    {
        $admin = $this->webAdministrator();

        $this->actingAs($admin)->post(route('web-admin.master.wilayah-store'), [
            'code' => '016202',
            'kecamatan' => 'Kec. Kebon Jeruk',
            'kabupaten' => 'Kota Jakarta Barat',
            'provinsi' => 'Prov. D.K.I. Jakarta',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('wilayahs', [
            'code' => '016202',
            'kecamatan' => 'Kec. Kebon Jeruk',
        ]);

        $wilayah = Wilayah::where('code', '016202')->firstOrFail();

        $this->actingAs($admin)->patch(route('web-admin.master.wilayah-update', $wilayah), [
            'code' => '016202',
            'kecamatan' => 'Kec. Kebon Jeruk Baru',
            'kabupaten' => 'Kota Jakarta Barat',
            'provinsi' => 'Prov. D.K.I. Jakarta',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('wilayahs', [
            'code' => '016202',
            'kecamatan' => 'Kec. Kebon Jeruk Baru',
        ]);

        $this->actingAs($admin)
            ->delete(route('web-admin.master.wilayah-destroy', $wilayah))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('wilayahs', ['code' => '016202']);
    }

    public function test_import_creates_and_updates_rows_using_openfeeder_headers(): void
    {
        Storage::fake('local');
        $admin = $this->webAdministrator();

        Wilayah::create([
            'code' => '016202',
            'kecamatan' => 'Nama Lama',
            'kabupaten' => 'Kota Lama',
            'provinsi' => 'Provinsi Lama',
        ]);

        $csv = implode("\n", [
            'id_wil,kecamatan,kabupaten,provinsi',
            '016202,Kec. Kebon Jeruk,Kota Jakarta Barat,Prov. D.K.I. Jakarta',
            '020509,Kec. Caringin,Kab. Bogor,Prov. Jawa Barat',
            '999999,tidak ada,,',
        ]);

        $response = $this->actingAs($admin)->post(route('web-admin.master.wilayah-import'), [
            'import' => UploadedFile::fake()->createWithContent('kode-wilayah.csv', $csv),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('wilayahs', 3);
        $this->assertDatabaseHas('wilayahs', [
            'code' => '016202',
            'kecamatan' => 'Kec. Kebon Jeruk',
            'kabupaten' => 'Kota Jakarta Barat',
        ]);
        $this->assertDatabaseHas('wilayahs', [
            'code' => '999999',
            'kecamatan' => 'tidak ada',
            'kabupaten' => null,
            'provinsi' => null,
        ]);
    }

    public function test_import_rejects_an_invalid_code_without_partial_writes(): void
    {
        Storage::fake('local');
        $csv = implode("\n", [
            'id_wil,kecamatan,kabupaten,provinsi',
            '016202,Kec. Kebon Jeruk,Kota Jakarta Barat,Prov. D.K.I. Jakarta',
            '123,Kec. Tidak Valid,Kab. Contoh,Prov. Contoh',
        ]);

        $response = $this->actingAs($this->webAdministrator())->post(route('web-admin.master.wilayah-import'), [
            'import' => UploadedFile::fake()->createWithContent('kode-wilayah.csv', $csv),
        ]);

        $response->assertSessionHasErrors('import');
        $this->assertDatabaseCount('wilayahs', 0);
    }

    public function test_import_accepts_xlsx_detected_as_application_zip(): void
    {
        Storage::fake('local');
        $path = sys_get_temp_dir().'/wilayah-'.Str::uuid().'.xlsx';

        (new FastExcel(collect([
            [
                'id_wil' => '020509',
                'kecamatan' => 'Kec. Caringin',
                'kabupaten' => 'Kab. Bogor',
                'provinsi' => 'Prov. Jawa Barat',
            ],
        ])))->export($path);

        try {
            $file = new UploadedFile(
                $path,
                'kode-wilayah.xlsx',
                'application/zip',
                null,
                true,
            );

            $response = $this->actingAs($this->webAdministrator())->post(route('web-admin.master.wilayah-import'), [
                'import' => $file,
            ]);

            $response->assertSessionHasNoErrors();
            $this->assertDatabaseHas('wilayahs', [
                'code' => '020509',
                'kecamatan' => 'Kec. Caringin',
            ]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
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
