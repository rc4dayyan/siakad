<?php

namespace Database\Seeders;

use App\Models\AbsensiMahasiswa;
use App\Models\AcademicWorkflowAudit;
use App\Models\Dosen;
use App\Models\Gedung;
use App\Models\HasilStudi;
use App\Models\HistoryTagihan;
use App\Models\JadwalKuliah;
use App\Models\JadwalMingguan;
use App\Models\KalenderAkademik;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\MataKuliah;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\PenerbitanTagihanBatch;
use App\Models\PeriodPublication;
use App\Models\PeriodReadinessSnapshot;
use App\Models\PertemuanKuliah;
use App\Models\ProgramKuliah;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\Ruang;
use App\Models\studentScore;
use App\Models\studentTask;
use App\Models\TagihanKuliah;
use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Models\TemplateTagihan;
use App\Models\User;
use App\Services\Academic\PeriodReadinessService;
use App\Services\Academic\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class DemoDuaTahunAkademikSeeder extends Seeder
{
    private const LATEST_PERIOD_CODE = '252602';

    private const STUDENTS_PER_COHORT = 12;

    private const MAX_SCHEDULE_SEMESTER = 6;

    private const MAX_ACTIVE_STUDENT_SEMESTER = 8;

    /** @var list<Dosen> */
    private array $availableLecturers = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->resetDemoTransactions();
            $studyPrograms = $this->studyPrograms();
            $periods = $this->seedPeriods();
            $this->call(DosenSeeder::class);
            $lecturers = $this->assignLecturers($studyPrograms, $periods);
            $curriculum = $this->seedCurriculum();
            $rooms = $this->seedRooms($studyPrograms, $periods);
            $this->call(MasterMataKuliahSeeder::class);
            $masters = [];

            foreach ($studyPrograms as $studyProgram) {
                $masters[$studyProgram->id] = $this->seedMasterCourses($studyProgram);
            }

            $studentCohorts = $this->seedStudents($periods, $studyPrograms);
            $staff = $this->seedStaff();

            foreach (array_values($periods) as $periodIndex => $period) {
                $this->seedAcademicCalendar($period);

                foreach ($studyPrograms as $studyProgram) {
                    $studyProgramToken = $this->studyProgramToken($studyProgram);
                    $program = ProgramKuliah::updateOrCreate([
                        'code' => 'DEMO-REG-'.$period->code.'-'.$studyProgramToken,
                    ], [
                        'taka_id' => $period->id,
                        'pstudi_id' => $studyProgram->id,
                        'name' => 'Reguler Sore',
                        'wave' => 'Gelombang 1',
                        'wave_start' => $period->starts_at,
                        'wave_ended' => $period->ends_at,
                    ]);
                    $activeStudents = [];

                    foreach ($studentCohorts[$studyProgram->id] as $entryYear => $students) {
                        if ($entryYear > $period->year_start) {
                            continue;
                        }

                        $studentSemester = (($period->year_start - $entryYear) * 2) + (int) $period->raw_semester;

                        if ($studentSemester > self::MAX_ACTIVE_STUDENT_SEMESTER) {
                            if ($studentSemester === self::MAX_ACTIVE_STUDENT_SEMESTER + 1) {
                                $this->seedDropOutRegistrations(
                                    $period,
                                    $studentSemester,
                                    $entryYear,
                                    $students,
                                    $lecturers[$studyProgram->id][$entryYear][0]
                                );
                            }

                            continue;
                        }

                        $activeStudents = array_merge($activeStudents, $students);
                        $studentGroups = array_chunk($students, (int) ceil(count($students) / 2));

                        foreach ($studentGroups as $classIndex => $classStudents) {
                            $classLabel = chr(65 + $classIndex);
                            $classLecturer = $lecturers[$studyProgram->id][$entryYear][$classIndex];
                            $room = $rooms[$studyProgram->id][$entryYear][$classIndex];
                            $studyProgramSingkat = collect(explode(' ', $studyProgram->name))
                                ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                                ->implode('');
                            $class = Kelas::updateOrCreate([
                                'code' => 'DEMO-KLS-'.$studyProgramToken.'-A'.$entryYear.'-S'.$studentSemester.'-'.$classLabel,
                            ], [
                                'taka_id' => $period->id,
                                'pstudi_id' => $studyProgram->id,
                                'proku_id' => $program->id,
                                'dosen_id' => $classLecturer->id,
                                'capacity' => 30,
                                'name' => 'Kelas '.$classLabel.' '.$studyProgramSingkat.' '.$studentSemester,
                            ]);

                            foreach ($classStudents as $student) {
                                RegistrasiMahasiswa::updateOrCreate([
                                    'mahasiswa_id' => $student->id,
                                    'taka_id' => $period->id,
                                ], [
                                    'semester_mahasiswa' => $studentSemester,
                                    'status_akademik' => 'aktif',
                                    'status_registrasi' => 'terdaftar',
                                    'kelas_id' => $class->id,
                                    'dosen_wali_id' => $classLecturer->id,
                                    'batas_sks' => 24,
                                ]);
                                $student->update([
                                    'taka_id' => $period->id,
                                    'class_id' => $class->id,
                                    'years_id' => $entryYear,
                                    'mhs_stat' => 1,
                                ]);
                            }

                            if ($studentSemester > self::MAX_SCHEDULE_SEMESTER) {
                                continue;
                            }

                            $legacyOfferings = [];
                            $availableMasters = array_values(array_filter(
                                $masters[$studyProgram->id],
                                fn (MasterMataKuliah $master): bool => $master->semester === $studentSemester,
                            ));

                            foreach ($availableMasters as $courseIndex => $master) {
                                $courseLecturer = $this->lecturerForCourse($classLecturer, $courseIndex);
                                $legacyOfferings[] = MataKuliah::updateOrCreate([
                                    'code' => 'DEMO-MK-'.$period->code.'-K'.$class->id.'-'.($courseIndex + 1),
                                ], [
                                    'mid' => $master->id,
                                    'kuri_id' => $curriculum->id,
                                    'taka_id' => $period->id,
                                    'pstudi_id' => $studyProgram->id,
                                    'kelas_id' => $class->id,
                                    'dosen_1' => $courseLecturer->id,
                                    'name' => $master->name,
                                    'bsks' => $master->sks,
                                    'desc' => 'Penawaran mata kuliah demo angkatan '.$entryYear.'.',
                                ]);
                            }

                            $offerings = $this->seedNormalizedOfferings($period, $studyProgram, $curriculum, $class, $legacyOfferings);
                            $this->seedAcademicActivities($period, $periodIndex, $studentSemester, $class, $room, $legacyOfferings, $classStudents, $classIndex);
                            $meetings = $this->seedWeeklySchedulesAndMeetings($period, $class, $room, $offerings, $classIndex);
                            $krsItems = $this->seedKrs($period, $studentSemester, $classLecturer, $offerings, $classStudents);
                            $this->linkNormalizedAcademicData($period, $class, $offerings, $meetings, $krsItems, $classStudents);
                        }
                    }

                    $this->seedFinance(
                        $period,
                        $periodIndex,
                        $studyProgram,
                        $program,
                        $activeStudents,
                        $staff['finance']
                    );
                }

                $this->seedPublication($period, $staff['academic']);
            }
        });
    }

    private function seedDropOutRegistrations(
        TahunAkademik $period,
        int $studentSemester,
        int $entryYear,
        array $students,
        Dosen $advisor
    ): void {
        foreach ($students as $student) {
            $hasGraduated = RegistrasiMahasiswa::query()
                ->where('mahasiswa_id', $student->id)
                ->where('status_akademik', RegistrasiMahasiswa::STATUS_AKADEMIK_LULUS)
                ->exists();

            if ($hasGraduated) {
                continue;
            }

            RegistrasiMahasiswa::updateOrCreate([
                'mahasiswa_id' => $student->id,
                'taka_id' => $period->id,
            ], [
                'semester_mahasiswa' => $studentSemester,
                'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_DROP_OUT,
                'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
                'kelas_id' => null,
                'dosen_wali_id' => $advisor->id,
                'batas_sks' => 0,
            ]);
            $student->update([
                'taka_id' => $period->id,
                'class_id' => 0,
                'years_id' => $entryYear,
                'mhs_stat' => 2,
            ]);
        }
    }

    private function resetDemoTransactions(): void
    {
        $studentIds = Mahasiswa::query()->where('mhs_code', 'like', 'DEMO-%')->pluck('id');
        $registrationIds = RegistrasiMahasiswa::query()->whereIn('mahasiswa_id', $studentIds)->pluck('id');
        $krsIds = Krs::query()->whereIn('registrasi_mahasiswa_id', $registrationIds)->pluck('id');
        $taskIds = studentTask::query()->where('code', 'like', 'DEMO-%')->pluck('id');
        $templateIds = TemplateTagihan::query()->where('name', 'like', 'UKT Demo %')->pluck('id');
        $periodCodes = [];

        foreach (range(2023, 2025) as $startYear) {
            $yearCode = substr((string) $startYear, -2).substr((string) ($startYear + 1), -2);
            $periodCodes[] = $yearCode.'01';
            $periodCodes[] = $yearCode.'02';
        }

        $periodIds = TahunAkademik::query()->whereIn('code', $periodCodes)->pluck('id');

        AcademicWorkflowAudit::query()->where('event', 'period.demo_seeded')->whereIn('taka_id', $periodIds)->delete();
        PeriodPublication::query()->whereIn('taka_id', $periodIds)->delete();
        PeriodReadinessSnapshot::query()->where('purpose', 'demo_seed')->whereIn('taka_id', $periodIds)->delete();
        HistoryTagihan::query()->where('code', 'like', 'DEMO-%')->delete();
        PenerbitanTagihanBatch::query()->whereIn('template_tagihan_id', $templateIds)->delete();
        TagihanKuliah::query()->where('code', 'like', 'DEMO-%')->delete();
        TemplateTagihan::query()->whereIn('id', $templateIds)->delete();
        AbsensiMahasiswa::query()->where('code', 'like', 'DEMO-%')->delete();
        PertemuanKuliah::query()->where('code', 'like', 'DEMO-%')->delete();
        JadwalMingguan::query()->where('code', 'like', 'DEMO-%')->delete();
        studentScore::query()->whereIn('student_id', $studentIds)->delete();
        studentTask::query()->whereIn('id', $taskIds)->delete();
        NilaiMahasiswa::query()->whereIn('mahasiswa_id', $studentIds)->delete();
        HasilStudi::query()->where('code', 'like', 'DEMO-%')->delete();
        DB::table('krs_items')->whereIn('krs_id', $krsIds)->delete();
        Krs::query()->whereIn('id', $krsIds)->delete();

        if (Schema::hasTable('riwayat_status_akademik_mahasiswas')) {
            DB::table('riwayat_status_akademik_mahasiswas')->whereIn('registrasi_mahasiswa_id', $registrationIds)->delete();
        }

        JadwalKuliah::query()->where('code', 'like', 'DEMO-%')->delete();
        PenawaranMataKuliah::query()->where('code', 'like', 'DEMO-%')->delete();
        RegistrasiMahasiswa::query()->whereIn('id', $registrationIds)->delete();
        MataKuliah::query()->where('code', 'like', 'DEMO-%')->delete();
        KalenderAkademik::query()->where('nama', 'like', '% Demo')->whereIn('taka_id', $periodIds)->delete();
        Mahasiswa::query()->whereIn('id', $studentIds)->update(['taka_id' => 0, 'class_id' => 0]);
        Kelas::query()->where('code', 'like', 'DEMO-%')->delete();
        ProgramKuliah::query()->where('code', 'like', 'DEMO-%')->delete();
    }

    private function seedPeriods(): array
    {
        $definitions = [];

        for ($startYear = 2023; $startYear <= 2025; $startYear++) {
            $endYear = $startYear + 1;
            $yearCode = substr((string) $startYear, -2).substr((string) $endYear, -2);
            $academicYearId = null;

            if (Schema::hasTable('tahun_akademik')) {
                $academicYearId = TahunAkademikInduk::updateOrCreate([
                    'year_start' => $startYear,
                    'year_end' => $endYear,
                ], [
                    'name' => 'Tahun Akademik '.$startYear.'/'.$endYear,
                    'code' => $startYear.'-'.$endYear,
                ])->id;
            }

            foreach ([1 => TahunAkademik::TERM_GANJIL, 2 => TahunAkademik::TERM_GENAP] as $semester => $term) {
                $code = $yearCode.'0'.$semester;
                $isOddSemester = $semester === 1;
                $status = $code === self::LATEST_PERIOD_CODE
                    ? TahunAkademik::STATUS_ACTIVE
                    : ($code === '252601' ? TahunAkademik::STATUS_CLOSED : TahunAkademik::STATUS_ARCHIVED);
                $definitions[$code] = [
                    'TA. '.$startYear.'/'.$endYear.' '.($isOddSemester ? 'Ganjil' : 'Genap'),
                    $startYear,
                    $endYear,
                    $semester,
                    $term,
                    $isOddSemester ? $startYear.'-08-01' : $endYear.'-02-01',
                    $isOddSemester ? $endYear.'-01-31' : $endYear.'-07-31',
                    $status,
                    $academicYearId,
                ];
            }
        }

        $demoCodes = array_keys($definitions);
        $hasExternalActivePeriod = TahunAkademik::query()
            ->where('is_active', true)
            ->whereNotIn('code', $demoCodes)
            ->exists();

        TahunAkademik::query()->whereIn('code', $demoCodes)->where('is_active', true)->update([
            'is_active' => false,
            'status' => TahunAkademik::STATUS_CLOSED,
        ]);

        $periods = [];

        foreach ($definitions as $code => [$name, $startYear, $endYear, $semester, $term, $startsAt, $endsAt, $status, $academicYearId]) {
            $isActive = $status === TahunAkademik::STATUS_ACTIVE && ! $hasExternalActivePeriod;
            $attributes = [
                'name' => $name,
                'year_start' => $startYear,
                'year_end' => $endYear,
                'semester' => $semester,
                'term' => $term,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $isActive ? TahunAkademik::STATUS_ACTIVE : ($status === TahunAkademik::STATUS_ACTIVE ? TahunAkademik::STATUS_CLOSED : $status),
                'is_active' => $isActive,
                'activated_at' => $isActive ? now() : null,
            ];

            if (Schema::hasColumn('tahun_akademiks', 'tid')) {
                $attributes['tid'] = $academicYearId;
            }

            $periods[$code] = TahunAkademik::updateOrCreate(['code' => $code], $attributes);
        }

        return $periods;
    }

    /**
     * @param  array<int, ProgramStudi>  $studyPrograms
     * @param  array<string, TahunAkademik>  $periods
     * @return array<int, array<int, array<int, Dosen>>>
     */
    private function assignLecturers(array $studyPrograms, array $periods): array
    {
        $lecturers = [];
        $lecturersByNidn = Dosen::query()
            ->whereIn('dsn_nidn', array_column(DosenSeeder::rows(), 'dsn_nidn'))
            ->where('dsn_stat', 1)
            ->get()
            ->keyBy('dsn_nidn');
        $availableLecturers = array_values(array_filter(array_map(
            fn (array $row): ?Dosen => $lecturersByNidn->get($row['dsn_nidn']),
            DosenSeeder::rows(),
        )));
        $parallelGroups = count($studyPrograms) * count($this->entryYears($periods));
        $requiredLecturers = ($parallelGroups * 2) + 15;

        if (count($availableLecturers) < $requiredLecturers) {
            throw new RuntimeException(sprintf(
                'Seeder membutuhkan %d dosen aktif untuk merotasi pengampu tanpa bentrok, tetapi data sumber hanya menyediakan %d.',
                $requiredLecturers,
                count($availableLecturers),
            ));
        }

        $this->availableLecturers = $availableLecturers;
        $groupIndex = 0;

        foreach ($studyPrograms as $studyProgram) {
            foreach ($this->entryYears($periods) as $entryYear) {
                $lecturers[$studyProgram->id][$entryYear][0] = $availableLecturers[$groupIndex];
                $lecturers[$studyProgram->id][$entryYear][1] = $availableLecturers[$parallelGroups + 1 + $groupIndex];
                $groupIndex++;
            }
        }

        return $lecturers;
    }

    private function lecturerForCourse(Dosen $classLecturer, int $courseIndex): Dosen
    {
        if ($courseIndex >= count($this->availableLecturers)) {
            throw new RuntimeException(sprintf(
                'Kelas memiliki lebih dari %d mata kuliah sehingga dosen pengampu unik tidak mencukupi.',
                count($this->availableLecturers),
            ));
        }

        $classLecturerIndex = array_search(
            $classLecturer->id,
            array_map(fn (Dosen $lecturer): int => $lecturer->id, $this->availableLecturers),
            true,
        );

        if ($classLecturerIndex === false) {
            throw new RuntimeException('Dosen wali kelas tidak ditemukan dalam data sumber dosen.');
        }

        return $this->availableLecturers[($classLecturerIndex + $courseIndex) % count($this->availableLecturers)];
    }

    /** @return array<int, ProgramStudi> */
    private function studyPrograms(): array
    {
        $studyPrograms = ProgramStudi::query()
            ->orderBy('id')
            ->get()
            ->all();

        if ($studyPrograms === []) {
            throw new RuntimeException('Seeder demo membutuhkan minimal satu data program studi yang sudah tersedia.');
        }

        return $studyPrograms;
    }

    private function studyProgramToken(ProgramStudi $studyProgram): string
    {
        return strtoupper(Str::slug($studyProgram->code));
    }

    /**
     * @param  array<string, TahunAkademik>  $periods
     * @return list<int>
     */
    private function entryYears(array $periods): array
    {
        $entryYears = array_values(array_unique(array_map(
            fn (TahunAkademik $period): int => (int) $period->year_start,
            $periods,
        )));
        sort($entryYears);

        return $entryYears;
    }

    private function seedCurriculum(): Kurikulum
    {
        return Kurikulum::updateOrCreate(['code' => 'DEMO-KUR-2024'], [
            'name' => 'Kurikulum Demo 2023',
            'desc' => 'Kurikulum khusus simulasi tahun akademik 2023/2024 sampai 2025/2026.',
            'year_start' => 2023,
            'year_ended' => 2026,
        ]);
    }

    /**
     * @param  array<int, ProgramStudi>  $studyPrograms
     * @param  array<string, TahunAkademik>  $periods
     * @return array<int, array<int, array<int, Ruang>>>
     */
    private function seedRooms(array $studyPrograms, array $periods): array
    {
        $building = Gedung::updateOrCreate(['code' => 'DEMO-GDG'], ['name' => 'Gedung Perkuliahan Demo']);
        $rooms = [];
        $serial = 0;

        foreach ($studyPrograms as $studyProgramIndex => $studyProgram) {
            foreach ($this->entryYears($periods) as $entryYear) {
                foreach ([0 => 'A', 1 => 'B'] as $classIndex => $classLabel) {
                    $serial++;
                    $rooms[$studyProgram->id][$entryYear][$classIndex] = Ruang::updateOrCreate([
                        'code' => 'DEMO-R-'.$this->studyProgramToken($studyProgram).'-'.$entryYear.'-'.$classLabel,
                    ], [
                        'gedu_id' => $building->id,
                        'type' => 0,
                        'floor' => $studyProgramIndex + 1,
                        'name' => 'Ruang Demo '.str_pad((string) $serial, 3, '0', STR_PAD_LEFT).' '.$studyProgram->name,
                    ]);
                }
            }
        }

        return $rooms;
    }

    private function seedMasterCourses(ProgramStudi $studyProgram): array
    {
        $masters = MasterMataKuliah::query()
            ->where('program_studi', $studyProgram->code)
            ->orderBy('semester')
            ->orderBy('id')
            ->get()
            ->all();

        if ($masters === []) {
            throw new RuntimeException(
                'Tidak ada master mata kuliah workbook untuk program studi '.$studyProgram->code.'.'
            );
        }

        return $masters;
    }

    /**
     * @param  array<string, TahunAkademik>  $periods
     * @param  array<int, ProgramStudi>  $studyPrograms
     * @return array<int, array<int, array<int, Mahasiswa>>>
     */
    private function seedStudents(array $periods, array $studyPrograms): array
    {
        $names = [
            'Ali Rahman',
            'Siti Aisyah',
            'Ahmad Fauzan',
            'Nurul Hidayah',
            'Muhammad Rizki',
            'Dewi Lestari',
            'Hasan Basri',
            'Fitri Handayani',
            'Abdul Aziz',
            'Rina Marlina',
            'Yusuf Maulana',
            'Nadia Rahmawati',
        ];
        $cohorts = [];
        $entryYears = $this->entryYears($periods);

        foreach ($studyPrograms as $studyProgramIndex => $studyProgram) {
            $studyProgramToken = strtolower($this->studyProgramToken($studyProgram));
            $studyProgramNumber = str_pad((string) ($studyProgramIndex + 1), 2, '0', STR_PAD_LEFT);

            foreach ($entryYears as $entryYear) {
                $entryPeriod = array_values(array_filter(
                    $periods,
                    fn (TahunAkademik $period): bool => (int) $period->year_start === $entryYear
                        && (int) $period->raw_semester === 1,
                ))[0] ?? null;

                if (! $entryPeriod) {
                    throw new RuntimeException('Periode masuk semester ganjil untuk angkatan '.$entryYear.' tidak ditemukan.');
                }

                $yearSuffix = substr((string) $entryYear, -2);
                $definitions = [];

                foreach (array_slice($names, 0, self::STUDENTS_PER_COHORT) as $index => $name) {
                    $number = $index + 1;
                    $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
                    $studentIdentity = $studyProgramToken.'-'.$yearSuffix.'-'.$suffix;
                    $definitions[] = [
                        'DEMO-MHS-'.strtoupper($studentIdentity),
                        $yearSuffix.'99'.$studyProgramNumber.$suffix,
                        $name.' - '.$studyProgram->code.' (Angkatan '.$entryYear.')',
                        'demo.'.$studyProgramToken.'.'.$yearSuffix.'.'.$number,
                        'demo.'.$studyProgramToken.'.'.$yearSuffix.'.'.$number.'@example.test',
                        '0899'.$yearSuffix.$studyProgramNumber.$suffix,
                    ];
                }

                $cohorts[$studyProgram->id][$entryYear] = array_map(
                    fn (array $data) => Mahasiswa::updateOrCreate(['mhs_code' => $data[0]], [
                        'mhs_stat' => 1,
                        'mhs_nim' => $data[1],
                        'mhs_name' => $data[2],
                        'mhs_user' => $data[3],
                        'mhs_mail' => $data[4],
                        'mhs_phone' => $data[5],
                        'mhs_register_date' => $entryPeriod->starts_at->copy()->startOfDay(),
                        'password' => Hash::make('Demo123!'),
                    ]),
                    $definitions
                );
            }
        }

        return $cohorts;
    }

    private function seedStaff(): array
    {
        $definitions = [
            'academic' => ['DEMO-STAFF-AKADEMIK', 3, 'Staf Akademik Demo', 'demo.akademik', 'demo.akademik@example.test', '089920000001'],
            'finance' => ['DEMO-STAFF-KEUANGAN', 1, 'Staf Keuangan Demo', 'demo.keuangan', 'demo.keuangan@example.test', '089920000002'],
        ];
        $staff = [];

        foreach ($definitions as $key => [$code, $type, $name, $username, $email, $phone]) {
            $staff[$key] = User::updateOrCreate(['code' => $code], [
                'type' => $type,
                'name' => $name,
                'user' => $username,
                'phone' => $phone,
                'email' => $email,
                'status' => 1,
                'email_verified_at' => now(),
                'password' => Hash::make('Demo123!'),
            ]);
        }

        return $staff;
    }

    private function seedNormalizedOfferings(
        TahunAkademik $period,
        ProgramStudi $studyProgram,
        Kurikulum $curriculum,
        Kelas $class,
        array $legacyOfferings
    ): array {
        return array_map(fn (MataKuliah $course) => PenawaranMataKuliah::updateOrCreate([
            'legacy_mata_kuliah_id' => $course->id,
        ], [
            'master_mata_kuliah_id' => $course->mid,
            'taka_id' => $period->id,
            'pstudi_id' => $studyProgram->id,
            'kuri_id' => $curriculum->id,
            'kelas_id' => $class->id,
            'dosen_utama_id' => $course->dosen_1,
            'code' => $course->code,
            'sks' => $course->bsks,
            'kapasitas' => 30,
            'deskripsi' => 'Penawaran normalisasi untuk simulasi tiga tahun akademik.',
        ]), $legacyOfferings);
    }

    private function seedAcademicCalendar(TahunAkademik $period): void
    {
        $events = [
            ['krs', 'Pengisian KRS', 0, 14],
            ['perkuliahan', 'Perkuliahan dan Presensi', 14, 112],
            ['uts', 'Ujian Tengah Semester', 56, 63],
            ['uas', 'Ujian Akhir Semester', 112, 119],
        ];

        foreach ($events as [$category, $name, $startDay, $endDay]) {
            KalenderAkademik::updateOrCreate([
                'taka_id' => $period->id,
                'kategori' => $category,
                'nama' => $name.' Demo',
            ], [
                'mulai_at' => $period->starts_at->copy()->addDays($startDay)->startOfDay(),
                'selesai_at' => $period->starts_at->copy()->addDays($endDay)->endOfDay(),
                'dipublikasikan' => true,
            ]);
        }
    }

    private function seedWeeklySchedulesAndMeetings(
        TahunAkademik $period,
        Kelas $class,
        Ruang $room,
        array $offerings,
        int $classIndex
    ): array {
        $meetings = [];

        foreach ($offerings as $courseIndex => $offering) {
            [$startsAt, $endsAt] = $this->scheduleTimes($classIndex, $courseIndex);
            $scheduleCode = 'DEMO-JM-'.$period->code.'-'.$class->id.'-'.($courseIndex + 1);
            $attributes = [
                'penawaran_mata_kuliah_id' => $offering->id,
                'kelas_id' => $class->id,
                'dosen_id' => $offering->dosen_utama_id,
                'ruang_id' => $room->id,
                'hari' => $this->scheduleDay($courseIndex),
                'mulai' => $startsAt,
                'selesai' => $endsAt,
            ];
            $existingSchedule = JadwalMingguan::query()->where('code', $scheduleCode)->first();
            app(ScheduleConflictService::class)->validate(
                $offering,
                $attributes,
                $existingSchedule
            );
            $fingerprint = JadwalMingguan::fingerprint($attributes);
            $weekly = JadwalMingguan::updateOrCreate(['code' => $scheduleCode], $attributes + [
                'fingerprint' => $fingerprint,
            ]);
            $legacySchedule = JadwalKuliah::where('code', 'DEMO-JDW-'.$period->code.'-'.$class->id.'-'.($courseIndex + 1))->firstOrFail();

            for ($meetingNumber = 1; $meetingNumber <= 4; $meetingNumber++) {
                $meeting = PertemuanKuliah::updateOrCreate([
                    'jadwal_mingguan_id' => $weekly->id,
                    'pertemuan_ke' => $meetingNumber,
                ], [
                    'legacy_jadwal_kuliah_id' => $meetingNumber === 1 ? $legacySchedule->id : null,
                    'dosen_id' => $weekly->dosen_id,
                    'ruang_id' => $weekly->ruang_id,
                    'tanggal' => $this->scheduleDate($period, $courseIndex, $meetingNumber + 1),
                    'mulai' => $weekly->mulai,
                    'selesai' => $weekly->selesai,
                    'metode' => $meetingNumber === 3 ? 'daring' : 'tatap_muka',
                    'materi' => 'Materi demo pertemuan '.$meetingNumber,
                    'status' => $meetingNumber < 4 ? PertemuanKuliah::STATUS_COMPLETED : PertemuanKuliah::STATUS_SCHEDULED,
                    'code' => 'DEMO-PRT-'.$period->code.'-'.$class->id.'-'.($courseIndex + 1).'-'.$meetingNumber,
                ]);
                $meetings[$offering->id][$meetingNumber] = $meeting;
            }
        }

        return $meetings;
    }

    private function seedKrs(
        TahunAkademik $period,
        int $studentSemester,
        Dosen $advisor,
        array $offerings,
        array $students
    ): array {
        $items = [];

        foreach ($students as $studentIndex => $student) {
            $registration = RegistrasiMahasiswa::where('mahasiswa_id', $student->id)->where('taka_id', $period->id)->firstOrFail();
            $finalStatus = $period->code !== self::LATEST_PERIOD_CODE
                ? Krs::STATUS_LOCKED
                : ($studentIndex === 0 ? Krs::STATUS_APPROVED : Krs::STATUS_DRAFT);
            $krs = Krs::updateOrCreate(['registrasi_mahasiswa_id' => $registration->id], [
                'status' => Krs::STATUS_DRAFT,
                'total_sks' => array_sum(array_map(fn ($offering) => (int) $offering->sks, $offerings)),
                'catatan_mahasiswa' => 'KRS demo semester '.$studentSemester.'.',
                'catatan_keputusan' => $finalStatus === Krs::STATUS_DRAFT ? null : 'Disetujui dosen wali untuk simulasi.',
                'diajukan_at' => $finalStatus === Krs::STATUS_DRAFT ? null : $period->starts_at->copy()->addDays(5),
                'diputuskan_at' => $finalStatus === Krs::STATUS_DRAFT ? null : $period->starts_at->copy()->addDays(7),
                'diputuskan_oleh' => $finalStatus === Krs::STATUS_DRAFT ? null : $advisor->id,
            ]);

            foreach ($offerings as $offering) {
                DB::table('krs_items')->updateOrInsert([
                    'krs_id' => $krs->id,
                    'penawaran_mata_kuliah_id' => $offering->id,
                ], [
                    'sks' => $offering->sks,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $items[$student->id][$offering->id] = DB::table('krs_items')
                    ->where('krs_id', $krs->id)
                    ->where('penawaran_mata_kuliah_id', $offering->id)
                    ->value('id');
            }
            $krs->update(['status' => $finalStatus]);
        }

        return $items;
    }

    private function linkNormalizedAcademicData(
        TahunAkademik $period,
        Kelas $class,
        array $offerings,
        array $meetings,
        array $krsItems,
        array $students
    ): void {
        foreach ($offerings as $courseIndex => $offering) {
            JadwalKuliah::where('code', 'DEMO-JDW-'.$period->code.'-'.$class->id.'-'.($courseIndex + 1))
                ->update(['penawaran_mata_kuliah_id' => $offering->id]);
            NilaiMahasiswa::where('mata_kuliah_id', $offering->legacy_mata_kuliah_id)
                ->update(['penawaran_mata_kuliah_id' => $offering->id]);

            foreach ($students as $student) {
                AbsensiMahasiswa::where('code', 'DEMO-ABS-'.$period->code.'-'.$class->id.'-'.$courseIndex.'-'.$student->id)->update([
                    'pertemuan_kuliah_id' => $meetings[$offering->id][1]->id,
                    'krs_item_id' => $krsItems[$student->id][$offering->id],
                ]);
            }
        }
    }

    private function seedFinance(
        TahunAkademik $period,
        int $periodIndex,
        ProgramStudi $studyProgram,
        ProgramKuliah $program,
        array $students,
        User $financeStaff
    ): void {
        $studyProgramToken = $this->studyProgramToken($studyProgram);
        $template = TemplateTagihan::updateOrCreate([
            'taka_id' => $period->id,
            'jenis' => 'ukt',
            'name' => 'UKT Demo '.$period->code.' '.$studyProgramToken,
        ], [
            'nominal' => 2500000 + ($periodIndex * 100000),
            'tanggal_terbit' => $period->starts_at,
            'jatuh_tempo' => $period->starts_at->copy()->addDays(10),
            'wajib_lunas_krs' => true,
            'target_type' => 'prodi',
            'target_prodi_id' => $studyProgram->id,
            'kelompok_target' => 'mahasiswa_aktif',
            'created_by' => $financeStaff->id,
        ]);

        foreach ($students as $studentIndex => $student) {
            $billCode = 'DEMO-TGH-'.$period->code.'-'.$studyProgramToken.'-'.($studentIndex + 1);
            $bill = TagihanKuliah::updateOrCreate([
                'taka_id' => $period->id,
                'jenis' => 'ukt',
                'target_mahasiswa_id' => $student->id,
            ], [
                'code' => $billCode,
                'author_id' => (string) $financeStaff->id,
                'proku_id' => (string) $program->id,
                'prodi_id' => (string) $studyProgram->id,
                'users_id' => (string) $student->id,
                'name' => $template->name,
                'price' => (string) $template->nominal,
                'taka_id' => $period->id,
                'template_tagihan_id' => $template->id,
                'nominal' => $template->nominal,
                'tanggal_terbit' => $template->tanggal_terbit,
                'jatuh_tempo' => $template->jatuh_tempo,
                'status' => TagihanKuliah::STATUS_TERBIT,
                'jenis' => 'ukt',
                'wajib_lunas_krs' => true,
                'target_type' => 'mahasiswa',
                'target_mahasiswa_id' => $student->id,
                'target_prodi_id' => null,
                'target_proku_id' => null,
                'kelompok_target' => 'mahasiswa_aktif',
            ]);
            $paid = $period->code !== self::LATEST_PERIOD_CODE || $studentIndex === 0;
            HistoryTagihan::updateOrCreate(['code' => 'DEMO-BYR-'.$period->code.'-'.$studyProgramToken.'-'.($studentIndex + 1)], [
                'users_id' => $student->id,
                'stat' => $paid ? 1 : 0,
                'tagihan_code' => $billCode,
                'desc' => $paid ? 'Pembayaran demo terverifikasi.' : 'Menunggu pembayaran demo.',
                'snap_token' => null,
                'tagihan_kuliah_id' => $bill->id,
                'taka_id' => $period->id,
                'nominal' => $template->nominal,
                'status' => $paid ? 'lunas' : 'pending',
                'dibayar_at' => $paid ? $period->starts_at->copy()->addDays(3) : null,
            ]);
        }

        PenerbitanTagihanBatch::updateOrCreate([
            'template_tagihan_id' => $template->id,
            'taka_id' => $period->id,
        ], [
            'actor_id' => $financeStaff->id,
            'calon' => count($students),
            'berhasil' => count($students),
            'dilewati' => 0,
            'gagal' => 0,
            'konfigurasi' => ['sumber' => 'DemoDuaTahunAkademikSeeder'],
            'ringkasan' => 'Penerbitan tagihan UKT demo berhasil.',
        ]);
    }

    private function seedPublication(TahunAkademik $period, User $academicStaff): void
    {
        $result = app(PeriodReadinessService::class)->check($period);
        $snapshot = PeriodReadinessSnapshot::updateOrCreate([
            'taka_id' => $period->id,
            'purpose' => 'demo_seed',
        ], [
            'actor_id' => $academicStaff->id,
            'status' => $result['status'],
            'ready_count' => $result['counts']['siap'],
            'warning_count' => $result['counts']['peringatan'],
            'failed_count' => $result['counts']['gagal'],
            'checks' => $result['checks'],
            'checked_at' => now(),
        ]);
        $publishedAt = $period->starts_at->copy()->subDay();

        $period->update([
            'is_published' => true,
            'published_at' => $publishedAt,
            'published_by' => $academicStaff->id,
        ]);
        PeriodPublication::updateOrCreate(['taka_id' => $period->id], [
            'readiness_snapshot_id' => $snapshot->id,
            'actor_id' => $academicStaff->id,
            'summary' => ['status' => $result['status'], 'counts' => $result['counts'], 'sumber' => 'demo_seed'],
            'published_at' => $publishedAt,
        ]);
        AcademicWorkflowAudit::updateOrCreate([
            'taka_id' => $period->id,
            'event' => 'period.demo_seeded',
            'subject_type' => TahunAkademik::class,
            'subject_id' => $period->id,
        ], [
            'actor_id' => $academicStaff->id,
            'actor_type' => User::class,
            'actor_reference_id' => $academicStaff->id,
            'after' => ['status' => $period->status, 'is_published' => true],
            'metadata' => ['sumber' => 'DemoDuaTahunAkademikSeeder'],
        ]);
    }

    private function seedAcademicActivities(
        TahunAkademik $period,
        int $periodIndex,
        int $studentSemester,
        Kelas $class,
        Ruang $room,
        array $offerings,
        array $students,
        int $classIndex
    ): void {
        $isUngradedPeriod = $period->code === self::LATEST_PERIOD_CODE;

        foreach ($offerings as $courseIndex => $course) {
            $date = $this->scheduleDate($period, $courseIndex, 2)->toDateString();
            [$startsAt, $endsAt, $attendanceAt] = $this->scheduleTimes($classIndex, $courseIndex);
            $schedule = JadwalKuliah::updateOrCreate(['code' => 'DEMO-JDW-'.$period->code.'-'.$class->id.'-'.($courseIndex + 1)], [
                'makul_id' => $course->id,
                'kelas_id' => $class->id,
                'dosen_id' => $course->dosen_1,
                'ruang_id' => $room->id,
                'pert_id' => 1,
                'meth_id' => 0,
                'days_id' => $this->scheduleDay($courseIndex),
                'bsks' => min(8, (int) $course->bsks),
                'date' => $date,
                'start' => $startsAt,
                'ended' => $endsAt,
            ]);

            foreach ($students as $studentIndex => $student) {
                $gradePoint = 4 - (($periodIndex + $studentIndex + $courseIndex) % 2);
                NilaiMahasiswa::updateOrCreate([
                    'mahasiswa_id' => $student->id,
                    'mata_kuliah_id' => $course->id,
                    'kelas_id' => $class->id,
                ], [
                    'taka_id' => $period->id,
                    'dosen_id' => $course->dosen_1,
                    'nilai' => $isUngradedPeriod ? null : ($gradePoint === 4 ? 'A' : 'B'),
                    'keterangan' => $isUngradedPeriod ? null : 'Nilai demo telah dipublikasikan.',
                ]);
                AbsensiMahasiswa::updateOrCreate([
                    'jadkul_code' => $schedule->code,
                    'author_id' => $student->id,
                ], [
                    'absen_type' => $studentIndex % 5 === 4 ? 'I' : 'H',
                    'absen_proof' => 'default/default-profile.jpg',
                    'code' => 'DEMO-ABS-'.$period->code.'-'.$class->id.'-'.$courseIndex.'-'.$student->id,
                    'absen_date' => $date,
                    'absen_time' => $attendanceAt,
                    'absen_desc' => 'Presensi contoh untuk simulasi.',
                ]);
            }

            if ($courseIndex === 0) {
                $task = studentTask::updateOrCreate(['jadkul_id' => $schedule->id], [
                    'dosen_id' => $course->dosen_1,
                    'code' => 'DEMO-TGS-'.$period->code.'-'.$class->id,
                    'title' => 'Tugas Refleksi Semester '.$studentSemester,
                    'detail_task' => 'Tuliskan refleksi pembelajaran pada periode ini.',
                    'exp_date' => $period->starts_at->copy()->addMonth()->toDateString(),
                    'exp_time' => '23:59:00',
                ]);
                foreach ($students as $studentIndex => $student) {
                    $taskScore = 8 + (($periodIndex + $studentIndex) % 3);
                    studentScore::updateOrCreate([
                        'stask_id' => $task->id,
                        'student_id' => $student->id,
                    ], [
                        'score' => $isUngradedPeriod ? null : $taskScore,
                        'desc' => 'Jawaban tugas demo.',
                        'code' => 900000000 + ($task->id * 10000) + $student->id,
                    ]);
                }
            }
        }

        if ($isUngradedPeriod) {
            return;
        }

        foreach ($students as $studentIndex => $student) {
            // Kolom nilai_ips dan nilai_ipk pada skema lama masih bertipe integer.
            $gradePoint = 4 - (($periodIndex + $studentIndex) % 2);
            $taskScore = 8 + (($periodIndex + $studentIndex) % 3);
            HasilStudi::updateOrCreate([
                'student_id' => $student->id,
                'taka_id' => $period->id,
            ], [
                'score_absen' => 90 - (($studentIndex % 5) * 5),
                'score_tugas' => $taskScore,
                'score_uts' => min(95, 80 + $periodIndex),
                'score_uas' => min(97, 82 + $periodIndex),
                'max_absen' => 2,
                'max_tugas' => 1,
                'smt_id' => $studentSemester,
                'nilai_ips' => $gradePoint,
                'nilai_ipk' => $gradePoint,
                'code' => 'DEMO-KHS-'.$period->code.'-'.$student->id,
            ]);
        }
    }

    /** @return array{string, string, string} */
    private function scheduleTimes(int $classIndex, int $courseIndex): array
    {
        $slots = [
            0 => [
                ['13:00:00', '14:20:00', '13:05:00'],
                ['14:25:00', '15:45:00', '14:30:00'],
                ['15:50:00', '17:10:00', '15:55:00'],
                ['17:15:00', '18:35:00', '17:20:00'],
                ['18:35:00', '19:55:00', '18:40:00'],
            ],
            1 => [
                ['13:05:00', '14:25:00', '13:10:00'],
                ['14:30:00', '15:50:00', '14:35:00'],
                ['15:55:00', '17:15:00', '16:00:00'],
                ['17:20:00', '18:40:00', '17:25:00'],
                ['18:40:00', '20:00:00', '18:45:00'],
            ],
        ];
        $slotIndex = $courseIndex % 5;

        return $slots[$classIndex][$slotIndex];
    }

    private function scheduleDay(int $courseIndex): int
    {
        $day = intdiv($courseIndex, 5) + 4;

        if ($day > 6) {
            throw new RuntimeException('Jumlah mata kuliah melebihi 15 slot yang tersedia pada Kamis sampai Sabtu.');
        }

        return $day;
    }

    private function scheduleDate(TahunAkademik $period, int $courseIndex, int $weekOffset): Carbon
    {
        $date = $period->starts_at->copy()->startOfDay();
        $daysUntilSchedule = ($this->scheduleDay($courseIndex) - $date->dayOfWeekIso + 7) % 7;

        return $date->addDays($daysUntilSchedule)->addWeeks($weekOffset);
    }
}
