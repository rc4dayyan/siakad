<?php

namespace App\Http\Controllers\Services\Convert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// SECTION ADDONS SYSTEM
use Illuminate\Support\Facades\File;
use Auth;
use Hash;
use Str;
use Carbon\Carbon;
// SECTION ADDONS EXTERNAL
use Alert;
use Rap2hpoutre\FastExcel\FastExcel;
// SECTION MODELS
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\JadwalKuliah;
use App\Models\Gedung;
use App\Models\Ruang;

class ExportController extends Controller
{
    public function exportUsers()
    {
        $users = User::all();

        (new FastExcel($users))->export('export-users-'.uniqid().'.csv', function ($user) {
            return [
                'Username' => $user->user,
                'Email' => $user->email,
                'Phone' => $user->phone,
                'FullName' => $user->name,
                'Gender' => $user->gend,
                'Religion' => $user->raw_reli,
                'BirthPlace' => $user->birth_place,
                'BirthDate' => $user->birth_date,
                'TypeUser' => $user->raw_type,
                'Status' => $user->status,
            ];
        });

        return (new FastExcel($users))->download('export-users-'.uniqid().'.csv');

    }

    public function exportStudent()
    {
        $users = Mahasiswa::all();

        (new FastExcel($users))->export('export-student-'.uniqid().'.csv', function ($user) {
            return [
                'NIM' => $user->mhs_nim,
                'Email' => $user->mhs_mail,
                'Phone' => $user->mhs_phone,
                'FullName' => $user->mhs_name,
                'Gender' => $user->mhs_gend,
                'Religion' => $user->raw_mhs_reli,
                'BirthPlace' => $user->mhs_birthplace,
                'BirthDate' => $user->mhs_birthdate,
                'TypeUser' => $user->raw_mhs_stat,
                'YearsID' => $user->years_id,
                'ClassID' => $user->class_id,
            ];
        });

        return (new FastExcel($users))->download('export-student-'.uniqid().'.csv');

    }

    public function exportKelas()
    {
        $kelas = Kelas::with(['taka', 'pstudi', 'proku', 'dosen'])->get();

        return (new FastExcel($kelas))->download('export-kelas-'.uniqid().'.csv', function (Kelas $item) {
            return [
                'Kode Kelas' => $item->code,
                'Nama Kelas' => $item->name,
                'Kapasitas' => $item->capacity,
                'Kode Tahun Akademik' => $item->taka?->code,
                'Kode Program Studi' => $item->pstudi?->code,
                'Kode Program Kuliah' => $item->proku?->code,
                'NIDN Wali Dosen' => $item->dosen?->dsn_nidn,
            ];
        });
    }

    public function exportMataKuliah()
    {
        $mataKuliah = MataKuliah::with([
            'kuri',
            'taka',
            'pstudi',
            'requ',
            'dosen1',
            'dosen2',
            'dosen3',
        ])->get();

        return (new FastExcel($mataKuliah))->download('export-mata-kuliah-'.uniqid().'.csv', function (MataKuliah $item) {
            return [
                'Kode' => $item->code,
                'Nama' => $item->name,
                'SKS' => $item->bsks,
                'Deskripsi' => $item->desc,
                'Kode Kurikulum' => $item->kuri?->code,
                'Kode Tahun Akademik' => $item->taka?->code,
                'Kode Program Studi' => $item->pstudi?->code,
                'Kode Mata Kuliah Prasyarat' => $item->requ?->code,
                'NIDN Dosen Utama' => $item->dosen1?->dsn_nidn,
                'NIDN Dosen Kedua' => $item->dosen2?->dsn_nidn,
                'NIDN Dosen Ketiga' => $item->dosen3?->dsn_nidn,
            ];
        });
    }

    public function exportJadwalKuliah()
    {
        $jadwalKuliah = JadwalKuliah::with(['matkul', 'kelas', 'dosen', 'ruang'])->get();

        return (new FastExcel($jadwalKuliah))->download('export-jadwal-kuliah-'.uniqid().'.csv', function (JadwalKuliah $item) {
            return [
                'Kode Jadwal' => $item->code,
                'Kode Mata Kuliah' => $item->matkul?->code,
                'Kode Kelas' => $item->kelas?->code,
                'NIDN Dosen' => $item->dosen?->dsn_nidn,
                'Kode Ruang' => $item->ruang?->code,
                'Pertemuan' => $item->raw_pert_id,
                'Metode' => $item->raw_meth_id,
                'Hari' => $item->raw_days_id,
                'SKS' => $item->bsks,
                'Tanggal' => $item->date,
                'Waktu Mulai' => $item->start,
                'Waktu Selesai' => $item->ended,
            ];
        });
    }

    public function exportGedung()
    {
        $gedung = Gedung::all();

        return (new FastExcel($gedung))->download('export-gedung-'.uniqid().'.csv', function (Gedung $item) {
            return [
                'Kode Gedung' => $item->code,
                'Nama Gedung' => $item->name,
            ];
        });
    }

    public function exportRuang()
    {
        $ruang = Ruang::with('gedung')->get();

        return (new FastExcel($ruang))->download('export-ruang-'.uniqid().'.csv', function (Ruang $item) {
            return [
                'Kode Ruang' => $item->code,
                'Nama Ruang' => $item->name,
                'Kode Gedung' => $item->gedung?->code,
                'Tipe' => $item->raw_type,
                'Lantai' => $item->floor,
            ];
        });
    }
}
