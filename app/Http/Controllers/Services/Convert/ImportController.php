<?php

namespace App\Http\Controllers\Services\Convert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// SECTION ADDONS SYSTEM
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Auth;
use Hash;
use Str;
// SECTION ADDONS EXTERNAL
use Alert;
use Rap2hpoutre\FastExcel\FastExcel;
// SECTION MODELS
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Kelas;
use App\Models\ProgramKuliah;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use App\Models\JadwalKuliah;
use App\Models\Ruang;
use App\Models\Gedung;
use DateTime;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

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
        $users = (new FastExcel)->import(storage_path('app/' . $path) , function ($line) {
            return User::create([
                'user' => $line['Username'],
                'email' => $line['Email'],
                'phone' => $line['Phone'],
                'name' => $line['FullName'],
                'gend' => $line['Gender'] ,
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
    public function importStudent(Request $request)
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
        $rows = (new FastExcel)->import(storage_path('app/' . $path));
        $message = '';

        $class_id = $request->input('class_id');

        DB::beginTransaction();

        try {
            $continue = true;
            $totalSavedData = 0;
            foreach ($rows as $line) {
                if (Mahasiswa::where('mhs_nim', $line['NIM'])->exists()) {
                    continue;
                    // $message = "❌ Data dengan NIM {$line['NIM']} sudah ada. Import dihentikan.";
                    // $continue = false;
                    // break; // ❗ Stop the loop immediately
                }
                $ttl          = explode(',', $line['Tempat,Tanggal Lahir']);
                $tempatLahir  = $ttl[0] ?? '';
                if(isset($ttl[1])){
                    $date = new DateTime($ttl[1]);
                    $tanggalLahir = $date->format('Y-m-d');
                } else {
                    $tanggalLahir = '';
                }

                $regDateTmp = new DateTime($line['Tanggal Masuk']);
                $registerDate = $regDateTmp->format('Y-m-d');
                

                Mahasiswa::create([
                    'mhs_nim'             => $line['NIM'],
                    'mhs_nik'             => $line['NIK'],
                    'mhs_mail'            => $line['Email'],
                    'mhs_phone'           => $line['Telepon'],
                    'mhs_name'            => $line['Nama'],
                    'mhs_gend'            => $line['Jenis Kelamin'],
                    'mhs_reli'            => $line['Agama'] ?? '',
                    'mhs_birthplace'      => $tempatLahir,
                    'mhs_birthdate'       => $tanggalLahir,
                    'mhs_stat'            => 1,
                    'years_id'            => 0,
                    'class_id'            => $class_id,
                    'mhs_code'            => Str::random(6),
                    'mhs_user'            => Str::random(6),
                    'password'            => Hash::make($line['NIK']),
                    'mhs_register_date'   => $registerDate,
                    'mhs_status'          => $line['Status Mahasiswa'],
                    'mhs_register_type'   => $line['Jenis Pendaftaran'],
                    'mhs_register_amount' => $line['Biaya Masuk'],
                    'mhs_sync_status'     => $line['Status Sync'],
                ]);

                $totalSavedData++;
            }

            if($continue) {
                // Commit transaction
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();

            $message = "Failed! " . $e->getMessage();
        }


        if(empty($message)){
            $message = "✅ Data berhasil diimport dengan total data baru masuk berjumlah $totalSavedData.";
            Alert::success('Sukses', $message);
        } else {
            var_dump($message);exit;
            Alert::error('Gagal', $message);
        }


        return back();
    }

    public function importMataKuliah(Request $request)
    {
        $request->validate(
            [
                'import' => 'required|file|mimes:xls,xlsx,csv|max:2048', // max:2048 untuk batasan 2MB
            ],
            [
                'import.required' => 'File harus diunggah.',
                'import.mimes' => 'File harus dalam format xls, xlsx, atau csv.',
                'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
            ]
        );

        $taka_id   = $request->input('taka_id');
        $pstudi_id = $request->input('pstudi_id');
        $dosen_1 = $request->input('dosen_1');


        $path = $request->file('import')->store('public/excel-files');
        $rows = (new FastExcel)->import(storage_path('app/' . $path));
        $message = '';

        DB::beginTransaction();

        try {
            $continue = true;
            $totalSavedData = 0;
            foreach ($rows as $line) {
                
                if (MataKuliah::where('code', $line['Kode'])->where('taka_id', $taka_id)->exists()) {
                    continue;
                    // $message = "❌ Data dengan matakuliah {$line['Kode']} sudah ada. Import dihentikan.";
                    // $continue = false;
                    // break; // ❗ Stop the loop immediately
                }

                MataKuliah::create([
                    'kuri_id'   => 1,
                    'taka_id'   => $taka_id,
                    'pstudi_id' => $pstudi_id,
                    'dosen_1'   => $dosen_1,
                    'dosen_2'   => null,
                    'dosen_3'   => null,
                    'name'      => $line['Nama'],
                    'code'      => $line['Kode'],
                    'bsks'      => 20,
                    'desc'      => '',
                ]);
                $totalSavedData++;
            }

            if ($continue) {
                // Commit transaction
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();

            $message = "Failed! " . $e->getMessage();
        }


        if (empty($message)) {
            $message = "✅ Data berhasil diimport dengan data baru masuk berjumlah $totalSavedData";
            Alert::success('Sukses', $message);
        } else {
            Alert::error('Gagal', $message);
        }


        return back();
    }

    public function importKelas(Request $request)
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

        $totalSavedData = DB::transaction(function () use ($rows) {
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

                if (Kelas::where('code', $code)->exists()) {
                    continue;
                }

                $taka = TahunAkademik::where('code', $takaCode)->first();
                $pstudi = ProgramStudi::where('code', $pstudiCode)->first();
                $proku = $prokuCode === '' ? null : ProgramKuliah::where('code', $prokuCode)
                    ->where('taka_id', $taka?->id)
                    ->where('pstudi_id', $pstudi?->id)
                    ->first();
                $dosen = $dosenNidn === '' ? null : Dosen::where('dsn_nidn', $dosenNidn)->first();

                if (! $taka || ! $pstudi || ($prokuCode !== '' && ! $proku) || ($dosenNidn !== '' && ! $dosen)) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: referensi tahun akademik, program studi, program kuliah, atau wali dosen tidak ditemukan/tidak sesuai.",
                    ]);
                }

                $kelas = new Kelas;
                $kelas->name = $name;
                $kelas->code = $code;
                $kelas->capacity = $capacity;
                $kelas->taka_id = $taka->id;
                $kelas->pstudi_id = $pstudi->id;
                $kelas->proku_id = $proku?->id;
                $kelas->dosen_id = $dosen?->id;
                $kelas->save();

                $totalSavedData++;
            }

            return $totalSavedData;
        });

        Alert::success('Sukses', "Data kelas berhasil diimport. Total data baru: {$totalSavedData}.");

        return back();
    }

    public function importJadwalKuliah(Request $request)
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

        $requiredHeaders = [
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

        $totalSavedData = DB::transaction(function () use ($rows) {
            $totalSavedData = 0;

            foreach ($rows as $index => $line) {
                $rowNumber = $index + 2;
                $code = trim((string) $line['Kode Jadwal']);
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

                if (JadwalKuliah::where('code', $code)->exists()) {
                    continue;
                }

                $mataKuliah = MataKuliah::where('code', $mataKuliahCode)->first();
                $kelas = Kelas::where('code', $kelasCode)->first();
                $dosen = Dosen::where('dsn_nidn', $dosenNidn)->first();
                $ruang = Ruang::where('code', $ruangCode)->first();

                if (! $mataKuliah || ! $kelas || ! $dosen || ! $ruang) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: mata kuliah, kelas, dosen, atau ruang tidak ditemukan.",
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

        Alert::success('Sukses', "Data jadwal kuliah berhasil diimport. Total data baru: {$totalSavedData}.");

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

        $totalSavedData = DB::transaction(function () use ($rows) {
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

        $totalSavedData = DB::transaction(function () use ($rows) {
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
}
