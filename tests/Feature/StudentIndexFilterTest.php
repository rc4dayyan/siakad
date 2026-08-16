<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class StudentIndexFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        (require database_path('migrations/2024_06_26_050556_create_web_settings_table.php'))->up();
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        (require database_path('migrations/2024_03_09_024013_create_mahasiswas_table.php'))->up();
        (require database_path('migrations/2025_06_15_153738_add__to_mahasiswas_table.php'))->up();
        (require database_path('migrations/2024_03_09_024021_create_dosens_table.php'))->up();
        (require database_path('migrations/2024_04_27_041303_create_kelas_table.php'))->up();
        (require database_path('migrations/2024_05_23_095204_create_ticket_supports_table.php'))->up();
        (require database_path('migrations/2024_05_30_004205_create_notifications_table.php'))->up();
        (require database_path('migrations/2026_07_17_000005_create_registrasi_mahasiswas_table.php'))->up();
        (require database_path('migrations/2026_08_16_000002_create_wilayahs_table.php'))->up();

        Schema::create('program_studis', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
        });
        DB::table('program_studis')->insert([
            'id' => 1,
            'name' => 'Pendidikan Agama Islam',
            'code' => 'PAI',
        ]);
        DB::table('wilayahs')->insert([
            'code' => '021614',
            'kecamatan' => 'Jatiwangi',
            'kabupaten' => 'Majalengka',
            'provinsi' => 'Jawa Barat',
        ]);

        DB::table('web_settings')->insert([
            'school_apps' => 'SIAKAD',
            'school_name' => 'Kampus Test',
            'school_head' => 'Ketua Test',
            'school_link' => 'https://example.test',
            'school_desc' => 'Kampus untuk pengujian.',
            'school_email' => 'kampus@example.test',
            'school_phone' => '0800000000',
            'social_fb' => '-',
            'social_ig' => '-',
            'social_in' => '-',
            'social_tw' => '-',
        ]);
    }

    public function test_students_can_be_filtered_by_entry_year_and_current_academic_class(): void
    {
        $entryPeriod = $this->period(
            '2025-GANJIL',
            TahunAkademik::STATUS_CLOSED,
            false,
            2025,
            '2025-08-01'
        );
        $period = $this->period();
        $targetClass = $this->kelas($period, 'Kelas A');
        $otherClass = $this->kelas($period, 'Kelas B');
        $entryClass = $this->kelas($entryPeriod, 'Kelas Angkatan 2025');

        $matchingStudent = Mahasiswa::factory()->create([
            'years_id' => 2025,
            'class_id' => $otherClass->id,
            'mhs_nik' => '3210112601070002',
            'mhs_birthplace' => 'Majalengka',
            'mhs_birthdate' => '2007-01-26',
            'mhs_gend' => 'L',
            'mhs_reli' => '1',
            'mhs_addr_domisili' => 'Jalan Raya',
            'mhs_addr_kelurahan' => 'Pinangraja',
            'mhs_addr_kecamatan' => 'Jatiwangi',
            'mhs_addr_kota' => 'Majalengka',
            'mhs_addr_provinsi' => 'Jawa Barat',
            'mhs_register_date' => '2025-09-01',
            'mhs_register_type' => '1',
            'mhs_register_amount' => 3000000,
        ]);
        $wrongYear = Mahasiswa::factory()->create(['years_id' => 2024]);
        $wrongClass = Mahasiswa::factory()->create(['years_id' => 2025]);

        $this->registration($matchingStudent, $entryPeriod, $entryClass);
        $this->registration($matchingStudent, $period, $targetClass);
        $this->registration($wrongYear, $period, $targetClass);
        $this->registration($wrongClass, $entryPeriod, $entryClass);
        $this->registration($wrongClass, $period, $otherClass);

        $response = $this
            ->actingAs($this->webAdmin())
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->get(route('web-admin.workers.student-index', [
                'angkatan' => 2025,
                'kelas_id' => $targetClass->id,
            ]));

        $response->assertOk();
        $response->assertViewHas('student', function ($students) use ($matchingStudent): bool {
            return $students->modelKeys() === [$matchingStudent->id];
        });
        $response->assertSee('Angkatan 2025');
        $response->assertSee('Kelas A');

        $export = $this
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->get(route('web-admin.services.convert.export-student', [
                'angkatan' => 2025,
                'kelas_id' => $targetClass->id,
            ]));
        $export->assertOk();
        $content = $export->streamedContent();
        $this->assertStringEndsWith('.xlsx', (string) $export->headers->get('content-disposition'));

        $path = sys_get_temp_dir().'/student-openfeeder-export-'.uniqid().'.xlsx';
        file_put_contents($path, $content);
        try {
            $rows = (new FastExcel)->import($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(1, $rows);
        $this->assertSame([
            'NIM', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'NIK', 'Agama', 'NISN',
            'Jalur Pendaftaran', 'NPWP', 'Kewarganegaraan', 'Jenis Pendaftaran', 'Tanggal Masuk Kuliah',
            'Mulai Semester', 'Jalan', 'RT', 'RW', 'Nama Dusun', 'Kelurahan', 'Kecamatan', 'Kode Pos',
            'Jenis Tinggal', 'Alat Transportasi', 'Telp Rumah', 'No HP', 'Email', 'Terima KPS', 'No KPS',
            'NIK Ayah', 'Nama Ayah', 'Tanggal Lahir Ayah', 'Pendidikan Ayah', 'Pekerjaan Ayah',
            'Penghasilan Ayah', 'NIK Ibu', 'Nama Ibu', 'Tanggal Lahir Ibu', 'Pendidikan Ibu',
            'Pekerjaan Ibu', 'Penghasilan Ibu', 'Nama Wali', 'Tanggal Lahir Wali', 'Pendidikan Wali',
            'Pekerjaan Wali', 'Penghasilan Wali', 'Kode Prodi', 'Nama Prodi', 'SKS Diakui', 'Kode PT Asal',
            'Nama PT Asal', 'Kode Prodi Asal', 'Nama Prodi Asal', 'Jenis Pembiayaan', 'Jumlah Biaya Masuk',
            'Status Biodata', 'Keterangan Biodata', 'Status Riwayat', 'Keterangan Riwayat',
        ], array_keys($rows->first()));
        $this->assertSame($matchingStudent->mhs_nim, (string) $rows->first()['NIM']);
        $this->assertSame('021614', (string) $rows->first()['Kecamatan']);
        $this->assertSame('PAI', $rows->first()['Kode Prodi']);
        $this->assertSame('20251', (string) $rows->first()['Mulai Semester']);
    }

    public function test_invalid_entry_year_filter_is_rejected(): void
    {
        $period = $this->period();

        $response = $this
            ->actingAs($this->webAdmin())
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->get(route('web-admin.workers.student-index', ['angkatan' => 1800]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('angkatan');
    }

    private function period(
        string $code = '2026-GANJIL',
        string $status = TahunAkademik::STATUS_ACTIVE,
        bool $isActive = true,
        int $yearStart = 2026,
        string $startsAt = '2026-08-01'
    ): TahunAkademik {
        return TahunAkademik::factory()->create([
            'name' => $code,
            'code' => $code,
            'year_start' => $yearStart,
            'year_end' => $yearStart + 1,
            'status' => $status,
            'is_active' => $isActive,
            'starts_at' => $startsAt,
            'ends_at' => date('Y-m-d', strtotime($startsAt.' +5 months')),
        ]);
    }

    private function kelas(TahunAkademik $period, string $name): Kelas
    {
        return Kelas::create([
            'taka_id' => $period->id,
            'pstudi_id' => 1,
            'capacity' => 30,
            'name' => $name,
            'code' => str_replace(' ', '-', strtoupper($name)),
        ]);
    }

    private function registration(Mahasiswa $student, TahunAkademik $period, Kelas $class): void
    {
        RegistrasiMahasiswa::factory()->create([
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
            'kelas_id' => $class->id,
        ]);
    }

    private function webAdmin(): User
    {
        return User::create([
            'type' => 0,
            'code' => 'ADM-FILTER',
            'name' => 'Admin Filter',
            'user' => 'admin.filter',
            'phone' => '0800000001',
            'email' => 'admin.filter@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
