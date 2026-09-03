<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Services\Academic\PeriodReadinessService;
use Database\Seeders\DemoDuaTahunAkademikSeeder;
use Database\Seeders\DemoDuaTahunLaluAkademikSeeder;
use Database\Seeders\DemoSatuTahunLaluAkademikSeeder;
use Database\Seeders\DosenSeeder;
use Database\Seeders\ResetDanSeedDataAkademikSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DemoDuaTahunAkademikSeederTest extends TestCase
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
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->tinyInteger('status')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        foreach ($this->migrationFiles() as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        DB::table('fakultas')->insert([
            'code' => 'FTK-EXISTING',
            'name' => 'Fakultas Tarbiyah Existing',
            'head_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('program_studis')->insert([
            'faku_id' => DB::table('fakultas')->where('code', 'FTK-EXISTING')->value('id'),
            'name' => 'Pendidikan Agama Islam Existing',
            'cnim' => '20',
            'code' => '86208',
            'slug' => 'pendidikan-agama-islam-existing',
            'head_id' => 0,
            'title' => 'S.Pd.',
            'level' => 'S1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_seeder_creates_three_complete_academic_years_and_is_idempotent(): void
    {
        $this->seed(DemoDuaTahunAkademikSeeder::class);
        $this->seed(DemoDuaTahunAkademikSeeder::class);

        $this->assertSame(6, TahunAkademik::query()->whereIn('code', [
            '232401',
            '232402',
            '242501',
            '242502',
            '252601',
            '252602',
        ])->count());
        $this->assertDatabaseHas('tahun_akademiks', [
            'code' => '252602',
            'status' => TahunAkademik::STATUS_ACTIVE,
            'is_active' => 1,
        ]);
        $this->assertSame(3, DB::table('tahun_akademik')->count());
        $this->assertSame(0, TahunAkademik::query()->whereNull('tid')->count());
        $this->assertSame(1, TahunAkademik::query()->where('is_active', true)->count());

        $this->assertSame(39, DB::table('dosens')->count());
        $this->assertDatabaseHas('dosens', [
            'dsn_nidn' => '2008017601',
            'dsn_name' => 'NIDA NURJUNAEDAH',
            'dsn_code' => 'zlKgc9W30aYt',
        ]);
        $this->assertDatabaseHas('dosens', [
            'dsn_nidn' => '2126099101',
            'dsn_name' => "DEDEH SYA'ADATUL KAMILAH",
            'dsn_code' => 'MP28JoFKebH0',
        ]);
        $this->assertSame(1, DB::table('fakultas')->count());
        $this->assertSame(1, DB::table('program_studis')->count());
        $this->assertSame(
            [86208 => 62, 86233 => 62, 88204 => 62],
            DB::table('master_mata_kuliahs')
                ->selectRaw('program_studi, COUNT(*) as total')
                ->groupBy('program_studi')
                ->orderBy('program_studi')
                ->pluck('total', 'program_studi')
                ->map(fn ($total): int => (int) $total)
                ->all()
        );
        $this->assertDatabaseHas('master_mata_kuliahs', [
            'program_studi' => '86208',
            'code' => 'PAI.62',
            'name' => 'Munaqasyah',
            'sks' => 7,
            'semester' => 8,
        ]);
        $this->assertDatabaseHas('master_mata_kuliahs', [
            'program_studi' => '88204',
            'code' => 'PGBA.74',
            'name' => 'KKM',
            'sks' => 6,
            'semester' => 5,
        ]);
        $this->assertSame(
            DB::table('fakultas')->where('code', 'FTK-EXISTING')->value('id'),
            DB::table('program_studis')->where('code', '86208')->value('faku_id')
        );
        $this->assertSame(36, Mahasiswa::query()->where('mhs_code', 'like', 'DEMO-%')->count());
        $this->assertSame(
            [2023 => 12, 2024 => 12, 2025 => 12],
            Mahasiswa::query()
                ->where('mhs_code', 'like', 'DEMO-%')
                ->pluck('years_id')
                ->countBy()
                ->sortKeys()
                ->all()
        );
        foreach ([2023, 2024, 2025] as $entryYear) {
            $this->assertSame(12, Mahasiswa::query()
                ->where('mhs_code', 'like', 'DEMO-%')
                ->where('years_id', $entryYear)
                ->whereDate('mhs_register_date', $entryYear.'-08-01')
                ->count());
        }
        $this->assertSame(24, DB::table('kelas')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(216, DB::table('mata_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(216, DB::table('jadwal_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(1296, DB::table('absensi_mahasiswas')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(108, DB::table('hasil_studis')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(24, DB::table('student_tasks')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(2, DB::table('users')->where('code', 'like', 'DEMO-STAFF-%')->count());
        $this->assertSame(24, DB::table('kalender_akademiks')->where('nama', 'like', '% Demo')->count());
        $this->assertSame(216, DB::table('penawaran_mata_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $classesWithRepeatedLecturers = DB::table('penawaran_mata_kuliahs')
            ->where('code', 'like', 'DEMO-%')
            ->get(['kelas_id', 'dosen_utama_id'])
            ->groupBy('kelas_id')
            ->filter(fn ($offerings): bool => $offerings->count() !== $offerings->pluck('dosen_utama_id')->unique()->count());
        $this->assertCount(0, $classesWithRepeatedLecturers);
        $this->assertSame(216, DB::table('jadwal_mingguans')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(0, DB::table('jadwal_mingguans')
            ->where('code', 'like', 'DEMO-%')
            ->whereNotBetween('hari', [4, 6])
            ->count());
        $this->assertSame(0, DB::table('jadwal_mingguans')
            ->where('code', 'like', 'DEMO-%')
            ->where(fn ($query) => $query->where('mulai', '<', '13:00:00')->orWhere('selesai', '>', '20:00:00'))
            ->count());
        $this->assertSame(864, DB::table('pertemuan_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(144, DB::table('krs')->count());
        $this->assertSame(1296, DB::table('krs_items')->count());
        $incompleteKrs = DB::table('krs')
            ->join('registrasi_mahasiswas', 'registrasi_mahasiswas.id', '=', 'krs.registrasi_mahasiswa_id')
            ->select(['krs.id', 'registrasi_mahasiswas.kelas_id'])
            ->get()
            ->filter(function (object $krs): bool {
                $availableOfferingIds = DB::table('penawaran_mata_kuliahs')
                    ->where('kelas_id', $krs->kelas_id)
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();
                $selectedOfferingIds = DB::table('krs_items')
                    ->where('krs_id', $krs->id)
                    ->orderBy('penawaran_mata_kuliah_id')
                    ->pluck('penawaran_mata_kuliah_id')
                    ->all();

                return $availableOfferingIds !== $selectedOfferingIds;
            });
        $this->assertCount(0, $incompleteKrs);
        $this->assertSame(6, DB::table('template_tagihans')->where('name', 'like', 'UKT Demo %')->count());
        $this->assertSame(144, DB::table('tagihan_kuliahs')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(144, DB::table('history_tagihans')->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(6, DB::table('penerbitan_tagihan_batches')->count());
        $this->assertSame(6, DB::table('period_readiness_snapshots')->where('purpose', 'demo_seed')->count());
        $this->assertSame(6, DB::table('period_publications')->count());
        $this->assertSame(6, DB::table('academic_workflow_audits')->where('event', 'period.demo_seeded')->count());

        $demoStudentIds = Mahasiswa::query()
            ->where('mhs_code', 'like', 'DEMO-%')
            ->pluck('id');
        $latestPeriodId = TahunAkademik::where('code', '252602')->value('id');
        $this->assertSame(144, RegistrasiMahasiswa::query()->whereIn('mahasiswa_id', $demoStudentIds)->count());
        foreach (['232401', '242501', '252601'] as $firstSemesterPeriodCode) {
            $this->assertSame(
                12,
                RegistrasiMahasiswa::query()
                    ->where('taka_id', TahunAkademik::where('code', $firstSemesterPeriodCode)->value('id'))
                    ->where('semester_mahasiswa', 1)
                    ->count()
            );
        }
        $this->assertSame(0, RegistrasiMahasiswa::query()->whereIn('mahasiswa_id', $demoStudentIds)->where('status_akademik', RegistrasiMahasiswa::STATUS_AKADEMIK_DROP_OUT)->count());
        $this->assertSame(0, RegistrasiMahasiswa::query()->whereIn('mahasiswa_id', $demoStudentIds)->where('semester_mahasiswa', '>', 8)->where('status_akademik', RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF)->count());
        $this->assertSame(1296, DB::table('nilai_mahasiswas')->whereIn('mahasiswa_id', $demoStudentIds)->count());
        $this->assertSame(144, DB::table('student_scores')->whereIn('student_id', $demoStudentIds)->count());
        $this->assertSame(0, DB::table('nilai_mahasiswas')
            ->where('taka_id', $latestPeriodId)
            ->whereNotNull('nilai')
            ->count());
        $this->assertSame(0, DB::table('hasil_studis')->where('taka_id', $latestPeriodId)->count());
        $this->assertSame(0, DB::table('student_scores')
            ->join('student_tasks', 'student_tasks.id', '=', 'student_scores.stask_id')
            ->where('student_tasks.code', 'like', 'DEMO-TGS-252602-%')
            ->whereNotNull('student_scores.score')
            ->count());

        $firstStudent = Mahasiswa::query()->where('mhs_code', 'DEMO-MHS-86208-23-01')->firstOrFail();
        $this->assertSame(
            range(1, 6),
            $firstStudent->registrasiAkademik()->orderBy('semester_mahasiswa')->pluck('semester_mahasiswa')->all()
        );
        $this->assertSame(1, $firstStudent->raw_mhs_stat);
        $newestStudent = Mahasiswa::query()->where('mhs_code', 'DEMO-MHS-86208-25-01')->firstOrFail();
        $this->assertSame([1, 2], $newestStudent->registrasiAkademik()->orderBy('semester_mahasiswa')->pluck('semester_mahasiswa')->all());
        $this->assertSame(2025, $newestStudent->years_id);
        $this->assertSame(
            [2 => 12, 4 => 12, 6 => 12],
            RegistrasiMahasiswa::where('taka_id', $latestPeriodId)
                ->pluck('semester_mahasiswa')
                ->countBy()
                ->sortKeys()
                ->all()
        );
        $this->assertSame(6, DB::table('jadwal_mingguans')
            ->join('penawaran_mata_kuliahs', 'penawaran_mata_kuliahs.id', '=', 'jadwal_mingguans.penawaran_mata_kuliah_id')
            ->join('master_mata_kuliahs', 'master_mata_kuliahs.id', '=', 'penawaran_mata_kuliahs.master_mata_kuliah_id')
            ->where('jadwal_mingguans.code', 'like', 'DEMO-%')
            ->max('master_mata_kuliahs.semester'));
        $this->assertDatabaseHas('tahun_akademiks', ['code' => '252602', 'is_published' => 1]);
        $this->assertDatabaseHas('krs', ['registrasi_mahasiswa_id' => $newestStudent->registrasiAkademik()->where('taka_id', $latestPeriodId)->value('id'), 'status' => 'approved']);
        $this->assertDatabaseHas('history_tagihans', ['code' => 'DEMO-BYR-252602-86208-2', 'status' => 'pending']);
    }

    public function test_academic_purge_previews_then_removes_transactions_but_preserves_identities(): void
    {
        $this->seed(DemoDuaTahunAkademikSeeder::class);

        $this->artisan('academic:purge', ['--preview' => true])
            ->expectsOutputToContain('PREVIEW selesai')
            ->assertSuccessful();

        $this->assertSame(6, TahunAkademik::count());
        $this->assertSame(36, Mahasiswa::where('mhs_code', 'like', 'DEMO-%')->count());

        $this->artisan('academic:purge', ['--confirm' => true])
            ->expectsOutputToContain('Pembersihan selesai')
            ->assertSuccessful();

        $this->assertSame(0, TahunAkademik::count());
        $this->assertSame(0, DB::table('registrasi_mahasiswas')->count());
        $this->assertSame(0, DB::table('penawaran_mata_kuliahs')->count());
        $this->assertSame(0, DB::table('pertemuan_kuliahs')->count());
        $this->assertSame(0, DB::table('krs')->count());
        $this->assertSame(0, DB::table('tagihan_kuliahs')->count());
        $this->assertSame(36, Mahasiswa::where('mhs_code', 'like', 'DEMO-%')->count());
        $this->assertSame(39, DB::table('dosens')->count());
        $this->assertSame(2, DB::table('users')->where('code', 'like', 'DEMO-STAFF-%')->count());
        $this->assertSame(186, DB::table('master_mata_kuliahs')->count());
        $this->assertSame(0, Mahasiswa::where('mhs_code', 'like', 'DEMO-%')->where(fn ($query) => $query->where('taka_id', '!=', 0)->orWhere('class_id', '!=', 0))->count());
    }

    public function test_new_master_course_is_automatically_offered_and_taken_by_all_students_in_its_semester(): void
    {
        MasterMataKuliah::query()->create([
            'program_studi' => '86208',
            'code' => 'CUSTOM.01',
            'semester' => 1,
            'name' => 'Teknologi Pembelajaran',
            'sks' => 2,
        ]);

        $this->seed(DemoDuaTahunAkademikSeeder::class);

        $firstPeriodId = TahunAkademik::query()->where('code', '232401')->value('id');
        $firstSemesterKrsIds = DB::table('krs')
            ->join('registrasi_mahasiswas', 'registrasi_mahasiswas.id', '=', 'krs.registrasi_mahasiswa_id')
            ->where('registrasi_mahasiswas.taka_id', $firstPeriodId)
            ->where('registrasi_mahasiswas.semester_mahasiswa', 1)
            ->pluck('krs.id');

        $this->assertCount(12, $firstSemesterKrsIds);

        foreach ($firstSemesterKrsIds as $krsId) {
            $this->assertSame(10, DB::table('krs_items')->where('krs_id', $krsId)->count());
        }
    }

    public function test_reset_seeder_removes_old_academic_data_before_creating_the_three_year_demo(): void
    {
        DB::table('dosens')->insert([
            'dsn_stat' => 1,
            'dsn_nidn' => 'LOCAL-001',
            'dsn_name' => 'Dosen Master Lokal',
            'dsn_code' => 'LOCAL-DSN-001',
            'dsn_user' => 'dosen.master.lokal',
            'password' => Hash::make('RahasiaLokal!'),
            'dsn_mail' => 'dosen.master.lokal@example.test',
            'dsn_phone' => '080000009999',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Mahasiswa::query()->create([
            'taka_id' => 0,
            'years_id' => 2020,
            'class_id' => 0,
            'mhs_stat' => 1,
            'mhs_nim' => 'LAMA-001',
            'mhs_name' => 'Mahasiswa Lama',
            'mhs_code' => 'LAMA-MHS-001',
            'mhs_user' => 'mahasiswa.lama',
            'password' => bcrypt('password'),
            'mhs_mail' => 'mahasiswa.lama@example.test',
            'mhs_phone' => '080000000001',
        ]);

        $this->seed(ResetDanSeedDataAkademikSeeder::class);

        $this->assertDatabaseMissing('mahasiswas', ['mhs_code' => 'LAMA-MHS-001']);
        $this->assertSame(36, Mahasiswa::query()->where('mhs_code', 'like', 'DEMO-MHS-%')->count());
        $this->assertSame(6, TahunAkademik::query()->count());
        $this->assertSame(1, TahunAkademik::query()->where('is_active', true)->count());
        $this->assertSame(1, DB::table('program_studis')->count());
        $this->assertSame(40, DB::table('dosens')->count());
        $this->assertDatabaseHas('dosens', [
            'dsn_nidn' => 'LOCAL-001',
            'dsn_name' => 'Dosen Master Lokal',
        ]);
        $this->assertSame(216, DB::table('penawaran_mata_kuliahs')->count());
        $this->assertSame(144, DB::table('krs')->count());
        $this->assertSame(144, DB::table('tagihan_kuliahs')->count());
    }

    public function test_dosen_seeder_uses_source_identity_and_nidn_as_password(): void
    {
        $existingPassword = Hash::make('KataSandiTetap!');
        DB::table('dosens')->insert([
            'dsn_stat' => 0,
            'dsn_nidn' => '2008017601',
            'dsn_name' => 'Nama Sebelum Sinkronisasi',
            'dsn_code' => 'KODE-LAMA',
            'dsn_image' => 'dosen/foto-tetap.jpg',
            'dsn_user' => 'user-lama',
            'password' => $existingPassword,
            'dsn_mail' => 'lama@example.test',
            'dsn_phone' => '080000008888',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(DosenSeeder::class);
        $lecturer = DB::table('dosens')->where('dsn_nidn', '2008017601')->first();

        $this->assertSame(39, DB::table('dosens')->count());
        $this->assertSame('NIDA NURJUNAEDAH', $lecturer->dsn_name);
        $this->assertSame('zlKgc9W30aYt', $lecturer->dsn_code);
        $this->assertSame('2008017601', $lecturer->dsn_user);
        $this->assertSame('dosen/foto-tetap.jpg', $lecturer->dsn_image);
        $this->assertFalse(Hash::check('KataSandiTetap!', $lecturer->password));
        $this->assertTrue(Hash::check('2008017601', $lecturer->password));
    }

    public function test_seeder_creates_twelve_students_and_two_parallel_classes_for_each_study_program(): void
    {
        $facultyId = DB::table('fakultas')->value('id');
        $secondStudyProgramId = DB::table('program_studis')->insertGetId([
            'faku_id' => $facultyId,
            'name' => 'Pendidikan Bahasa Arab Existing',
            'cnim' => '21',
            'code' => '88204',
            'slug' => 'pendidikan-bahasa-arab-existing',
            'head_id' => 0,
            'title' => 'S.Pd.',
            'level' => 'S1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $thirdStudyProgramId = DB::table('program_studis')->insertGetId([
            'faku_id' => $facultyId,
            'name' => 'Pendidikan Islam Anak Usia Dini Existing',
            'cnim' => '22',
            'code' => '86233',
            'slug' => 'pendidikan-islam-anak-usia-dini-existing',
            'head_id' => 0,
            'title' => 'S.Pd.',
            'level' => 'S1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(ResetDanSeedDataAkademikSeeder::class);

        $studyProgramIds = [
            DB::table('program_studis')->where('code', '86208')->value('id'),
            $secondStudyProgramId,
            $thirdStudyProgramId,
        ];

        foreach ($studyProgramIds as $studyProgramId) {
            foreach ([2023, 2024, 2025] as $entryYear) {
                $studentCount = DB::table('mahasiswas')
                    ->join('registrasi_mahasiswas', 'registrasi_mahasiswas.mahasiswa_id', '=', 'mahasiswas.id')
                    ->join('kelas', 'kelas.id', '=', 'registrasi_mahasiswas.kelas_id')
                    ->where('kelas.pstudi_id', $studyProgramId)
                    ->where('mahasiswas.years_id', $entryYear)
                    ->distinct()
                    ->count('mahasiswas.id');

                $this->assertSame(12, $studentCount);
            }

            $firstPeriodId = TahunAkademik::query()->where('code', '252601')->value('id');
            $classes = DB::table('kelas')
                ->where('pstudi_id', $studyProgramId)
                ->where('taka_id', $firstPeriodId)
                ->where('code', 'like', '%-A2025-S1-%')
                ->pluck('id');

            $this->assertCount(2, $classes);
            $this->assertSame(
                [6, 6],
                $classes->map(fn (int $classId): int => DB::table('registrasi_mahasiswas')
                    ->where('kelas_id', $classId)
                    ->count())
                    ->sort()
                    ->values()
                    ->all()
            );

            $this->assertSame(
                2,
                DB::table('kelas')->whereIn('id', $classes)->distinct()->count('dosen_id')
            );

            $schedulesByClass = DB::table('jadwal_mingguans')
                ->whereIn('kelas_id', $classes)
                ->orderBy('hari')
                ->get()
                ->groupBy('kelas_id');
            $firstClassSchedules = $schedulesByClass->get($classes[0]);
            $secondClassSchedules = $schedulesByClass->get($classes[1]);

            $this->assertSame(1, $firstClassSchedules->pluck('ruang_id')->unique()->count());
            $this->assertSame(1, $secondClassSchedules->pluck('ruang_id')->unique()->count());
            $this->assertNotSame(
                $firstClassSchedules->first()->ruang_id,
                $secondClassSchedules->first()->ruang_id
            );
            $this->assertSame(
                [],
                array_values(array_intersect(
                    $firstClassSchedules->pluck('mulai')->all(),
                    $secondClassSchedules->pluck('mulai')->all()
                ))
            );
            $this->assertSame(
                $firstClassSchedules->count(),
                $firstClassSchedules->pluck('dosen_id')->unique()->count()
            );
            $this->assertSame(
                $secondClassSchedules->count(),
                $secondClassSchedules->pluck('dosen_id')->unique()->count()
            );
        }

        $this->assertSame(108, Mahasiswa::query()->where('mhs_code', 'like', 'DEMO-MHS-%')->count());
        $this->assertSame(72, DB::table('kelas')->where('code', 'like', 'DEMO-KLS-%')->count());
        $this->assertSame(658, DB::table('penawaran_mata_kuliahs')->count());
        $this->assertSame(432, DB::table('krs')->count());
        $this->assertSame(432, DB::table('tagihan_kuliahs')->count());

        $latestPeriod = TahunAkademik::query()->where('code', '252602')->firstOrFail();
        $readiness = app(PeriodReadinessService::class)->check($latestPeriod);
        $scheduleCheck = collect($readiness['checks'])->firstWhere('key', 'jadwal');

        $this->assertSame('siap', $scheduleCheck['status']);
        $this->assertSame(
            '162 jadwal; 0 penawaran belum dijadwalkan; 0 bentrok ditemukan.',
            $scheduleCheck['message']
        );

        $latestSchedules = DB::table('jadwal_mingguans')
            ->join(
                'penawaran_mata_kuliahs',
                'penawaran_mata_kuliahs.id',
                '=',
                'jadwal_mingguans.penawaran_mata_kuliah_id'
            )
            ->where('penawaran_mata_kuliahs.taka_id', $latestPeriod->id)
            ->select('jadwal_mingguans.*')
            ->get()
            ->values();
        $lecturerConflicts = 0;

        for ($left = 0; $left < $latestSchedules->count(); $left++) {
            for ($right = $left + 1; $right < $latestSchedules->count(); $right++) {
                $first = $latestSchedules[$left];
                $second = $latestSchedules[$right];
                $sameLecturerAndDay = $first->dosen_id === $second->dosen_id
                    && $first->hari === $second->hari;
                $overlaps = $first->mulai < $second->selesai
                    && $first->selesai > $second->mulai;
                $lecturerConflicts += $sameLecturerAndDay && $overlaps ? 1 : 0;
            }
        }

        $this->assertSame(0, $lecturerConflicts);
    }

    public function test_seeder_requires_an_existing_study_program(): void
    {
        DB::table('program_studis')->delete();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('minimal satu data program studi');

        $this->seed(DemoDuaTahunAkademikSeeder::class);
    }

    public function test_one_year_ago_seeder_creates_an_idempotent_2024_2025_academic_flow(): void
    {
        $this->seed(DemoSatuTahunLaluAkademikSeeder::class);
        $this->seed(DemoSatuTahunLaluAkademikSeeder::class);

        $periodIds = TahunAkademik::whereIn('code', ['242501', '242502'])->pluck('id');
        $studentIds = Mahasiswa::where('mhs_code', 'like', 'SATUTA-MHS-%')->pluck('id');

        $this->assertCount(2, $periodIds);
        $this->assertSame(12, $studentIds->count());
        $this->assertSame(2, DB::table('kelas')->where('code', 'like', 'SATUTA-%')->count());
        $this->assertSame(24, RegistrasiMahasiswa::whereIn('mahasiswa_id', $studentIds)->count());
        $this->assertSame(4, DB::table('mata_kuliahs')->where('code', 'like', 'SATUTA-%')->count());
        $this->assertSame(4, DB::table('penawaran_mata_kuliahs')->where('code', 'like', 'SATUTA-%')->count());
        $this->assertSame(24, DB::table('krs')->whereIn('registrasi_mahasiswa_id', RegistrasiMahasiswa::whereIn('mahasiswa_id', $studentIds)->pluck('id'))->count());
        $this->assertSame(48, DB::table('krs_items')->count());
        $this->assertSame(4, DB::table('jadwal_kuliahs')->where('code', 'like', 'SATUTA-%')->count());
        $this->assertSame(4, DB::table('jadwal_mingguans')->where('code', 'like', 'SATUTA-%')->count());
        $this->assertSame(16, DB::table('pertemuan_kuliahs')->where('code', 'like', 'SATUTA-%')->count());
        $this->assertSame(48, DB::table('nilai_mahasiswas')->whereIn('mahasiswa_id', $studentIds)->count());
        $this->assertSame(48, DB::table('absensi_mahasiswas')->where('code', 'like', 'SATUTA-%')->count());
        $this->assertSame(24, DB::table('hasil_studis')->whereIn('student_id', $studentIds)->count());
        $this->assertSame(8, DB::table('kalender_akademiks')->where('nama', 'like', '% Satu Tahun Lalu')->count());
        $this->assertSame(1, DB::table('program_studis')->count());

        $student = Mahasiswa::where('mhs_code', 'SATUTA-MHS-01')->firstOrFail();
        $this->assertSame([1, 2], $student->registrasiAkademik()->orderBy('semester_mahasiswa')->pluck('semester_mahasiswa')->all());
        $this->assertSame(2024, $student->years_id);
        $this->assertSame(TahunAkademik::STATUS_ACTIVE, TahunAkademik::where('code', '242502')->value('status'));
        $this->assertSame('242502', TahunAkademik::where('is_active', true)->value('code'));
    }

    public function test_two_years_ago_seeder_creates_2023_2024_and_can_coexist_with_one_year_ago_data(): void
    {
        $this->seed(DemoSatuTahunLaluAkademikSeeder::class);
        $this->seed(DemoDuaTahunLaluAkademikSeeder::class);
        $this->seed(DemoDuaTahunLaluAkademikSeeder::class);

        $studentIds = Mahasiswa::where('mhs_code', 'like', 'DUATA-MHS-%')->pluck('id');

        $this->assertSame(4, TahunAkademik::whereIn('code', ['232401', '232402', '242501', '242502'])->count());
        $this->assertSame(12, $studentIds->count());
        $this->assertSame(2, DB::table('kelas')->where('code', 'like', 'DUATA-%')->count());
        $this->assertSame(24, RegistrasiMahasiswa::whereIn('mahasiswa_id', $studentIds)->count());
        $this->assertSame(4, DB::table('mata_kuliahs')->where('code', 'like', 'DUATA-%')->count());
        $this->assertSame(4, DB::table('penawaran_mata_kuliahs')->where('code', 'like', 'DUATA-%')->count());
        $this->assertSame(4, DB::table('jadwal_kuliahs')->where('code', 'like', 'DUATA-%')->count());
        $this->assertSame(16, DB::table('pertemuan_kuliahs')->where('code', 'like', 'DUATA-%')->count());
        $this->assertSame(48, DB::table('absensi_mahasiswas')->where('code', 'like', 'DUATA-%')->count());
        $this->assertSame(8, DB::table('kalender_akademiks')->where('nama', 'like', '% Dua Tahun Lalu')->count());
        $this->assertSame(4, DB::table('master_mata_kuliahs')->where('program_studi', '86208')->count());
        $this->assertSame(1, DB::table('program_studis')->count());

        $student = Mahasiswa::where('mhs_code', 'DUATA-MHS-01')->firstOrFail();
        $this->assertSame([1, 2], $student->registrasiAkademik()->orderBy('semester_mahasiswa')->pluck('semester_mahasiswa')->all());
        $this->assertSame(2023, $student->years_id);
        $this->assertSame(TahunAkademik::STATUS_ACTIVE, TahunAkademik::where('code', '232402')->value('status'));
        $this->assertSame('232402', TahunAkademik::where('is_active', true)->value('code'));
        $this->assertSame(TahunAkademik::STATUS_CLOSED, TahunAkademik::where('code', '242502')->value('status'));
    }

    private function migrationFiles(): array
    {
        return [
            '2024_04_26_060533_create_tahun_akademiks_table.php',
            '2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php',
            '2024_03_09_024013_create_mahasiswas_table.php',
            '2025_06_15_153738_add__to_mahasiswas_table.php',
            '2024_03_09_024021_create_dosens_table.php',
            '2024_04_25_082451_create_fakultas_table.php',
            '2024_04_25_082531_create_program_studis_table.php',
            '2024_04_26_061235_create_program_kuliahs_table.php',
            '2024_04_27_041303_create_kelas_table.php',
            '2024_04_28_035926_create_gedungs_table.php',
            '2024_04_28_052322_create_ruangs_table.php',
            '2024_04_28_063053_create_kurikulums_table.php',
            '2026_07_17_000001_create_master_mata_kuliahs_table.php',
            '2026_08_17_000001_add_code_to_master_mata_kuliahs_table.php',
            '2024_04_30_032644_create_mata_kuliahs_table.php',
            '2026_07_17_000002_add_mid_to_mata_kuliahs_table.php',
            '2025_07_05_112548_add_matakuliah_kelas_id.php',
            '2024_04_30_055648_create_jadwal_kuliahs_table.php',
            '2024_05_10_080721_create_tagihan_kuliahs_table.php',
            '2024_05_10_081438_create_history_tagihans_table.php',
            '2024_04_30_102751_create_absensi_mahasiswas_table.php',
            '2024_06_16_033935_create_hasil_studis_table.php',
            '2025_07_05_091153_create_nilai_mahasiswas_table.php',
            '2026_07_17_000004_link_grades_and_study_results_to_academic_periods.php',
            '2026_07_17_000005_create_registrasi_mahasiswas_table.php',
            '2024_06_13_085258_create_student_tasks_table.php',
            '2024_06_14_102445_create_student_scores_table.php',
            '2026_07_17_000008_create_course_offerings_and_krs_tables.php',
            '2026_07_17_000009_create_weekly_schedules_and_course_meetings.php',
            '2026_07_17_000010_normalize_period_billing_and_financial_krs_policy.php',
            '2026_07_17_000011_create_period_opening_workflow_and_audit.php',
            '2026_08_20_000001_create_tahun_akademik_and_link_periods.php',
            '2026_09_03_000001_add_sks_to_jadwal_mingguans_table.php',
            '2026_09_03_000002_add_schedule_requirement_to_course_offerings.php',
        ];
    }
}
