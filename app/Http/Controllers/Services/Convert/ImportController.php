<?php

namespace App\Http\Controllers\Services\Convert;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
// SECTION ADDONS SYSTEM
use App\Models\Gedung;
use App\Models\JadwalKuliah;
use App\Models\Kelas;
// SECTION ADDONS EXTERNAL
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
// SECTION MODELS
use App\Models\MataKuliah;
use App\Models\ProgramKuliah;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Carbon\Carbon;
use DateTime;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Rap2hpoutre\FastExcel\FastExcel;
use Str;

class ImportController extends Controller
{
    public function importUsers(Request $request)
    {
        $request->validate(
            [
                'import' => 'required|file|mimes:xls,xlsx,csv|max:2048', // max:2048 untuk batasan 2MB
            ],
            [
                'import.required' => 'File harus diunggah.',
                'import.mimes' => 'File harus dalam format xls, xlsx, atau csv.',
                'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
            ]);

        $path = $request->file('import')->store('public/excel-files');
        $users = (new FastExcel)->import(storage_path('app/'.$path), function ($line) {
            return User::create([
                'user' => $line['Username'],
                'email' => $line['Email'],
                'phone' => $line['Phone'],
                'name' => $line['FullName'],
                'gend' => $line['Gender'],
                'reli' => $line['Religion'] == null ? null : $line['Religion'],
                'birth_place' => $line['BirthPlace'] == null ? null : $line['BirthPlace'],
                'birth_date' => $line['BirthDate'] == null ? null : $line['BirthDate'],
                'type' => $line['TypeUser'],
                'status' => $line['Status'],
                'code' => Str::random(6),
                'password' => Hash::make($line['Phone']),
            ]);
        });

        Alert::success('Sukses', 'Data berhasil diimport !');

        return back();
    }

    public function importStudent(Request $request, AcademicPeriodContext $context)
    {
        $period = $context->requireWritableCurrent($request->user());
        $this->assertPeriodImportAllowed($request, $period);
        $request->validate([
            'import' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048'],
            'class_id' => ['required', 'integer', Rule::exists('kelas', 'id')->where(fn ($query) => $query->where('taka_id', $period->id))],
        ]);
        $class = Kelas::forAcademicPeriod($period)->findOrFail($request->integer('class_id'));
        $path = $request->file('import')->store('excel-files', 'local');
        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages(['import' => 'File mahasiswa tidak dapat dibaca.']);
        } finally {
            Storage::disk('local')->delete($path);
        }
        $required = ['NIM', 'NIK', 'Email', 'Telepon', 'Nama', 'Jenis Kelamin', 'Tempat,Tanggal Lahir', 'Tanggal Masuk'];
        if ($rows->isEmpty() || ($missing = array_diff($required, array_keys($rows->first()))) !== []) {
            throw ValidationException::withMessages(['import' => $rows->isEmpty()
                ? 'File import tidak berisi data mahasiswa.'
                : 'Kolom wajib tidak ditemukan: '.implode(', ', $missing).'.']);
        }

        $totalSavedData = $this->runImport($request, function () use ($rows, $period, $class): int {
            $saved = 0;
            foreach ($rows as $index => $line) {
                $row = $index + 2;
                $nim = trim((string) $line['NIM']);
                if ($nim === '' || Mahasiswa::where('mhs_nim', $nim)->exists()) {
                    if ($nim === '') {
                        throw ValidationException::withMessages(['import' => "Baris {$row}: NIM wajib diisi."]);
                    }

                    continue;
                }
                try {
                    [$birthplace, $birthdateText] = array_pad(explode(',', (string) $line['Tempat,Tanggal Lahir'], 2), 2, null);
                    $birthdate = $birthdateText ? (new DateTime($birthdateText))->format('Y-m-d') : null;
                    $registerDate = (new DateTime((string) $line['Tanggal Masuk']))->format('Y-m-d');
                } catch (\Throwable) {
                    throw ValidationException::withMessages(['import' => "Baris {$row}: tanggal lahir atau tanggal masuk tidak valid."]);
                }
                $student = Mahasiswa::create([
                    'mhs_nim' => $nim, 'mhs_nik' => $line['NIK'], 'mhs_mail' => $line['Email'],
                    'mhs_phone' => $line['Telepon'], 'mhs_name' => $line['Nama'], 'mhs_gend' => $line['Jenis Kelamin'],
                    'mhs_reli' => $line['Agama'] ?? '', 'mhs_birthplace' => trim((string) $birthplace),
                    'mhs_birthdate' => $birthdate, 'mhs_stat' => 1, 'taka_id' => $period->id,
                    'years_id' => 0, 'class_id' => $class->id, 'mhs_code' => Str::random(12),
                    'mhs_user' => Str::random(12), 'password' => Hash::make((string) $line['NIK']),
                    'mhs_register_date' => $registerDate, 'mhs_status' => $line['Status Mahasiswa'] ?? null,
                    'mhs_register_type' => $line['Jenis Pendaftaran'] ?? null,
                    'mhs_register_amount' => $line['Biaya Masuk'] ?? null, 'mhs_sync_status' => $line['Status Sync'] ?? null,
                ]);
                RegistrasiMahasiswa::create([
                    'mahasiswa_id' => $student->id, 'taka_id' => $period->id, 'semester_mahasiswa' => 1,
                    'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
                    'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
                    'kelas_id' => $class->id, 'dosen_wali_id' => $class->dosen_id, 'batas_sks' => 24,
                ]);
                $saved++;
            }

            return $saved;
        });
        Alert::success('Sukses', $request->boolean('dry_run')
            ? "Dry-run berhasil. {$totalSavedData} mahasiswa valid; tidak ada data disimpan."
            : "Data mahasiswa berhasil diimport dan diregistrasikan. Total: {$totalSavedData}.");

