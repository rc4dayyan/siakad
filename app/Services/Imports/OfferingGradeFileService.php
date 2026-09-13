<?php

namespace App\Services\Imports;

use App\Models\Mahasiswa;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\TahunAkademik;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OfferingGradeFileService
{
    public static function semester(TahunAkademik $period): string
    {
        return $period->year_start.match ($period->term) {
            'ganjil' => '1', 'genap' => '2', 'pendek' => '3',
            default => throw ValidationException::withMessages(['import' => 'Jenis periode tidak dapat dipetakan ke semester OpenFeeder.']),
        };
    }

    public static function exportRow(Mahasiswa $student, ?NilaiMahasiswa $grade, PenawaranMataKuliah $offering, TahunAkademik $period): array
    {
        $program = $offering->pstudi;
        $aliases = $program?->masterMataKuliahCodes() ?? [];
        $programCode = collect($aliases)->first(fn ($code) => preg_match('/^\d{5}$/', $code)) ?? $program?->code;

        return [
            'NIM' => (string) $student->mhs_nim,
            'Nama Mahasiswa' => $student->mhs_name,
            'Kode Mata Kuliah' => $offering->masterMataKuliah?->code ?? $offering->code,
            'Nama Mata Kuliah' => $offering->masterMataKuliah?->name ?? $offering->legacyMataKuliah?->name,
            'Semester' => self::semester($period),
            'Nama Kelas' => $offering->kelas?->name,
            'Nilai Huruf' => $grade?->nilai,
            'Nilai Indeks' => $grade?->nilai_indeks,
            'Nilai Angka' => $grade?->nilai_angka,
            'Kode Prodi' => $programCode,
            'Nama Prodi' => $program?->name,
        ];
    }

    public static function normalize(Collection $rows, PenawaranMataKuliah $offering, TahunAkademik $period): Collection
    {
        if ($rows->isEmpty() || ! array_key_exists('Nilai Huruf', $rows->first())) {
            return $rows;
        }
        $missing = array_diff(NilaiOpenFeederImportService::HEADERS, array_keys($rows->first()));
        if ($missing !== []) {
            throw ValidationException::withMessages(['import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missing).'.']);
        }
        $offering->loadMissing(['masterMataKuliah', 'kelas', 'pstudi']);
        $semester = self::semester($period);
        $courseCode = $offering->masterMataKuliah?->code ?? $offering->code;
        $className = trim((string) $offering->kelas?->name);

        return $rows->map(function (array $row, int $index) use ($offering, $semester, $courseCode, $className): array {
            $line = $index + 2;
            $label = trim((string) $row['Nama Kelas']);
            if (trim((string) $row['Semester']) !== $semester
                || strcasecmp(trim((string) $row['Kode Mata Kuliah']), (string) $courseCode) !== 0
                || ! in_array(strtoupper(trim((string) $row['Kode Prodi'])), $offering->pstudi?->masterMataKuliahCodes() ?? [], true)
                || $label === ''
                || (strcasecmp($className, $label) !== 0 && ! str_ends_with(strtoupper($className), ' '.strtoupper($label)))) {
                throw ValidationException::withMessages(['import' => "Baris {$line}: periode, mata kuliah, kelas, atau prodi tidak sesuai dengan penawaran pada halaman ini."]);
            }
            $row['Nilai'] = $row['Nilai Huruf'];

            return $row;
        });
    }
}
