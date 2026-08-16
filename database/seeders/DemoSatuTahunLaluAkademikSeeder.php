<?php

namespace Database\Seeders;

use App\Models\AbsensiMahasiswa;
use App\Models\Dosen;
use App\Models\Gedung;
use App\Models\HasilStudi;
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
use App\Models\PertemuanKuliah;
use App\Models\ProgramKuliah;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoSatuTahunLaluAkademikSeeder extends Seeder
{
    private const STUDENT_COUNT = 12;

    protected function entryYear(): int
    {
        return 2024;
    }

    protected function prefix(): string
    {
        return 'SATUTA';
    }

    protected function historyLabel(): string
    {
        return 'Satu Tahun Lalu';
    }

    public function run(): void
    {
        DB::transaction(function (): void {
            $studyProgram = $this->existingStudyProgram();
            $lecturers = $this->seedLecturers();
            $students = $this->seedStudents();
            $periods = $this->seedPeriods();
            $curriculum = $this->seedCurriculum();
            $room = $this->seedRoom();
            $masters = $this->seedMasterCourses($studyProgram);
            $classPrefix = $this->prefix().'-KLS-'.strtoupper(Str::slug($studyProgram->code));

            foreach (array_values($periods) as $periodIndex => $period) {
                $studentSemester = $periodIndex + 1;
                $program = ProgramKuliah::updateOrCreate([
                    'code' => $this->prefix().'-REG-'.$period->code,
                ], [
                    'taka_id' => $period->id,
                    'pstudi_id' => $studyProgram->id,
                    'name' => 'Reguler Demo '.$this->historyLabel(),
                    'wave' => 'Gelombang '.$this->entryYear(),
                    'wave_start' => $period->starts_at,
                    'wave_ended' => $period->ends_at,
                ]);
                $class = Kelas::updateOrCreate([
                    'code' => $classPrefix.'-S'.$studentSemester,
                ], [
                    'taka_id' => $period->id,
                    'pstudi_id' => $studyProgram->id,
                    'proku_id' => $program->id,
                    'dosen_id' => $lecturers[0]->id,
                    'capacity' => 30,
                    'name' => 'Kelas Angkatan '.$this->entryYear().' Semester '.$studentSemester,
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
                }

                $offerings = $this->seedOfferings(
                    $period,
                    $studentSemester,
                    $studyProgram,
                    $curriculum,
                    $class,
                    $lecturers,
                    $masters
                );
                $this->seedCalendar($period);
                $krsItems = $this->seedKrs($period, $studentSemester, $lecturers[0], $offerings, $students);
                $this->seedSchedulesGradesAndAttendance(
                    $period,
                    $studentSemester,
                    $class,
                    $room,
                    $offerings,
                    $krsItems,
                    $students
                );

                foreach ($students as $student) {
                    $student->update([
                        'taka_id' => $period->id,
                        'class_id' => $class->id,
                        'years_id' => $this->entryYear(),
                    ]);
                }
            }
        });
    }

    private function existingStudyProgram(): ProgramStudi
    {
        $studyProgram = ProgramStudi::query()
            ->where('code', 'not like', 'DEMO-%')
            ->orderBy('id')
            ->first();

        if (! $studyProgram) {
            throw new RuntimeException('Seeder historis membutuhkan minimal satu program studi yang sudah tersedia.');
        }

        return $studyProgram;
    }

    private function seedPeriods(): array
    {
        $yearStart = $this->entryYear();
        $yearEnd = $yearStart + 1;
        $yearCode = substr((string) $yearStart, -2).substr((string) $yearEnd, -2);
        $definitions = [
            $yearCode.'01' => [
                'TA. '.$yearStart.'/'.$yearEnd.' Ganjil',
                $yearStart,
                $yearEnd,
                1,
                TahunAkademik::TERM_GANJIL,
                $yearStart.'-08-01',
                $yearEnd.'-01-31',
            ],
            $yearCode.'02' => [
                'TA. '.$yearStart.'/'.$yearEnd.' Genap',
                $yearStart,
                $yearEnd,
                2,
                TahunAkademik::TERM_GENAP,
                $yearEnd.'-02-01',
                $yearEnd.'-07-31',
            ],
        ];
        $periods = [];
        $activeCode = $yearCode.'02';

        TahunAkademik::query()
            ->where('is_active', true)
            ->where('code', '!=', $activeCode)
            ->update([
                'is_active' => false,
                'status' => TahunAkademik::STATUS_CLOSED,
            ]);

        foreach ($definitions as $code => [$name, $yearStart, $yearEnd, $semester, $term, $startsAt, $endsAt]) {
            $isActive = (string) $code === $activeCode;
            $periods[$code] = TahunAkademik::updateOrCreate(['code' => $code], [
                'name' => $name,
                'year_start' => $yearStart,
                'year_end' => $yearEnd,
                'semester' => $semester,
                'term' => $term,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $isActive ? TahunAkademik::STATUS_ACTIVE : TahunAkademik::STATUS_ARCHIVED,
                'is_active' => $isActive,
                'activated_at' => $isActive ? now() : null,
                'is_published' => true,
                'published_at' => $endsAt,
                'published_by' => null,
            ]);
        }

        return $periods;
    }

    private function seedLecturers(): array
    {
        $prefix = $this->prefix();
        $usernamePrefix = strtolower($prefix);
        $yearSuffix = substr((string) $this->entryYear(), -2);
        $definitions = [
            [$prefix.'-DSN-01', '97'.$yearSuffix.'000001', 'Dosen Wali '.$this->historyLabel(), $usernamePrefix.'.dosen1', $usernamePrefix.'.dosen1@example.test', '0877'.$yearSuffix.'0001'],
            [$prefix.'-DSN-02', '97'.$yearSuffix.'000002', 'Dosen Pengampu '.$this->historyLabel(), $usernamePrefix.'.dosen2', $usernamePrefix.'.dosen2@example.test', '0877'.$yearSuffix.'0002'],
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

    private function seedStudents(): array
    {
        $prefix = $this->prefix();
        $usernamePrefix = strtolower($prefix);
        $entryYear = $this->entryYear();
        $yearSuffix = substr((string) $entryYear, -2);
        $names = [
            'Aulia Rahman',
            'Nisa Khairunnisa',
            'Fajar Ramadhan',
            'Putri Amelia',
            'Rifki Maulana',
            'Salma Nur Azizah',
            'Ilham Hidayat',
            'Zahra Fadillah',
            'Reza Firmansyah',
            'Anisa Fitriani',
            'Dimas Saputra',
            'Laila Mardhiyah',
        ];
        $students = [];

        foreach (array_slice($names, 0, self::STUDENT_COUNT) as $index => $name) {
            $number = $index + 1;
            $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $students[] = Mahasiswa::updateOrCreate([
                'mhs_code' => $prefix.'-MHS-'.$suffix,
            ], [
                'mhs_stat' => 1,
                'mhs_nim' => $yearSuffix.'9800'.$suffix,
                'mhs_name' => $name.' (Angkatan '.$entryYear.')',
                'mhs_user' => $usernamePrefix.'.mahasiswa'.$number,
                'mhs_mail' => $usernamePrefix.'.mahasiswa'.$number.'@example.test',
                'mhs_phone' => '0888'.$yearSuffix.'00'.$suffix,
                'password' => Hash::make('Demo123!'),
            ]);
        }

        return $students;
    }

    private function seedCurriculum(): Kurikulum
    {
        $entryYear = $this->entryYear();

        return Kurikulum::updateOrCreate(['code' => $this->prefix().'-KUR-'.$entryYear], [
            'name' => 'Kurikulum Demo Angkatan '.$entryYear,
            'desc' => 'Kurikulum untuk simulasi data akademik '.$this->historyLabel().'.',
            'year_start' => $entryYear,
            'year_ended' => $entryYear + 4,
        ]);
    }

    private function seedRoom(): Ruang
    {
        $building = Gedung::updateOrCreate(['code' => $this->prefix().'-GDG'], [
            'name' => 'Gedung Demo '.$this->historyLabel(),
        ]);

        return Ruang::updateOrCreate(['code' => $this->prefix().'-R101'], [
            'gedu_id' => $building->id,
            'type' => 0,
            'floor' => 1,
            'name' => 'Ruang Demo 101',
        ]);
    }

    private function seedMasterCourses(ProgramStudi $studyProgram): array
    {
        $definitions = [
            ['Literasi Akademik', 1, 2],
            ['Pengantar Keilmuan Program Studi', 1, 3],
            ['Metodologi Belajar', 2, 2],
            ['Dasar Keilmuan Program Studi', 2, 3],
        ];
        $masters = [];

        foreach ($definitions as [$name, $semester, $sks]) {
            $masters[$semester][] = MasterMataKuliah::updateOrCreate([
                'program_studi' => $studyProgram->code,
                'semester' => $semester,
                'name' => $name,
            ], [
                'sks' => $sks,
            ]);
        }

        return $masters;
    }

    private function seedOfferings(
        TahunAkademik $period,
        int $studentSemester,
        ProgramStudi $studyProgram,
        Kurikulum $curriculum,
        Kelas $class,
        array $lecturers,
        array $masters
    ): array {
        $offerings = [];

        foreach ($masters[$studentSemester] as $courseIndex => $master) {
            $lecturer = $lecturers[$courseIndex % count($lecturers)];
            $legacy = MataKuliah::updateOrCreate([
                'code' => $this->prefix().'-MK-'.$period->code.'-'.($courseIndex + 1),
            ], [
                'mid' => $master->id,
                'kuri_id' => $curriculum->id,
                'taka_id' => $period->id,
                'pstudi_id' => $studyProgram->id,
                'kelas_id' => $class->id,
                'dosen_1' => $lecturer->id,
                'name' => $master->name,
                'bsks' => $master->sks,
                'desc' => 'Penawaran mata kuliah demo tahun akademik '.$period->year_start.'/'.$period->year_end.'.',
            ]);
            $offerings[] = PenawaranMataKuliah::updateOrCreate([
                'legacy_mata_kuliah_id' => $legacy->id,
            ], [
                'master_mata_kuliah_id' => $master->id,
                'taka_id' => $period->id,
                'pstudi_id' => $studyProgram->id,
                'kuri_id' => $curriculum->id,
                'kelas_id' => $class->id,
                'dosen_utama_id' => $lecturer->id,
                'code' => $legacy->code,
                'sks' => $master->sks,
                'kapasitas' => 30,
                'deskripsi' => 'Penawaran normalisasi demo '.$this->historyLabel().'.',
            ]);
        }

        return $offerings;
    }

    private function seedCalendar(TahunAkademik $period): void
    {
        $events = [
            ['krs', 'Pengisian KRS', 0, 14],
            ['perkuliahan', 'Perkuliahan', 14, 112],
            ['uts', 'Ujian Tengah Semester', 56, 63],
            ['uas', 'Ujian Akhir Semester', 112, 119],
        ];

        foreach ($events as [$category, $name, $startDay, $endDay]) {
            KalenderAkademik::updateOrCreate([
                'taka_id' => $period->id,
                'kategori' => $category,
                'nama' => $name.' '.$this->historyLabel(),
            ], [
                'mulai_at' => $period->starts_at->copy()->addDays($startDay)->startOfDay(),
                'selesai_at' => $period->starts_at->copy()->addDays($endDay)->endOfDay(),
                'dipublikasikan' => true,
            ]);
        }
    }

    private function seedKrs(
        TahunAkademik $period,
        int $studentSemester,
        Dosen $advisor,
        array $offerings,
        array $students
    ): array {
        $items = [];

        foreach ($students as $student) {
            $registration = RegistrasiMahasiswa::query()
                ->where('mahasiswa_id', $student->id)
                ->where('taka_id', $period->id)
                ->firstOrFail();
            $krs = Krs::updateOrCreate([
                'registrasi_mahasiswa_id' => $registration->id,
            ], [
                'status' => Krs::STATUS_DRAFT,
                'total_sks' => array_sum(array_map(fn ($offering) => (int) $offering->sks, $offerings)),
                'catatan_mahasiswa' => 'KRS historis semester '.$studentSemester.'.',
                'catatan_keputusan' => 'Disetujui dosen wali untuk data historis.',
                'diajukan_at' => $period->starts_at->copy()->addDays(5),
                'diputuskan_at' => $period->starts_at->copy()->addDays(7),
                'diputuskan_oleh' => $advisor->id,
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
            $krs->update(['status' => Krs::STATUS_LOCKED]);
        }

        return $items;
    }

    private function seedSchedulesGradesAndAttendance(
        TahunAkademik $period,
        int $studentSemester,
        Kelas $class,
        Ruang $room,
        array $offerings,
        array $krsItems,
        array $students
    ): void {
        foreach ($offerings as $courseIndex => $offering) {
            $date = $period->starts_at->copy()->addWeeks(2 + $courseIndex)->toDateString();
            $legacySchedule = JadwalKuliah::updateOrCreate([
                'code' => $this->prefix().'-JDW-'.$period->code.'-'.($courseIndex + 1),
            ], [
                'makul_id' => $offering->legacy_mata_kuliah_id,
                'penawaran_mata_kuliah_id' => $offering->id,
                'kelas_id' => $class->id,
                'dosen_id' => $offering->dosen_utama_id,
                'ruang_id' => $room->id,
                'pert_id' => 1,
                'meth_id' => 0,
                'days_id' => $courseIndex + 1,
                'bsks' => min(8, (int) $offering->sks),
                'date' => $date,
                'start' => $courseIndex === 0 ? '08:00:00' : '10:00:00',
                'ended' => $courseIndex === 0 ? '09:40:00' : '11:40:00',
            ]);
            $weeklyAttributes = [
                'penawaran_mata_kuliah_id' => $offering->id,
                'kelas_id' => $class->id,
                'dosen_id' => $offering->dosen_utama_id,
                'ruang_id' => $room->id,
                'hari' => $courseIndex + 1,
                'mulai' => $legacySchedule->start,
                'selesai' => $legacySchedule->ended,
            ];
            $weekly = JadwalMingguan::updateOrCreate([
                'fingerprint' => JadwalMingguan::fingerprint($weeklyAttributes),
            ], $weeklyAttributes + [
                'code' => $this->prefix().'-JM-'.$period->code.'-'.($courseIndex + 1),
            ]);
            $firstMeeting = null;

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
                    'metode' => 'tatap_muka',
                    'materi' => 'Materi historis pertemuan '.$meetingNumber,
                    'status' => PertemuanKuliah::STATUS_COMPLETED,
                    'code' => $this->prefix().'-PRT-'.$period->code.'-'.($courseIndex + 1).'-'.$meetingNumber,
                ]);
                $firstMeeting ??= $meeting;
            }

            foreach ($students as $studentIndex => $student) {
                $gradePoint = 4 - (($studentSemester + $studentIndex + $courseIndex) % 2);
                NilaiMahasiswa::updateOrCreate([
                    'mahasiswa_id' => $student->id,
                    'mata_kuliah_id' => $offering->legacy_mata_kuliah_id,
                    'kelas_id' => $class->id,
                ], [
                    'penawaran_mata_kuliah_id' => $offering->id,
                    'taka_id' => $period->id,
                    'dosen_id' => $offering->dosen_utama_id,
                    'nilai' => $gradePoint === 4 ? 'A' : 'B',
                    'keterangan' => 'Nilai historis telah dipublikasikan.',
                ]);
                AbsensiMahasiswa::updateOrCreate([
                    'jadkul_code' => $legacySchedule->code,
                    'author_id' => $student->id,
                ], [
                    'pertemuan_kuliah_id' => $firstMeeting->id,
                    'krs_item_id' => $krsItems[$student->id][$offering->id],
                    'absen_type' => $studentIndex % 6 === 5 ? 'I' : 'H',
                    'absen_proof' => 'default/default-profile.jpg',
                    'code' => $this->prefix().'-ABS-'.$period->code.'-'.$courseIndex.'-'.$student->id,
                    'absen_date' => $date,
                    'absen_time' => $courseIndex === 0 ? '08:05:00' : '10:05:00',
                    'absen_desc' => 'Presensi historis.',
                ]);
            }
        }

        foreach ($students as $studentIndex => $student) {
            $gradePoint = 4 - (($studentSemester + $studentIndex) % 2);
            HasilStudi::updateOrCreate([
                'student_id' => $student->id,
                'taka_id' => $period->id,
            ], [
                'score_absen' => 90 - (($studentIndex % 4) * 5),
                'score_tugas' => 8 + ($studentIndex % 3),
                'score_uts' => 80 + $studentSemester,
                'score_uas' => 82 + $studentSemester,
                'max_absen' => 2,
                'max_tugas' => 1,
                'smt_id' => $studentSemester,
                'nilai_ips' => $gradePoint,
                'nilai_ipk' => $gradePoint,
                'code' => $this->prefix().'-KHS-'.$period->code.'-'.$student->id,
            ]);
        }
    }
}
