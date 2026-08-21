<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\PenawaranMataKuliah;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\KrsService;
use App\Services\Academic\StudentAcademicContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KrsController extends Controller
{
    public function index(StudentAcademicContext $context, KrsService $service): View
    {
        $student = Auth::guard('mahasiswa')->user();
        $period = app(AcademicPeriodContext::class)->published();
        $registration = $context->registrationFor($student, $period);
        $krs = $registration ? $service->forRegistration($registration)->load([
            'items.penawaranMataKuliah.masterMataKuliah',
            'items.penawaranMataKuliah.kelas',
        ]) : null;

        return view('mahasiswa.pages.krs-index', [
            'period' => $period,
            'registration' => $registration,
            'krs' => $krs,
            'offerings' => $registration ? PenawaranMataKuliah::query()
                ->forAcademicPeriod($period)
                ->where('pstudi_id', $registration->kelas?->pstudi_id)
                ->where('kelas_id', $registration->kelas_id)
                ->with(['masterMataKuliah', 'kelas', 'dosenUtama', 'prasyaratMaster'])
                ->withCount('krsItems')
                ->orderBy('code')->get() : collect(),
        ]);
    }

    public function add(PenawaranMataKuliah $penawaran, StudentAcademicContext $context, KrsService $service): RedirectResponse
    {
        $krs = $this->currentKrs($context, $service);
        $service->add($krs, $penawaran);

        return back()->with('success', 'Mata kuliah berhasil ditambahkan ke draft KRS.');
    }

    public function addMany(Request $request, StudentAcademicContext $context, KrsService $service): RedirectResponse
    {
        $data = $request->validate([
            'penawaran_ids' => ['required', 'array', 'min:1'],
            'penawaran_ids.*' => ['required', 'integer', 'distinct', 'exists:penawaran_mata_kuliahs,id'],
        ], [
            'penawaran_ids.required' => 'Pilih minimal satu mata kuliah yang akan ditambahkan.',
            'penawaran_ids.min' => 'Pilih minimal satu mata kuliah yang akan ditambahkan.',
            'penawaran_ids.*.distinct' => 'Pilihan mata kuliah tidak boleh duplikat.',
            'penawaran_ids.*.exists' => 'Salah satu penawaran mata kuliah tidak ditemukan.',
        ]);

        $offerings = PenawaranMataKuliah::query()
            ->whereKey($data['penawaran_ids'])
            ->get()
            ->keyBy('id');
        $orderedOfferings = collect($data['penawaran_ids'])
            ->map(fn (int|string $id) => $offerings->get((int) $id));

        $service->addMany($this->currentKrs($context, $service), $orderedOfferings);

        return back()->with('success', $orderedOfferings->count().' mata kuliah berhasil ditambahkan ke draft KRS.');
    }

    public function remove(int $item, StudentAcademicContext $context, KrsService $service): RedirectResponse
    {
        $service->remove($this->currentKrs($context, $service), $item);

        return back()->with('success', 'Mata kuliah berhasil dihapus dari draft KRS.');
    }

    public function submit(Request $request, StudentAcademicContext $context, KrsService $service): RedirectResponse
    {
        $data = $request->validate(['catatan_mahasiswa' => ['nullable', 'string', 'max:2000']]);
        $service->submit($this->currentKrs($context, $service), $data['catatan_mahasiswa'] ?? null);

        return back()->with('success', 'KRS berhasil diajukan kepada dosen wali.');
    }

    public function print(StudentAcademicContext $context, KrsService $service): View
    {
        $krs = $this->currentKrs($context, $service)->load([
            'registrasiMahasiswa.mahasiswa', 'registrasiMahasiswa.taka', 'registrasiMahasiswa.kelas.pstudi',
            'registrasiMahasiswa.dosenWali', 'items.penawaranMataKuliah.masterMataKuliah',
            'items.penawaranMataKuliah.kelas', 'items.penawaranMataKuliah.dosenUtama',
        ]);

        return view('base.cetak.cetak-krs', [
            'krs' => $krs,
            'web' => webSettings::query()->first(),
            'printedAt' => now(),
        ]);
    }

    private function currentKrs(StudentAcademicContext $context, KrsService $service)
    {
        $student = Auth::guard('mahasiswa')->user();
        $period = app(AcademicPeriodContext::class)->published();
        $registration = $context->registrationFor($student, $period);
        if (! $registration) {
            throw ValidationException::withMessages(['krs' => 'Registrasi mahasiswa untuk periode aktif belum tersedia.']);
        }

        return $service->forRegistration($registration);
    }
}
