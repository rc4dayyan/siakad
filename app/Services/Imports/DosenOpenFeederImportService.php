<?php

namespace App\Services\Imports;

use App\Models\Dosen;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DosenOpenFeederImportService
{
    /**
     * @return array{imported: int, skipped: int}
     */
    public function import(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            $this->reject('File import tidak berisi data dosen.');
        }

        $requiredHeaders = ['NIDN', 'Nama Dosen'];
        $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            $this->reject('Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.');
        }

        $lecturers = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $nidn = $this->normalizeNidn($row['NIDN'] ?? null);
            $name = trim((string) ($row['Nama Dosen'] ?? ''));

            if (! preg_match('/^\d{10}$/', $nidn)) {
                $this->reject("Baris {$rowNumber}: NIDN wajib terdiri dari 10 digit angka.");
            }

            if ($name === '' || mb_strlen($name) > 255) {
                $this->reject("Baris {$rowNumber}: Nama Dosen wajib diisi dan maksimal 255 karakter.");
            }

            if (isset($lecturers[$nidn]) && $lecturers[$nidn] !== $name) {
                $this->reject("Baris {$rowNumber}: NIDN {$nidn} memiliki Nama Dosen yang berbeda.");
            }

            $lecturers[$nidn] = $name;
        }

        return DB::transaction(function () use ($lecturers): array {
            $existingNidns = Dosen::query()
                ->whereIn('dsn_nidn', array_keys($lecturers))
                ->pluck('dsn_nidn')
                ->all();
            $existingNidns = array_fill_keys($existingNidns, true);
            $imported = 0;
            $skipped = 0;

            foreach ($lecturers as $nidn => $name) {
                if (isset($existingNidns[$nidn])) {
                    $skipped++;

                    continue;
                }

                $email = $nidn.'@import.invalid';
                $hasAccountConflict = Dosen::query()
                    ->where('dsn_user', $nidn)
                    ->orWhere('dsn_mail', $email)
                    ->orWhere('dsn_phone', $nidn)
                    ->exists();

                if ($hasAccountConflict) {
                    $this->reject("NIDN {$nidn} bentrok dengan username, email, atau telepon dosen yang sudah ada.");
                }

                Dosen::create([
                    'dsn_stat' => 1,
                    'dsn_nidn' => $nidn,
                    'dsn_name' => $name,
                    'dsn_code' => Str::random(12),
                    'dsn_user' => $nidn,
                    'password' => Hash::make($nidn),
                    'dsn_mail' => $email,
                    'dsn_phone' => $nidn,
                ]);

                $imported++;
            }

            return compact('imported', 'skipped');
        });
    }

    private function normalizeNidn(mixed $value): string
    {
        if (is_float($value) || is_int($value)) {
            return number_format((float) $value, 0, '', '');
        }

        return trim((string) $value);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['import' => $message]);
    }
}
