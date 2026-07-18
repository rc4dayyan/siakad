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
use App\Models\TemplateTagihan;
use App\Models\User;
use App\Services\Academic\PeriodReadinessService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoDuaTahunAkademikSeeder extends Seeder
{
    private const LATEST_PERIOD_CODE = '252602';

    private const STUDENTS_PER_COHORT = 12;

    public function run(): void
    {
        DB::transaction(function (): void {
            $lecturers = $this->seedLecturers();
            $studyProgram = $this->seedStudyProgram();
            $curriculum = $this->seedCurriculum();
            $room = $this->seedRoom();
            $periods = $this->seedPeriods();
            $masters = $this->seedMasterCourses($studyProgram);
            $studentCohorts = $this->seedStudents();
            $staff = $this->seedStaff();
            $classCodePrefix = 'DEMO-KLS-'.strtoupper(Str::slug($studyProgram->code));

            $courseNamesBySemester = [
                1 => ['Pengantar Studi Islam', 'Bahasa Arab Dasar'],
                2 => ['Fikih I', 'Ulumul Quran'],
                3 => ['Fikih II', 'Studi Hadis'],
                4 => ['Metodologi Studi Islam', 'Pendidikan Islam'],
                5 => ['Akhlak Tasawuf', 'Sejarah Peradaban Islam'],
                6 => ['Ushul Fikih', 'Ilmu Kalam'],
                7 => ['Tafsir Tarbawi', 'Hadis Tarbawi'],
                8 => ['Filsafat Pendidikan Islam', 'Manajemen Pendidikan Islam'],
                9 => ['Metodologi Penelitian', 'Statistik Pendidikan'],
                10 => ['Pengembangan Kurikulum', 'Evaluasi Pembelajaran'],
                11 => ['Seminar Proposal', 'Praktik Pengalaman Lapangan'],
                12 => ['Kuliah Kerja Nyata', 'Skripsi'],
            ];

            foreach (array_values($periods) as $periodIndex => $period) {
                $program = ProgramKuliah::updateOrCreate(['code' => 'DEMO-REG-'.$period->code], [
                    'taka_id' => $period->id,
                    'pstudi_id' => $studyProgram->id,
                    'name' => 'Reguler Pagi Demo',
                    'wave' => 'Gelombang Demo',
                    'wave_start' => $period->starts_at,
                    'wave_ended' => $period->ends_at,
                ]);
                $this->seedAcademicCalendar($period);
                $activeStudents = [];

                foreach ($studentCohorts as $entryYear => $students) {
                    if ($entryYear > $period->year_start) {
                        continue;
                    }

                    $studentSemester = (($period->year_start - $entryYear) * 2) + (int) $period->raw_semester;
                    $class = Kelas::updateOrCreate([
                        'code' => $classCodePrefix.'-A'.$entryYear.'-S'.$studentSemester,
                    ], [
                        'taka_id' => $period->id,
                        'pstudi_id' => $studyProgram->id,
                        'proku_id' => $program->id,
                        'dosen_id' => $lecturers[0]->id,
                        'capacity' => 30,
                        'name' => 'Kelas Demo Angkatan '.$entryYear.' Semester '.$studentSemester,
                    ]);

                    foreach ($students as $student) {
                        RegistrasiMahasiswa::updateOrCreate([
                            'mahasiswa_id' => $student->id,
                            'taka_id' => $period->id,
                        ], [
                            'semester_mahasiswa' => $studentSemester,
                            'status_akademik' => 'aktif',
                            'status_registrasi' => 'terdaftar',
                            'kelas_id' => $class->id,
                            'dosen_wali_id' => $lecturers[0]->id,
                            'batas_sks' => 24,
                        ]);
                        $student->update([
                            'taka_id' => $period->id,
                            'class_id' => $class->id,
                            'years_id' => $entryYear,
                        ]);
                    }
                    $activeStudents = array_merge($activeStudents, $students);

                    $legacyOfferings = [];
                    foreach ($courseNamesBySemester[$studentSemester] as $courseIndex => $courseName) {
                        $master = $masters[$courseName];
                        $lecturer = $lecturers[($studentSemester + $courseIndex - 1) % count($lecturers)];
                        $legacyOfferings[] = MataKuliah::updateOrCreate([
                            'code' => 'DEMO-MK-'.$period->code.'-A'.$entryYear.'-'.($courseIndex + 1),
                        ], [
                            'mid' => $master->id,
                            'kuri_id' => $curriculum->id,
                            'taka_id' => $period->id,
                            'pstudi_id' => $studyProgram->id,
                            'kelas_id' => $class->id,
                            'dosen_1' => $lecturer->id,
                            'name' => $master->name,
                            'bsks' => $master->sks,
                            'desc' => 'Penawaran mata kuliah demo angkatan '.$entryYear.'.',
                        ]);
                    }

                    $offerings = $this->seedNormalizedOfferings($period, $studyProgram, $curriculum, $class, $legacyOfferings);
                    $this->seedAcademicActivities($period, $periodIndex, $studentSemester, $class, $room, $legacyOfferings, $students);
                    $meetings = $this->seedWeeklySchedulesAndMeetings($period, $class, $room, $offerings);
                    $krsItems = $this->seedKrs($period, $studentSemester, $lecturers[0], $offerings, $students);
                    $this->linkNormalizedAcademicData($period, $class, $offerings, $meetings, $krsItems, $students);
                }

                $this->seedFinance($period, $periodIndex, $studyProgram, $program, $activeStudents, $staff['finance']);
                $this->seedPublication($period, $staff['academic']);
            }
        });
    }

    private function seedPeriods(): array
    {
        $definitions = [];

        for ($startYear = 2020; $startYear <= 2025; $startYear++) {
            $endYear = $startYear + 1;
            $yearCode = substr((string) $startYear, -2).substr((string) $endYear, -2);

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

        foreach ($definitions as $code => [$name, $startYear, $endYear, $semester, $term, $startsAt, $endsAt, $status]) {
            $isActive = $status === TahunAkademik::STATUS_ACTIVE && ! $hasExternalActivePeriod;
            $periods[$code] = TahunAkademik::updateOrCreate(['code' => $code], [
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
            ]);
        }

        return $periods;
    }

    private function seedLecturers(): array
    {
        $definitions = [
            ['DEMO-DSN-01', '9900000001', 'DEVI GANJAR MUSTHOFA, M.Pd', 'demo.dosen1', 'demo.dosen1@example.test', '089900000001'],
            ['DEMO-DSN-02', '9900000002', 'ENIH HARTIANI, M.Pd', 'demo.dosen2', 'demo.dosen2@example.test', '089900000002'],
            ['DEMO-DSN-03', '9900000003', 'ANTO FEBRIANTO, M.Pd', 'demo.dosen3', 'demo.dosen3@example.test', '089900000003'],
            ['DEMO-DSN-04', '9900000004', 'MUHAMMAD ZAKIYAMAN, M.Pd.I', 'demo.dosen4', 'demo.dosen4@example.test', '089900000004'],
        ];

        return array_map(fn (array $data) => Dosen::updateOrCreate(['dsn_code' => $data[0]], [
            'dsn_stat' => 1,
            'dsn_nidn' => $data[1],
            'dsn_name' => $data[2],
            'dsn_user' => $data[3],
            'dsn_mail' => $data[4],
            'dsn_phone' => $data[5],
            'password' => Hash::make('Demo123!'),
        ]), $definitions);
    }

    private function seedStudyProgram(): ProgramStudi
    {
        $studyProgram = ProgramStudi::query()
            ->where('code', 'not like', 'DEMO-%')
            ->orderBy('id')
            ->first() ?? ProgramStudi::query()->orderBy('id')->first();

        if (! $studyProgram) {
            throw new RuntimeException('Seeder demo membutuhkan minimal satu data program studi yang sudah tersedia.');
        }

        return $studyProgram;
    }

    private function seedCurriculum(): Kurikulum
    {
        return Kurikulum::updateOrCreate(['code' => 'DEMO-KUR-2024'], [
            'name' => 'Kurikulum Demo Enam Tahun',
            'desc' => 'Kurikulum khusus simulasi alur akademik enam tahun.',
            'year_start' => 2020,
            'year_ended' => 2026,
        ]);
    }

    private function seedRoom(): Ruang
    {
        $building = Gedung::updateOrCreate(['code' => 'DEMO-GDG'], ['name' => 'Gedung Perkuliahan Demo']);

        return Ruang::updateOrCreate(['code' => 'DEMO-R101'], [
            'gedu_id' => $building->id,
            'type' => 0,
            'floor' => 1,
            'name' => 'Ruang Demo 101',
        ]);
    }

    private function seedMasterCourses(ProgramStudi $studyProgram): array
    {
        $definitions = [
            ['Pengantar Studi Islam', 1, 3],
            ['Bahasa Arab Dasar', 1, 2],
            ['Fikih I', 2, 3],
            ['Ulumul Quran', 2, 3],
            ['Fikih II', 3, 3],
            ['Studi Hadis', 3, 3],
            ['Metodologi Studi Islam', 4, 3],
            ['Pendidikan Islam', 4, 3],
            ['Akhlak Tasawuf', 5, 3],
            ['Sejarah Peradaban Islam', 5, 3],
            ['Ushul Fikih', 6, 3],
            ['Ilmu Kalam', 6, 2],
            ['Tafsir Tarbawi', 7, 3],
            ['Hadis Tarbawi', 7, 3],
            ['Filsafat Pendidikan Islam', 8, 3],
            ['Manajemen Pendidikan Islam', 8, 3],
            ['Metodologi Penelitian', 9, 3],
            ['Statistik Pendidikan', 9, 2],
            ['Pengembangan Kurikulum', 10, 3],
            ['Evaluasi Pembelajaran', 10, 3],
            ['Seminar Proposal', 11, 2],
            ['Praktik Pengalaman Lapangan', 11, 4],
            ['Kuliah Kerja Nyata', 12, 4],
            ['Skripsi', 12, 6],
        ];
        $masters = [];

        foreach ($definitions as [$name, $semester, $sks]) {
            $master = MasterMataKuliah::query()
                ->where('program_studi', $studyProgram->code)
                ->where('semester', $semester)
                ->where('name', $name)
                ->first();

            $master ??= new MasterMataKuliah;
            $master->fill([
                'program_studi' => $studyProgram->code,
                'semester' => $semester,
                'name' => $name,
                'sks' => $sks,
            ])->save();
            $masters[$name] = $master;
        }

        return $masters;
    }

    private function seedStudents(): array
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

        for ($entryYear = 2020; $entryYear <= 2025; $entryYear++) {
            $yearSuffix = substr((string) $entryYear, -2);
            $definitions = [];

            foreach (array_slice($names, 0, self::STUDENTS_PER_COHORT) as $index => $name) {
                $number = $index + 1;
                $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
                $isFirstCohort = $entryYear === 2020;
                $definitions[] = [
                    $isFirstCohort ? 'DEMO-MHS-'.$suffix : 'DEMO-MHS-'.$yearSuffix.'-'.$suffix,
                    $yearSuffix.'9900'.$suffix,
                    $name.' (Angkatan '.$entryYear.')',
                    $isFirstCohort ? 'demo.mahasiswa'.$number : 'demo.mahasiswa'.$yearSuffix.'.'.$number,
                    $isFirstCohort
                        ? 'demo.mahasiswa'.$number.'@example.test'
                        : 'demo.mahasiswa'.$yearSuffix.'.'.$number.'@example.test',
                    '0899'.$yearSuffix.'00'.$suffix,
                ];
            }

            $cohorts[$entryYear] = array_map(fn (array $data) => Mahasiswa::updateOrCreate(['mhs_code' => $data[0]], [
                'mhs_stat' => 1,
                'mhs_nim' => $data[1],
                'mhs_name' => $data[2],
                'mhs_user' => $data[3],
                'mhs_mail' => $data[4],
                'mhs_phone' => $data[5],
                'password' => Hash::make('Demo123!'),
            ]), $definitions);
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
            'deskripsi' => 'Penawaran normalisasi untuk simulasi enam tahun akademik.',
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
        array $offerings
    ): array {
        $meetings = [];

        foreach ($offerings as $courseIndex => $offering) {
            $attributes = [
                'penawaran_mata_kuliah_id' => $offering->id,
                'kelas_id' => $class->id,
                'dosen_id' => $offering->dosen_utama_id,
                'ruang_id' => $room->id,
                'hari' => $courseIndex + 1,
                'mulai' => $courseIndex === 0 ? '08:00:00' : '10:00:00',
                'selesai' => $courseIndex === 0 ? '09:40:00' : '11:40:00',
            ];
            $fingerprint = JadwalMingguan::fingerprint($attributes);
            $weekly = JadwalMingguan::updateOrCreate(['fingerprint' => $fingerprint], $attributes + [
                'code' => 'DEMO-JM-'.$period->code.'-'.$class->id.'-'.($courseIndex + 1),
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
                    'tanggal' => $period->starts_at->copy()->addWeeks($meetingNumber + 1)->addDays($courseIndex),
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
        $template = TemplateTagihan::updateOrCreate([
            'taka_id' => $period->id,
            'jenis' => 'ukt',
            'name' => 'UKT Demo '.$period->code,
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
            $billCode = 'DEMO-TGH-'.$period->code.'-'.($studentIndex + 1);
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
            HistoryTagihan::updateOrCreate(['code' => 'DEMO-BYR-'.$period->code.'-'.($studentIndex + 1)], [
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
        array $students
    ): void {
        foreach ($offerings as $courseIndex => $course) {
            $date = $period->starts_at->copy()->addWeeks(2 + $courseIndex)->toDateString();
            $schedule = JadwalKuliah::updateOrCreate(['code' => 'DEMO-JDW-'.$period->code.'-'.$class->id.'-'.($courseIndex + 1)], [
                'makul_id' => $course->id,
                'kelas_id' => $class->id,
                'dosen_id' => $course->dosen_1,
                'ruang_id' => $room->id,
                'pert_id' => 1,
                'meth_id' => 0,
                'days_id' => 1 + $courseIndex,
                'bsks' => min(8, (int) $course->bsks),
                'date' => $date,
                'start' => $courseIndex === 0 ? '08:00:00' : '10:00:00',
                'ended' => $courseIndex === 0 ? '09:40:00' : '11:40:00',
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
                    'nilai' => $gradePoint === 4 ? 'A' : 'B',
                    'keterangan' => 'Nilai demo telah dipublikasikan.',
                ]);
                AbsensiMahasiswa::updateOrCreate([
                    'jadkul_code' => $schedule->code,
                    'author_id' => $student->id,
                ], [
                    'absen_type' => $studentIndex % 5 === 4 ? 'I' : 'H',
                    'absen_proof' => 'default/default-profile.jpg',
                    'code' => 'DEMO-ABS-'.$period->code.'-'.$class->id.'-'.$courseIndex.'-'.$student->id,
                    'absen_date' => $date,
                    'absen_time' => $courseIndex === 0 ? '08:05:00' : '10:05:00',
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
                        'score' => $taskScore,
                        'desc' => 'Jawaban tugas demo.',
                        'code' => 900000000 + (($periodIndex + 1) * 1000000) + ($studentSemester * 10000) + $studentIndex + 1,
                    ]);
                }
            }
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
}
