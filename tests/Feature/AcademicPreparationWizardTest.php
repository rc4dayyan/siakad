<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AcademicPreparationController;
use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicPreparationWizardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createUsersTable();
        (require database_path('migrations/2024_05_30_004205_create_notifications_table.php'))->up();
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        (require database_path('migrations/2026_08_20_000001_create_tahun_akademik_and_link_periods.php'))->up();
        $this->createClassTables();
    }

    public function test_web_administrator_can_complete_both_wizard_steps(): void
    {
        $admin = $this->staffUser(0, 'WEBADMIN');

        $yearResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.academic-year.store'),
            [
                '_wizard_step' => 1,
                'name' => 'Tahun Akademik 2028/2029',
                'code' => 'ta-2028-2029',
                'year_start' => 2028,
                'year_end' => 2029,
            ]
        );

        $academicYear = TahunAkademikInduk::firstOrFail();

        $yearResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('web-admin.academic-preparation.index', [
                'step' => 2,
                'tid' => $academicYear->id,
            ]));

        $periodResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.academic-period.store'),
            [
                '_wizard_step' => 2,
                'tid' => $academicYear->id,
                'name' => '2028/2029 Ganjil',
                'code' => 'ta-2028-2029-ganjil',
                'term' => TahunAkademik::TERM_GANJIL,
                'starts_at' => '2028-08-01',
                'ends_at' => '2028-12-31',
            ]
        );

        $periodResponse
            ->assertSessionHasNoErrors()
            ->assertSessionHas('created_period_code', 'TA-2028-2029-GANJIL')
            ->assertRedirect(route('web-admin.academic-preparation.index', [
                'step' => 3,
                'taka_id' => TahunAkademik::where('code', 'TA-2028-2029-GANJIL')->value('id'),
            ]));

        $this->assertDatabaseHas('tahun_akademik', [
            'id' => $academicYear->id,
            'code' => 'TA-2028-2029',
        ]);
        $this->assertDatabaseHas('tahun_akademiks', [
            'tid' => $academicYear->id,
            'code' => 'TA-2028-2029-GANJIL',
            'term' => TahunAkademik::TERM_GANJIL,
            'status' => TahunAkademik::STATUS_DRAFT,
            'is_active' => 0,
            'year_start' => 2028,
            'year_end' => 2029,
        ]);
    }

    public function test_wizard_view_receives_the_web_administrator_route_prefix(): void
    {
        $this->actingAs($this->staffUser(0, 'WEBADMIN'));

        $view = app(AcademicPreparationController::class)->index(
            Request::create('/web-admin/persiapan-akademik-baru', 'GET')
        );

        $this->assertSame('web-admin.', $view->getData()['prefix']);
    }

    public function test_third_step_imports_unique_classes_from_openfeeder_rows(): void
    {
        $admin = $this->staffUser(0, 'WEBADMIN');
        $academicYear = TahunAkademikInduk::create([
            'name' => 'Tahun Akademik 2025/2026',
            'code' => '2025-2026',
            'year_start' => 2025,
            'year_end' => 2026,
        ]);
        $period = TahunAkademik::create([
            'tid' => $academicYear->id,
            'name' => '2025/2026 Ganjil',
            'code' => '2025-GANJIL',
            'semester' => 1,
            'year_start' => 2025,
            'year_end' => 2026,
            'term' => TahunAkademik::TERM_GANJIL,
            'status' => TahunAkademik::STATUS_DRAFT,
        ]);
        DB::table('program_studis')->insert([
            'name' => 'Pendidikan Agama Islam',
            'code' => '86208',
        ]);
        DB::table('master_mata_kuliahs')->insert([
            ['program_studi' => '86208', 'semester' => 1, 'code' => 'PAI.01', 'name' => 'PPKN', 'sks' => 2],
        ]);

        $csv = implode("\n", [
            $this->openFeederClassHeaders(),
            '20251,PAI.01,PPKN,A,,,,,,86208,,2,0,0,0',
            '20251,PAI.02,Bahasa Inggris,A,,,,,,86208,,2,0,0,0',
            '20251,PAI.03,Bahasa Arab,A,,,,,,86208,,0,0,0,0',
            '20251,PAI.01,PPKN,B,,,,,,86208,,2,0,0,0',
        ]);

        $response = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.classes.import'),
            [
                '_wizard_step' => 3,
                'taka_id' => $period->id,
                'capacity' => 35,
                'import' => UploadedFile::fake()->createWithContent('kelas.csv', $csv),
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('kelas', 2);
        $this->assertDatabaseHas('kelas', [
            'taka_id' => $period->id,
            'name' => 'PAI I A',
            'code' => '2025-GANJIL-PAI-S1-A',
            'capacity' => 35,
        ]);
        $this->assertDatabaseHas('master_mata_kuliahs', [
            'program_studi' => '86208',
            'semester' => 1,
            'code' => 'PAI.02',
            'name' => 'Bahasa Inggris',
            'sks' => 2,
        ]);
        $this->assertDatabaseHas('master_mata_kuliahs', [
            'program_studi' => '86208',
            'semester' => 1,
            'code' => 'PAI.03',
            'name' => 'Bahasa Arab',
            'sks' => 2,
        ]);
        $this->assertDatabaseHas('kelas', [
            'taka_id' => $period->id,
            'name' => 'PAI I B',
            'code' => '2025-GANJIL-PAI-S1-B',
            'capacity' => 35,
        ]);
    }

    public function test_third_step_rejects_an_openfeeder_semester_that_does_not_match_the_period(): void
    {
        $admin = $this->staffUser(0, 'WEBADMIN');
        $academicYear = TahunAkademikInduk::create([
            'name' => 'Tahun Akademik 2025/2026',
            'code' => '2025-2026',
            'year_start' => 2025,
            'year_end' => 2026,
        ]);
        $period = TahunAkademik::create([
            'tid' => $academicYear->id,
            'name' => '2025/2026 Ganjil',
            'code' => '2025-GANJIL',
            'semester' => 1,
            'year_start' => 2025,
            'year_end' => 2026,
            'term' => TahunAkademik::TERM_GANJIL,
            'status' => TahunAkademik::STATUS_DRAFT,
        ]);
        DB::table('program_studis')->insert(['name' => 'Pendidikan Agama Islam', 'code' => '86208']);
        DB::table('master_mata_kuliahs')->insert([
            'program_studi' => '86208', 'semester' => 1, 'code' => 'PAI.01', 'name' => 'PPKN', 'sks' => 2,
        ]);
        $csv = $this->openFeederClassHeaders()."\n20252,PAI.01,PPKN,A,,,,,,86208,,2,0,0,0";

        $response = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.classes.import'),
            [
                '_wizard_step' => 3,
                'taka_id' => $period->id,
                'capacity' => 30,
                'import' => UploadedFile::fake()->createWithContent('kelas.csv', $csv),
            ]
        );

        $response->assertSessionHasErrors('import');
        $this->assertDatabaseCount('kelas', 0);
    }

    public function test_fourth_step_creates_a_lecturer_and_course_offering(): void
    {
        $admin = $this->staffUser(0, 'WEBADMIN');
        $academicYear = TahunAkademikInduk::create([
            'name' => 'Tahun Akademik 2025/2026',
            'code' => '2025-2026',
            'year_start' => 2025,
            'year_end' => 2026,
        ]);
        $period = TahunAkademik::create([
            'tid' => $academicYear->id,
            'name' => '2025/2026 Ganjil',
            'code' => '2025-GANJIL',
            'semester' => 1,
            'year_start' => 2025,
            'year_end' => 2026,
            'term' => TahunAkademik::TERM_GANJIL,
            'status' => TahunAkademik::STATUS_DRAFT,
        ]);
        $programId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Agama Islam',
            'code' => '86208',
        ]);
        $masterId = DB::table('master_mata_kuliahs')->insertGetId([
            'program_studi' => '86208',
            'semester' => 1,
            'code' => 'PAI.01',
            'name' => 'PPKN',
            'sks' => 2,
        ]);
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'capacity' => 35,
            'name' => 'PAI I A',
            'code' => '2025-GANJIL-PAI-S1-A',
        ]);
        $curriculumId = DB::table('kurikulums')->insertGetId([
            'name' => 'Kurikulum 2025',
            'code' => 'KUR-2025',
            'desc' => '-',
            'year_start' => 2025,
            'year_ended' => 2029,
        ]);
        $csv = $this->openFeederTeachingLecturerHeaders()
            ."\n20251,2103039305,,EMAN SULAEMAN,PAI.01,PPKN,A,14,,86208,,2,1";

        $response = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.teaching-lecturers.import'),
            [
                '_wizard_step' => 4,
                'taka_id' => $period->id,
                'kuri_id' => $curriculumId,
                'import' => UploadedFile::fake()->createWithContent('dosen-pengajar.csv', $csv),
            ]
        );

        $response->assertSessionHasNoErrors();
        $lecturerId = DB::table('dosens')->where('dsn_nidn', '2103039305')->value('id');
        $this->assertNotNull($lecturerId);
        $this->assertDatabaseHas('penawaran_mata_kuliahs', [
            'master_mata_kuliah_id' => $masterId,
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'kuri_id' => $curriculumId,
            'kelas_id' => $classId,
            'dosen_utama_id' => $lecturerId,
            'sks' => 2,
            'kapasitas' => 35,
        ]);

        $repeatResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.teaching-lecturers.import'),
            [
                '_wizard_step' => 4,
                'taka_id' => $period->id,
                'kuri_id' => $curriculumId,
                'import' => UploadedFile::fake()->createWithContent('dosen-pengajar-ulang.csv', $csv),
            ]
        );

        $repeatResponse->assertSessionHasNoErrors();
        $this->assertDatabaseCount('dosens', 1);
        $this->assertDatabaseCount('penawaran_mata_kuliahs', 1);
    }

    public function test_steps_five_to_seven_import_finalize_and_schedule_krs(): void
    {
        $admin = $this->staffUser(0, 'WEBADMIN');
        $academicYear = TahunAkademikInduk::create([
            'name' => 'Tahun Akademik 2025/2026',
            'code' => '2025-2026',
            'year_start' => 2025,
            'year_end' => 2026,
        ]);
        $period = TahunAkademik::create([
            'tid' => $academicYear->id,
            'name' => '2025/2026 Ganjil',
            'code' => '2025-GANJIL',
            'semester' => 1,
            'year_start' => 2025,
            'year_end' => 2026,
            'term' => TahunAkademik::TERM_GANJIL,
            'status' => TahunAkademik::STATUS_DRAFT,
            'starts_at' => '2025-08-04',
            'ends_at' => '2025-12-31',
        ]);
        $programId = DB::table('program_studis')->insertGetId(['name' => 'Pendidikan Agama Islam', 'code' => '86208']);
        $masterId = DB::table('master_mata_kuliahs')->insertGetId([
            'program_studi' => '86208', 'semester' => 1, 'code' => 'PAI.01', 'name' => 'PPKN', 'sks' => 2,
        ]);
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'capacity' => 1,
            'name' => 'PAI I A',
            'code' => '2025-GANJIL-PAI-S1-A',
        ]);
        $curriculumId = DB::table('kurikulums')->insertGetId([
            'name' => 'Kurikulum 2025', 'code' => 'KUR-2025', 'desc' => '-', 'year_start' => 2025, 'year_ended' => 2029,
        ]);
        $lecturerId = DB::table('dosens')->insertGetId([
            'dsn_stat' => 1,
            'dsn_nidn' => '2103039305',
            'dsn_name' => 'EMAN SULAEMAN',
            'dsn_code' => 'DOSEN-1',
            'dsn_user' => '2103039305',
            'password' => 'password',
            'dsn_mail' => '2103039305@example.test',
            'dsn_phone' => '2103039305',
        ]);
        $offeringId = DB::table('penawaran_mata_kuliahs')->insertGetId([
            'master_mata_kuliah_id' => $masterId,
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'kuri_id' => $curriculumId,
            'kelas_id' => $classId,
            'dosen_utama_id' => $lecturerId,
            'code' => 'OFFER-PAI-01-A',
            'sks' => 2,
            'kapasitas' => 1,
        ]);
        $secondMasterId = DB::table('master_mata_kuliahs')->insertGetId([
            'program_studi' => '86208', 'semester' => 1, 'code' => 'PAI.02', 'name' => 'Bahasa Inggris', 'sks' => 2,
        ]);
        $secondOfferingId = DB::table('penawaran_mata_kuliahs')->insertGetId([
            'master_mata_kuliah_id' => $secondMasterId,
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'kuri_id' => $curriculumId,
            'kelas_id' => $classId,
            'dosen_utama_id' => $lecturerId,
            'code' => 'OFFER-PAI-02-A',
            'sks' => 2,
            'kapasitas' => 2,
        ]);
        $csv = $this->openFeederKrsHeaders()
            ."\n225862085432,ALDI HERDIANSYAH,20251,PAI.01,PPKN,A,86208,,,,"
            ."\n225862085433,ANNAISA PUTRI,20251,PAI.01,PPKN,A,86208,,,,";

        $response = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.krs.import'),
            [
                '_wizard_step' => 5,
                'taka_id' => $period->id,
                'pstudi_id' => $programId,
                'student_semester' => 1,
                'import' => UploadedFile::fake()->createWithContent('krs.csv', $csv),
            ]
        );

        $response->assertSessionHasNoErrors();
        $studentId = DB::table('mahasiswas')->where('mhs_nim', '225862085432')->value('id');
        $this->assertDatabaseHas('mahasiswas', [
            'id' => $studentId,
            'mhs_name' => 'ALDI HERDIANSYAH',
            'mhs_user' => '225862085432',
            'mhs_stat' => 1,
            'taka_id' => $period->id,
            'class_id' => $classId,
        ]);
        $registrationId = DB::table('registrasi_mahasiswas')
            ->where('mahasiswa_id', $studentId)
            ->where('taka_id', $period->id)
            ->value('id');
        $krsId = DB::table('krs')->where('registrasi_mahasiswa_id', $registrationId)->value('id');
        $this->assertNotNull($registrationId);
        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'id' => $registrationId,
            'semester_mahasiswa' => 1,
            'kelas_id' => $classId,
        ]);
        $this->assertDatabaseHas('krs', [
            'id' => $krsId,
            'status' => 'draft',
            'total_sks' => 2,
        ]);
        $this->assertDatabaseHas('krs_items', [
            'krs_id' => $krsId,
            'penawaran_mata_kuliah_id' => $offeringId,
            'sks' => 2,
        ]);
        $this->assertDatabaseHas('kelas', [
            'id' => $classId,
            'capacity' => 2,
        ]);
        $this->assertDatabaseHas('penawaran_mata_kuliahs', [
            'id' => $offeringId,
            'kapasitas' => 2,
        ]);

        $repeatResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.krs.import'),
            [
                '_wizard_step' => 5,
                'taka_id' => $period->id,
                'pstudi_id' => $programId,
                'student_semester' => 1,
                'import' => UploadedFile::fake()->createWithContent('krs-ulang.csv', $csv),
            ]
        );

        $repeatResponse->assertSessionHasNoErrors();
        $this->assertDatabaseCount('mahasiswas', 2);
        $this->assertDatabaseCount('registrasi_mahasiswas', 2);
        $this->assertDatabaseCount('krs', 2);
        $this->assertDatabaseCount('krs_items', 2);

        $unconfirmedResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.krs.finalize'),
            ['_wizard_step' => 6, 'taka_id' => $period->id]
        );
        $unconfirmedResponse->assertSessionHasErrors('confirmation');
        $this->assertSame(2, DB::table('krs')->where('status', 'draft')->count());

        $finalizeResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.krs.finalize'),
            ['_wizard_step' => 6, 'taka_id' => $period->id, 'confirmation' => '1']
        );

        $finalizeResponse
            ->assertSessionHasNoErrors()
            ->assertSessionHas('krs_finalized', true)
            ->assertRedirect(route('web-admin.academic-preparation.index', [
                'step' => 7,
                'taka_id' => $period->id,
            ]));
        $this->assertSame(2, DB::table('krs')->where('status', 'approved')->count());
        $this->assertSame(0, DB::table('krs')->whereNull('diajukan_at')->count());
        $this->assertSame(0, DB::table('krs')->whereNull('diputuskan_at')->count());
        $this->assertDatabaseCount('notifications', 4);

        DB::table('ruangs')->insert([
            'gedu_id' => 1,
            'type' => 0,
            'floor' => 1,
            'kapasitas' => 40,
            'name' => 'Ruang 101',
            'code' => 'R101',
        ]);
        $summaryRequest = Request::create('/web-admin/persiapan-akademik-baru', 'GET', [
            'step' => 7,
            'taka_id' => $period->id,
        ]);
        $summaryRequest->setUserResolver(fn () => $admin);
        $scheduleSummary = app(AcademicPreparationController::class)->index($summaryRequest)->getData()['scheduleSummary'];
        $this->assertSame(2, $scheduleSummary['offerings']);
        $this->assertSame(4, $scheduleSummary['total_credits']);
        $this->assertSame(1, $scheduleSummary['estimated_rooms']);
        $this->assertSame(1, $scheduleSummary['rooms']);
        $this->assertSame(2, $scheduleSummary['largest_capacity']);
        $this->assertSame(1, $scheduleSummary['adequate_rooms']);

        $schedulePayload = [
            '_wizard_step' => 7,
            'taka_id' => $period->id,
            'days' => [1],
            'day_starts_at' => '08:00',
            'day_ends_at' => '12:00',
            'minutes_per_credit' => 50,
            'gap_minutes' => 10,
            'meeting_count' => 16,
        ];

        $dryRunResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.schedules.generate'),
            [...$schedulePayload, 'dry_run' => 1]
        );
        $dryRunResponse->assertSessionHasNoErrors();
        $this->assertDatabaseCount('jadwal_mingguans', 0);
        $this->assertDatabaseCount('pertemuan_kuliahs', 0);
        $this->assertDatabaseCount('jadwal_kuliahs', 0);

        $scheduleResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.schedules.generate'),
            [...$schedulePayload, 'dry_run' => 0]
        );
        $scheduleResponse
            ->assertSessionHasNoErrors()
            ->assertSessionHas('schedules_generated', true)
            ->assertRedirect(route('web-admin.academic-preparation.index', [
                'step' => 7,
                'taka_id' => $period->id,
            ]));
        $this->assertDatabaseHas('jadwal_mingguans', [
            'penawaran_mata_kuliah_id' => $offeringId,
            'kelas_id' => $classId,
            'dosen_id' => $lecturerId,
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:40',
        ]);
        $this->assertDatabaseHas('jadwal_mingguans', [
            'penawaran_mata_kuliah_id' => $secondOfferingId,
            'kelas_id' => $classId,
            'dosen_id' => $lecturerId,
            'hari' => 1,
            'mulai' => '09:50',
            'selesai' => '11:30',
        ]);
        $this->assertDatabaseCount('pertemuan_kuliahs', 32);
        $this->assertDatabaseCount('jadwal_kuliahs', 32);
        $this->assertSame(32, DB::table('pertemuan_kuliahs')->whereNotNull('legacy_jadwal_kuliah_id')->count());
        $this->assertSame(2, DB::table('mata_kuliahs')->count());

        $repeatResponse = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.meetings.generate'),
            ['taka_id' => $period->id, 'meeting_count' => 16, 'confirmation' => '1']
        );
        $repeatResponse->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pertemuan_kuliahs', 32);
        $this->assertDatabaseCount('jadwal_kuliahs', 32);
    }

    public function test_period_step_rejects_invalid_parent_and_date_range(): void
    {
        $response = $this->actingAs($this->staffUser(0, 'WEBADMIN'))->post(
            route('web-admin.academic-preparation.academic-period.store'),
            [
                '_wizard_step' => 2,
                'tid' => 999999,
                'name' => 'Periode Tidak Valid',
                'code' => 'INVALID-PERIOD',
                'term' => TahunAkademik::TERM_GANJIL,
                'starts_at' => '2028-12-31',
                'ends_at' => '2028-08-01',
            ]
        );

        $response->assertSessionHasErrors(['tid', 'ends_at']);
        $this->assertDatabaseCount('tahun_akademiks', 0);
    }

    public function test_seventh_step_assigns_class_advisors_and_synchronizes_student_registrations(): void
    {
        $data = $this->advisorSynchronizationFixture();
        $admin = $this->staffUser(0, 'ADVISOR-ADMIN');

        $response = $this->actingAs($admin)->post(
            route('web-admin.academic-preparation.academic-advisors.synchronize'),
            [
                '_wizard_step' => 7,
                'taka_id' => $data['period']->id,
                'confirmation' => 1,
                'class_advisors' => [
                    $data['class_ids'][0] => $data['advisor_ids'][0],
                    $data['class_ids'][1] => $data['advisor_ids'][1],
                ],
            ]
        );

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('academic_advisors_synchronized', true)
            ->assertRedirect(route('web-admin.academic-preparation.index', [
                'step' => 7,
                'taka_id' => $data['period']->id,
            ]));
        $this->assertDatabaseHas('kelas', [
            'id' => $data['class_ids'][0],
            'dosen_id' => $data['advisor_ids'][0],
        ]);
        $this->assertDatabaseHas('kelas', [
            'id' => $data['class_ids'][1],
            'dosen_id' => $data['advisor_ids'][1],
        ]);
        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'kelas_id' => $data['class_ids'][0],
            'dosen_wali_id' => $data['advisor_ids'][0],
        ]);
        $this->assertDatabaseHas('registrasi_mahasiswas', [
            'kelas_id' => $data['class_ids'][1],
            'dosen_wali_id' => $data['advisor_ids'][1],
        ]);
    }

    public function test_advisor_synchronization_requires_an_advisor_for_every_class(): void
    {
        $data = $this->advisorSynchronizationFixture();

        $response = $this->actingAs($this->staffUser(0, 'ADVISOR-VALIDATION'))->post(
            route('web-admin.academic-preparation.academic-advisors.synchronize'),
            [
                '_wizard_step' => 7,
                'taka_id' => $data['period']->id,
                'confirmation' => 1,
                'class_advisors' => [
                    $data['class_ids'][0] => $data['advisor_ids'][0],
                ],
            ]
        );

        $response->assertSessionHasErrors('class_advisors');
        $this->assertDatabaseMissing('kelas', [
            'id' => $data['class_ids'][0],
            'dosen_id' => $data['advisor_ids'][0],
        ]);
        $this->assertDatabaseMissing('registrasi_mahasiswas', [
            'kelas_id' => $data['class_ids'][0],
            'dosen_wali_id' => $data['advisor_ids'][0],
        ]);
    }

    public function test_non_web_administrator_cannot_use_wizard_actions(): void
    {
        $response = $this->actingAs($this->staffUser(3, 'ACADEMIC'))->post(
            route('web-admin.academic-preparation.academic-year.store'),
            [
                'name' => 'Tahun Akademik 2028/2029',
                'code' => '2028-2029',
                'year_start' => 2028,
                'year_end' => 2029,
            ]
        );

        $response->assertRedirect(route('error.access'));
        $this->assertDatabaseCount('tahun_akademik', 0);
    }

    public function test_schedule_generator_splits_seven_credits_into_balanced_sessions(): void
    {
        $academicYear = TahunAkademikInduk::create([
            'name' => 'Tahun Akademik 2026/2027',
            'code' => '2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
        ]);
        $period = TahunAkademik::create([
            'tid' => $academicYear->id,
            'name' => '2026/2027 Ganjil',
            'code' => '2026-GANJIL',
            'semester' => 1,
            'year_start' => 2026,
            'year_end' => 2027,
            'term' => TahunAkademik::TERM_GANJIL,
            'status' => TahunAkademik::STATUS_DRAFT,
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-12-31',
        ]);
        $programId = DB::table('program_studis')->insertGetId(['name' => 'PIAUD', 'code' => '86207']);
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'capacity' => 30,
            'name' => 'PIAUD VII A',
            'code' => '2026-GANJIL-PIAUD-S7-A',
        ]);
        $masterId = DB::table('master_mata_kuliahs')->insertGetId([
            'program_studi' => '86207',
            'semester' => 7,
            'code' => 'PPK',
            'name' => 'PPK',
            'sks' => 7,
        ]);
        $curriculumId = DB::table('kurikulums')->insertGetId([
            'name' => 'Kurikulum PIAUD',
            'code' => 'KUR-PIAUD',
            'desc' => '-',
            'year_start' => 2026,
            'year_ended' => 2030,
        ]);
        $lecturerId = DB::table('dosens')->insertGetId([
            'dsn_stat' => 1,
            'dsn_nidn' => '2103039305',
            'dsn_name' => 'Dosen PPK',
            'dsn_code' => 'DOSEN-PPK',
            'dsn_user' => 'dosen-ppk',
            'password' => 'password',
            'dsn_mail' => 'dosen-ppk@example.test',
            'dsn_phone' => '081234567890',
        ]);
        $offeringId = DB::table('penawaran_mata_kuliahs')->insertGetId([
            'master_mata_kuliah_id' => $masterId,
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'kuri_id' => $curriculumId,
            'kelas_id' => $classId,
            'dosen_utama_id' => $lecturerId,
            'code' => 'OFFER-PPK-7',
            'sks' => 7,
            'kapasitas' => 30,
        ]);
        $optionalMasterId = DB::table('master_mata_kuliahs')->insertGetId([
            'program_studi' => '86207',
            'semester' => 7,
            'code' => 'KKM',
            'name' => 'KKM',
            'sks' => 4,
        ]);
        $optionalOfferingId = DB::table('penawaran_mata_kuliahs')->insertGetId([
            'master_mata_kuliah_id' => $optionalMasterId,
            'taka_id' => $period->id,
            'pstudi_id' => $programId,
            'kuri_id' => $curriculumId,
            'kelas_id' => $classId,
            'dosen_utama_id' => $lecturerId,
            'code' => 'OFFER-KKM-OPTIONAL',
            'sks' => 4,
            'kapasitas' => 30,
            'wajib_dijadwalkan' => false,
        ]);
        DB::table('ruangs')->insert([
            'gedu_id' => 1,
            'type' => 0,
            'floor' => 1,
            'kapasitas' => 40,
            'name' => 'Ruang PPK',
            'code' => 'RPPK',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->actingAs($this->staffUser(0, 'SCHEDULE-ADMIN'))->post(
            route('web-admin.academic-preparation.schedules.generate'),
            [
                '_wizard_step' => 7,
                'taka_id' => $period->id,
                'days' => [5, 6],
                'day_starts_at' => '13:00',
                'day_ends_at' => '18:00',
                'minutes_per_credit' => 45,
                'gap_minutes' => 10,
                'meeting_count' => 16,
                'dry_run' => 0,
                'excluded_offering_ids' => [$optionalOfferingId],
            ]
        );
        $generationQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertSessionHasNoErrors()->assertSessionHas('schedules_generated', true);
        $this->assertLessThan(100, $generationQueryCount, 'Pencarian slot tidak boleh menjalankan query per kandidat waktu dan ruang.');
        $schedules = DB::table('jadwal_mingguans')
            ->where('penawaran_mata_kuliah_id', $offeringId)
            ->orderBy('hari')
            ->get();
        $this->assertCount(2, $schedules);
        $this->assertDatabaseHas('penawaran_mata_kuliahs', [
            'id' => $optionalOfferingId,
            'wajib_dijadwalkan' => false,
        ]);
        $this->assertDatabaseMissing('jadwal_mingguans', ['penawaran_mata_kuliah_id' => $optionalOfferingId]);
        $this->assertSame([4, 3], $schedules->pluck('sks')->map(fn ($credits) => (int) $credits)->all());
        $this->assertSame([5, 6], $schedules->pluck('hari')->map(fn ($day) => (int) $day)->all());
        $this->assertSame(['13:00', '16:00'], [substr($schedules[0]->mulai, 0, 5), substr($schedules[0]->selesai, 0, 5)]);
        $this->assertSame(['13:00', '15:15'], [substr($schedules[1]->mulai, 0, 5), substr($schedules[1]->selesai, 0, 5)]);
    }

    private function staffUser(int $type, string $code): User
    {
        return User::create([
            'type' => $type,
            'code' => $code,
            'name' => 'Pengguna '.$code,
            'user' => strtolower($code),
            'phone' => '08123'.str_pad((string) $type, 7, '0', STR_PAD_LEFT),
            'email' => strtolower($code).'@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }

    private function advisorSynchronizationFixture(): array
    {
        $academicYear = TahunAkademikInduk::create([
            'name' => 'Tahun Akademik 2027/2028',
            'code' => '2027-2028',
            'year_start' => 2027,
            'year_end' => 2028,
        ]);
        $period = TahunAkademik::create([
            'tid' => $academicYear->id,
            'name' => '2027/2028 Ganjil',
            'code' => '2027-GANJIL',
            'semester' => 1,
            'year_start' => 2027,
            'year_end' => 2028,
            'term' => TahunAkademik::TERM_GANJIL,
            'status' => TahunAkademik::STATUS_DRAFT,
        ]);
        $programId = DB::table('program_studis')->insertGetId([
            'name' => 'Pendidikan Agama Islam',
            'code' => '86208',
        ]);
        $classIds = [
            DB::table('kelas')->insertGetId([
                'taka_id' => $period->id,
                'pstudi_id' => $programId,
                'capacity' => 30,
                'name' => 'PAI I A',
                'code' => '2027-GANJIL-PAI-A',
            ]),
            DB::table('kelas')->insertGetId([
                'taka_id' => $period->id,
                'pstudi_id' => $programId,
                'capacity' => 30,
                'name' => 'PAI I B',
                'code' => '2027-GANJIL-PAI-B',
            ]),
        ];
        $advisorIds = [];
        foreach ([1, 2] as $number) {
            $advisorIds[] = DB::table('dosens')->insertGetId([
                'dsn_stat' => 1,
                'dsn_nidn' => '202700000'.$number,
                'dsn_name' => 'Dosen Wali '.$number,
                'dsn_code' => 'WALI-'.$number,
                'dsn_user' => 'wali-'.$number,
                'password' => 'password',
                'dsn_mail' => 'wali-'.$number.'@example.test',
                'dsn_phone' => '08127000000'.$number,
            ]);
        }
        foreach ($classIds as $index => $classId) {
            $studentId = DB::table('mahasiswas')->insertGetId([
                'taka_id' => $period->id,
                'years_id' => $academicYear->id,
                'class_id' => $classId,
                'mhs_stat' => 1,
                'mhs_nim' => '20278620800'.($index + 1),
                'mhs_name' => 'Mahasiswa '.($index + 1),
                'mhs_code' => 'MHS-'.($index + 1),
                'mhs_user' => 'mahasiswa-'.($index + 1),
                'password' => 'password',
                'mhs_mail' => 'mahasiswa-'.($index + 1).'@example.test',
                'mhs_phone' => '08128000000'.($index + 1),
            ]);
            DB::table('registrasi_mahasiswas')->insert([
                'mahasiswa_id' => $studentId,
                'taka_id' => $period->id,
                'semester_mahasiswa' => 1,
                'status_akademik' => 'aktif',
                'status_registrasi' => 'terdaftar',
                'kelas_id' => $classId,
                'dosen_wali_id' => null,
                'batas_sks' => 24,
            ]);
        }

        return [
            'period' => $period,
            'class_ids' => $classIds,
            'advisor_ids' => $advisorIds,
        ];
    }

    private function createUsersTable(): void
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
    }

    private function createClassTables(): void
    {
        Schema::create('program_studis', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('pstudi_id');
            $table->unsignedBigInteger('proku_id')->nullable();
            $table->unsignedBigInteger('dosen_id')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('master_mata_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->string('program_studi');
            $table->unsignedTinyInteger('semester');
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('sks');
            $table->timestamps();
        });

        Schema::create('dosens', function (Blueprint $table): void {
            $table->id();
            $table->integer('dsn_stat')->default(0);
            $table->string('dsn_nidn')->unique();
            $table->string('dsn_name');
            $table->string('dsn_code');
            $table->string('dsn_user')->unique();
            $table->string('password');
            $table->string('dsn_mail')->unique();
            $table->string('dsn_phone')->unique();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('kurikulums', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('desc');
            $table->integer('year_start');
            $table->integer('year_ended');
            $table->timestamps();
        });

        Schema::create('penawaran_mata_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_mata_kuliah_id');
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('pstudi_id');
            $table->unsignedBigInteger('kuri_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('dosen_utama_id');
            $table->unsignedBigInteger('dosen_pendamping_1_id')->nullable();
            $table->unsignedBigInteger('dosen_pendamping_2_id')->nullable();
            $table->unsignedBigInteger('prasyarat_master_id')->nullable();
            $table->unsignedBigInteger('legacy_mata_kuliah_id')->nullable();
            $table->string('code')->unique();
            $table->unsignedTinyInteger('sks');
            $table->unsignedSmallInteger('kapasitas');
            $table->boolean('wajib_dijadwalkan')->default(true);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->unique(['master_mata_kuliah_id', 'taka_id', 'pstudi_id', 'kuri_id', 'kelas_id']);
        });

        Schema::create('kalender_akademiks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id');
            $table->string('kategori', 40);
            $table->string('nama');
            $table->dateTime('mulai_at');
            $table->dateTime('selesai_at');
            $table->boolean('dipublikasikan')->default(false);
            $table->timestamps();
        });

        Schema::create('ruangs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('gedu_id');
            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('floor');
            $table->unsignedSmallInteger('kapasitas')->default(40);
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('jadwal_mingguans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penawaran_mata_kuliah_id')->nullable();
            $table->unsignedTinyInteger('sks')->nullable();
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('dosen_id');
            $table->unsignedBigInteger('ruang_id');
            $table->unsignedTinyInteger('hari');
            $table->time('mulai');
            $table->time('selesai');
            $table->string('code')->unique();
            $table->string('fingerprint', 64)->unique();
            $table->text('alasan_pengecualian')->nullable();
            $table->unsignedBigInteger('pengecualian_oleh')->nullable();
            $table->timestamps();
        });

        Schema::create('mata_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('mid')->nullable();
            $table->unsignedBigInteger('kuri_id');
            $table->unsignedBigInteger('taka_id');
            $table->unsignedBigInteger('requ_id')->nullable();
            $table->unsignedBigInteger('pstudi_id');
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->unsignedBigInteger('dosen_1');
            $table->unsignedBigInteger('dosen_2')->nullable();
            $table->unsignedBigInteger('dosen_3')->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('bsks');
            $table->longText('desc');
            $table->timestamps();
        });

        Schema::create('jadwal_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('makul_id');
            $table->unsignedBigInteger('penawaran_mata_kuliah_id')->nullable();
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('dosen_id');
            $table->unsignedBigInteger('ruang_id');
            $table->unsignedTinyInteger('pert_id');
            $table->unsignedTinyInteger('meth_id');
            $table->unsignedTinyInteger('days_id');
            $table->unsignedTinyInteger('bsks');
            $table->date('date');
            $table->time('start');
            $table->time('ended');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('pertemuan_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jadwal_mingguan_id');
            $table->unsignedBigInteger('legacy_jadwal_kuliah_id')->nullable()->unique();
            $table->unsignedBigInteger('dosen_id');
            $table->unsignedBigInteger('ruang_id');
            $table->unsignedTinyInteger('pertemuan_ke');
            $table->date('tanggal');
            $table->time('mulai');
            $table->time('selesai');
            $table->string('metode', 24)->default('tatap_muka');
            $table->text('materi')->nullable();
            $table->string('status', 24)->default('terjadwal');
            $table->string('code')->unique();
            $table->timestamps();
            $table->unique(['jadwal_mingguan_id', 'pertemuan_ke']);
            $table->unique(['jadwal_mingguan_id', 'tanggal']);
        });

        Schema::create('mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taka_id')->default(0);
            $table->unsignedInteger('years_id')->default(0);
            $table->unsignedBigInteger('class_id')->default(0);
            $table->unsignedTinyInteger('mhs_stat')->default(0);
            $table->string('mhs_nim')->unique();
            $table->string('mhs_name');
            $table->string('mhs_code')->unique();
            $table->string('mhs_user')->unique();
            $table->string('password');
            $table->string('mhs_mail')->unique();
            $table->string('mhs_phone')->unique();
            $table->timestamps();
        });

        Schema::create('registrasi_mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('mahasiswa_id');
            $table->unsignedBigInteger('taka_id');
            $table->unsignedTinyInteger('semester_mahasiswa');
            $table->string('status_akademik');
            $table->string('status_registrasi');
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->unsignedBigInteger('dosen_wali_id')->nullable();
            $table->unsignedTinyInteger('batas_sks')->default(24);
            $table->timestamps();
            $table->unique(['mahasiswa_id', 'taka_id']);
        });

        Schema::create('krs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('registrasi_mahasiswa_id')->unique();
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('total_sks')->default(0);
            $table->text('catatan_mahasiswa')->nullable();
            $table->text('catatan_keputusan')->nullable();
            $table->dateTime('diajukan_at')->nullable();
            $table->dateTime('diputuskan_at')->nullable();
            $table->unsignedBigInteger('diputuskan_oleh')->nullable();
            $table->timestamps();
        });

        Schema::create('krs_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('krs_id');
            $table->unsignedBigInteger('penawaran_mata_kuliah_id');
            $table->unsignedTinyInteger('sks');
            $table->timestamps();
            $table->unique(['krs_id', 'penawaran_mata_kuliah_id']);
        });
    }

    private function openFeederClassHeaders(): string
    {
        return implode(',', [
            'Semester', 'Kode Matakuliah', 'Nama Matakuliah', 'Nama Kelas', 'Bahasan',
            'Tanggal Mulai Efektif', 'Tanggal Akhir Efektif', 'Lingkup Kelas', 'Mode Kuliah',
            'Kode Prodi', 'Nama Prodi', 'Sks Tatap Muka', 'Sks Praktek',
            'Sks Praktek Lapangan', 'Sks Simulasi',
        ]);
    }

    private function openFeederTeachingLecturerHeaders(): string
    {
        return implode(',', [
            'Semester', 'NIDN', 'NUPTK', 'Nama Dosen', 'Kode Matakuliah', 'Nama Matakuliah',
            'Nama Kelas', 'Tatap Muka', 'Tatap Muka Realisasi', 'Kode Prodi', 'Nama Prodi',
            'Sks Ajar', 'Jenis Evaluasi',
        ]);
    }

    private function openFeederKrsHeaders(): string
    {
        return implode(',', [
            'NIM', 'Nama', 'Semester', 'Kode Mata Kuliah', 'Nama Mata Kuliah', 'Nama Kelas',
            'Kode Prodi', 'Nama Prodi', 'Nilai Huruf', 'Nilai Indeks', 'Nilai Angka',
        ]);
    }
}
