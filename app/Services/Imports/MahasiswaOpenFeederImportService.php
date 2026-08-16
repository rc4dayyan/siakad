<?php

namespace App\Services\Imports;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\Wilayah;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MahasiswaOpenFeederImportService
{
    /** @var array<string, Wilayah|null> */
    private array $regions = [];

    private const REQUIRED_HEADERS = [
        'NIM',
        'Nama',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Jenis Kelamin',
        'NIK',
        'Agama',
        'Jenis Pendaftaran',
        'Tanggal Masuk Kuliah',
        'Mulai Semester',
        'Jalan',
        'RT',
        'RW',
        'Nama Dusun',
        'Kelurahan',
        'Kecamatan',
        'No HP',
        'Email',
        'Nama Ayah',
        'Nama Ibu',
        'Nama Wali',
        'Kode Prodi',
        'Nama Prodi',
        'Jumlah Biaya Masuk',
    ];

    /**
     * @return array{imported: int, skipped: int}
     */
    public function import(Collection $rows, Kelas $class, bool $dryRun = false): array
    {
        if ($rows->isEmpty()) {
            $this->reject('File import tidak berisi data mahasiswa.');
        }

        $missingHeaders = array_diff(self::REQUIRED_HEADERS, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            $this->reject('Kolom wajib OpenFeeder tidak ditemukan: '.implode(', ', $missingHeaders).'.');
        }

        $class->loadMissing(['pstudi', 'taka', 'dosen']);

        if (! $class->pstudi || ! $class->taka) {
            $this->reject('Kelas yang dipilih belum memiliki program studi atau periode akademik yang valid.');
        }

        $students = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $student = $this->validateRow($row, $rowNumber, $class);
            $nim = $student['mhs_nim'];

            if (isset($students[$nim])) {
                if ($students[$nim] !== $student) {
                    $this->reject("Baris {$rowNumber}: NIM {$nim} memiliki data yang berbeda pada baris lain.");
                }

                continue;
            }

            $students[$nim] = $student;
        }

        DB::beginTransaction();

