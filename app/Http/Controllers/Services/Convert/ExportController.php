<?php

namespace App\Http\Controllers\Services\Convert;

use App\Http\Controllers\Controller;
// SECTION ADDONS SYSTEM
// SECTION ADDONS EXTERNAL
use App\Models\Gedung;
// SECTION MODELS
use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Academic\AcademicPeriodContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function exportStudent(Request $request, AcademicPeriodContext $context)
    {
        $filters = $request->validate([
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'prodi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ]);

        $period = $context->requireCurrent(auth()->user());
        $studentIds = null;

        if ($filters['angkatan'] ?? null) {
            $studentIds = Mahasiswa::query()
                ->with('registrasiAwal.taka')
                ->get(['id', 'years_id'])
                ->filter(fn (Mahasiswa $student) => ($student->registrasiAwal?->taka?->year_start
                    ?: ((int) $student->years_id ?: null)) === (int) $filters['angkatan'])
                ->modelKeys();
        }

        $registrations = RegistrasiMahasiswa::query()
            ->forAcademicPeriod($period)
            ->when($studentIds !== null, fn ($query) => $query->whereIn('mahasiswa_id', $studentIds))
            ->when($filters['prodi_id'] ?? null, fn ($query, $programId) => $query
                ->whereHas('kelas', fn ($class) => $class->where('pstudi_id', $programId)))
            ->when($filters['kelas_id'] ?? null, fn ($query, $kelasId) => $query->where('kelas_id', $kelasId))
            ->with(['mahasiswa.registrasiAwal.taka', 'kelas.pstudi', 'taka'])
            ->get();

        $districtNames = $registrations
            ->pluck('mahasiswa.mhs_addr_kecamatan')
            ->filter()
            ->unique()
            ->values();
        $regions = Wilayah::query()
            ->whereIn('kecamatan', $districtNames)
            ->get();

        return (new FastExcel($registrations))->download('export-mahasiswa-openfeeder-'.$period->code.'-'.now()->format('YmdHis').'.xlsx', function ($registration) use ($regions) {
            $student = $registration->mahasiswa;
            $studyProgram = $registration->kelas?->pstudi;
            $region = $regions->first(fn (Wilayah $item) => $this->sameRegion($student, $item));

            return [
                'NIM' => $student?->mhs_nim,
                'Nama' => $student?->mhs_name,
                'Tempat Lahir' => $student?->mhs_birthplace,
                'Tanggal Lahir' => $this->exportDate($student?->mhs_birthdate),
                'Jenis Kelamin' => $student?->mhs_gend,
                'NIK' => $student?->mhs_nik,
                'Agama' => $student?->raw_mhs_reli,
                'NISN' => null,
                'Jalur Pendaftaran' => null,
                'NPWP' => null,
                'Kewarganegaraan' => null,
                'Jenis Pendaftaran' => $student?->mhs_register_type,
                'Tanggal Masuk Kuliah' => $this->exportDate($student?->mhs_register_date),
                'Mulai Semester' => $student ? $this->openFeederSemester($student->registrasiAwal?->taka, $student) : null,
                'Jalan' => $student?->mhs_addr_domisili,
                'RT' => null,
                'RW' => null,
                'Nama Dusun' => null,
                'Kelurahan' => $student?->mhs_addr_kelurahan,
                'Kecamatan' => $region?->code,
                'Kode Pos' => null,
                'Jenis Tinggal' => null,
                'Alat Transportasi' => null,
                'Telp Rumah' => null,
                'No HP' => $student?->getRawOriginal('mhs_phone'),
                'Email' => $student?->mhs_mail,
                'Terima KPS' => null,
                'No KPS' => null,
                'NIK Ayah' => null,
                'Nama Ayah' => $student?->mhs_parent_father,
                'Tanggal Lahir Ayah' => null,
                'Pendidikan Ayah' => null,
                'Pekerjaan Ayah' => null,
                'Penghasilan Ayah' => null,
                'NIK Ibu' => null,
                'Nama Ibu' => $student?->mhs_parent_mother,
                'Tanggal Lahir Ibu' => null,
                'Pendidikan Ibu' => null,
                'Pekerjaan Ibu' => null,
                'Penghasilan Ibu' => null,
                'Nama Wali' => $student?->mhs_wali_name,
                'Tanggal Lahir Wali' => null,
                'Pendidikan Wali' => null,
                'Pekerjaan Wali' => null,
                'Penghasilan Wali' => null,
                'Kode Prodi' => $studyProgram?->code,
                'Nama Prodi' => $studyProgram?->name,
                'SKS Diakui' => null,
                'Kode PT Asal' => null,
                'Nama PT Asal' => null,
                'Kode Prodi Asal' => null,
                'Nama Prodi Asal' => null,
                'Jenis Pembiayaan' => null,
                'Jumlah Biaya Masuk' => $student?->mhs_register_amount,
                'Status Biodata' => 1,
                'Keterangan Biodata' => null,
                'Status Riwayat' => 1,
                'Keterangan Riwayat' => null,
            ];
        });
    }

    private function openFeederSemester(?TahunAkademik $period, Mahasiswa $student): ?string
    {
        $year = $period?->year_start ?: ((int) $student->years_id ?: null);

        if (! $year) {
            return null;
        }

        $term = match ($period?->term) {
            TahunAkademik::TERM_GENAP => '2',
            TahunAkademik::TERM_PENDEK => '3',
            default => '1',
        };

        return $year.$term;
    }

    private function exportDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function sameRegion(?Mahasiswa $student, Wilayah $region): bool
    {
        if (! $student || strcasecmp(trim((string) $student->mhs_addr_kecamatan), trim($region->kecamatan)) !== 0) {
            return false;
        }

        return ($student->mhs_addr_kota === null || strcasecmp(trim($student->mhs_addr_kota), trim((string) $region->kabupaten)) === 0)
            && ($student->mhs_addr_provinsi === null || strcasecmp(trim($student->mhs_addr_provinsi), trim((string) $region->provinsi)) === 0);
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
