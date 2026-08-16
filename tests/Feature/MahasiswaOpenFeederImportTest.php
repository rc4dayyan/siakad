<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class MahasiswaOpenFeederImportTest extends TestCase
{
    private TahunAkademik $period;

    private Kelas $class;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', true);
        DB::purge('sqlite');
        Storage::fake('local');

        $this->createTables();

        $this->period = TahunAkademik::create([
            'name' => '2025/2026 Ganjil',
            'code' => '20251',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => TahunAkademik::STATUS_DRAFT,
            'is_active' => false,
        ]);
        $program = ProgramStudi::create([
            'name' => 'Pendidikan Agama Islam',
            'code' => 'PAI',
        ]);
        $this->class = Kelas::create([
            'taka_id' => $this->period->id,
            'pstudi_id' => $program->id,
            'name' => 'PAI A',
            'code' => 'PAI-2025-A',
        ]);
        Wilayah::create([
            'code' => '021614',
            'kecamatan' => 'Jatiwangi',
            'kabupaten' => 'Majalengka',
            'provinsi' => 'Jawa Barat',
        ]);
    }

    public function test_it_imports_openfeeder_students_and_registers_them_in_the_selected_class(): void
    {
        $this->period->update([
            'status' => TahunAkademik::STATUS_ACTIVE,
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $response = $this->withSession([AcademicPeriodContext::SESSION_KEY => $this->period->id])
            ->actingAs($this->webAdministrator())
            ->post(route('web-admin.workers.student-import'), [
                'class_id' => $this->class->id,
                'import' => $this->xlsx([
                    '225862085431', 'ADLY MUHAMMAD HERISJUAN', 'MAJALENGKA', '2007-01-26', 'L',
                    '3210112601070002', '1', '1', '2026-09-05', '20251', 'Jalan Raya', '001', '002',
                    'Pinang', 'PINANGRAJA', '021614', '082119682157', 'adly@example.test', '', 'IIS JUMANISARI',
                    '', '86208', 'Pendidikan Agama Islam', '3000000',
                ]),
            ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mahasiswas', [
            'mhs_nim' => '225862085431',
            'mhs_nik' => '3210112601070002',
            'mhs_user' => '225862085431',
            'mhs_addr_kecamatan' => 'Jatiwangi',
            'mhs_addr_kota' => 'Majalengka',
            'mhs_addr_provinsi' => 'Jawa Barat',
            'years_id' => 2025,
            'class_id' => $this->class->id,
        ]);
        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'taka_id' => $this->period->id,
            'kelas_id' => $this->class->id,
            'semester_mahasiswa' => 1,
            'status_akademik' => 'aktif',
        ]);
        $password = DB::table('mahasiswas')->where('mhs_nim', '225862085431')->value('password');
        $this->assertTrue(Hash::check('225862085431', $password));
    }

    public function test_it_rolls_back_every_row_when_a_district_code_is_unknown(): void
    {
        $valid = [
            '225862085431', 'Mahasiswa Valid', 'Majalengka', '2007-01-26', 'L', '3210112601070002',
            '1', '1', '2026-09-05', '20251', '', '', '', '', 'PINANGRAJA', '021614', '082119682157',
            'valid@example.test', '', 'Ibu Valid', '', '86208', 'Pendidikan Agama Islam', '3000000',
        ];
        $invalid = [
            '225862085432', 'Mahasiswa Salah', 'Majalengka', '2007-02-26', 'P', '3210116602070002',
            '1', '1', '2026-09-05', '20251', '', '', '', '', 'TIDAK ADA', '999999', '082119682158',
            'salah@example.test', '', 'Ibu Salah', '', '86208', 'Pendidikan Agama Islam', '3000000',
        ];

        $file = $this->csv($valid)."\n".implode(',', $invalid);
        $response = $this->from(route('web-admin.workers.student-index'))
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->period->id])
            ->actingAs($this->webAdministrator())
            ->post(route('web-admin.workers.student-import'), [
                'class_id' => $this->class->id,
                'import' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $file),
            ]);

        $response->assertRedirect(route('web-admin.workers.student-index'))
            ->assertSessionHasErrors('import');
        $this->assertDatabaseCount('mahasiswas', 0);
        $this->assertDatabaseCount('registrasi_mahasiswas', 0);
    }

    public function test_it_reports_the_exact_account_field_and_owner_when_an_account_value_conflicts(): void
    {
        Mahasiswa::create([
            'mhs_nim' => '22586200001',
            'mhs_name' => 'Mahasiswa Lama',
            'mhs_code' => 'MHS-LAMA',
            'mhs_user' => '22586200001',
            'password' => 'password',
            'mhs_mail' => 'dipakai@example.test',
            'mhs_phone' => '081200000001',
        ]);

        $row = [
            '22586233450', 'Mahasiswa Baru', 'Majalengka', '2007-03-26', 'L', '3210112603070002',
            '1', '1', '2026-09-05', '20251', '', '', '', '', 'PINANGRAJA', '021614', '081200000002',
            'dipakai@example.test', '', 'Ibu Baru', '', '86208', 'Pendidikan Agama Islam', '3000000',
        ];

        $response = $this->from(route('web-admin.workers.student-index'))
            ->withSession([AcademicPeriodContext::SESSION_KEY => $this->period->id])
            ->actingAs($this->webAdministrator())
            ->post(route('web-admin.workers.student-import'), [
                'class_id' => $this->class->id,
                'import' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $this->csv($row)),
            ]);

        $response->assertRedirect(route('web-admin.workers.student-index'))
            ->assertSessionHasErrors([
                'import' => 'NIM 22586233450: Email dipakai@example.test sudah digunakan oleh Mahasiswa Lama (NIM 22586200001).',
            ]);
        $this->assertDatabaseCount('mahasiswas', 1);
        $this->assertDatabaseMissing('mahasiswas', ['mhs_nim' => '22586233450']);
    }

    public function test_it_allows_students_to_share_the_same_phone_number(): void
    {
        Mahasiswa::create([
            'mhs_nim' => '22586200001',
            'mhs_name' => 'Mahasiswa Lama',
            'mhs_code' => 'MHS-LAMA',
            'mhs_user' => '22586200001',
            'password' => 'password',
            'mhs_mail' => 'lama@example.test',
            'mhs_phone' => '081200000001',
        ]);

        $row = [
            '22586233450', 'Mahasiswa Baru', 'Majalengka', '2007-03-26', 'L', '3210112603070002',
            '1', '1', '2026-09-05', '20251', '', '', '', '', 'PINANGRAJA', '021614', '081200000001',
            'baru@example.test', '', 'Ibu Baru', '', '86208', 'Pendidikan Agama Islam', '3000000',
        ];

        $response = $this->withSession([AcademicPeriodContext::SESSION_KEY => $this->period->id])
            ->actingAs($this->webAdministrator())
            ->post(route('web-admin.workers.student-import'), [
                'class_id' => $this->class->id,
                'import' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $this->csv($row)),
            ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('mahasiswas', 2);
        $this->assertDatabaseHas('mahasiswas', [
            'mhs_nim' => '22586233450',
            'mhs_phone' => '081200000001',
        ]);
    }

    /** @param array<int, string> $values */
    private function csv(array $values): string
    {
        return implode(',', $this->headers())."\n".implode(',', $values);
    }

    /** @param array<int, string> $values */
    private function xlsx(array $values): UploadedFile
    {
        $path = sys_get_temp_dir().'/mahasiswa-openfeeder-'.uniqid().'.xlsx';
        (new FastExcel(collect([array_combine($this->headers(), $values)])))->export($path);
        $file = UploadedFile::fake()->createWithContent('mahasiswa.xlsx', file_get_contents($path));
        @unlink($path);

        return $file;
    }

    /** @return array<int, string> */
    private function headers(): array
    {
        return [
            'NIM', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'NIK', 'Agama',
            'Jenis Pendaftaran', 'Tanggal Masuk Kuliah', 'Mulai Semester', 'Jalan', 'RT', 'RW',
            'Nama Dusun', 'Kelurahan', 'Kecamatan', 'No HP', 'Email', 'Nama Ayah', 'Nama Ibu',
            'Nama Wali', 'Kode Prodi', 'Nama Prodi', 'Jumlah Biaya Masuk',
        ];
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

    private function createTables(): void
    {
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
        Schema::create('tahun_akademiks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedSmallInteger('year_start');
            $table->unsignedSmallInteger('year_end');
            $table->string('status');
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('program_studis', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });
        Schema::create('dosens', function (Blueprint $table): void {
            $table->id();
            $table->string('dsn_name')->nullable();
            $table->timestamps();
        });
        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('taka_id')->constrained('tahun_akademiks');
            $table->foreignId('pstudi_id')->constrained('program_studis');
            $table->foreignId('dosen_id')->nullable()->constrained('dosens');
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });
        Schema::create('wilayahs', function (Blueprint $table): void {
            $table->id();
            $table->char('code', 6)->unique();
            $table->string('kecamatan');
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->timestamps();
        });
        Schema::create('mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->integer('taka_id')->default(0);
            $table->integer('years_id')->default(0);
            $table->integer('class_id')->default(0);
            $table->integer('mhs_stat')->default(0);
            $table->string('mhs_nim')->unique();
            $table->string('mhs_nik')->nullable();
            $table->string('mhs_name');
            $table->string('mhs_code')->unique();
            $table->string('mhs_birthplace')->nullable();
            $table->date('mhs_birthdate')->nullable();
            $table->string('mhs_gend')->nullable();
            $table->string('mhs_reli')->nullable();
            $table->string('mhs_addr_domisili')->nullable();
            $table->string('mhs_addr_kelurahan')->nullable();
            $table->string('mhs_addr_kecamatan')->nullable();
            $table->string('mhs_addr_kota')->nullable();
            $table->string('mhs_addr_provinsi')->nullable();
            $table->string('mhs_parent_mother')->nullable();
            $table->string('mhs_parent_father')->nullable();
            $table->string('mhs_wali_name')->nullable();
            $table->string('mhs_user')->unique();
            $table->string('password');
            $table->string('mhs_mail')->unique();
            $table->string('mhs_phone');
            $table->dateTime('mhs_register_date')->nullable();
            $table->string('mhs_status')->nullable();
            $table->string('mhs_register_type')->nullable();
            $table->float('mhs_register_amount')->nullable();
            $table->string('mhs_sync_status')->nullable();
            $table->timestamps();
        });
        Schema::create('registrasi_mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->foreignId('taka_id')->constrained('tahun_akademiks');
            $table->unsignedTinyInteger('semester_mahasiswa');
            $table->string('status_akademik');
            $table->string('status_registrasi');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas');
            $table->foreignId('dosen_wali_id')->nullable()->constrained('dosens');
            $table->unsignedTinyInteger('batas_sks')->default(24);
            $table->timestamps();
        });
    }
}
