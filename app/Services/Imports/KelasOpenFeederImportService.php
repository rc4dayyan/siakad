<?php

namespace App\Services\Imports;

use App\Models\Kelas;
use App\Models\MasterMataKuliah;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use Database\Seeders\MasterMataKuliahSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KelasOpenFeederImportService
{
    private const REQUIRED_HEADERS = [
        'Semester',
        'Kode Matakuliah',
        'Nama Matakuliah',
        'Nama Kelas',
        'Bahasan',
        'Tanggal Mulai Efektif',
        'Tanggal Akhir Efektif',
        'Lingkup Kelas',
        'Mode Kuliah',
        'Kode Prodi',
        'Nama Prodi',
        'Sks Tatap Muka',
        'Sks Praktek',
        'Sks Praktek Lapangan',
        'Sks Simulasi',
    ];

    /**
     * @return array{imported: int, skipped: int, courses_created: int}
     */
    public function import(Collection $rows, TahunAkademik $period, int $capacity, bool $dryRun = false): array
    {
        if ($rows->isEmpty()) {
            $this->reject('File import tidak berisi data kelas.');
        }

        $missingHeaders = array_diff(self::REQUIRED_HEADERS, array_keys($rows->first()));
        if ($missingHeaders !== []) {
            $this->reject('Kolom wajib format kelas OpenFeeder tidak ditemukan: '.implode(', ', $missingHeaders).'.');
        }

        $expectedSemester = $this->openFeederSemester($period);
        $programs = ProgramStudi::query()->get();
        $masterCourses = MasterMataKuliah::query()->get();
        $catalogCourses = collect(MasterMataKuliahSeeder::rows());
        $newMasterCourses = [];
        $classes = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $semester = $this->digits($row['Semester'] ?? null);
            $programCode = trim((string) ($row['Kode Prodi'] ?? ''));
            $classLabel = trim((string) ($row['Nama Kelas'] ?? ''));
            $courseCode = trim((string) ($row['Kode Matakuliah'] ?? ''));
            $courseName = trim((string) ($row['Nama Matakuliah'] ?? ''));

            if ($semester !== $expectedSemester) {
                $this->reject("Baris {$rowNumber}: Semester harus {$expectedSemester} sesuai periode {$period->name}.");
            }

            if ($programCode === '') {
                $this->reject("Baris {$rowNumber}: Kode Prodi wajib diisi.");
            }

            if ($classLabel === '' || mb_strlen($classLabel) > 100) {
                $this->reject("Baris {$rowNumber}: Nama Kelas wajib diisi dan maksimal 100 karakter.");
            }

            if ($courseCode === '') {
                $this->reject("Baris {$rowNumber}: Kode Matakuliah wajib diisi.");
            }

            if ($courseName === '' || mb_strlen($courseName) > 255) {
                $this->reject("Baris {$rowNumber}: Nama Matakuliah wajib diisi dan maksimal 255 karakter.");
            }

            $program = $programs->first(fn (ProgramStudi $item): bool => collect($item->masterMataKuliahCodes())
                ->contains(fn (string $alias): bool => strcasecmp($alias, $programCode) === 0));

            if (! $program) {
                $this->reject("Baris {$rowNumber}: program studi dengan kode {$programCode} tidak ditemukan.");
            }

            $programAliases = $program->masterMataKuliahCodes();
            $masterCourse = $masterCourses->first(fn (MasterMataKuliah $item): bool => strcasecmp((string) $item->code, $courseCode) === 0
                && collect($programAliases)->contains(fn (string $alias): bool => strcasecmp($alias, (string) $item->program_studi) === 0));

            if (! $masterCourse) {
                $conflictingCourse = $masterCourses->first(fn (MasterMataKuliah $item): bool => strcasecmp((string) $item->code, $courseCode) === 0);
                if ($conflictingCourse) {
                    $this->reject("Baris {$rowNumber}: Kode Matakuliah {$courseCode} sudah digunakan oleh program studi lain.");
                }

                $catalogCourse = $catalogCourses->first(fn (array $item): bool => strcasecmp((string) $item['code'], $courseCode) === 0
                    && collect($programAliases)->contains(fn (string $alias): bool => strcasecmp($alias, (string) $item['program_studi']) === 0));

                if (! $catalogCourse) {
                    $this->reject("Baris {$rowNumber}: Kode Matakuliah {$courseCode} belum ada dan semester mata kuliahnya tidak ditemukan pada katalog bawaan.");
                }

                $masterCourse = new MasterMataKuliah([
                    'program_studi' => $catalogCourse['program_studi'],
                    'semester' => $catalogCourse['semester'],
                    'code' => $courseCode,
                    'name' => $courseName,
                    'sks' => $this->courseCredits($row, $rowNumber, (int) $catalogCourse['sks']),
                ]);
                $masterCourses->push($masterCourse);
                $newMasterCourses[strtoupper($courseCode)] = $masterCourse;
            }

            $studentSemester = (int) $masterCourse->semester;
            if ($studentSemester < 1 || $studentSemester > 14) {
                $this->reject("Baris {$rowNumber}: semester master mata kuliah {$courseCode} tidak valid.");
            }

            $key = $period->id.'|'.$program->id.'|'.$studentSemester.'|'.mb_strtolower($classLabel);
            if (isset($classes[$key])) {
                $skipped++;

                continue;
            }

            $classes[$key] = compact('program', 'studentSemester', 'classLabel', 'rowNumber');
        }

        DB::beginTransaction();

        try {
            $imported = 0;

            foreach ($newMasterCourses as $masterCourse) {
                $masterCourse->save();
            }

            foreach ($classes as $candidate) {
                $programToken = $this->programToken($candidate['program']);
                $className = $programToken.' '.$this->romanSemester($candidate['studentSemester']).' '.$candidate['classLabel'];
                $existing = Kelas::query()
                    ->where('taka_id', $period->id)
                    ->where('pstudi_id', $candidate['program']->id)
                    ->get()
                    ->first(fn (Kelas $class): bool => strcasecmp($class->name, $className) === 0);

                if ($existing) {
                    $skipped++;

                    continue;
                }

                $code = Str::upper(Str::slug($period->code.'-'.$programToken.'-S'.$candidate['studentSemester'].'-'.$candidate['classLabel']));
                if ($code === '' || mb_strlen($code) > 255) {
                    $this->reject("Baris {$candidate['rowNumber']}: kode kelas hasil penggabungan periode, prodi, dan nama kelas tidak valid.");
                }

                if (Kelas::query()->where('code', $code)->exists()) {
                    $this->reject("Baris {$candidate['rowNumber']}: kode kelas {$code} sudah digunakan oleh kelas lain.");
                }

                Kelas::create([
                    'taka_id' => $period->id,
                    'pstudi_id' => $candidate['program']->id,
                    'capacity' => $capacity,
                    'name' => $className,
                    'code' => $code,
                ]);
                $imported++;
            }

            $dryRun ? DB::rollBack() : DB::commit();

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'courses_created' => count($newMasterCourses),
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
        if (is_float($value) || is_int($value)) {
            return number_format((float) $value, 0, '', '');
        }

        return trim((string) ($value ?? ''));
    }

    private function courseCredits(array|Collection $row, int $rowNumber, int $catalogCredits): int
    {
        $credits = 0.0;

        foreach (['Sks Tatap Muka', 'Sks Praktek', 'Sks Praktek Lapangan', 'Sks Simulasi'] as $column) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value === '') {
                $value = '0';
            }

            if (! is_numeric($value) || (float) $value < 0) {
                $this->reject("Baris {$rowNumber}: {$column} harus berupa angka nol atau lebih.");
            }

            $credits += (float) $value;
        }

        if ($credits === 0.0 && $catalogCredits >= 1 && $catalogCredits <= 20) {
            return $catalogCredits;
        }

        if ($credits < 1 || $credits > 20 || floor($credits) !== $credits) {
            $this->reject("Baris {$rowNumber}: total komponen SKS harus berupa bilangan bulat antara 1 sampai 20.");
        }

        return (int) $credits;
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
