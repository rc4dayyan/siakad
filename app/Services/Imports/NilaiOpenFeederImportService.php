<?php

namespace App\Services\Imports;

use App\Models\Mahasiswa;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\TahunAkademik;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NilaiOpenFeederImportService
{
    public const HEADERS = ['NIM', 'Nama Mahasiswa', 'Kode Mata Kuliah', 'Nama Mata Kuliah', 'Semester', 'Nama Kelas', 'Nilai Huruf', 'Nilai Indeks', 'Nilai Angka', 'Kode Prodi'];

    public function import(Collection $rows, TahunAkademik $period): int
    {
        return DB::transaction(function () use ($rows, $period): int {
            $period = TahunAkademik::query()->lockForUpdate()->findOrFail($period->id);
            if (! $period->isWritable()) {
                $this->reject('Periode yang ditutup atau diarsipkan tidak dapat menerima import nilai.');
            }
            if ($rows->isEmpty()) {
                $this->reject('File tidak berisi data nilai.');
            }
            $missing = array_diff(self::HEADERS, array_keys($rows->first()));
            if ($missing !== []) {
                $this->reject('Kolom wajib tidak ditemukan: '.implode(', ', $missing).'.');
            }
            $term = match ($period->term) {
                'ganjil' => '1', 'genap' => '2', 'pendek' => '3', default => '',
            };
            $semester = $period->year_start.$term;
            $offerings = PenawaranMataKuliah::query()->forAcademicPeriod($period)
                ->with(['masterMataKuliah', 'pstudi', 'kelas'])->get();
            $students = Mahasiswa::query()->whereIn('mhs_nim', $rows->pluck('NIM')->map(fn ($nim) => trim((string) $nim)))
                ->get()->groupBy(fn ($student) => trim((string) $student->mhs_nim));
            $prepared = [];
            foreach ($rows as $index => $row) {
                if (collect($row)->every(fn ($value) => trim((string) $value) === '')) {
                    continue;
                }
                $line = $index + 2;
                $nim = trim((string) ($row['NIM'] ?? ''));
                $grade = strtoupper(trim((string) ($row['Nilai Huruf'] ?? '')));
                if (trim((string) $row['Semester']) !== $semester || $term === '') {
                    $this->reject("Baris {$line}: Semester harus {$semester} sesuai periode yang dipilih.");
                }
                if (! in_array($grade, ['A', 'B', 'C', 'D', 'E'], true)) {
                    $this->reject("Baris {$line}: Nilai Huruf wajib A, B, C, D, atau E.");
                }
                $numericGrades = [];
                foreach (['Nilai Indeks' => 4, 'Nilai Angka' => 100] as $column => $maximum) {
                    $value = str_replace(',', '.', trim((string) ($row[$column] ?? '')));
                    if ($value !== '' && (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0 || (float) $value > $maximum)) {
                        $this->reject("Baris {$line}: {$column} harus berupa angka antara 0 dan {$maximum}, atau kosong.");
                    }
                    $numericGrades[$column] = $value === '' ? null : round((float) $value, 2);
                }
                $matches = $students->get($nim, collect());
                if ($nim === '' || $matches->count() !== 1) {
                    $this->reject("Baris {$line}: NIM tidak ditemukan atau tidak unik.");
                }
                $student = $matches->first();
                $candidates = $offerings->filter(function ($offering) use ($row): bool {
                    $label = trim((string) $row['Nama Kelas']);
                    $name = trim((string) $offering->kelas?->name);

                    return $label !== '' && $offering->masterMataKuliah && $offering->pstudi
                        && strcasecmp((string) $offering->masterMataKuliah->code, trim((string) $row['Kode Mata Kuliah'])) === 0
                        && in_array(strtoupper(trim((string) $row['Kode Prodi'])), $offering->pstudi->masterMataKuliahCodes(), true)
                        && (strcasecmp($name, $label) === 0 || str_ends_with(strtoupper($name), ' '.strtoupper($label)));
                });
                $candidates = $candidates->filter(fn ($offering) => Mahasiswa::query()->forApprovedOffering($offering)->whereKey($student->id)->exists());
                if ($candidates->count() !== 1) {
                    $this->reject("Baris {$line}: penawaran mata kuliah, kelas, dan prodi harus cocok dengan satu KRS yang disetujui untuk NIM {$nim}.");
                }
                $offering = $candidates->first();
                $key = $student->id.'|'.$offering->id;
                if (isset($prepared[$key])) {
                    $this->reject("Baris {$line}: nilai NIM {$nim} untuk mata kuliah ini tercantum lebih dari sekali.");
                }
                $prepared[$key] = compact('student', 'offering', 'grade', 'numericGrades');
            }
            if ($prepared === []) {
                $this->reject('File tidak berisi data nilai.');
            }
            foreach ($prepared as $item) {
                $offering = $item['offering'];
                NilaiMahasiswa::updateOrCreate([
                    'mahasiswa_id' => $item['student']->id,
                    'penawaran_mata_kuliah_id' => $offering->id,
                ], [
                    'taka_id' => $period->id,
                    'mata_kuliah_id' => $offering->legacy_mata_kuliah_id,
                    'kelas_id' => $offering->kelas_id,
                    'dosen_id' => $offering->dosen_utama_id,
                    'nilai' => $item['grade'],
                    'nilai_indeks' => $item['numericGrades']['Nilai Indeks'],
                    'nilai_angka' => $item['numericGrades']['Nilai Angka'],
                ]);
            }

            return count($prepared);
        });
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['import' => $message]);
    }
}