        return back();
    }

    public function importMataKuliah(Request $request, AcademicPeriodContext $context)
    {
        $period = $context->requireWritableCurrent($request->user());
        $this->assertPeriodImportAllowed($request, $period);

        $request->validate(
            [
                'import' => 'required|file|mimes:xlsx,csv|max:2048',
                'pstudi_id' => 'required|integer|exists:program_studis,id',
                'kuri_id' => 'required|integer|exists:kurikulums,id',
                'dosen_1' => 'required|integer|exists:dosens,id',
            ],
            [
                'import.required' => 'File harus diunggah.',
                'import.mimes' => 'File harus dalam format xlsx atau csv.',
                'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
            ]
        );

        $programStudi = ProgramStudi::findOrFail($request->integer('pstudi_id'));
        $path = $request->file('import')->store('excel-files', 'local');
        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan file menggunakan format xlsx atau csv yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'import' => 'File import tidak berisi data mata kuliah.',
            ]);
        }

        $missingHeaders = array_diff(['Nama', 'Kode', 'Kode Tahun Akademik'], array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $totalSavedData = $this->runImport($request, function () use ($request, $rows, $period, $programStudi) {
            $totalSavedData = 0;

            foreach ($rows as $index => $line) {
                $rowNumber = $index + 2;
                $name = trim((string) $line['Nama']);
                $code = trim((string) $line['Kode']);
                $periodCode = trim((string) $line['Kode Tahun Akademik']);

                if ($name === '' || strlen($name) > 255 || $code === '' || strlen($code) > 255) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: nama dan kode mata kuliah wajib diisi dengan benar.",
                    ]);
                }

                if ($periodCode !== $period->code) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kode periode akademik harus {$period->code}.",
                    ]);
                }

                if (MataKuliah::where('code', $code)->exists()) {
                    continue;
                }

                $masters = MasterMataKuliah::query()
                    ->where('program_studi', $programStudi->code)
                    ->where('name', $name)
                    ->get();

                if ($masters->count() !== 1) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: mata kuliah {$name} harus cocok tepat dengan satu data master pada program studi {$programStudi->code}.",
                    ]);
                }

                $master = $masters->first();
                MataKuliah::create([
                    'mid' => $master->id,
                    'kuri_id' => $request->integer('kuri_id'),
                    'taka_id' => $period->id,
                    'pstudi_id' => $programStudi->id,
                    'dosen_1' => $request->integer('dosen_1'),
                    'name' => $master->name,
                    'code' => $code,
                    'bsks' => $master->sks,
                    'desc' => 'Data mata kuliah hasil import.',
                ]);

                $totalSavedData++;
            }

            return $totalSavedData;
        });

        Alert::success('Sukses', $request->boolean('dry_run')
            ? "Dry-run berhasil. {$totalSavedData} baris mata kuliah valid; tidak ada data disimpan."
            : "Data mata kuliah berhasil diimport. Total data baru: {$totalSavedData}.");

        return back();
    }

    public function importKelas(Request $request, AcademicPeriodContext $context)
    {
        $period = $context->requireWritableCurrent($request->user());
        $this->assertPeriodImportAllowed($request, $period);

        $request->validate(
            [
                'import' => 'required|file|mimes:xlsx,csv|max:2048',
            ],
            [
                'import.required' => 'File harus diunggah.',
                'import.mimes' => 'File harus dalam format xlsx atau csv.',
                'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
            ]
        );

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan file menggunakan format xlsx atau csv yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }
        $requiredHeaders = [
            'Kode Kelas',
            'Nama Kelas',
            'Kapasitas',
            'Kode Tahun Akademik',
            'Kode Program Studi',
            'Kode Program Kuliah',
            'NIDN Wali Dosen',
        ];

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'import' => 'File import tidak berisi data kelas.',
            ]);
        }

        $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $totalSavedData = $this->runImport($request, function () use ($rows, $period) {
            $totalSavedData = 0;

            foreach ($rows as $index => $line) {
                $rowNumber = $index + 2;
                $code = trim((string) $line['Kode Kelas']);
                $name = trim((string) $line['Nama Kelas']);
                $capacity = filter_var($line['Kapasitas'], FILTER_VALIDATE_INT);
                $takaCode = trim((string) $line['Kode Tahun Akademik']);
                $pstudiCode = trim((string) $line['Kode Program Studi']);
                $prokuCode = trim((string) $line['Kode Program Kuliah']);
                $dosenNidn = trim((string) $line['NIDN Wali Dosen']);

                if ($code === '' || strlen($code) > 255 || $name === '' || strlen($name) > 255 || $capacity === false || $capacity < 1 || $capacity > 35) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kode, nama, dan kapasitas kelas wajib diisi dengan benar.",
                    ]);
                }

                if ($takaCode !== $period->code) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kode periode akademik harus {$period->code} sesuai periode yang sedang dipilih.",
                    ]);
                }

                if (Kelas::where('code', $code)->exists()) {
                    continue;
                }

                $pstudi = ProgramStudi::where('code', $pstudiCode)->first();
                $proku = $prokuCode === '' ? null : ProgramKuliah::where('code', $prokuCode)
                    ->where('taka_id', $period->id)
                    ->where('pstudi_id', $pstudi?->id)
                    ->first();
                $dosen = $dosenNidn === '' ? null : Dosen::where('dsn_nidn', $dosenNidn)->first();

                if (! $pstudi || ($prokuCode !== '' && ! $proku) || ($dosenNidn !== '' && ! $dosen)) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: referensi program studi, program kuliah, atau wali dosen tidak ditemukan/tidak sesuai.",
                    ]);
                }

                $kelas = new Kelas;
                $kelas->name = $name;
                $kelas->code = $code;
                $kelas->capacity = $capacity;
                $kelas->taka_id = $period->id;
                $kelas->pstudi_id = $pstudi->id;
                $kelas->proku_id = $proku?->id;
                $kelas->dosen_id = $dosen?->id;
                $kelas->save();

                $totalSavedData++;
            }

            return $totalSavedData;
        });

        Alert::success('Sukses', $request->boolean('dry_run')
            ? "Dry-run berhasil. {$totalSavedData} baris kelas valid; tidak ada data disimpan."
            : "Data kelas berhasil diimport. Total data baru: {$totalSavedData}.");

        return back();
    }

    public function importJadwalKuliah(Request $request, AcademicPeriodContext $context)
    {
        $period = $context->requireWritableCurrent($request->user());
        $this->assertPeriodImportAllowed($request, $period);

        $request->validate(
            [
                'import' => 'required|file|mimes:xlsx,csv|max:2048',
            ],
            [
                'import.required' => 'File harus diunggah.',
                'import.mimes' => 'File harus dalam format xlsx atau csv.',
                'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
            ]
        );

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan file menggunakan format xlsx atau csv yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $requiredHeaders = [
            'Kode Tahun Akademik',
            'Kode Jadwal',
            'Kode Mata Kuliah',
            'Kode Kelas',
            'NIDN Dosen',
            'Kode Ruang',
            'Pertemuan',
            'Metode',
            'Hari',
            'SKS',
            'Tanggal',
            'Waktu Mulai',
            'Waktu Selesai',
        ];

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'import' => 'File import tidak berisi data jadwal kuliah.',
            ]);
        }

        $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $totalSavedData = $this->runImport($request, function () use ($rows, $period) {
            $totalSavedData = 0;

            foreach ($rows as $index => $line) {
                $rowNumber = $index + 2;
                $code = trim((string) $line['Kode Jadwal']);
                $periodCode = trim((string) $line['Kode Tahun Akademik']);
                $mataKuliahCode = trim((string) $line['Kode Mata Kuliah']);
                $kelasCode = trim((string) $line['Kode Kelas']);
                $dosenNidn = trim((string) $line['NIDN Dosen']);
                $ruangCode = trim((string) $line['Kode Ruang']);
                $pertemuan = filter_var($line['Pertemuan'], FILTER_VALIDATE_INT);
                $metode = filter_var($line['Metode'], FILTER_VALIDATE_INT);
                $hari = filter_var($line['Hari'], FILTER_VALIDATE_INT);
                $sks = filter_var($line['SKS'], FILTER_VALIDATE_INT);

                if (
                    $code === '' || strlen($code) > 255 ||
                    $mataKuliahCode === '' || $kelasCode === '' || $dosenNidn === '' || $ruangCode === '' ||
                    $pertemuan === false || $pertemuan < 1 || $pertemuan > 16 ||
                    $metode === false || ! in_array($metode, [0, 1], true) ||
                    $hari === false || $hari < 0 || $hari > 6 ||
                    $sks === false || $sks < 1 || $sks > 8
                ) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: data jadwal wajib diisi sesuai format dan rentang yang ditentukan.",
                    ]);
                }

                if ($periodCode !== $period->code) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kode periode akademik harus {$period->code}.",
                    ]);
                }

                if (JadwalKuliah::where('code', $code)->exists()) {
                    continue;
                }

                $mataKuliah = MataKuliah::query()->forAcademicPeriod($period)->where('code', $mataKuliahCode)->first();
                $kelas = Kelas::query()->forAcademicPeriod($period)->where('code', $kelasCode)->first();
                $dosen = Dosen::where('dsn_nidn', $dosenNidn)->first();
                $ruang = Ruang::where('code', $ruangCode)->first();

                if (! $mataKuliah || ! $kelas || ! $dosen || ! $ruang) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: mata kuliah atau kelas tidak ditemukan pada periode {$period->code}, atau dosen/ruang tidak ditemukan.",
                    ]);
                }

                $dosenPengampu = array_map('intval', array_filter([
                    $mataKuliah->dosen_1,
                    $mataKuliah->dosen_2,
                    $mataKuliah->dosen_3,
                ]));

                if ((int) $mataKuliah->pstudi_id !== (int) $kelas->pstudi_id || ! in_array((int) $dosen->id, $dosenPengampu, true)) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kelas atau dosen tidak sesuai dengan mata kuliah.",
                    ]);
                }

                if (
                    (! $line['Tanggal'] instanceof \DateTimeInterface && trim((string) $line['Tanggal']) === '') ||
                    (! $line['Waktu Mulai'] instanceof \DateTimeInterface && trim((string) $line['Waktu Mulai']) === '') ||
                    (! $line['Waktu Selesai'] instanceof \DateTimeInterface && trim((string) $line['Waktu Selesai']) === '')
                ) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: tanggal dan waktu perkuliahan wajib diisi.",
                    ]);
                }

                try {
                    $date = $line['Tanggal'] instanceof \DateTimeInterface
                        ? $line['Tanggal']->format('Y-m-d')
                        : Carbon::parse((string) $line['Tanggal'])->format('Y-m-d');
                    $start = $line['Waktu Mulai'] instanceof \DateTimeInterface
                        ? $line['Waktu Mulai']->format('H:i:s')
                        : Carbon::parse((string) $line['Waktu Mulai'])->format('H:i:s');
                    $ended = $line['Waktu Selesai'] instanceof \DateTimeInterface
                        ? $line['Waktu Selesai']->format('H:i:s')
                        : Carbon::parse((string) $line['Waktu Selesai'])->format('H:i:s');
                } catch (\Throwable) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: tanggal atau waktu perkuliahan tidak valid.",
                    ]);
                }

                if ($ended <= $start) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: waktu selesai harus setelah waktu mulai.",
                    ]);
                }

                JadwalKuliah::create([
                    'code' => $code,
                    'makul_id' => $mataKuliah->id,
                    'kelas_id' => $kelas->id,
                    'dosen_id' => $dosen->id,
                    'ruang_id' => $ruang->id,
                    'pert_id' => $pertemuan,
                    'meth_id' => $metode,
                    'days_id' => $hari,
                    'bsks' => $sks,
                    'date' => $date,
                    'start' => $start,
                    'ended' => $ended,
                ]);

                $totalSavedData++;
            }

            return $totalSavedData;
        });

        Alert::success('Sukses', $request->boolean('dry_run')
            ? "Dry-run berhasil. {$totalSavedData} baris jadwal valid; tidak ada data disimpan."
            : "Data jadwal kuliah berhasil diimport. Total data baru: {$totalSavedData}.");

        return back();
    }

    public function importGedung(Request $request)
    {
        $request->validate(
            [
                'import' => 'required|file|mimes:xlsx,csv|max:2048',
            ],
            [
                'import.required' => 'File harus diunggah.',
                'import.mimes' => 'File harus dalam format xlsx atau csv.',
                'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
            ]
        );

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan file menggunakan format xlsx atau csv yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $requiredHeaders = ['Kode Gedung', 'Nama Gedung'];

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'import' => 'File import tidak berisi data gedung.',
            ]);
        }

        $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $totalSavedData = $this->runImport($request, function () use ($rows) {
            $totalSavedData = 0;

            foreach ($rows as $index => $line) {
                $rowNumber = $index + 2;
                $code = strtoupper(trim((string) $line['Kode Gedung']));
                $name = trim((string) $line['Nama Gedung']);

                if ($code === '' || strlen($code) > 3 || $name === '' || strlen($name) > 255) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kode gedung maksimal 3 karakter dan nama gedung wajib diisi.",
                    ]);
                }

                if (Gedung::where('code', $code)->exists()) {
                    continue;
                }

                Gedung::create([
                    'code' => $code,
                    'name' => $name,
                ]);

                $totalSavedData++;
            }

            return $totalSavedData;
        });

        Alert::success('Sukses', "Data gedung berhasil diimport. Total data baru: {$totalSavedData}.");

        return back();
    }

    public function importRuang(Request $request)
    {
        $request->validate(
            [
                'import' => 'required|file|mimes:xlsx,csv|max:2048',
            ],
            [
                'import.required' => 'File harus diunggah.',
                'import.mimes' => 'File harus dalam format xlsx atau csv.',
                'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
            ]
        );

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan file menggunakan format xlsx atau csv yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $requiredHeaders = ['Kode Ruang', 'Nama Ruang', 'Kode Gedung', 'Tipe', 'Lantai'];

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'import' => 'File import tidak berisi data ruang.',
            ]);
        }

        $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $totalSavedData = $this->runImport($request, function () use ($rows) {
            $totalSavedData = 0;

            foreach ($rows as $index => $line) {
                $rowNumber = $index + 2;
                $code = strtoupper(trim((string) $line['Kode Ruang']));
                $name = trim((string) $line['Nama Ruang']);
                $gedungCode = strtoupper(trim((string) $line['Kode Gedung']));
                $type = filter_var($line['Tipe'], FILTER_VALIDATE_INT);
                $floor = filter_var($line['Lantai'], FILTER_VALIDATE_INT);

                if (
                    $code === '' || strlen($code) > 5 ||
                    $name === '' || strlen($name) > 255 ||
                    $gedungCode === '' ||
                    $type === false || $type < 0 || $type > 4 ||
                    $floor === false
                ) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: data ruang wajib diisi sesuai format yang ditentukan.",
                    ]);
                }

                if (Ruang::where('code', $code)->exists()) {
                    continue;
                }

                $gedung = Gedung::where('code', $gedungCode)->first();

                if (! $gedung) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kode gedung {$gedungCode} tidak ditemukan.",
                    ]);
                }

                Ruang::create([
                    'code' => $code,
                    'name' => $name,
                    'gedu_id' => $gedung->id,
                    'type' => $type,
                    'floor' => $floor,
                ]);

                $totalSavedData++;
            }

            return $totalSavedData;
        });

        Alert::success('Sukses', "Data ruang berhasil diimport. Total data baru: {$totalSavedData}.");

        return back();
    }

    private function runImport(Request $request, callable $callback): mixed
    {
        DB::beginTransaction();

        try {
            $result = $callback();
            $request->boolean('dry_run') ? DB::rollBack() : DB::commit();

            return $result;
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }
    }

    private function assertPeriodImportAllowed(Request $request, TahunAkademik $period): void
    {
        if ($period->status !== TahunAkademik::STATUS_DRAFT && ! $request->boolean('dry_run')) {
            throw ValidationException::withMessages([
                'academic_period' => 'Import yang mengubah data hanya diizinkan pada periode draft. Gunakan dry-run untuk memeriksa file pada periode aktif.',
            ]);
        }
    }
}
