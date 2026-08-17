<?php

namespace App\Http\Controllers\Dosen\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Services\Academic\KrsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KrsApprovalController extends Controller
{
    public function index(): View
    {
        $advisor = Auth::guard('dosen')->user();

        return view('dosen.pages.krs-approval-index', [
            'submissions' => Krs::query()
                ->whereHas('registrasiMahasiswa', fn ($query) => $query->where('dosen_wali_id', $advisor->id))
                ->with([
                    'registrasiMahasiswa.mahasiswa',
                    'registrasiMahasiswa.taka',
                    'registrasiMahasiswa.kelas',
                    'items.penawaranMataKuliah.masterMataKuliah',
                    'items.penawaranMataKuliah.kelas',
                    'items.penawaranMataKuliah.dosenUtama',
                ])
                ->latest('diajukan_at')->get(),
        ]);
    }

    public function decide(Request $request, Krs $krs, KrsService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->decide($krs, Auth::guard('dosen')->user(), $data['status'], $data['catatan'] ?? null);

        return back()->with('success', $data['status'] === Krs::STATUS_APPROVED ? 'KRS berhasil disetujui.' : 'KRS dikembalikan untuk diperbaiki.');
    }
}
