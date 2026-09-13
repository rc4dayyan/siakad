<?php

namespace Tests\Feature;

use App\Models\AbsensiMahasiswa;
use App\Models\Dosen;
use App\Models\FeedBack\FBPerkuliahan;
use App\Models\JadwalKuliah;
use App\Models\JadwalMingguan;
use App\Models\KalenderAkademik;
use App\Models\Krs;
use App\Models\KrsItem;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\MateriAjar;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\PertemuanKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AttendanceEligibilityService;
use App\Services\Academic\KrsService;
use App\Services\Academic\MeetingGeneratorService;
use App\Services\Academic\ScheduleConflictService;
use App\Services\Academic\ScheduleNotificationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class KrsWorkflowTest extends TestCase
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
            '2024_04_27_041303_create_kelas_table.php',
            '2024_04_28_063053_create_kurikulums_table.php',
            '2024_04_28_035926_create_gedungs_table.php',
            '2024_04_28_052322_create_ruangs_table.php',
            '2024_04_30_032644_create_mata_kuliahs_table.php',
            '2024_04_30_055648_create_jadwal_kuliahs_table.php',
            '2024_04_30_102751_create_absensi_mahasiswas_table.php',
            '2024_06_09_053130_create_f_b_perkuliahans_table.php',
            '2024_05_30_004205_create_notifications_table.php',
            '2024_06_16_033935_create_hasil_studis_table.php',
            '2024_06_26_050556_create_web_settings_table.php',
            '2025_07_05_091153_create_nilai_mahasiswas_table.php',
            '2025_07_05_112548_add_matakuliah_kelas_id.php',
            '2026_07_17_000001_create_master_mata_kuliahs_table.php',
            '2026_07_17_000002_add_mid_to_mata_kuliahs_table.php',
            '2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php',
            '2026_07_17_000004_link_grades_and_study_results_to_academic_periods.php',
            '2026_07_17_000005_create_registrasi_mahasiswas_table.php',
            '2026_07_17_000011_create_period_opening_workflow_and_audit.php',
            '2026_08_17_000001_add_code_to_master_mata_kuliahs_table.php',
        ] as $migration) {
            if ($migration === '2026_07_17_000004_link_grades_and_study_results_to_academic_periods.php') {
                // Migration tersebut juga mengubah hasil_studis; bagian ini tidak dibutuhkan untuk workflow KRS.
                DB::statement('ALTER TABLE nilai_mahasiswas ADD COLUMN taka_id INTEGER NULL');

                continue;
            }
            (require database_path('migrations/'.$migration))->up();
        }

        (require database_path('migrations/2026_07_17_000008_create_course_offerings_and_krs_tables.php'))->up();
        (require database_path('migrations/2026_07_17_000009_create_weekly_schedules_and_course_meetings.php'))->up();
        (require database_path('migrations/2026_08_18_000001_normalize_grades_by_course_offering.php'))->up();
        (require database_path('migrations/2026_09_03_000001_add_sks_to_jadwal_mingguans_table.php'))->up();
        (require database_path('migrations/2026_09_03_000002_add_schedule_requirement_to_course_offerings.php'))->up();
        (require database_path('migrations/2026_09_04_000001_add_structured_answers_to_feedback_perkuliahans.php'))->up();
        (require database_path('migrations/2026_09_05_000001_create_materi_ajars_table.php'))->up();
    }

    public function test_migration_prevents_duplicate_course_offering_combination(): void
    {
        $data = $this->academicData();
        $attributes = $this->offeringAttributes($data);
        PenawaranMataKuliah::create($attributes);

        $this->expectException(UniqueConstraintViolationException::class);
        PenawaranMataKuliah::create([...$attributes, 'code' => 'OF-DUPLICATE']);
    }

    public function test_staff_receives_validation_error_when_creating_duplicate_course_offering(): void
    {
        $data = $this->academicData();
        PenawaranMataKuliah::create($this->offeringAttributes($data));
        $administrator = $this->webAdministrator('OFFERINGADMIN');

        $response = $this->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->actingAs($administrator)
            ->from(route('web-admin.master.penawaran-index'))
            ->post(route('web-admin.master.penawaran-store'), [
                '_form' => 'create-offering',
                'master_mata_kuliah_id' => $data['master']->id,
                'pstudi_id' => $data['programId'],
                'kuri_id' => $data['curriculumId'],
                'kelas_ids' => [$data['classId']],
                'dosen_utama_id' => $data['advisor']->id,
                'kapasitas' => 40,
            ]);

        $response
            ->assertRedirect(route('web-admin.master.penawaran-index'))
            ->assertSessionHasErrors([
                'kelas_ids' => 'Penawaran mata kuliah tersebut sudah tersedia untuk kelas: PAI 1A.',
            ]);
        $this->assertSame(1, PenawaranMataKuliah::count());
    }

    public function test_course_offering_list_filters_by_search_class_lecturer_and_semester(): void
    {
        $data = $this->academicData();
        PenawaranMataKuliah::create([...$this->offeringAttributes($data), 'code' => 'OF-INTRO']);
        $advancedMaster = MasterMataKuliah::create([
            'program_studi' => 'PAI',
            'code' => 'PAI202',
            'semester' => 2,
            'name' => 'Fikih Lanjutan',
            'sks' => 3,
        ]);
        $advancedOffering = PenawaranMataKuliah::create([
            ...$this->offeringAttributes($data),
            'master_mata_kuliah_id' => $advancedMaster->id,
            'code' => 'OF-ADVANCED',
        ]);
        $administrator = $this->webAdministrator('OFFERINGFILTER');
        $this->actingAs($administrator);
        session([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);
        $request = Request::create('/web-admin/master/penawaran-matkul', 'GET', [
            'q' => 'Fikih',
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'semester' => 2,
        ]);
        $request->setUserResolver(fn () => $administrator);

        $view = app(\App\Http\Controllers\Admin\PenawaranMataKuliahController::class)
            ->index($request, app(AcademicPeriodContext::class));

        $this->assertSame([$advancedOffering->id], $view->getData()['offerings']->pluck('id')->all());
        $this->assertSame('Fikih', $view->getData()['filters']['q']);
        $this->assertSame(2, $view->getData()['filters']['semester']);
    }

    public function test_participants_view_receives_web_admin_route_prefix(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $webAdmin = User::create([
            'type' => 0,
            'code' => 'WEBADMIN',
            'name' => 'Web Administrator',
            'user' => 'webadmin',
            'phone' => '0812000000',
            'email' => 'webadmin@example.test',
            'password' => 'secret',
            'status' => 1,
        ]);

        $this->actingAs($webAdmin);

        $view = app(\App\Http\Controllers\Admin\PenawaranMataKuliahController::class)
            ->participants($offering, app(AcademicPeriodContext::class));

        $this->assertSame('web-admin.', $view->getData()['prefix']);
        $this->assertTrue($offering->is($view->getData()['penawaran']));
    }

    public function test_staff_manages_grades_from_approved_course_offering_without_legacy_course(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $administrator = $this->webAdministrator('GRADEADMIN');
        $outsider = Mahasiswa::create([
            'mhs_nim' => '26998', 'mhs_name' => 'Mahasiswa Luar', 'mhs_code' => 'MHS998',
            'mhs_user' => 'mhs998', 'password' => 'secret', 'mhs_mail' => 'mhs998@example.test',
            'mhs_phone' => '0899999998',
        ]);

        $this->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->actingAs($administrator)
            ->post(route('web-admin.master.penawaran-grades-store', $offering), [
                'nilai' => [['mahasiswa_id' => $outsider->id, 'nilai' => 'A']],
            ])
            ->assertSessionHasErrors('nilai.0.mahasiswa_id');

        $this->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->actingAs($administrator)
            ->post(route('web-admin.master.penawaran-grades-store', $offering), [
                'nilai' => [['mahasiswa_id' => $data['student']->id, 'nilai' => 'A']],
            ])
            ->assertRedirect(route('web-admin.master.penawaran-grades', $offering))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('nilai_mahasiswas', [
            'mahasiswa_id' => $data['student']->id,
            'taka_id' => $data['periodId'],
            'mata_kuliah_id' => null,
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'nilai' => 'A',
        ]);
        $this->assertFalse(Route::has('web-admin.master.matkul-nilai'));
    }

    public function test_course_class_page_uses_offerings_and_reports_approved_participants_and_grades(): void
    {
        $data = $this->academicData();
        $data['master']->update(['code' => 'PAI-101']);
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        NilaiMahasiswa::create([
            'mahasiswa_id' => $data['student']->id,
            'taka_id' => $data['periodId'],
            'mata_kuliah_id' => null,
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'nilai' => 'A',
        ]);
        $administrator = $this->webAdministrator('COURSECLASSADMIN');
        $request = \Illuminate\Http\Request::create('/master/data-matkul', 'GET', [
            'q' => 'PAI-101',
            'pstudi_id' => $data['programId'],
            'kelas_id' => $data['classId'],
        ]);
        $request->setUserResolver(fn () => $administrator);
        $this->actingAs($administrator)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);

        $view = app(\App\Http\Controllers\Admin\Pages\Core\MataKuliahController::class)
            ->index($request, app(AcademicPeriodContext::class));
        $offerings = $view->getData()['offerings'];

        $this->assertSame('user.admin.master.admin-matkul-index', $view->name());
        $this->assertCount(1, $offerings);
        $this->assertSame($offering->id, $offerings->first()->id);
        $this->assertSame(1, $offerings->first()->peserta_count);
        $this->assertSame(1, $offerings->first()->nilai_terisi_count);
    }

    public function test_assigned_lecturer_can_manage_offering_grades_but_other_lecturer_cannot(): void
    {
        $data = $this->academicData();
        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);

        $this->actingAs($data['advisor'], 'dosen');
        $view = app(\App\Http\Controllers\Dosen\Akademik\MataKuliahController::class)
            ->index(app(AcademicPeriodContext::class));
        $this->assertCount(1, $view->getData()['offerings']);
        $this->assertSame($offering->id, $view->getData()['offerings']->first()->id);

        $this->post(route('dosen.akademik.matkul-nilai-store', $offering), [
            'nilai' => [['mahasiswa_id' => $data['student']->id, 'nilai' => 'A']],
        ])->assertRedirect(route('dosen.akademik.matkul-nilai', $offering));
        $this->assertDatabaseHas('nilai_mahasiswas', [
            'penawaran_mata_kuliah_id' => $offering->id,
            'mahasiswa_id' => $data['student']->id,
            'nilai' => 'A',
        ]);
        $this->actingAs($data['student'], 'mahasiswa');
        $studentView = app(\App\Http\Controllers\Mahasiswa\Pages\StudentNilaiController::class)->index(
            \Illuminate\Http\Request::create('/mahasiswa/nilai-kuliah'),
            app(AcademicPeriodContext::class),
            app(\App\Services\Academic\StudentAcademicContext::class)
        );
        $this->assertSame('A', $studentView->getData()['nilai']->first()['nilai']);
        $this->assertSame($offering->kelas->name, $studentView->getData()['nilai']->first()['kelas']);

        $otherLecturer = $this->lecturer('2002', 'Dosen Lain');
        $this->actingAs($otherLecturer, 'dosen');

        try {
            app(\App\Http\Controllers\Dosen\Akademik\MataKuliahController::class)->storeGrades(
                \Illuminate\Http\Request::create('/dosen/data-akademik/mata-kuliah/nilai', 'POST', [
                    'nilai' => [['mahasiswa_id' => $data['student']->id, 'nilai' => 'B']],
                ]),
                $offering,
                app(AcademicPeriodContext::class)
            );
            $this->fail('Dosen yang tidak ditugaskan seharusnya tidak dapat mengubah nilai.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas('nilai_mahasiswas', [
            'penawaran_mata_kuliah_id' => $offering->id,
            'nilai' => 'A',
        ]);
    }

    public function test_lecturer_shares_private_course_material_only_with_approved_participants(): void
    {
        Storage::fake('local');
        $data = $this->academicData();
        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);

        $this->actingAs($data['advisor'], 'dosen')
            ->post(route('dosen.akademik.matkul-materi-store', $offering), [
                '_form' => 'create-materi',
                'judul' => 'Modul Pertemuan Pertama',
                'deskripsi' => 'Baca modul sebelum perkuliahan.',
                'file' => UploadedFile::fake()->create('modul-pai.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('dosen.akademik.matkul-materi', $offering))
            ->assertSessionHasNoErrors();

        $material = MateriAjar::query()->firstOrFail();
        $this->assertSame($data['advisor']->id, $material->dosen_id);
        $this->assertSame($offering->id, $material->penawaran_mata_kuliah_id);
        Storage::disk('local')->assertExists($material->file_path);

        $this->actingAs($data['student'], 'mahasiswa');
        $studentView = app(\App\Http\Controllers\Mahasiswa\Pages\MateriAjarController::class)
            ->index(app(AcademicPeriodContext::class));
        $this->assertSame('mahasiswa.pages.materi-ajar-index', $studentView->name());
        $this->assertSame('Modul Pertemuan Pertama', $studentView->getData()['offerings']->first()->materiAjars->first()->judul);
        $this->get(route('mahasiswa.akademik.materi-download', $material))
            ->assertOk()
            ->assertDownload('modul-pai.pdf');

        $outsider = Mahasiswa::create([
            'mhs_stat' => 1,
            'mhs_nim' => '26999',
            'mhs_name' => 'Mahasiswa Luar',
            'mhs_code' => 'MHS999',
            'mhs_user' => 'mhs999',
            'password' => 'secret',
            'mhs_mail' => 'mhs999@example.test',
            'mhs_phone' => '0899999999',
        ]);
        $this->actingAs($outsider, 'mahasiswa');
        try {
            app(\App\Http\Controllers\Mahasiswa\Pages\MateriAjarController::class)
                ->download($material, app(AcademicPeriodContext::class));
            $this->fail('Mahasiswa di luar peserta KRS seharusnya tidak dapat mengunduh materi.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $this->assertTrue(true);
        }

        $otherLecturer = $this->lecturer('2003', 'Dosen Tidak Ditugaskan');
        $this->actingAs($otherLecturer, 'dosen');
        try {
            app(\App\Http\Controllers\Dosen\Akademik\MataKuliahController::class)->storeMaterial(
                Request::create('/dosen/data-akademik/mata-kuliah/materi', 'POST', [
                    '_form' => 'create-materi',
                    'judul' => 'Materi Tidak Sah',
                    'deskripsi' => 'Tidak boleh tersimpan.',
                ]),
                $offering,
                app(AcademicPeriodContext::class)
            );
            $this->fail('Dosen yang tidak ditugaskan seharusnya tidak dapat menambah materi.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $this->assertTrue(true);
        }
        $this->assertSame(1, MateriAjar::count());
    }

    public function test_lecturer_and_student_schedule_filters_share_their_authorized_schedules(): void
    {
        $data = $this->academicData();
        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $courseId = DB::table('mata_kuliahs')->insertGetId([
            'kuri_id' => $data['curriculumId'], 'taka_id' => $data['periodId'], 'pstudi_id' => $data['programId'],
            'dosen_1' => $data['advisor']->id, 'name' => 'Pengantar Studi Islam', 'code' => 'PAI-JADWAL',
            'bsks' => 3, 'desc' => '-', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $firstRoom = $this->room(40, 'FILTER-A');
        $secondRoom = $this->room(40, 'FILTER-B');
        $matching = JadwalKuliah::create([
            'penawaran_mata_kuliah_id' => $offering->id,
            'makul_id' => $courseId, 'kelas_id' => $data['classId'], 'dosen_id' => $data['advisor']->id,
            'ruang_id' => $firstRoom, 'pert_id' => 1, 'meth_id' => 0, 'days_id' => 1, 'bsks' => 3,
            'date' => '2026-08-17', 'start' => '08:00', 'ended' => '10:00', 'code' => 'JAD-FILTER-A',
        ]);
        JadwalKuliah::create([
            'penawaran_mata_kuliah_id' => $offering->id,
            'makul_id' => $courseId, 'kelas_id' => $data['classId'], 'dosen_id' => $data['advisor']->id,
            'ruang_id' => $secondRoom, 'pert_id' => 2, 'meth_id' => 1, 'days_id' => 2, 'bsks' => 3,
            'date' => '2026-08-18', 'start' => '10:00', 'ended' => '12:00', 'code' => 'JAD-FILTER-B',
        ]);
        $weeklyAttributes = [
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $firstRoom,
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '10:00',
            'sks' => 3,
        ];
        $weeklySchedule = JadwalMingguan::create([
            ...$weeklyAttributes,
            'code' => 'WEEKLY-FILTER-A',
            'fingerprint' => JadwalMingguan::fingerprint($weeklyAttributes),
        ]);
        $secondWeeklyAttributes = [
            ...$weeklyAttributes,
            'ruang_id' => $secondRoom,
            'hari' => 2,
            'mulai' => '10:00',
            'selesai' => '12:00',
        ];
        JadwalMingguan::create([
            ...$secondWeeklyAttributes,
            'code' => 'WEEKLY-FILTER-B',
            'fingerprint' => JadwalMingguan::fingerprint($secondWeeklyAttributes),
        ]);
        $lecturerRequest = Request::create('/dosen/data-akademik/jadwal', 'GET', [
            'q' => 'Pengantar Studi', 'ruang_id' => $firstRoom, 'days_id' => 1,
        ]);
        $this->actingAs($data['advisor'], 'dosen');
        $lecturerView = app(\App\Http\Controllers\Dosen\Akademik\JadwalAjarController::class)
            ->index($lecturerRequest, app(AcademicPeriodContext::class));

        $this->assertSame([$weeklySchedule->id], $lecturerView->getData()['jadkul']->modelKeys());
        $this->assertTrue($lecturerView->getData()['filterRooms']->contains('id', $firstRoom));

        $studentRequest = Request::create('/mahasiswa/jadwal-kuliah', 'GET', [
            'q' => 'WEEKLY-FILTER-A', 'ruang_id' => $firstRoom, 'days_id' => 1,
        ]);
        $this->actingAs($data['student'], 'mahasiswa');
        $studentView = app(\App\Http\Controllers\Mahasiswa\HomeController::class)->jadkulIndex(
            $studentRequest,
            app(AcademicPeriodContext::class),
            app(\App\Services\Academic\StudentAcademicContext::class)
        );

        $this->assertSame([$weeklySchedule->id], $studentView->getData()['jadkul']->modelKeys());
        $this->assertSame($data['advisor']->id, $studentView->getData()['filterLecturers']->sole()->id);

        $printRequest = Request::create('/mahasiswa/jadwal-kuliah/cetak', 'GET', [
            'q' => 'JAD-FILTER-A', 'ruang_id' => $firstRoom, 'date_to' => '2026-08-17',
        ]);
        $printView = app(\App\Http\Controllers\Mahasiswa\HomeController::class)->jadkulPrint(
            $printRequest,
            app(AcademicPeriodContext::class),
            app(\App\Services\Academic\StudentAcademicContext::class)
        );
        $printHtml = $printView->render();

        $this->assertSame([$matching->id], $printView->getData()['schedules']->modelKeys());
        $this->assertStringContainsString('Jadwal Kuliah Mahasiswa', $printHtml);
        $this->assertStringContainsString($data['student']->mhs_name, $printHtml);
        $this->assertStringContainsString('Ruang FILTER-A', $printHtml);
        $this->assertStringNotContainsString('Ruang FILTER-B', $printHtml);
    }

    public function test_student_can_submit_complete_lecturer_evaluation_once(): void
    {
        $data = $this->academicData();
        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $courseId = DB::table('mata_kuliahs')->insertGetId([
            'kuri_id' => $data['curriculumId'], 'taka_id' => $data['periodId'], 'pstudi_id' => $data['programId'],
            'dosen_1' => $data['advisor']->id, 'name' => 'Pengantar Studi Islam', 'code' => 'PAI-EVALUASI',
            'bsks' => 3, 'desc' => '-', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $schedule = JadwalKuliah::create([
            'penawaran_mata_kuliah_id' => $offering->id,
            'makul_id' => $courseId, 'kelas_id' => $data['classId'], 'dosen_id' => $data['advisor']->id,
            'ruang_id' => $this->room(40, 'EVALUASI'), 'pert_id' => 1, 'meth_id' => 0, 'days_id' => 1,
            'bsks' => 3, 'date' => '2026-08-17', 'start' => '08:00', 'ended' => '10:00',
            'code' => 'JAD-EVALUASI',
        ]);
        $ratings = collect(config('lecturer_evaluation.sections'))
            ->flatMap(fn (array $section) => array_fill_keys(array_keys($section['questions']), 4))
            ->all();
        $narratives = collect(config('lecturer_evaluation.narratives'))
            ->mapWithKeys(fn (string $question, string $key) => [$key => 'Jawaban untuk '.$key])
            ->all();

        $this->actingAs($data['student'], 'mahasiswa');
        $controller = app(\App\Http\Controllers\Mahasiswa\HomeController::class);
        $form = $controller->feedbackForm(
            $schedule->code,
            app(AcademicPeriodContext::class),
            app(\App\Services\Academic\StudentAcademicContext::class)
        );
        $this->assertSame('mahasiswa.pages.mhs-jadkul-feedback', $form->name());
        $this->assertCount(39, collect($form->getData()['evaluation']['sections'])->flatMap(fn (array $section) => $section['questions']));

        $response = $controller->storeFBPerkuliahan(
            Request::create('/mahasiswa/jadwal-kuliah/store/'.$schedule->code.'/feedback', 'POST', compact('ratings', 'narratives')),
            $schedule->code,
            app(AcademicPeriodContext::class),
            app(\App\Services\Academic\StudentAcademicContext::class)
        );

        $this->assertSame(route('mahasiswa.home-jadkul-index'), $response->getTargetUrl());
        $feedback = FBPerkuliahan::sole();
        $this->assertSame('4.00', $feedback->fb_average_score);
        $this->assertSame('Sangat Puas', $feedback->fb_score);
        $this->assertCount(39, $feedback->fb_answers['ratings']);
        $this->assertCount(5, $feedback->fb_answers['narratives']);

        $controller->storeFBPerkuliahan(
            Request::create('/mahasiswa/jadwal-kuliah/store/'.$schedule->code.'/feedback', 'POST', compact('ratings', 'narratives')),
            $schedule->code,
            app(AcademicPeriodContext::class),
            app(\App\Services\Academic\StudentAcademicContext::class)
        );
        $this->assertSame(1, FBPerkuliahan::count());
    }

    public function test_student_can_print_only_approved_weekly_schedules_from_their_krs(): void
    {
        $data = $this->academicData();
        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);

        $roomId = $this->room(40, 'STUDENT-WEEKLY');
        $scheduleAttributes = [
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $roomId,
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:40',
        ];
        $schedule = JadwalMingguan::create([
            ...$scheduleAttributes,
            'code' => 'JMG-STUDENT-WEEKLY',
            'fingerprint' => JadwalMingguan::fingerprint($scheduleAttributes),
        ]);
        $tuesdayAttributes = [
            ...$scheduleAttributes,
            'hari' => 2,
            'mulai' => '13:00',
            'selesai' => '14:40',
        ];
        $tuesdaySchedule = JadwalMingguan::create([
            ...$tuesdayAttributes,
            'code' => 'JMG-STUDENT-TUESDAY',
            'fingerprint' => JadwalMingguan::fingerprint($tuesdayAttributes),
        ]);

        $otherMaster = MasterMataKuliah::create([
            'program_studi' => 'PAI',
            'code' => 'PAI999',
            'semester' => 1,
            'name' => 'Mata Kuliah Tidak Diambil',
            'sks' => 2,
        ]);
        $otherOffering = PenawaranMataKuliah::create([
            ...$this->offeringAttributes($data),
            'master_mata_kuliah_id' => $otherMaster->id,
            'code' => 'OF-NOT-TAKEN',
            'sks' => 2,
        ]);
        $otherScheduleAttributes = [
            'penawaran_mata_kuliah_id' => $otherOffering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $roomId,
            'hari' => 1,
            'mulai' => '10:00',
            'selesai' => '11:40',
        ];
        JadwalMingguan::create([
            ...$otherScheduleAttributes,
            'code' => 'JMG-NOT-TAKEN',
            'fingerprint' => JadwalMingguan::fingerprint($otherScheduleAttributes),
        ]);

        $this->actingAs($data['student'], 'mahasiswa');
        $request = Request::create('/mahasiswa/jadwal-kuliah/cetak-mingguan');
        $view = app(\App\Http\Controllers\Mahasiswa\HomeController::class)->jadkulWeeklyPrint(
            $request,
            app(AcademicPeriodContext::class),
            app(\App\Services\Academic\StudentAcademicContext::class)
        );
        $html = $view->render();

        $this->assertSame([$schedule->id, $tuesdaySchedule->id], $view->getData()['schedules']->modelKeys());
        $this->assertStringContainsString('Jadwal Kuliah Mingguan', $html);
        $this->assertStringContainsString('<h3>Senin</h3>', $html);
        $this->assertStringContainsString('<h3>Selasa</h3>', $html);
        $this->assertStringContainsString('Pengantar Studi Islam', $html);
        $this->assertStringContainsString('Dosen Wali', $html);
        $this->assertStringContainsString('Ruang STUDENT-WEEKLY', $html);
        $this->assertStringNotContainsString('Mata Kuliah Tidak Diambil', $html);
    }

    public function test_offering_grades_can_be_imported_exported_and_not_changed_after_period_closes(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $administrator = $this->webAdministrator('GRADEFILEADMIN');
        $file = UploadedFile::fake()->createWithContent(
            'nilai.csv',
            "NIM,Nama Mahasiswa,Nilai\n{$data['student']->mhs_nim},{$data['student']->mhs_name},B\n"
        )->mimeType('text/csv');

        $this->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->actingAs($administrator)
            ->post(route('web-admin.master.penawaran-grades-import', $offering), [
                '_form' => 'import-nilai',
                'import' => $file,
            ])
            ->assertRedirect(route('web-admin.master.penawaran-grades', $offering))
            ->assertSessionHasNoErrors();

        $export = $this->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->actingAs($administrator)
            ->get(route('web-admin.master.penawaran-grades-export', $offering));
        $export->assertOk();
        $path = sys_get_temp_dir().'/offering-grade-export-'.uniqid().'.xlsx';
        file_put_contents($path, $export->streamedContent());

        try {
            $rows = (new FastExcel)->import($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(1, $rows);
        $this->assertSame('B', $rows->first()['Nilai Huruf']);

        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'status' => 'closed',
            'is_active' => false,
        ]);
        $this->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']])
            ->actingAs($administrator)
            ->post(route('web-admin.master.penawaran-grades-store', $offering), [
                'nilai' => [['mahasiswa_id' => $data['student']->id, 'nilai' => 'C']],
            ])
            ->assertSessionHasErrors('academic_period');
        $this->assertDatabaseHas('nilai_mahasiswas', [
            'penawaran_mata_kuliah_id' => $offering->id,
            'nilai' => 'B',
        ]);
    }

    public function test_student_submits_and_advisor_approves_locked_krs_with_notification(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->forRegistration($data['registration']);

        $service->add($krs, $offering);
        $this->assertSame(3, $krs->fresh()->total_sks);
        $service->submit($krs->fresh(), 'Mohon persetujuan.');
        $approved = $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, 'Disetujui.');

        $this->assertSame(Krs::STATUS_APPROVED, $approved->status);
        $this->assertNotNull($approved->diputuskan_at);
        $this->assertSame($data['advisor']->id, $approved->diputuskan_oleh);
        $this->assertDatabaseHas('notifications', ['student_id' => $data['student']->id, 'type' => 'krs']);
        $this->assertTrue($offering->pesertaDisetujui()->whereKey($data['student']->id)->exists());

        $this->expectException(LogicException::class);
        KrsItem::create(['krs_id' => $approved->id, 'penawaran_mata_kuliah_id' => $offering->id, 'sks' => 3]);
    }

    public function test_student_can_add_multiple_course_offerings_in_one_request(): void
    {
        $data = $this->academicData();
        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $firstOffering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $secondMaster = MasterMataKuliah::create([
            'program_studi' => 'PAI', 'semester' => 1, 'name' => 'Bahasa Arab Dasar', 'sks' => 2,
        ]);
        $secondOffering = PenawaranMataKuliah::create([
            ...$this->offeringAttributes($data),
            'master_mata_kuliah_id' => $secondMaster->id,
            'sks' => 2,
        ]);

        $this->actingAs($data['student'], 'mahasiswa')
            ->post(route('mahasiswa.akademik.krs-add-many'), [
                'penawaran_ids' => [$firstOffering->id, $secondOffering->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 mata kuliah berhasil ditambahkan ke draft KRS.');

        $krs = Krs::where('registrasi_mahasiswa_id', $data['registration']->id)->firstOrFail();
        $this->assertSame(5, $krs->total_sks);
        $this->assertEqualsCanonicalizing(
            [$firstOffering->id, $secondOffering->id],
            $krs->items()->pluck('penawaran_mata_kuliah_id')->all()
        );
    }

    public function test_multiple_course_offering_addition_is_rolled_back_when_one_selection_is_invalid(): void
    {
        $data = $this->academicData();
        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $data['registration']->update(['batas_sks' => 4]);
        $firstOffering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $secondMaster = MasterMataKuliah::create([
            'program_studi' => 'PAI', 'semester' => 1, 'name' => 'Bahasa Arab Dasar', 'sks' => 2,
        ]);
        $secondOffering = PenawaranMataKuliah::create([
            ...$this->offeringAttributes($data),
            'master_mata_kuliah_id' => $secondMaster->id,
            'sks' => 2,
        ]);

        $this->actingAs($data['student'], 'mahasiswa')
            ->from(route('mahasiswa.akademik.krs-index'))
            ->post(route('mahasiswa.akademik.krs-add-many'), [
                'penawaran_ids' => [$firstOffering->id, $secondOffering->id],
            ])
            ->assertRedirect(route('mahasiswa.akademik.krs-index'))
            ->assertSessionHasErrors('penawaran');

        $krs = Krs::where('registrasi_mahasiswa_id', $data['registration']->id)->firstOrFail();
        $this->assertSame(0, $krs->fresh()->total_sks);
        $this->assertDatabaseCount('krs_items', 0);
    }

    public function test_krs_rejects_closed_window_and_credit_limit(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->forRegistration($data['registration']);
        KalenderAkademik::query()->update(['selesai_at' => now()->subMinute()]);

        try {
            $service->add($krs, $offering);
            $this->fail('KRS di luar jadwal seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('krs', $exception->errors());
        }

        KalenderAkademik::query()->update(['selesai_at' => now()->addDay()]);
        $data['registration']->update(['batas_sks' => 2]);

        $this->expectException(ValidationException::class);
        $service->add($krs->fresh(), $offering);
    }

    public function test_only_assigned_advisor_can_decide_submitted_krs(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $other = $this->lecturer('9999', 'Dosen Lain');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->decide($krs->fresh(), $other, Krs::STATUS_APPROVED, null);
    }

    public function test_weekly_schedule_detects_lecturer_class_room_conflicts_and_capacity(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $roomId = $this->room(40);
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id, 'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id, 'ruang_id' => $roomId, 'hari' => 1,
            'mulai' => '08:00', 'selesai' => '09:40',
        ];
        JadwalMingguan::create([...$attributes, 'code' => 'JMG-ONE', 'fingerprint' => JadwalMingguan::fingerprint($attributes)]);

        try {
            app(ScheduleConflictService::class)->validate($offering, [...$attributes, 'mulai' => '09:00', 'selesai' => '10:00']);
            $this->fail('Semua bentrok sumber daya seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('dosen_id', $exception->errors());
            $this->assertArrayHasKey('kelas_id', $exception->errors());
            $this->assertArrayHasKey('ruang_id', $exception->errors());
        }

        $administrator = User::create([
            'type' => 0, 'code' => 'WEBADMIN', 'name' => 'Web Administrator', 'user' => 'webadmin',
            'phone' => '0812000000', 'email' => 'webadmin@example.test', 'password' => 'secret', 'status' => 1,
        ]);
        $override = app(ScheduleConflictService::class)->validate(
            $offering,
            [...$attributes, 'mulai' => '09:00', 'selesai' => '10:00'],
            actor: $administrator,
            override: true,
            reason: 'Kegiatan akademik khusus yang telah disetujui pimpinan.'
        );
        $this->assertSame($administrator->id, $override['pengecualian_oleh']);

        $smallRoom = $this->room(2, 'R002');
        $this->expectException(ValidationException::class);
        app(ScheduleConflictService::class)->validate($offering, [...$attributes, 'ruang_id' => $smallRoom, 'hari' => 2]);
    }

    public function test_printed_weekly_timetable_contains_professional_schedule_details(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $roomId = $this->room(40, 'PRINT');
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $roomId,
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:40',
        ];
        JadwalMingguan::create([
            ...$attributes,
            'code' => 'JMG-PRINT',
            'fingerprint' => JadwalMingguan::fingerprint($attributes),
        ]);
        $administrator = $this->webAdministrator('TIMETABLEPRINT');
        $this->actingAs($administrator);
        session([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);
        $request = Request::create('/web-admin/master/jadwal-mingguan/cetak', 'GET', [
            'pstudi_id' => $data['programId'],
        ]);
        $request->setUserResolver(fn () => $administrator);

        $view = app(\App\Http\Controllers\Admin\JadwalMingguanController::class)
            ->printTimetable($request, app(AcademicPeriodContext::class));
        $html = $view->render();

        $this->assertStringContainsString('Pengantar Studi Islam', $html);
        $this->assertStringContainsString('PAI 1A', $html);
        $this->assertStringContainsString('Ruang PRINT', $html);
        $this->assertStringContainsString('3 SKS', $html);
        $this->assertStringContainsString('Nama Dosen', $html);
        $this->assertStringNotContainsString('Kode Dosen', $html);
        $this->assertStringContainsString('Dosen Wali', $html);
        $this->assertSame([
            'schedules' => 1,
            'classes' => 1,
            'lecturers' => 1,
            'rooms' => 1,
        ], $view->getData()['summary']);
    }

    public function test_weekly_schedule_list_combines_academic_resource_and_day_filters(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $roomId = $this->room(40, 'FILTER');
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $roomId,
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:40',
        ];
        $schedule = JadwalMingguan::create([
            ...$attributes,
            'code' => 'JMG-FILTER',
            'fingerprint' => JadwalMingguan::fingerprint($attributes),
        ]);
        $administrator = $this->webAdministrator('SCHEDULEFILTER');
        $this->actingAs($administrator);
        session([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);
        $request = Request::create('/web-admin/master/jadwal-mingguan', 'GET', [
            'q' => 'Pengantar',
            'pstudi_id' => $data['programId'],
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $roomId,
            'hari' => 1,
        ]);
        $request->setUserResolver(fn () => $administrator);

        $view = app(\App\Http\Controllers\Admin\JadwalMingguanController::class)
            ->index($request, app(AcademicPeriodContext::class));

        $this->assertSame([$schedule->id], $view->getData()['schedules']->pluck('id')->all());
        $this->assertSame(1, $view->getData()['filters']['hari']);

        $sundayRequest = Request::create('/web-admin/master/jadwal-mingguan', 'GET', ['hari' => 0]);
        $sundayRequest->setUserResolver(fn () => $administrator);
        $sundayView = app(\App\Http\Controllers\Admin\JadwalMingguanController::class)
            ->index($sundayRequest, app(AcademicPeriodContext::class));

        $this->assertSame(0, $sundayView->getData()['filters']['hari']);
        $this->assertTrue($sundayView->getData()['schedules']->isEmpty());
    }

    public function test_weekly_schedule_list_is_paginated_and_keeps_filters(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $roomId = $this->room(40, 'PAGINATION');

        foreach (range(0, 29) as $number) {
            $attributes = [
                'penawaran_mata_kuliah_id' => $offering->id,
                'kelas_id' => $data['classId'],
                'dosen_id' => $data['advisor']->id,
                'ruang_id' => $roomId,
                'hari' => 1,
                'mulai' => sprintf('08:%02d', $number),
                'selesai' => sprintf('09:%02d', $number),
            ];
            JadwalMingguan::create([
                ...$attributes,
                'code' => 'JMG-PAGE-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'fingerprint' => JadwalMingguan::fingerprint($attributes),
            ]);
        }

        $administrator = $this->webAdministrator('SCHEDULEPAGE');
        $this->actingAs($administrator);
        session([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);
        $firstRequest = Request::create('/web-admin/master/jadwal-mingguan', 'GET', [
            'q' => 'JMG-PAGE',
        ]);
        $firstRequest->setUserResolver(fn () => $administrator);
        \Illuminate\Pagination\Paginator::currentPageResolver(fn () => 1);
        $firstPage = app(\App\Http\Controllers\Admin\JadwalMingguanController::class)
            ->index($firstRequest, app(AcademicPeriodContext::class));
        $firstSchedules = $firstPage->getData()['schedules'];

        $this->assertSame(25, $firstSchedules->count());
        $this->assertSame(30, $firstSchedules->total());
        $this->assertSame(1, $firstSchedules->currentPage());
        $this->assertSame('JMG-PAGE', $firstPage->getData()['filters']['q']);

        $secondRequest = Request::create('/web-admin/master/jadwal-mingguan', 'GET', [
            'q' => 'JMG-PAGE',
            'page' => 2,
        ]);
        $secondRequest->setUserResolver(fn () => $administrator);
        \Illuminate\Pagination\Paginator::currentPageResolver(fn () => 2);
        $secondPage = app(\App\Http\Controllers\Admin\JadwalMingguanController::class)
            ->index($secondRequest, app(AcademicPeriodContext::class));
        $secondSchedules = $secondPage->getData()['schedules'];

        $this->assertSame(5, $secondSchedules->count());
        $this->assertSame(30, $secondSchedules->total());
        $this->assertSame(26, $secondSchedules->firstItem());
        \Illuminate\Pagination\Paginator::currentPageResolver(fn () => 1);
    }

    public function test_meeting_generator_skips_holiday_and_is_idempotent(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $roomId = $this->room(40);
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id, 'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id, 'ruang_id' => $roomId, 'hari' => 1,
            'mulai' => '08:00', 'selesai' => '09:40', 'sks' => 2,
        ];
        $schedule = JadwalMingguan::create([...$attributes, 'code' => 'JMG-GEN', 'fingerprint' => JadwalMingguan::fingerprint($attributes)]);
        KalenderAkademik::create([
            'taka_id' => $data['periodId'], 'kategori' => 'libur', 'nama' => 'Hari Libur',
            'mulai_at' => '2026-08-03 00:00:00', 'selesai_at' => '2026-08-03 23:59:59', 'dipublikasikan' => true,
        ]);
        $generator = app(MeetingGeneratorService::class);
        $preview = $generator->preview($schedule, '2026-08-01', '2026-08-31', 2);

        $this->assertSame(['2026-08-10', '2026-08-17'], $preview->pluck('tanggal')->all());
        $this->assertSame(['created' => 2, 'skipped' => 0], $generator->generate($schedule, '2026-08-01', '2026-08-31', 2));
        $this->assertSame(['created' => 0, 'skipped' => 2], $generator->generate($schedule, '2026-08-01', '2026-08-31', 2));
        $this->assertSame(2, PertemuanKuliah::where('jadwal_mingguan_id', $schedule->id)->count());
        $this->assertDatabaseCount('jadwal_kuliahs', 2);
        $this->assertSame([2], DB::table('jadwal_kuliahs')->distinct()->pluck('bsks')->map(fn ($credits) => (int) $credits)->all());
    }

    public function test_attendance_requires_approved_krs_participant(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $krsService = app(KrsService::class);
        $krs = $krsService->add($krsService->forRegistration($data['registration']), $offering);
        $krsService->submit($krs);
        $krsService->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id, 'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id, 'ruang_id' => $this->room(40), 'hari' => 1,
            'mulai' => '08:00', 'selesai' => '09:40',
        ];
        $schedule = JadwalMingguan::create([...$attributes, 'code' => 'JMG-ABS', 'fingerprint' => JadwalMingguan::fingerprint($attributes)]);
        app(MeetingGeneratorService::class)->generate($schedule, '2026-08-01', '2026-08-31', 1);
        $meeting = $schedule->pertemuans()->firstOrFail();

        $item = app(AttendanceEligibilityService::class)->eligibleKrsItem($meeting, $data['student']);
        $this->assertSame($offering->id, $item->penawaran_mata_kuliah_id);
        $this->assertSame(1, app(ScheduleNotificationService::class)->changed($schedule, 'Jadwal kuliah berubah.'));
        $this->assertDatabaseHas('notifications', [
            'student_id' => $data['student']->id, 'type' => 'jadwal', 'desc' => 'Jadwal kuliah berubah.',
        ]);

        $outsider = Mahasiswa::create([
            'mhs_nim' => '26999', 'mhs_name' => 'Mahasiswa Luar', 'mhs_code' => 'MHS999',
            'mhs_user' => 'mhs999', 'password' => 'secret', 'mhs_mail' => 'mhs999@example.test', 'mhs_phone' => '0899999999',
        ]);
        $this->expectException(ValidationException::class);
        app(AttendanceEligibilityService::class)->eligibleKrsItem($meeting, $outsider);
    }

    public function test_lecturer_can_manage_attendance_only_for_an_owned_weekly_meeting(): void
    {
        $data = $this->academicData();
        DB::table('tahun_akademiks')->where('id', $data['periodId'])->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $krsService = app(KrsService::class);
        $krs = $krsService->add($krsService->forRegistration($data['registration']), $offering);
        $krsService->submit($krs);
        $krsService->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $attributes = [
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $this->room(40, 'PRESENSI-DOSEN'),
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:40',
            'sks' => 2,
        ];
        $schedule = JadwalMingguan::create([
            ...$attributes,
            'code' => 'JMG-PRESENSI-DOSEN',
            'fingerprint' => JadwalMingguan::fingerprint($attributes),
        ]);
        app(MeetingGeneratorService::class)->generate($schedule, '2026-08-01', '2026-08-31', 1);
        $meeting = $schedule->pertemuans()->firstOrFail();
        $otherLecturer = $this->lecturer('1002', 'Dosen Lain');
        $payload = [
            'presences' => [
                $data['student']->id => ['status' => 'H', 'description' => 'Hadir tepat waktu'],
            ],
        ];
        DB::table('web_settings')->insert([
            'id' => 1,
            'school_apps' => 'SIAKAD',
            'school_name' => 'Kampus Test',
            'school_head' => 'Ketua Test',
            'school_link' => 'https://example.test',
            'school_desc' => 'Kampus pengujian',
            'school_email' => 'kampus@example.test',
            'school_phone' => '0800000000',
            'social_fb' => '-',
            'social_ig' => '-',
            'social_in' => '-',
            'social_tw' => '-',
        ]);

        $this->actingAs($data['advisor'], 'dosen');
        \Illuminate\Support\Facades\Auth::shouldUse('web');
        $meetingListResponse = $this->get(route('dosen.akademik.jadwal-meetings', $schedule->code));
        $meetingListResponse
            ->assertOk()
            ->assertSee('Presensi')
            ->assertSee('Evaluasi')
            ->assertSee(route('dosen.akademik.jadwal-view-feedback', $meeting->code), false);

        $evaluationRatings = collect(config('lecturer_evaluation.sections'))
            ->flatMap(fn (array $section) => array_fill_keys(array_keys($section['questions']), 4))
            ->all();
        $evaluationNarratives = collect(config('lecturer_evaluation.narratives'))
            ->mapWithKeys(fn (string $question, string $key) => [$key => 'Masukan '.$key])
            ->all();
        FBPerkuliahan::create([
            'fb_users_code' => $data['student']->mhs_code,
            'fb_jakul_code' => $meeting->code,
            'fb_code' => 'FEEDBACK-DOSEN-TEST',
            'fb_score' => 'Sangat Puas',
            'fb_reason' => 'Masukan pengujian',
            'fb_answers' => ['ratings' => $evaluationRatings, 'narratives' => $evaluationNarratives],
            'fb_average_score' => 4,
        ]);
        $this->get(route('dosen.akademik.jadwal-view-feedback', $meeting->code))
            ->assertOk()
            ->assertSee('Rata-rata kinerja dosen')
            ->assertSee('Kompetensi Pedagogik')
            ->assertSee('Detail Indikator Penilaian')
            ->assertSee('Responden anonim #1')
            ->assertDontSee($data['student']->mhs_name);

        $this->actingAs($data['student'], 'mahasiswa');
        $studentMeetingsView = app(\App\Http\Controllers\Mahasiswa\HomeController::class)->jadkulMeetings(
            $schedule->code,
            app(AcademicPeriodContext::class),
            app(\App\Services\Academic\StudentAcademicContext::class)
        );
        $this->assertSame('mahasiswa.pages.mhs-jadkul-pertemuan', $studentMeetingsView->name());
        $this->assertSame($schedule->id, $studentMeetingsView->getData()['schedule']->id);
        $this->assertCount(1, $studentMeetingsView->getData()['schedule']->pertemuans);

        $unauthorizedResponse = $this->actingAs($otherLecturer, 'dosen')
            ->patch(route('dosen.akademik.jadwal-meeting-attendance-update', [$schedule->code, $meeting->code]), $payload);
        $unauthorizedResponse->assertRedirect(route('error.notfound'));
        $this->actingAs($data['advisor'], 'dosen')
            ->patch(route('dosen.akademik.jadwal-meeting-attendance-update', [$schedule->code, $meeting->code]), $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('absensi_mahasiswas', [
            'pertemuan_kuliah_id' => $meeting->id,
            'krs_item_id' => $krs->items()->sole()->id,
            'author_id' => $data['student']->id,
            'jadkul_code' => $meeting->code,
            'absen_type' => 'H',
            'absen_desc' => 'Hadir tepat waktu',
        ]);
        $this->assertSame(PertemuanKuliah::STATUS_COMPLETED, $meeting->fresh()->status);

        $payload['presences'][$data['student']->id] = ['status' => 'I', 'description' => 'Izin'];
        $this->actingAs($data['advisor'], 'dosen')
            ->patch(route('dosen.akademik.jadwal-meeting-attendance-update', [$schedule->code, $meeting->code]), $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame(1, AbsensiMahasiswa::where('pertemuan_kuliah_id', $meeting->id)->count());
        $this->assertDatabaseHas('absensi_mahasiswas', [
            'pertemuan_kuliah_id' => $meeting->id,
            'author_id' => $data['student']->id,
            'absen_type' => 'I',
            'absen_desc' => 'Izin',
        ]);
    }

    public function test_krs_list_filters_rows_and_exports_reference_template_columns(): void
    {
        $data = $this->academicData();
        $data['master']->update(['code' => 'PAI101']);
        $offering = PenawaranMataKuliah::create([
            ...$this->offeringAttributes($data),
            'code' => 'PAI101-1A',
        ]);
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        NilaiMahasiswa::create([
            'mahasiswa_id' => $data['student']->id,
            'taka_id' => $data['periodId'],
            'mata_kuliah_id' => null,
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'nilai' => 'A',
        ]);
        $administrator = $this->webAdministrator('KRSLISTADMIN');
        $this->actingAs($administrator);
        session([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);

        $request = Request::create('/web-admin/academic/krs-list', 'GET', [
            'q' => '26001',
            'status' => Krs::STATUS_APPROVED,
            'nilai' => 'A',
        ]);
        $request->setUserResolver(fn () => $administrator);
        $view = app(\App\Http\Controllers\Admin\KrsListController::class)
            ->index($request, app(AcademicPeriodContext::class));

        $this->assertSame(1, $view->getData()['items']->total());
        $this->assertSame('26001', $view->getData()['items']->first()->nim);
        $this->assertSame('A', $view->getData()['items']->first()->nilai_huruf);

        $export = app(\App\Http\Controllers\Admin\KrsListController::class)
            ->export($request, app(AcademicPeriodContext::class));
        ob_start();
        $export->sendContent();
        $content = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'krs-list-').'.xlsx';
        file_put_contents($path, $content);

        try {
            $rows = (new FastExcel)->import($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(1, $rows);
        $this->assertSame([
            'NIM', 'Nama', 'Semester', 'Kode Mata Kuliah', 'Nama Mata Kuliah', 'Nama Kelas',
            'Kode Prodi', 'Nama Prodi', 'Nilai Huruf', 'Nilai Indeks', 'Nilai Angka',
        ], array_keys($rows->first()));
        $this->assertSame('4.00', $rows->first()['Nilai Indeks']);
    }

    public function test_non_academic_staff_cannot_access_krs_list_controller(): void
    {
        $data = $this->academicData();
        $staff = User::create([
            'type' => 4,
            'code' => 'DEPARTMENTADMIN',
            'name' => 'Departement Admin',
            'user' => 'departmentadmin',
            'phone' => '081234567890',
            'email' => 'departmentadmin@example.test',
            'password' => 'secret',
            'status' => 1,
        ]);
        $this->actingAs($staff);
        session([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);
        $request = Request::create('/admin/krs-list');
        $request->setUserResolver(fn () => $staff);

        try {
            app(\App\Http\Controllers\Admin\KrsListController::class)
                ->index($request, app(AcademicPeriodContext::class));
            $this->fail('Staf non-akademik seharusnya tidak dapat membuka List KRS.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_teaching_lecturer_list_includes_assistants_and_exports_reference_columns(): void
    {
        $data = $this->academicData();
        $data['master']->update(['code' => 'PAI102']);
        $assistant = $this->lecturer('1002', 'Dosen Pendamping');
        PenawaranMataKuliah::create([
            ...$this->offeringAttributes($data),
            'code' => 'PAI102-1A',
            'dosen_pendamping_1_id' => $assistant->id,
        ]);
        $administrator = $this->webAdministrator('LECTURERLISTADMIN');
        $this->actingAs($administrator);
        session([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);

        $allRequest = Request::create('/web-admin/academic/dosen-pengajar-list');
        $allRequest->setUserResolver(fn () => $administrator);
        $controller = app(\App\Http\Controllers\Admin\DosenPengajarListController::class);
        $view = $controller->index($allRequest, app(AcademicPeriodContext::class));

        $this->assertSame(2, $view->getData()['items']->total());
        $this->assertSame(['pendamping_1', 'utama'], $view->getData()['items']->pluck('peran')->sort()->values()->all());

        $request = Request::create('/web-admin/academic/dosen-pengajar-list', 'GET', [
            'dosen_id' => $assistant->id,
            'peran' => 'pendamping_1',
        ]);
        $request->setUserResolver(fn () => $administrator);
        $export = $controller->export($request, app(AcademicPeriodContext::class));
        ob_start();
        $export->sendContent();
        $content = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'dosen-pengajar-list-').'.xlsx';
        file_put_contents($path, $content);

        try {
            $rows = (new FastExcel)->import($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(1, $rows);
        $this->assertSame([
            'Semester', 'NIDN', 'NUPTK', 'Nama Dosen', 'Kode Matakuliah', 'Nama Matakuliah',
            'Nama Kelas', 'Tatap Muka', 'Tatap Muka Realisasi', 'Kode Prodi', 'Nama Prodi',
            'Sks Ajar', 'Jenis Evaluasi',
        ], array_keys($rows->first()));
        $this->assertSame('Dosen Pendamping', $rows->first()['Nama Dosen']);
        $this->assertSame(1, $rows->first()['Jenis Evaluasi']);
    }

    public function test_academic_dashboard_metrics_follow_selected_period(): void
    {
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        app(KrsService::class)->add(
            app(KrsService::class)->forRegistration($data['registration']),
            $offering
        );
        $roomId = $this->room(40, 'DASHBOARD');
        $scheduleAttributes = [
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $data['classId'],
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $roomId,
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '09:40',
        ];
        $schedule = JadwalMingguan::create([
            ...$scheduleAttributes,
            'code' => 'DASHBOARD-SCHEDULE',
            'fingerprint' => JadwalMingguan::fingerprint($scheduleAttributes),
        ]);
        PertemuanKuliah::create([
            'jadwal_mingguan_id' => $schedule->id,
            'dosen_id' => $data['advisor']->id,
            'ruang_id' => $roomId,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-08-03',
            'mulai' => '08:00',
            'selesai' => '09:40',
            'metode' => 'tatap_muka',
            'status' => PertemuanKuliah::STATUS_SCHEDULED,
            'code' => 'DASHBOARD-MEETING',
        ]);
        $academicStaff = User::create([
            'type' => 3,
            'code' => 'ACADEMICDASHBOARD',
            'name' => 'Staf Akademik',
            'user' => 'academicdashboard',
            'phone' => '081234567891',
            'email' => 'academicdashboard@example.test',
            'password' => 'secret',
            'status' => 1,
        ]);
        $this->actingAs($academicStaff);
        session([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);

        $dashboard = app(\App\Services\Academic\AcademicDashboardService::class)
            ->forUser($academicStaff);

        $this->assertSame(1, $dashboard['registrations']);
        $this->assertSame(1, $dashboard['activeStudents']);
        $this->assertSame(1, $dashboard['offerings']);
        $this->assertSame(1, $dashboard['weeklySchedules']);
        $this->assertSame(1, $dashboard['meetings']);
        $this->assertSame(1, $dashboard['krsStatuses'][Krs::STATUS_DRAFT]);
        $this->assertSame(0, $dashboard['withoutKrs']);
        $this->assertSame(0, $dashboard['offeringsWithoutSchedule']);

        $html = view('user.academic.home-dashboard', [
            'academicDashboard' => $dashboard,
            'prefix' => 'academic.',
        ])->render();
        $this->assertStringContainsString('Ruang Kerja Akademik', $html);
        $this->assertStringContainsString('1 jadwal mingguan', $html);
    }

    public function test_openfeeder_grade_import_stores_all_grade_forms_and_rejects_invalid_rows(): void
    {
        (require database_path('migrations/2026_09_13_000001_add_numeric_grades_to_nilai_mahasiswas.php'))->up();
        $data = $this->academicData();
        $data['master']->update(['code' => 'PAI.01']);
        DB::table('kelas')->where('id', $data['classId'])->update(['name' => 'PAI I A']);
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $krsService = app(KrsService::class);
        $krs = $krsService->add($krsService->forRegistration($data['registration']), $offering);
        $krsService->submit($krs);
        $krsService->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $administrator = $this->webAdministrator('OPENFEEDERGRADE');
        $row = [
            'NIM' => $data['student']->mhs_nim, 'Nama Mahasiswa' => $data['student']->mhs_name,
            'Kode Mata Kuliah' => 'PAI.01', 'Nama Mata Kuliah' => $data['master']->name,
            'Semester' => '20261', 'Nama Kelas' => 'A', 'Nilai Huruf' => 'B',
            'Nilai Indeks' => 3, 'Nilai Angka' => 80, 'Kode Prodi' => '86208',
        ];
        $path = sys_get_temp_dir().'/openfeeder-grades-'.uniqid().'.xlsx';
        try {
            (new FastExcel(collect([$row])))->export($path);
            $file = new UploadedFile($path, 'nilai.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $this->actingAs($administrator)->post(route('web-admin.nilai-import.store'), ['periode' => '20261', 'import' => $file])
                ->assertRedirect(route('web-admin.nilai-import.index'))->assertSessionHasNoErrors();
        } finally {
            @unlink($path);
        }
        $grade = NilaiMahasiswa::where('penawaran_mata_kuliah_id', $offering->id)->sole();
        $this->assertSame('B', $grade->nilai);
        $this->assertSame('3.00', $grade->nilai_indeks);
        $this->assertSame('80.00', $grade->nilai_angka);
        $service = app(\App\Services\Imports\NilaiOpenFeederImportService::class);
        $period = \App\Models\TahunAkademik::findOrFail($data['periodId']);
        foreach ([
            [$row, [...$row, 'NIM' => 'UNKNOWN']],
            [[...$row, 'Nilai Angka' => 101]],
            [[...$row, 'Semester' => '20251']],
            [$row, $row],
        ] as $invalidRows) {
            try {
                $service->import(collect($invalidRows), $period);
                $this->fail('Invalid grade import was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('import', $exception->errors());
            }
            $this->assertSame(1, NilaiMahasiswa::count());
            $this->assertSame('80.00', $grade->fresh()->nilai_angka);
        }
        $service->import(collect([[...$row, 'Nilai Angka' => 90, 'Nilai Indeks' => 4, 'Nilai Huruf' => 'A']]), $period);
        $this->assertSame(1, NilaiMahasiswa::count());
        $this->assertSame('90.00', $grade->fresh()->nilai_angka);
        $krs->update(['status' => Krs::STATUS_DRAFT]);
        try {
            $service->import(collect([$row]), $period);
            $this->fail('Unapproved KRS was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('import', $exception->errors());
        }
        $period->update(['status' => 'closed']);
        try {
            $service->import(collect([$row]), $period);
            $this->fail('Closed period was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('import', $exception->errors());
        }
        $administrator->update(['type' => 4]);
        $this->actingAs($administrator)->get(route('web-admin.nilai-import.index'))->assertRedirect(route('error.access'));
    }

    public function test_grade_page_edits_and_round_trips_all_three_values(): void
    {
        (require database_path('migrations/2026_09_13_000001_add_numeric_grades_to_nilai_mahasiswas.php'))->up();
        $data = $this->academicData();
        $offering = PenawaranMataKuliah::create($this->offeringAttributes($data));
        $service = app(KrsService::class);
        $krs = $service->add($service->forRegistration($data['registration']), $offering);
        $service->submit($krs);
        $service->decide($krs->fresh(), $data['advisor'], Krs::STATUS_APPROVED, null);
        $this->actingAs($this->webAdministrator('THREEGRADEADMIN'));
        $this->withSession([AcademicPeriodContext::SESSION_KEY => $data['periodId']]);
        $studentId = $data['student']->id;
        $url = route('web-admin.master.penawaran-grades-store', $offering);
        $payload = ['mahasiswa_id' => $studentId, 'nilai' => 'B', 'nilai_indeks' => '3.00', 'nilai_angka' => '82.50'];
        $this->post($url, ['_form' => 'manual-nilai', 'nilai' => [$studentId => $payload]])
            ->assertSessionHasNoErrors()->assertRedirect(route('web-admin.master.penawaran-grades', $offering));
        $grade = NilaiMahasiswa::where('penawaran_mata_kuliah_id', $offering->id)->sole();
        $this->assertSame('3.00', $grade->nilai_indeks);
        $this->assertSame('82.50', $grade->nilai_angka);
        $view = app(\App\Http\Controllers\Admin\PenawaranMataKuliahController::class)
            ->grades($offering, app(AcademicPeriodContext::class));
        $template = str_replace("@extends('base.base-dash-index')", '', file_get_contents(resource_path('views/user/admin/master/admin-matkul-nilai.blade.php')));
        $html = \Illuminate\Support\Facades\Blade::render($template."@yield('content')", $view->getData());
        foreach (['Nilai Huruf', 'Nilai Indeks', 'Nilai Angka', 'value="3.00"', 'value="82.50"'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->post($url, ['nilai' => [$studentId => [...$payload, 'nilai_indeks' => 5, 'nilai_angka' => -1]]])
            ->assertSessionHasErrors(["nilai.{$studentId}.nilai_indeks", "nilai.{$studentId}.nilai_angka"]);
        $this->assertSame('82.50', $grade->fresh()->nilai_angka);
        $export = $this->get(route('web-admin.master.penawaran-grades-export', $offering))->assertOk();
        $path = sys_get_temp_dir().'/three-grade-export-'.uniqid().'.xlsx';
        try {
            file_put_contents($path, $export->streamedContent());
            $row = (new FastExcel)->import($path)->first();
            $this->assertSame([
                'NIM', 'Nama Mahasiswa', 'Kode Mata Kuliah', 'Nama Mata Kuliah', 'Semester',
                'Nama Kelas', 'Nilai Huruf', 'Nilai Indeks', 'Nilai Angka', 'Kode Prodi', 'Nama Prodi',
            ], array_keys($row));
            $this->assertSame('20261', (string) $row['Semester']);
            $this->assertSame('86208', (string) $row['Kode Prodi']);

            $this->assertEquals(3.00, $row['Nilai Indeks']);
            $this->assertEquals(82.5, $row['Nilai Angka']);
            $grade->update(['nilai_indeks' => 0, 'nilai_angka' => 0]);
            $file = new UploadedFile($path, 'nilai.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $this->post(route('web-admin.master.penawaran-grades-import', $offering), ['_form' => 'import-nilai', 'import' => $file])
                ->assertSessionHasNoErrors();
            $this->assertSame('3.00', $grade->fresh()->nilai_indeks);
            $this->assertSame('82.50', $grade->fresh()->nilai_angka);
            $numericOnly = [...$row, 'Nilai Huruf' => '', 'Nilai Indeks' => '', 'Nilai Angka' => 90];
            (new FastExcel(collect([$numericOnly])))->export($path);
            $this->post(route('web-admin.master.penawaran-grades-import', $offering), [
                '_form' => 'import-nilai',
                'import' => new UploadedFile($path, 'nilai.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ])->assertSessionHasNoErrors();
            $this->assertSame('A', $grade->fresh()->nilai);
            $this->assertSame('4.00', $grade->fresh()->nilai_indeks);
            foreach (['Semester' => '20251', 'Kode Mata Kuliah' => 'OTHER', 'Nama Kelas' => 'OTHER', 'Kode Prodi' => '88204'] as $column => $value) {
                (new FastExcel(collect([[...$numericOnly, $column => $value]])))->export($path);
                $this->post(route('web-admin.master.penawaran-grades-import', $offering), [
                    '_form' => 'import-nilai',
                    'import' => new UploadedFile($path, 'nilai.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])->assertSessionHasErrors('import');
                $this->assertSame('90.00', $grade->fresh()->nilai_angka);
            }

        } finally {
            @unlink($path);
        }
        $this->post($url, ['nilai' => [$studentId => ['mahasiswa_id' => $studentId, 'nilai_angka' => 90]]])
            ->assertSessionHasNoErrors();
        $this->assertSame('A', $grade->fresh()->nilai);
        $this->assertSame('4.00', $grade->fresh()->nilai_indeks);
        $this->post($url, ['nilai' => [$studentId => [...$payload, 'nilai' => 'A', 'nilai_indeks' => 4, 'nilai_angka' => 0]]])
            ->assertSessionHasNoErrors();
        $this->assertSame('E', $grade->fresh()->nilai);
        $this->assertSame('0.00', $grade->fresh()->nilai_indeks);
        $this->post($url, ['nilai' => [$studentId => [...$payload, 'nilai_indeks' => '', 'nilai_angka' => '']]])
            ->assertSessionHasNoErrors();
        $this->assertNull($grade->fresh()->nilai_indeks);
        $this->assertNull($grade->fresh()->nilai_angka);
    }

    private function academicData(): array
    {
        $advisor = $this->lecturer('1001', 'Dosen Wali');
        $periodId = DB::table('tahun_akademiks')->insertGetId([
            'name' => '2026/2027 Ganjil', 'code' => '20261', 'semester' => 1, 'year_start' => 2026,
            'year_end' => 2027, 'term' => 'ganjil', 'status' => 'active', 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $programId = DB::table('program_studis')->insertGetId([
            'faku_id' => 1, 'name' => 'Pendidikan Agama Islam', 'cnim' => '01', 'code' => 'PAI',
            'slug' => 'pai', 'head_id' => $advisor->id, 'title' => 'S.Pd', 'level' => 'S1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('kelas')->insertGetId([
            'taka_id' => $periodId, 'pstudi_id' => $programId, 'dosen_id' => $advisor->id,
            'capacity' => 40, 'name' => 'PAI 1A', 'code' => 'PAI-1A-2026', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $curriculumId = DB::table('kurikulums')->insertGetId([
            'name' => 'Kurikulum 2026', 'code' => 'K26', 'desc' => '-', 'year_start' => 2026,
            'year_ended' => 2030, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = Mahasiswa::create([
            'taka_id' => $periodId, 'class_id' => $classId, 'mhs_stat' => 1, 'mhs_nim' => '26001',
            'mhs_name' => 'Mahasiswa Uji', 'mhs_code' => 'MHS001', 'mhs_user' => 'mhs001', 'password' => 'secret',
            'mhs_mail' => 'mhs001@example.test', 'mhs_phone' => '0811111111',
        ]);
        $registration = RegistrasiMahasiswa::create([
            'mahasiswa_id' => $student->id, 'taka_id' => $periodId, 'semester_mahasiswa' => 1,
            'status_akademik' => 'aktif', 'status_registrasi' => 'terdaftar', 'kelas_id' => $classId,
            'dosen_wali_id' => $advisor->id, 'batas_sks' => 24,
        ]);
        $master = MasterMataKuliah::create(['program_studi' => 'PAI', 'semester' => 1, 'name' => 'Pengantar Studi Islam', 'sks' => 3]);
        KalenderAkademik::create([
            'taka_id' => $periodId, 'kategori' => 'krs', 'nama' => 'Pengisian KRS',
            'mulai_at' => now()->subDay(), 'selesai_at' => now()->addDay(), 'dipublikasikan' => true,
        ]);

        return compact('advisor', 'student', 'registration', 'master', 'periodId', 'programId', 'classId', 'curriculumId');
    }

    private function webAdministrator(string $code): User
    {
        return User::create([
            'type' => 0,
            'code' => $code,
            'name' => 'Web Administrator',
            'user' => strtolower($code),
            'phone' => '08'.random_int(1000000000, 9999999999),
            'email' => strtolower($code).'@example.test',
            'password' => 'secret',
            'status' => 1,
        ]);
    }

    private function offeringAttributes(array $data): array
    {
        return [
            'master_mata_kuliah_id' => $data['master']->id, 'taka_id' => $data['periodId'],
            'pstudi_id' => $data['programId'], 'kuri_id' => $data['curriculumId'], 'kelas_id' => $data['classId'],
            'dosen_utama_id' => $data['advisor']->id, 'code' => 'OF-'.uniqid(), 'sks' => 3, 'kapasitas' => 40,
        ];
    }

    private function lecturer(string $nidn, string $name): Dosen
    {
        return Dosen::create([
            'dsn_stat' => 1, 'dsn_nidn' => $nidn, 'dsn_name' => $name, 'dsn_code' => 'D'.$nidn,
            'dsn_user' => 'd'.$nidn, 'password' => 'secret', 'dsn_mail' => $nidn.'@example.test',
            'dsn_phone' => '08'.$nidn,
        ]);
    }

    private function room(int $capacity, string $code = 'R001'): int
    {
        $buildingId = DB::table('gedungs')->insertGetId([
            'name' => 'Gedung '.$code, 'code' => 'G'.$code,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('ruangs')->insertGetId([
            'gedu_id' => $buildingId, 'type' => 0, 'floor' => 1, 'kapasitas' => $capacity,
            'name' => 'Ruang '.$code, 'code' => $code, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
