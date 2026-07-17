<?php

namespace Database\Seeders;

use App\Models\AbsensiMahasiswa;
use App\Models\AcademicWorkflowAudit;
use App\Models\Dosen;
use App\Models\Fakultas;
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
use RuntimeException;

class DemoDuaTahunAkademikSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $lecturers = $this->seedLecturers();
            $studyProgram = $this->seedStudyProgram($lecturers[0]);
            $curriculum = $this->seedCurriculum();
            $room = $this->seedRoom();
            $periods = $this->seedPeriods();
            $masters = $this->seedMasterCourses();
            $students = $this->seedStudents();
            $staff = $this->seedStaff();

            $offeringsByPeriod = [
                '242501' => [
                    [$masters['Pengantar Studi Islam'], 'DEMO-PSI-242501', $lecturers[0]],
                    [$masters['Bahasa Arab Dasar'], 'DEMO-BAD-242501', $lecturers[1]],
                ],
                '242502' => [
                    [$masters['Fikih I'], 'DEMO-FIQ1-242502', $lecturers[0]],
                    [$masters['Ulumul Quran'], 'DEMO-UQ-242502', $lecturers[1]],
                ],
                '252601' => [
                    [$masters['Fikih II'], 'DEMO-FIQ2-252601', $lecturers[0]],
                    [$masters['Studi Hadis'], 'DEMO-HDS-252601', $lecturers[1]],
                ],
                '252602' => [
                    [$masters['Metodologi Studi Islam'], 'DEMO-MSI-252602', $lecturers[0]],
                    [$masters['Pendidikan Islam'], 'DEMO-PI-252602', $lecturers[1]],
                ],
            ];

            foreach (array_values($periods) as $periodIndex => $period) {
                $semester = $periodIndex + 1;
                $program = ProgramKuliah::updateOrCreate(['code' => 'DEMO-REG-'.$period->code], [
                    'taka_id' => $period->id,
                    'pstudi_id' => $studyProgram->id,
                    'name' => 'Reguler Pagi Demo',
                    'wave' => 'Gelombang Demo',
                    'wave_start' => $period->starts_at,
                    'wave_ended' => $period->ends_at,
                ]);
                $class = Kelas::updateOrCreate(['code' => 'DEMO-PAI-S'.$semester], [
                    'taka_id' => $period->id,
                    'pstudi_id' => $studyProgram->id,
                    'proku_id' => $program->id,
                    'dosen_id' => $lecturers[0]->id,
                    'capacity' => 30,
                    'name' => 'Kelas Demo PAI Semester '.$semester,
                ]);

                foreach ($students as $student) {
                    RegistrasiMahasiswa::updateOrCreate([
                        'mahasiswa_id' => $student->id,
                        'taka_id' => $period->id,
                    ], [
                        'semester_mahasiswa' => $semester,
                        'status_akademik' => 'aktif',
                        'status_registrasi' => 'terdaftar',
                        'kelas_id' => $class->id,
                        'dosen_wali_id' => $lecturers[0]->id,
                        'batas_sks' => 24,
                    ]);
                }

                $legacyOfferings = [];
                foreach ($offeringsByPeriod[$period->code] as [$master, $code, $lecturer]) {
                    $legacyOffering = MataKuliah::updateOrCreate(['code' => $code], [
                        'mid' => $master->id,
                        'kuri_id' => $curriculum->id,
                        'taka_id' => $period->id,
                        'pstudi_id' => $studyProgram->id,
                        'kelas_id' => $class->id,
                        'dosen_1' => $lecturer->id,
                        'name' => $master->name,
                        'bsks' => $master->sks,
                        'desc' => 'Penawaran mata kuliah untuk alur demo dua tahun akademik.',
                    ]);
                    $legacyOfferings[] = $legacyOffering;
                }

                $offerings = $this->seedNormalizedOfferings($period, $studyProgram, $curriculum, $class, $legacyOfferings);
                $this->seedAcademicCalendar($period);
                $this->seedAcademicActivities($period, $periodIndex, $class, $room, $legacyOfferings, $students);
                $meetings = $this->seedWeeklySchedulesAndMeetings($period, $class, $room, $offerings);
                $krsItems = $this->seedKrs($period, $periodIndex, $lecturers[0], $offerings, $students);
                $this->linkNormalizedAcademicData($period, $offerings, $meetings, $krsItems, $students);
                $this->seedFinance($period, $periodIndex, $studyProgram, $program, $students, $staff['finance']);
                $this->seedPublication($period, $staff['academic']);
            }

            $latestPeriod = $periods['252602'];
            $latestClass = Kelas::where('code', 'DEMO-PAI-S4')->firstOrFail();
            foreach ($students as $student) {
                $student->update([
                    'taka_id' => $latestPeriod->id,
                    'class_id' => $latestClass->id,
                    'years_id' => 2024,
                ]);
            }
        });
    }

    private function seedPeriods(): array
    {
        $demoCodes = ['242501', '242502', '252601', '252602'];
        $hasExternalActivePeriod = TahunAkademik::query()
            ->where('is_active', true)
            ->whereNotIn('code', $demoCodes)
            ->exists();

        TahunAkademik::query()->whereIn('code', $demoCodes)->where('is_active', true)->update([
            'is_active' => false,
            'status' => TahunAkademik::STATUS_CLOSED,
        ]);

        $definitions = [
            '242501' => ['TA. 2024/2025 Ganjil', 2024, 2025, 1, TahunAkademik::TERM_GANJIL, '2024-08-01', '2025-01-31', TahunAkademik::STATUS_ARCHIVED],
            '242502' => ['TA. 2024/2025 Genap', 2024, 2025, 2, TahunAkademik::TERM_GENAP, '2025-02-01', '2025-07-31', TahunAkademik::STATUS_ARCHIVED],
            '252601' => ['TA. 2025/2026 Ganjil', 2025, 2026, 1, TahunAkademik::TERM_GANJIL, '2025-08-01', '2026-01-31', TahunAkademik::STATUS_CLOSED],
            '252602' => ['TA. 2025/2026 Genap', 2025, 2026, 2, TahunAkademik::TERM_GENAP, '2026-02-01', '2026-07-31', TahunAkademik::STATUS_ACTIVE],
        ];
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

    private function seedStudyProgram(Dosen $head): ProgramStudi
    {
        $faculty = Fakultas::query()
            ->where('code', 'not like', 'DEMO-%')
            ->orderBy('id')
            ->first() ?? Fakultas::query()->orderBy('id')->first();

        if (! $faculty) {
            throw new RuntimeException('Seeder demo membutuhkan minimal satu data fakultas yang sudah tersedia.');
        }

        return ProgramStudi::updateOrCreate(['code' => 'DEMO-PAI'], [
            'faku_id' => $faculty->id,
            'name' => 'Pendidikan Agama Islam Demo',
            'cnim' => '99',
            'slug' => 'pendidikan-agama-islam-demo',
            'head_id' => $head->id,
            'title' => 'S.Pd.',
            'level' => 'S1',
        ]);
    }

    private function seedCurriculum(): Kurikulum
    {
        return Kurikulum::updateOrCreate(['code' => 'DEMO-KUR-2024'], [
            'name' => 'Kurikulum Demo 2024',
            'desc' => 'Kurikulum khusus simulasi alur akademik.',
            'year_start' => 2024,
            'year_ended' => 2028,
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

    private function seedMasterCourses(): array
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
        ];
        $masters = [];

        foreach ($definitions as [$name, $semester, $sks]) {
            $masters[$name] = MasterMataKuliah::updateOrCreate([
                'program_studi' => 'DEMO-PAI',
                'semester' => $semester,
                'name' => $name,
            ], ['sks' => $sks]);
        }

        return $masters;
    }

    private function seedStudents(): array
    {
        $definitions = [
            ['DEMO-MHS-01', '24990001', 'Ali Mahasiswa Demo', 'demo.mahasiswa1', 'demo.mahasiswa1@example.test', '089910000001'],
            ['DEMO-MHS-02', '24990002', 'Siti Mahasiswa Demo', 'demo.mahasiswa2', 'demo.mahasiswa2@example.test', '089910000002'],
        ];

        return array_map(fn (array $data) => Mahasiswa::updateOrCreate(['mhs_code' => $data[0]], [
            'mhs_stat' => 1,
            'mhs_nim' => $data[1],
            'mhs_name' => $data[2],
            'mhs_user' => $data[3],
            'mhs_mail' => $data[4],
            'mhs_phone' => $data[5],
            'password' => Hash::make('Demo123!'),
        ]), $definitions);
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
            'deskripsi' => 'Penawaran normalisasi untuk simulasi dua tahun akademik.',
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
                'code' => 'DEMO-JM-'.$period->code.'-'.($courseIndex + 1),
            ]);
            $legacySchedule = JadwalKuliah::where('code', 'DEMO-JDW-'.$period->code.'-'.($courseIndex + 1))->firstOrFail();

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
                    'code' => 'DEMO-PRT-'.$period->code.'-'.($courseIndex + 1).'-'.$meetingNumber,
                ]);
                $meetings[$offering->id][$meetingNumber] = $meeting;
            }
        }

        return $meetings;
    }

    private function seedKrs(
        TahunAkademik $period,
        int $periodIndex,
        Dosen $advisor,
        array $offerings,
        array $students
    ): array {
        $items = [];

        foreach ($students as $studentIndex => $student) {
            $registration = RegistrasiMahasiswa::where('mahasiswa_id', $student->id)->where('taka_id', $period->id)->firstOrFail();
            $finalStatus = $periodIndex < 3 ? Krs::STATUS_LOCKED : ($studentIndex === 0 ? Krs::STATUS_APPROVED : Krs::STATUS_DRAFT);
            $krs = Krs::updateOrCreate(['registrasi_mahasiswa_id' => $registration->id], [
                'status' => Krs::STATUS_DRAFT,
                'total_sks' => array_sum(array_map(fn ($offering) => (int) $offering->sks, $offerings)),
                'catatan_mahasiswa' => 'KRS demo semester '.($periodIndex + 1).'.',
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
        array $offerings,
        array $meetings,
        array $krsItems,
        array $students
    ): void {
        foreach ($offerings as $courseIndex => $offering) {
            JadwalKuliah::where('code', 'DEMO-JDW-'.$period->code.'-'.($courseIndex + 1))
                ->update(['penawaran_mata_kuliah_id' => $offering->id]);
            NilaiMahasiswa::where('mata_kuliah_id', $offering->legacy_mata_kuliah_id)
                ->update(['penawaran_mata_kuliah_id' => $offering->id]);

            foreach ($students as $studentIndex => $student) {
                AbsensiMahasiswa::where('code', 'DEMO-ABS-'.$period->code.'-'.$courseIndex.'-'.$studentIndex)->update([
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
            $paid = $periodIndex < 3 || $studentIndex === 0;
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
        Kelas $class,
        Ruang $room,
        array $offerings,
        array $students
    ): void {
        $gradeLetters = [['A', 'B'], ['B', 'A'], ['A', 'A'], ['B', 'A']][$periodIndex];
        // Kolom nilai_ips dan nilai_ipk pada skema lama masih bertipe integer.
        $gradePoints = [[3, 3], [3, 4], [4, 4], [4, 4]][$periodIndex];

        foreach ($offerings as $courseIndex => $course) {
            $date = $period->starts_at->copy()->addWeeks(2 + $courseIndex)->toDateString();
            $schedule = JadwalKuliah::updateOrCreate(['code' => 'DEMO-JDW-'.$period->code.'-'.($courseIndex + 1)], [
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
                NilaiMahasiswa::updateOrCreate([
                    'mahasiswa_id' => $student->id,
                    'mata_kuliah_id' => $course->id,
                    'kelas_id' => $class->id,
                ], [
                    'taka_id' => $period->id,
                    'dosen_id' => $course->dosen_1,
                    'nilai' => $gradeLetters[$studentIndex],
                    'keterangan' => 'Nilai demo telah dipublikasikan.',
                ]);
                AbsensiMahasiswa::updateOrCreate([
                    'jadkul_code' => $schedule->code,
                    'author_id' => $student->id,
                ], [
                    'absen_type' => $studentIndex === 0 ? 'H' : 'I',
                    'absen_proof' => 'default/default-profile.jpg',
                    'code' => 'DEMO-ABS-'.$period->code.'-'.$courseIndex.'-'.$studentIndex,
                    'absen_date' => $date,
                    'absen_time' => $courseIndex === 0 ? '08:05:00' : '10:05:00',
                    'absen_desc' => 'Presensi contoh untuk simulasi.',
                ]);
            }

            if ($courseIndex === 0) {
                $task = studentTask::updateOrCreate(['jadkul_id' => $schedule->id], [
                    'dosen_id' => $course->dosen_1,
                    'code' => 'DEMO-TGS-'.$period->code,
                    'title' => 'Tugas Refleksi '.$period->code,
                    'detail_task' => 'Tuliskan refleksi pembelajaran pada periode ini.',
                    'exp_date' => $period->starts_at->copy()->addMonth()->toDateString(),
                    'exp_time' => '23:59:00',
                ]);
                foreach ($students as $studentIndex => $student) {
                    studentScore::updateOrCreate([
                        'stask_id' => $task->id,
                        'student_id' => $student->id,
                    ], [
                        'score' => 8 + $studentIndex,
                        'desc' => 'Jawaban tugas demo.',
                        'code' => (int) ('99'.($periodIndex + 1).'0'.($studentIndex + 1)),
                    ]);
                }
            }
        }

        foreach ($students as $studentIndex => $student) {
            HasilStudi::updateOrCreate([
                'student_id' => $student->id,
                'taka_id' => $period->id,
            ], [
                'score_absen' => 90 - ($studentIndex * 5),
                'score_tugas' => 8 + $studentIndex,
                'score_uts' => 80 + ($periodIndex * 2),
                'score_uas' => 82 + ($periodIndex * 2),
                'max_absen' => 2,
                'max_tugas' => 1,
                'smt_id' => $periodIndex + 1,
                'nilai_ips' => $gradePoints[$studentIndex],
                'nilai_ipk' => $gradePoints[$studentIndex],
                'code' => 'DEMO-KHS-'.$period->code.'-'.$studentIndex,
            ]);
        }
    }
}
