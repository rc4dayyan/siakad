<?php

namespace App\Services\Imports;

use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\PenawaranMataKuliah;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KrsOpenFeederImportService
{
    private const REQUIRED_HEADERS = [
        'NIM',
        'Nama',
        'Semester',
        'Kode Mata Kuliah',
        'Nama Mata Kuliah',
        'Nama Kelas',
        'Kode Prodi',
        'Nama Prodi',
        'Nilai Huruf',
        'Nilai Indeks',
        'Nilai Angka',
    ];

    /**
     * @return array{students_created: int, registrations_created: int, krs_created: int, items_created: int, items_skipped: int, classes_resized: int}
     */
    public function import(
        Collection $rows,
        TahunAkademik $period,
        ProgramStudi $program,
        int $studentSemester,
        bool $dryRun = false
    ): array {
        if ($rows->isEmpty()) {
            $this->reject('File import tidak berisi data KRS.');
        }

        $missingHeaders = array_diff(self::REQUIRED_HEADERS, array_keys($rows->first()));
        if ($missingHeaders !== []) {
            $this->reject('Kolom wajib format KRS OpenFeeder tidak ditemukan: '.implode(', ', $missingHeaders).'.');
        }

        $expectedSemester = $this->openFeederSemester($period);
        $aliases = $program->masterMataKuliahCodes();
        $masters = MasterMataKuliah::query()->whereIn('program_studi', $aliases)->get();
        $classes = Kelas::query()->forAcademicPeriod($period)->where('pstudi_id', $program->id)->get();
        $offerings = PenawaranMataKuliah::query()
            ->forAcademicPeriod($period)
            ->where('pstudi_id', $program->id)
            ->get();
        $students = Mahasiswa::query()
            ->whereIn('mhs_nim', $rows->pluck('NIM')->map(fn ($nim) => $this->digits($nim))->unique())
            ->get()
            ->keyBy(fn (Mahasiswa $student) => (string) $student->mhs_nim);
        $newStudents = [];
        $prepared = [];
        $studentsByClass = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $nim = $this->digits($row['NIM'] ?? null);
            $studentName = trim((string) ($row['Nama'] ?? ''));
            $semester = $this->digits($row['Semester'] ?? null);
            $programCode = trim((string) ($row['Kode Prodi'] ?? ''));
            $courseCode = trim((string) ($row['Kode Mata Kuliah'] ?? ''));
            $classLabel = trim((string) ($row['Nama Kelas'] ?? ''));

            if ($semester !== $expectedSemester) {
                $this->reject("Baris {$rowNumber}: Semester harus {$expectedSemester} sesuai periode {$period->name}.");
            }

            if (! collect($aliases)->contains(fn (string $alias): bool => strcasecmp($alias, $programCode) === 0)) {
                $this->reject("Baris {$rowNumber}: Kode Prodi {$programCode} tidak sesuai dengan program studi yang dipilih.");
            }

            $student = $students->get($nim);
            if (! $student) {
                if ($studentName === '' || mb_strlen($studentName) > 255) {
                    $this->reject("Baris {$rowNumber}: Nama wajib diisi dan maksimal 255 karakter untuk mahasiswa baru.");
                }

                $email = $nim.'@import.invalid';
                $accountConflict = Mahasiswa::query()
                    ->where('mhs_user', $nim)
                    ->orWhere('mhs_mail', $email)
                    ->orWhere('mhs_phone', $nim)
                    ->exists();
                if ($accountConflict) {
                    $this->reject("Baris {$rowNumber}: akun untuk NIM {$nim} bentrok dengan username, email, atau telepon mahasiswa lain.");
                }

                $student = new Mahasiswa([
                    'taka_id' => $period->id,
                    'years_id' => $this->entryYear($period, $studentSemester),
                    'mhs_stat' => 1,
                    'mhs_nim' => $nim,
                    'mhs_name' => $studentName,
                    'mhs_code' => Str::random(12),
                    'mhs_user' => $nim,
                    'password' => Hash::make($nim),
                    'mhs_mail' => $email,
                    'mhs_phone' => $nim,
                ]);
                $students->put($nim, $student);
                $newStudents[$nim] = $student;
            }

            if ($studentName !== '' && strcasecmp(trim((string) $student->mhs_name), $studentName) !== 0) {
                $this->reject("Baris {$rowNumber}: Nama mahasiswa untuk NIM {$nim} tidak sesuai dengan data mahasiswa.");
            }

            $master = $masters->first(fn (MasterMataKuliah $item): bool => strcasecmp((string) $item->code, $courseCode) === 0);
            if (! $master) {
                $this->reject("Baris {$rowNumber}: Kode Mata Kuliah {$courseCode} tidak ditemukan pada program studi yang dipilih.");
            }

            if ((int) $master->semester !== $studentSemester) {
                $this->reject("Baris {$rowNumber}: mata kuliah {$courseCode} berada pada semester {$master->semester}, bukan semester {$studentSemester}.");
            }

            $className = $this->programToken($program).' '.$this->romanSemester($studentSemester).' '.$classLabel;
            $class = $classes->first(fn (Kelas $item): bool => strcasecmp($item->name, $className) === 0);
            if (! $class) {
                $this->reject("Baris {$rowNumber}: kelas {$className} tidak ditemukan pada periode dan program studi yang dipilih.");
            }

            if (! $student->exists && ! isset($prepared[$nim])) {
                $student->taka_id = $period->id;
                $student->class_id = $class->id;
            }

            $offering = $offerings->first(fn (PenawaranMataKuliah $item): bool => (int) $item->master_mata_kuliah_id === (int) $master->id
                && (int) $item->kelas_id === (int) $class->id);
            if (! $offering) {
                $this->reject("Baris {$rowNumber}: penawaran {$courseCode} untuk kelas {$className} belum tersedia. Jalankan langkah 4 terlebih dahulu.");
            }

            $studentKey = $nim;
            if (collect($prepared[$studentKey]['offerings'] ?? [])->contains(
                fn (PenawaranMataKuliah $item): bool => (int) $item->master_mata_kuliah_id === (int) $master->id
            )) {
                $this->reject("Baris {$rowNumber}: mata kuliah {$courseCode} untuk NIM {$nim} tercantum lebih dari sekali, termasuk pada kelas berbeda.");
            }
            $itemKey = (string) $offering->id;
            $studentsByClass[$class->id][$studentKey] = true;

            $prepared[$studentKey] ??= ['student' => $student, 'class' => $class, 'offerings' => []];
            $prepared[$studentKey]['offerings'][$itemKey] = $offering;
        }

        DB::beginTransaction();

        try {
            $studentsCreated = 0;
            $registrationsCreated = 0;
            $krsCreated = 0;
            $itemsCreated = 0;
            $itemsSkipped = 0;
            $classesResized = 0;

            foreach ($studentsByClass as $classId => $classStudents) {
                $class = $classes->firstWhere('id', $classId);
                $requiredCapacity = count($classStudents);
                $capacityAdjusted = false;

                if (! $class->capacity || $class->capacity < $requiredCapacity) {
                    $class->update(['capacity' => $requiredCapacity]);
                    $capacityAdjusted = true;
                }

                $offeringsAdjusted = PenawaranMataKuliah::query()
                    ->where('taka_id', $period->id)
                    ->where('kelas_id', $class->id)
                    ->where(function ($query) use ($requiredCapacity): void {
                        $query->whereNull('kapasitas')
                            ->orWhere('kapasitas', '<', $requiredCapacity);
                    })
                    ->update(['kapasitas' => $requiredCapacity]);

                if ($capacityAdjusted || $offeringsAdjusted > 0) {
                    $classesResized++;
                }
            }

            foreach ($prepared as $studentData) {
                if (! $studentData['student']->exists) {
                    $studentData['student']->save();
                    $studentsCreated++;
                }

                $registration = RegistrasiMahasiswa::query()->firstOrNew([
                    'mahasiswa_id' => $studentData['student']->id,
                    'taka_id' => $period->id,
                ]);

                if ($registration->exists) {
                    if ((int) $registration->semester_mahasiswa !== $studentSemester
                        || ($registration->kelas_id && (int) $registration->kelas?->pstudi_id !== (int) $program->id)) {
                        $this->reject("NIM {$studentData['student']->mhs_nim}: registrasi periode sudah menggunakan semester atau program studi yang berbeda.");
                    }

                    $registrationUpdates = [];
                    if (! $registration->kelas_id) {
                        $registrationUpdates['kelas_id'] = $studentData['class']->id;
                        $registrationUpdates['dosen_wali_id'] = $studentData['class']->dosen_id;
                    }
                    if (! $registration->batas_sks) {
                        $registrationUpdates['batas_sks'] = 24;
                    }
                    if ($registrationUpdates !== []) {
                        $registration->update($registrationUpdates);
                    }
                } else {
                    $registration->fill([
                        'semester_mahasiswa' => $studentSemester,
                        'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
                        'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
                        'kelas_id' => $studentData['class']->id,
                        'dosen_wali_id' => $studentData['class']->dosen_id,
                        'batas_sks' => 24,
                    ])->save();
                    $registrationsCreated++;
                }

                $krs = Krs::query()->firstOrCreate(
                    ['registrasi_mahasiswa_id' => $registration->id],
                    ['status' => Krs::STATUS_DRAFT, 'total_sks' => 0]
                );
                if ($krs->wasRecentlyCreated) {
                    $krsCreated++;
                }

                $existingOfferingIds = $krs->items()->pluck('penawaran_mata_kuliah_id')->map(fn ($id) => (int) $id)->all();
                $missingOfferings = collect($studentData['offerings'])->reject(
                    fn (PenawaranMataKuliah $offering) => in_array((int) $offering->id, $existingOfferingIds, true)
                );
                $existingMasterIds = $krs->items()->whereHas('penawaranMataKuliah')->with('penawaranMataKuliah')
                    ->get()->pluck('penawaranMataKuliah.master_mata_kuliah_id')->map(fn ($id) => (int) $id);
                foreach ($missingOfferings as $offering) {
                    if ($existingMasterIds->contains((int) $offering->master_mata_kuliah_id)) {
                        $this->reject("NIM {$studentData['student']->mhs_nim}: mata kuliah yang sama sudah ada di KRS pada kelas berbeda.");
                    }
                }
                $itemsSkipped += count($studentData['offerings']) - $missingOfferings->count();

                if ($missingOfferings->isNotEmpty() && ! $krs->isEditable()) {
                    $this->reject("NIM {$studentData['student']->mhs_nim}: KRS yang sudah diajukan atau diputuskan tidak dapat ditambah.");
                }

                $newTotal = (int) $krs->items()->sum('sks') + (int) $missingOfferings->sum('sks');
                if ($newTotal > $registration->batas_sks) {
                    $this->reject("NIM {$studentData['student']->mhs_nim}: total {$newTotal} SKS melebihi batas {$registration->batas_sks} SKS.");
                }

                foreach ($missingOfferings as $offering) {
                    $krs->items()->create([
                        'penawaran_mata_kuliah_id' => $offering->id,
                        'sks' => $offering->sks,
                    ]);
                    $itemsCreated++;
                }

                if ($krs->isEditable()) {
                    $krs->recalculateTotal();
                }
            }

            $dryRun ? DB::rollBack() : DB::commit();

            return [
                'students_created' => $studentsCreated,
                'registrations_created' => $registrationsCreated,
                'krs_created' => $krsCreated,
                'items_created' => $itemsCreated,
                'items_skipped' => $itemsSkipped,
                'classes_resized' => $classesResized,
            ];
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }
    }

    private function openFeederSemester(TahunAkademik $period): string
    {
        $term = match ($period->term) {
            TahunAkademik::TERM_GANJIL => '1',
            TahunAkademik::TERM_GENAP => '2',
            TahunAkademik::TERM_PENDEK => '3',
            default => null,
        };

        if ($term === null) {
            $this->reject('Jenis periode akademik belum dapat dipetakan ke kode semester OpenFeeder.');
        }

        return $period->year_start.$term;
    }

    private function digits(mixed $value): string
    {
        return is_float($value) || is_int($value)
            ? number_format((float) $value, 0, '', '')
            : trim((string) ($value ?? ''));
    }

    private function entryYear(TahunAkademik $period, int $studentSemester): int
    {
        return max(2000, $period->year_start - intdiv($studentSemester - 1, 2));
    }

    private function programToken(ProgramStudi $program): string
    {
        $aliases = array_map('strtoupper', $program->masterMataKuliahCodes());

        return match (true) {
            in_array('86208', $aliases, true), in_array('PAI', $aliases, true) => 'PAI',
            in_array('88204', $aliases, true), in_array('PBA', $aliases, true) => 'PBA',
            in_array('86233', $aliases, true), in_array('PIAUD', $aliases, true) => 'PIAUD',
            default => Str::upper(Str::slug((string) $program->code)),
        };
    }

    private function romanSemester(int $semester): string
    {
        return [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII',
            8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV',
        ][$semester];
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['import' => $message]);
    }
}
