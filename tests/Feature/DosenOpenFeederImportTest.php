<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Pages\WorkersController;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
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
            $table->string('gend')->nullable();
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

        (require database_path('migrations/2024_06_26_050556_create_web_settings_table.php'))->up();
        DB::table('web_settings')->insert([
            'id' => 1,
            'school_apps' => 'SIAKAD',
            'school_name' => 'Kampus Pengujian',
            'school_head' => 'Ketua',
            'school_link' => 'https://example.test',
            'school_desc' => 'Deskripsi kampus',
            'school_email' => 'kampus@example.test',
            'school_phone' => '081200000000',
            'social_fb' => '-',
            'social_ig' => '-',
            'social_in' => '-',
            'social_tw' => '-',
        ]);
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

    public function test_web_administrator_can_export_lecturers_in_the_import_format(): void
    {
        Dosen::create([
            'dsn_stat' => 1,
            'dsn_nidn' => '2008017601',
            'dsn_name' => 'Nida Nurjunaedah',
            'dsn_code' => 'DOSEN001',
            'dsn_user' => '2008017601',
            'password' => Hash::make('password'),
            'dsn_mail' => 'dosen@example.test',
            'dsn_phone' => '081234567891',
        ]);

        $response = $this
            ->actingAs($this->webAdministrator())
            ->get(route('web-admin.workers.lecture-export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('data-dosen-', (string) $response->headers->get('content-disposition'));

        $path = sys_get_temp_dir().'/data-dosen-export-'.uniqid().'.xlsx';
        file_put_contents($path, $response->streamedContent());

        try {
            $rows = (new FastExcel)->import($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(1, $rows);
        $this->assertSame(['NIDN', 'Nama Dosen'], array_keys($rows->first()));
        $this->assertSame('2008017601', (string) $rows->first()['NIDN']);
        $this->assertSame('Nida Nurjunaedah', $rows->first()['Nama Dosen']);
    }

    public function test_web_administrator_can_filter_the_lecturer_list(): void
    {
        $this->lecturer('2008017601', 'Dosen Aktif Laki-laki', 'L', 1);
        $matching = $this->lecturer('2102119602', 'Dosen Aktif Perempuan', 'P', 1);
        $this->lecturer('2203129703', 'Dosen Nonaktif Perempuan', 'P', 0);

        $this->actingAs($this->webAdministrator());
        $view = app(WorkersController::class)->indexLecture(Request::create(
            route('web-admin.workers.lecture-index'),
            'GET',
            ['search' => 'Aktif Perempuan', 'status' => '1', 'gender' => 'P'],
        ));
        $data = $view->getData();

        $this->assertSame('user.admin.pages.workers-lecture-index', $view->name());
        $this->assertSame([$matching->id], $data['dosen']->modelKeys());
        $this->assertSame(3, $data['lectureSummary']['total']);
        $this->assertSame(2, $data['lectureSummary']['active']);
        $this->assertTrue($data['hasLectureFilters']);
    }

    public function test_invalid_lecturer_filter_is_rejected(): void
    {
        $response = $this
            ->actingAs($this->webAdministrator())
            ->get(route('web-admin.workers.lecture-export', ['gender' => 'X']));

        $response->assertRedirect();
        $response->assertSessionHasErrors('gender');
    }

    public function test_web_administrator_can_filter_the_admin_list(): void
    {
        $this->actingAs($this->webAdministrator());
        $matching = $this->worker(0, 'Administrator Perempuan', 'P', 1, 'admin-perempuan');
        $this->worker(0, 'Administrator Nonaktif', 'L', 0, 'admin-nonaktif');

        $view = app(WorkersController::class)->indexAdmin(Request::create(
            route('web-admin.workers.admin-index'),
            'GET',
            ['search' => 'Perempuan', 'status' => '1', 'gender' => 'P'],
        ));
        $data = $view->getData();

        $this->assertSame([$matching->id], $data['admin']->modelKeys());
        $this->assertSame(3, $data['workerSummary']['total']);
        $this->assertTrue($data['hasWorkerFilters']);
    }

    public function test_web_administrator_can_filter_the_staff_list_by_role(): void
    {
        $this->actingAs($this->webAdministrator());
        $matching = $this->worker(3, 'Staff Akademik Perempuan', 'P', 1, 'staff-akademik');
        $this->worker(2, 'Staff Officer Perempuan', 'P', 1, 'staff-officer');

        $view = app(WorkersController::class)->indexWorkers(Request::create(
            route('web-admin.workers.staff-index'),
            'GET',
            ['search' => 'Perempuan', 'status' => '1', 'gender' => 'P', 'role' => '3'],
        ));
        $data = $view->getData();

        $this->assertSame([$matching->id], $data['admin']->modelKeys());
        $this->assertSame(2, $data['workerSummary']['total']);
        $this->assertTrue($data['hasWorkerFilters']);
    }

    private function lecturer(string $nidn, string $name, string $gender, int $status): Dosen
    {
        return Dosen::create([
            'dsn_stat' => $status,
            'dsn_nidn' => $nidn,
            'dsn_name' => $name,
            'dsn_code' => 'D'.$nidn,
            'dsn_gend' => $gender,
            'dsn_user' => $nidn,
            'password' => Hash::make('password'),
            'dsn_mail' => $nidn.'@example.test',
            'dsn_phone' => '08'.$nidn,
        ]);
    }

    private function worker(int $type, string $name, string $gender, int $status, string $username): User
    {
        return User::create([
            'type' => $type,
            'code' => strtoupper($username),
            'name' => $name,
            'gend' => $gender,
            'user' => $username,
            'phone' => '08'.str_pad((string) User::query()->count(), 10, '0', STR_PAD_LEFT),
            'email' => $username.'@example.test',
            'password' => 'password',
            'status' => $status,
        ]);
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
