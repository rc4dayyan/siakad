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

        DB::beginTransaction();

        try {
            $continue = true;
            foreach ($rows as $line) {
                if (Mahasiswa::where('mhs_nim', $line['NIM'])->exists()) {
                    $message = "❌ Data dengan NIM {$line['NIM']} sudah ada. Import dihentikan.";
                    $continue = false;
                    break; // ❗ Stop the loop immediately
                }

                Mahasiswa::create([
                    'mhs_nim'        => $line['NIM'],
                    'mhs_mail'       => $line['Email'],
                    'mhs_phone'      => $line['Phone'],
                    'mhs_name'       => $line['FullName'],
                    'mhs_gend'       => $line['Gender'],
                    'mhs_reli'       => $line['Religion'] == null ? null : $line['Religion'],
                    'mhs_birthplace' => $line['BirthPlace'] == null ? null : $line['BirthPlace'],
                    'mhs_birthdate'  => $line['BirthDate'] == null ? null : $line['BirthDate'],
                    'mhs_stat'       => $line['TypeUser'],
                    'years_id'       => $line['YearsID'],
                    'class_id'       => $line['ClassID'],
                    'mhs_code'       => Str::random(6),
                    'mhs_user'       => Str::random(6),
                    'password'       => Hash::make($line['Phone']),
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
