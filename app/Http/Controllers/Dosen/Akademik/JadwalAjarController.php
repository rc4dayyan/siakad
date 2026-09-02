<?php

namespace App\Http\Controllers\Dosen\Akademik;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\AbsensiMahasiswa;
use App\Models\FeedBack\FBPerkuliahan;
use App\Models\JadwalKuliah;
use App\Models\JadwalMingguan;
use App\Models\Kelas;
use App\Models\Ruang;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PDF;

class JadwalAjarController extends Controller
{
    public function index(Request $request, AcademicPeriodContext $context): View
    {
        $dosen = auth('dosen')->user();
        $period = $context->published();
        $baseQuery = JadwalKuliah::query()
            ->forAcademicPeriod($period)
            ->forLecturer($dosen->id);
        $classIds = (clone $baseQuery)->distinct()->pluck('kelas_id');
        $roomIds = (clone $baseQuery)->distinct()->pluck('ruang_id');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kelas_id' => ['nullable', 'integer', Rule::in($classIds->all())],
            'ruang_id' => ['nullable', 'integer', Rule::in($roomIds->all())],
            'meth_id' => ['nullable', 'integer', 'in:0,1'],
            'days_id' => ['nullable', 'integer', 'between:0,6'],
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                Rule::when($request->filled('date_from'), ['after_or_equal:date_from']),
            ],
        ]);

        $schedules = (clone $baseQuery)
            ->with(['matkul', 'kelas', 'dosen', 'ruang.gedung'])
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('matkul', fn ($course) => $course
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%"))
                        ->orWhereHas('kelas', fn ($class) => $class
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['kelas_id'] ?? null, fn ($query, $classId) => $query->where('kelas_id', $classId))
            ->when($filters['ruang_id'] ?? null, fn ($query, $roomId) => $query->where('ruang_id', $roomId))
            ->when(isset($filters['meth_id']) && $filters['meth_id'] !== null, fn ($query) => $query->where('meth_id', $filters['meth_id']))
            ->when(isset($filters['days_id']) && $filters['days_id'] !== null, fn ($query) => $query->where('days_id', $filters['days_id']))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->orderBy('date')
            ->orderBy('start')
            ->get();

        return view('dosen.pages.jadwal-index', [
            'web' => webSettings::where('id', 1)->first(),
            'jadkul' => $schedules,
            'filters' => $filters,
            'filterClasses' => Kelas::query()->whereIn('id', $classIds)->orderBy('name')->get(),
            'filterRooms' => Ruang::query()->whereIn('id', $roomIds)->orderBy('name')->get(),
            'selectedPeriod' => $period,
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

    public function downloadWeeklySchedule(AcademicPeriodContext $context)
    {
        $dosen = auth('dosen')->user();
        $period = $context->published();
        $schedules = JadwalMingguan::query()
            ->forAcademicPeriod($period)
            ->where('dosen_id', $dosen->id)
            ->with(['penawaranMataKuliah.masterMataKuliah', 'kelas', 'ruang.gedung'])
            ->orderBy('hari')
            ->orderBy('mulai')
            ->get();
        $lecturerIdentifier = preg_replace('/[^A-Za-z0-9_-]+/', '-', $dosen->dsn_nidn ?: $dosen->id);
        $periodCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', $period?->code ?? 'periode-aktif');

        return PDF::loadView('base.cetak.cetak-jadwal-mingguan-dosen', [
            'lecturer' => $dosen,
            'period' => $period,
            'schedules' => $schedules,
            'web' => webSettings::query()->first(),
            'printedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download("jadwal-mingguan-dosen-{$lecturerIdentifier}-{$periodCode}.pdf");
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
