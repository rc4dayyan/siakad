<?php

namespace Tests\Feature;

use App\Models\HistoryTagihan;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TagihanKuliah;
use App\Models\TemplateTagihan;
use App\Models\User;
use App\Services\Academic\KrsEligibilityService;
use App\Services\Finance\BillingTargetService;
use App\Services\Finance\BulkBillingService;
use App\Services\Finance\FinancialEligibilityService;
use App\Services\Finance\FinancialReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinanceAcademicPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        foreach ([
            '2014_10_12_000000_create_users_table.php',
            '2024_03_09_024013_create_mahasiswas_table.php',
            '2024_03_09_024021_create_dosens_table.php',
            '2024_04_25_082531_create_program_studis_table.php',
            '2024_04_26_060533_create_tahun_akademiks_table.php',
            '2024_04_26_061235_create_program_kuliahs_table.php',
            '2024_04_27_041303_create_kelas_table.php',
            '2024_05_10_080721_create_tagihan_kuliahs_table.php',
            '2024_05_10_081438_create_history_tagihans_table.php',
            '2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php',
            '2026_07_17_000005_create_registrasi_mahasiswas_table.php',
            '2026_07_17_000010_normalize_period_billing_and_financial_krs_policy.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    public function test_target_must_be_unique_and_belong_to_template_period(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
            'target_prodi_id' => $data['programId'],
        ]);

        $this->expectException(ValidationException::class);
        app(BillingTargetService::class)->validate($template);
    }

    public function test_bulk_preview_issue_and_rerun_are_safe(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, ['target_type' => 'kelompok', 'kelompok_target' => 'aktif']);
        $service = app(BulkBillingService::class);

        $this->assertSame(1, $service->preview($template)['calon']);
        $first = $service->issue($template, $data['actor']);
        $second = $service->issue($template, $data['actor']);

        $this->assertSame(1, $first->berhasil);
        $this->assertSame(0, $first->gagal);
        $this->assertSame(0, $second->berhasil);
        $this->assertSame(1, $second->dilewati);
        $this->assertDatabaseCount('tagihan_kuliahs', 1);
    }

    public function test_required_unpaid_bill_blocks_krs_until_paid_or_overridden(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
            'wajib_lunas_krs' => true,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);
        $service = app(FinancialEligibilityService::class);

        $this->assertFalse($service->isEligible($data['registration']));
        $this->assertFalse(app(KrsEligibilityService::class)->isEligible($data['registration']));
        $bill = TagihanKuliah::firstOrFail();
        HistoryTagihan::create([
            'users_id' => $data['student']->id,
            'stat' => 1,
            'tagihan_code' => $bill->code,
            'desc' => 'Pembayaran tes',
            'code' => 'PAY-ONE',
            'tagihan_kuliah_id' => $bill->id,
            'taka_id' => $data['periodId'],
            'nominal' => $bill->nominal,
            'status' => 'lunas',
            'dibayar_at' => now(),
        ]);
        $this->assertTrue($service->isEligible($data['registration']));

        HistoryTagihan::query()->delete();
        $service->createOverride($data['registration'], $data['actor'], 'Disetujui pimpinan karena pembayaran sedang direkonsiliasi.');
        $this->assertTrue($service->isEligible($data['registration']));
        $this->assertDatabaseHas('override_keuangan_krs', ['actor_id' => $data['actor']->id]);
    }

    public function test_non_required_bill_does_not_block_krs(): void
    {
        $data = $this->academicData();
        $template = $this->template($data, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $data['student']->id,
            'wajib_lunas_krs' => false,
        ]);
        app(BulkBillingService::class)->issue($template, $data['actor']);

        $this->assertTrue(app(FinancialEligibilityService::class)->isEligible($data['registration']));
        $this->assertTrue(app(KrsEligibilityService::class)->isEligible($data['registration']));
    }

    public function test_report_is_isolated_and_old_period_total_does_not_change(): void
    {
        $old = $this->academicData('2025/2026', '20251');
        $template = $this->template($old, ['target_type' => 'mahasiswa', 'target_mahasiswa_id' => $old['student']->id]);
        app(BulkBillingService::class)->issue($template, $old['actor']);
        $reports = app(FinancialReportService::class);
        $before = $reports->forPeriod($old['periodId'])['ringkasan'];

        $new = $this->academicData('2026/2027', '20261');
        $newTemplate = $this->template($new, [
            'target_type' => 'mahasiswa',
            'target_mahasiswa_id' => $new['student']->id,
            'jenis' => 'registrasi',
            'nominal' => 750000,
        ]);
        app(BulkBillingService::class)->issue($newTemplate, $new['actor']);

        $this->assertSame($before, $reports->forPeriod($old['periodId'])['ringkasan']);
        $this->assertSame(500000, $before['total_tagihan']);
        $this->assertSame(750000, $reports->forPeriod($new['periodId'])['ringkasan']['total_tagihan']);
    }

    private function academicData(string $name = '2026/2027', string $code = '20261'): array
    {
        $actor = User::firstOrCreate(['email' => 'finance@example.test'], [
            'type' => 1, 'code' => 'FINANCE', 'name' => 'Staf Finance', 'user' => 'finance',
            'phone' => '0812000000', 'password' => 'secret', 'status' => 1,
        ]);
        $periodId = DB::table('tahun_akademiks')->insertGetId([
            'name' => $name, 'code' => $code, 'semester' => 1,
            'year_start' => (int) substr($code, 0, 4), 'year_end' => (int) substr($code, 0, 4) + 1,
            'term' => 'ganjil', 'status' => 'active', 'is_active' => 1,
            'starts_at' => substr($code, 0, 4).'-07-01', 'ends_at' => ((int) substr($code, 0, 4) + 1).'-06-30',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $programId = DB::table('program_studis')->insertGetId([
            'faku_id' => 1, 'name' => 'PAI '.$code, 'cnim' => $code, 'code' => 'PAI'.$code,
            'slug' => 'pai-'.$code, 'head_id' => 0, 'title' => 'S.Pd', 'level' => 'S1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $prokuId = DB::table('program_kuliahs')->insertGetId([
            'taka_id' => $periodId, 'pstudi_id' => $programId, 'name' => 'Reguler '.$code,
            'code' => 'REG-'.$code, 'wave' => 'I', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $periodId, 'pstudi_id' => $programId, 'proku_id' => $prokuId,
            'name' => 'Kelas '.$code, 'code' => 'KLS-'.$code, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = Mahasiswa::create([
            'mhs_nim' => 'NIM'.$code, 'mhs_name' => 'Mahasiswa '.$code, 'mhs_code' => 'MHS'.$code,
            'mhs_user' => 'mhs'.$code, 'password' => 'secret', 'mhs_mail' => 'mhs'.$code.'@example.test',
            'mhs_phone' => '08'.$code.'1111',
        ]);
        $registration = RegistrasiMahasiswa::create([
            'taka_id' => $periodId, 'mahasiswa_id' => $student->id, 'kelas_id' => $classId,
            'semester_mahasiswa' => 1, 'status_akademik' => 'aktif', 'status_registrasi' => 'terdaftar', 'batas_sks' => 24,
        ]);

        return compact('actor', 'periodId', 'programId', 'prokuId', 'classId', 'student', 'registration');
    }

    private function template(array $data, array $overrides = []): TemplateTagihan
    {
        return TemplateTagihan::create(array_merge([
            'taka_id' => $data['periodId'], 'name' => 'UKT Semester', 'jenis' => 'ukt', 'nominal' => 500000,
            'tanggal_terbit' => '2026-07-01', 'jatuh_tempo' => '2026-08-01', 'wajib_lunas_krs' => false,
            'target_type' => 'mahasiswa', 'target_mahasiswa_id' => null, 'target_prodi_id' => null,
            'target_proku_id' => null, 'kelompok_target' => null, 'created_by' => $data['actor']->id,
        ], $overrides));
    }
}
