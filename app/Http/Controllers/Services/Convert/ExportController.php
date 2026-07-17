<?php

namespace App\Http\Controllers\Services\Convert;

use App\Http\Controllers\Controller;
// SECTION ADDONS SYSTEM
// SECTION ADDONS EXTERNAL
use App\Models\Gedung;
// SECTION MODELS
use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Ruang;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Rap2hpoutre\FastExcel\FastExcel;

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

    public function exportStudent(AcademicPeriodContext $context)
    {
        $period = $context->requireCurrent(auth()->user());
        $registrations = \App\Models\RegistrasiMahasiswa::query()
            ->forAcademicPeriod($period)->with(['mahasiswa', 'kelas', 'taka'])->get();

        return (new FastExcel($registrations))->download('export-student-'.$period->code.'-'.uniqid().'.csv', function ($registration) {
            return [
                'Kode Tahun Akademik' => $registration->taka?->code,
                'NIM' => $registration->mahasiswa?->mhs_nim,
                'Email' => $registration->mahasiswa?->mhs_mail,
                'Telepon' => $registration->mahasiswa?->mhs_phone,
                'Nama' => $registration->mahasiswa?->mhs_name,
                'Kode Kelas' => $registration->kelas?->code,
                'Semester Mahasiswa' => $registration->semester_mahasiswa,
                'Status Akademik' => $registration->status_akademik,
                'Status Registrasi' => $registration->status_registrasi,
                'Batas SKS' => $registration->batas_sks,
            ];
        });
    }

    public function exportKelas(AcademicPeriodContext $context)
    {
        $period = $context->requireCurrent(auth()->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->with(['taka', 'pstudi', 'proku', 'dosen'])
            ->get();

        return (new FastExcel($kelas))->download('export-kelas-'.$period->code.'-'.uniqid().'.csv', function (Kelas $item) {
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

    public function exportMataKuliah(AcademicPeriodContext $context)
    {
        $period = $context->requireCurrent(auth()->user());
        $mataKuliah = MataKuliah::query()
            ->forAcademicPeriod($period)
            ->with([
                'kuri',
                'taka',
                'pstudi',
                'requ',
                'dosen1',
                'dosen2',
                'dosen3',
            ])->get();

        return (new FastExcel($mataKuliah))->download('export-mata-kuliah-'.$period->code.'-'.uniqid().'.csv', function (MataKuliah $item) {
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

    public function exportJadwalKuliah(AcademicPeriodContext $context)
    {
        $period = $context->requireCurrent(auth()->user());
        $jadwalKuliah = JadwalKuliah::query()
            ->forAcademicPeriod($period)
            ->with(['matkul', 'kelas', 'dosen', 'ruang'])
            ->get();

        return (new FastExcel($jadwalKuliah))->download('export-jadwal-kuliah-'.$period->code.'-'.uniqid().'.csv', function (JadwalKuliah $item) use ($period) {
            return [
                'Kode Tahun Akademik' => $period->code,
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
