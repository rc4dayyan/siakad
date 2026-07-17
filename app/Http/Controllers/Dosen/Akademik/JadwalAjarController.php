<?php

namespace App\Http\Controllers\Dosen\Akademik;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\AbsensiMahasiswa;
use App\Models\FeedBack\FBPerkuliahan;
use App\Models\JadwalKuliah;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalAjarController extends Controller
{
    public function index(AcademicPeriodContext $context): View
    {
        $dosen = auth('dosen')->user();
        $period = $context->published();

        return view('dosen.pages.jadwal-index', [
            'web' => webSettings::where('id', 1)->first(),
            'jadkul' => JadwalKuliah::query()
                ->forAcademicPeriod($period)
                ->forLecturer($dosen->id)
                ->with(['matkul', 'kelas', 'dosen', 'ruang.gedung'])
                ->latest()
                ->get(),
        ]);
    }

    public function viewAbsen(string $code, AcademicPeriodContext $context): View
    {
        $jadwal = $this->ownedActiveSchedule($code, $context);

        return view('dosen.pages.jadwal-absen', [
            'web' => webSettings::where('id', 1)->first(),
            'absen' => AbsensiMahasiswa::where('jadkul_code', $jadwal->code)->with('mahasiswa')->get(),
        ]);
    }

    public function viewFeedBack(string $code, AcademicPeriodContext $context): View
    {
        $jadwal = $this->ownedActiveSchedule($code, $context);

        return view('dosen.pages.jadwal-feedback', [
            'web' => webSettings::where('id', 1)->first(),
            'feedback' => FBPerkuliahan::where('fb_jakul_code', $jadwal->code)->get(),
            'code' => $jadwal->code,
        ]);
    }

    public function updateAbsen(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->published();
        abort_unless($period?->isWritable(), 404);

        $validated = $request->validate(['absen_desc' => ['nullable', 'string', 'max:4000']]);
        $dosen = auth('dosen')->user();
        $absen = AbsensiMahasiswa::query()
            ->where('code', $code)
            ->whereHas('jadkul', fn ($query) => $query
                ->forAcademicPeriod($period)
                ->forLecturer($dosen->id))
            ->firstOrFail();
        $absen->update($validated);

        Alert::success('Berhasil', 'Keterangan absensi berhasil diperbarui.');

        return back();
    }

    private function ownedActiveSchedule(string $code, AcademicPeriodContext $context): JadwalKuliah
    {
        $dosen = auth('dosen')->user();

        return JadwalKuliah::query()
            ->forAcademicPeriod($context->published())
            ->forLecturer($dosen->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