        try {
            $existingNims = Mahasiswa::query()
                ->whereIn('mhs_nim', array_keys($students))
                ->pluck('mhs_nim')
                ->flip();
            $imported = 0;
            $skipped = 0;

            foreach ($students as $nim => $attributes) {
                if ($existingNims->has($nim)) {
                    $skipped++;

                    continue;
                }

                $this->rejectAccountConflict($attributes);
                $startingYear = $attributes['_starting_year'];
                unset($attributes['_starting_year']);

                $student = Mahasiswa::create($attributes + [
                    'mhs_stat' => 1,
                    'taka_id' => $class->taka_id,
                    'years_id' => $startingYear,
                    'class_id' => $class->id,
                    'mhs_code' => Str::random(12),
                    'mhs_user' => $nim,
                    'password' => Hash::make($nim),
                    'mhs_status' => 'AKTIF',
                    'mhs_sync_status' => 'Belum Sync',
                ]);

                RegistrasiMahasiswa::create([
                    'mahasiswa_id' => $student->id,
                    'taka_id' => $class->taka_id,
                    'semester_mahasiswa' => 1,
                    'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
                    'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
                    'kelas_id' => $class->id,
                    'dosen_wali_id' => $class->dosen_id,
                    'batas_sks' => 24,
                ]);

                $imported++;
            }

            $dryRun ? DB::rollBack() : DB::commit();

            return compact('imported', 'skipped');
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRow(array|Collection $row, int $rowNumber, Kelas $class): array
    {
        $nim = $this->digits($row['NIM'] ?? null);
        $nik = $this->digits($row['NIK'] ?? null);
        $phone = $this->digits($row['No HP'] ?? null);
        $name = trim((string) ($row['Nama'] ?? ''));
        $gender = strtoupper(trim((string) ($row['Jenis Kelamin'] ?? '')));
        $email = strtolower(trim((string) ($row['Email'] ?? '')));
        $religion = $this->digits($row['Agama'] ?? null);
        $startingSemester = $this->digits($row['Mulai Semester'] ?? null);
        $programCode = trim((string) ($row['Kode Prodi'] ?? ''));
        $programName = trim((string) ($row['Nama Prodi'] ?? ''));

        if (! preg_match('/^\d{1,30}$/', $nim)) {
            $this->reject("Baris {$rowNumber}: NIM wajib berupa angka dan maksimal 30 digit.");
        }

        if ($name === '' || mb_strlen($name) > 255) {
            $this->reject("Baris {$rowNumber}: Nama wajib diisi dan maksimal 255 karakter.");
        }

        if (! preg_match('/^\d{16}$/', $nik)) {
            $this->reject("Baris {$rowNumber}: NIK wajib terdiri dari 16 digit angka.");
        }

        if (! in_array($gender, ['L', 'P'], true)) {
            $this->reject("Baris {$rowNumber}: Jenis Kelamin wajib berisi L atau P.");
        }

        if (! preg_match('/^[0-7]$/', $religion)) {
            $this->reject("Baris {$rowNumber}: Agama wajib berupa kode 0 sampai 7.");
        }

        if (! preg_match('/^\d{8,20}$/', $phone)) {
            $this->reject("Baris {$rowNumber}: No HP wajib berisi 8 sampai 20 digit angka.");
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $this->reject("Baris {$rowNumber}: Email tidak valid.");
        }

        if (! preg_match('/^\d{5}$/', $startingSemester)) {
            $this->reject("Baris {$rowNumber}: Mulai Semester wajib menggunakan 5 digit kode semester OpenFeeder.");
        }

        $programMatches = $programName === ''
            || strcasecmp($programName, (string) $class->pstudi->name) === 0
            || strcasecmp($programCode, (string) $class->pstudi->code) === 0;

        if (! $programMatches) {
            $this->reject("Baris {$rowNumber}: program studi {$programName} ({$programCode}) tidak sesuai dengan kelas {$class->name}.");
        }

        $districtCode = $this->digits($row['Kecamatan'] ?? null);

        if (! preg_match('/^\d{6}$/', $districtCode)) {
            $this->reject("Baris {$rowNumber}: Kecamatan wajib menggunakan kode wilayah 6 digit.");
        }

        if (! array_key_exists($districtCode, $this->regions)) {
            $this->regions[$districtCode] = Wilayah::query()->where('code', $districtCode)->first();
        }

        $region = $this->regions[$districtCode];

        if (! $region) {
            $this->reject("Baris {$rowNumber}: kode Kecamatan {$districtCode} belum tersedia pada master wilayah.");
        }

        $entryAmount = trim((string) ($row['Jumlah Biaya Masuk'] ?? ''));

        if ($entryAmount !== '' && (! is_numeric($entryAmount) || (float) $entryAmount < 0)) {
            $this->reject("Baris {$rowNumber}: Jumlah Biaya Masuk harus berupa angka nol atau lebih.");
        }

        return [
            'mhs_nim' => $nim,
            'mhs_nik' => $nik,
            'mhs_name' => $name,
            'mhs_birthplace' => $this->nullableText($row['Tempat Lahir'] ?? null),
            'mhs_birthdate' => $this->date($row['Tanggal Lahir'] ?? null, $rowNumber, 'Tanggal Lahir'),
            'mhs_gend' => $gender,
            'mhs_reli' => $religion,
            'mhs_addr_domisili' => $this->address($row),
            'mhs_addr_kelurahan' => $this->nullableText($row['Kelurahan'] ?? null),
            'mhs_addr_kecamatan' => $region?->kecamatan,
            'mhs_addr_kota' => $region?->kabupaten,
            'mhs_addr_provinsi' => $region?->provinsi,
            'mhs_parent_mother' => $this->nullableText($row['Nama Ibu'] ?? null),
            'mhs_parent_father' => $this->nullableText($row['Nama Ayah'] ?? null),
            'mhs_wali_name' => $this->nullableText($row['Nama Wali'] ?? null),
            'mhs_mail' => $email,
            'mhs_phone' => $phone,
            'mhs_register_date' => $this->date($row['Tanggal Masuk Kuliah'] ?? null, $rowNumber, 'Tanggal Masuk Kuliah'),
            'mhs_register_type' => $this->nullableText($row['Jenis Pendaftaran'] ?? null),
            'mhs_register_amount' => $entryAmount === '' ? null : (float) $entryAmount,
            '_starting_year' => (int) substr($startingSemester, 0, 4),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function rejectAccountConflict(array $attributes): void
    {
        $uniqueAccounts = [
            'mhs_user' => ['label' => 'Username', 'value' => $attributes['mhs_nim']],
            'mhs_mail' => ['label' => 'Email', 'value' => $attributes['mhs_mail']],
        ];

        foreach ($uniqueAccounts as $column => $account) {
            $conflict = Mahasiswa::query()->where($column, $account['value'])->first();

            if ($conflict) {
                $this->reject(
                    "NIM {$attributes['mhs_nim']}: {$account['label']} {$account['value']} sudah digunakan oleh "
                    ."{$conflict->mhs_name} (NIM {$conflict->mhs_nim})."
                );
            }
        }
    }

    private function date(mixed $value, int $rowNumber, string $column): string
    {
        try {
            if ($value instanceof DateTimeInterface) {
                return Carbon::instance($value)->format('Y-m-d');
            }

            if (is_numeric($value)) {
                return Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
            }

            $value = trim((string) ($value ?? ''));

            if ($value === '') {
                throw new \InvalidArgumentException;
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            $this->reject("Baris {$rowNumber}: {$column} tidak valid.");
        }
    }

    private function address(array|Collection $row): ?string
    {
        $parts = array_filter([
            $this->nullableText($row['Jalan'] ?? null),
            $this->prefixedAddressPart('RT', $row['RT'] ?? null),
            $this->prefixedAddressPart('RW', $row['RW'] ?? null),
            $this->prefixedAddressPart('Dusun', $row['Nama Dusun'] ?? null),
        ]);

        return $parts === [] ? null : Str::limit(implode(', ', $parts), 255, '');
    }

    private function prefixedAddressPart(string $prefix, mixed $value): ?string
    {
        $value = $this->nullableText($value);

        return $value === null ? null : "{$prefix} {$value}";
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : Str::limit($value, 255, '');
    }

    private function digits(mixed $value): string
    {
        if (is_float($value) || is_int($value)) {
            return number_format((float) $value, 0, '', '');
        }

        return trim((string) ($value ?? ''));
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['import' => $message]);
    }
}
