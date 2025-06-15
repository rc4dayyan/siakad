<?php

namespace App\Http\Controllers\Services\Convert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// SECTION ADDONS SYSTEM
use Illuminate\Support\Facades\File;
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
use DateTime;

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
            foreach ($rows as $line) {
                
                if (Mahasiswa::where('mhs_nim', $line['NIM'])->exists()) {
                    $message = "❌ Data dengan NIM {$line['NIM']} sudah ada. Import dihentikan.";
                    $continue = false;
                    break; // ❗ Stop the loop immediately
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
            $message = "✅ Data berhasil diimport.";
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
            foreach ($rows as $line) {
                if (MataKuliah::where('code', $line['Kode'])->where('taka_id', $taka_id)->exists()) {
                    $message = "❌ Data dengan matakuliah {$line['Kode']} sudah ada. Import dihentikan.";
                    $continue = false;
                    break; // ❗ Stop the loop immediately
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
            $message = "✅ Data berhasil diimport.";
            Alert::success('Sukses', $message);
        } else {
            Alert::error('Gagal', $message);
        }


        return back();
    }
}
