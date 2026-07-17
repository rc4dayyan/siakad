<?php

namespace App\Http\Controllers\Admin;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AcademicStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicStatusController extends Controller
{
    public function update(
        Request $request,
        string $code,
        AcademicPeriodContext $periodContext,
        AcademicStatusService $statusService
    ): RedirectResponse {
        $period = $periodContext->requireWritableCurrent($request->user());
        $student = Mahasiswa::query()->where('mhs_code', $code)->firstOrFail();
        $registration = RegistrasiMahasiswa::query()
            ->where('mahasiswa_id', $student->id)
            ->where('taka_id', $period->id)
            ->firstOrFail();

        $validated = $request->validate([
            'status_akademik' => ['required', 'string', Rule::in(RegistrasiMahasiswa::academicStatuses())],
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
            'berlaku_mulai' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'status_akademik.required' => 'Pilih status akademik baru.',
            'status_akademik.in' => 'Status akademik yang dipilih tidak valid.',
            'alasan.required' => 'Alasan perubahan status wajib diisi.',
            'alasan.min' => 'Alasan perubahan status minimal 5 karakter.',
            'alasan.max' => 'Alasan perubahan status maksimal 1000 karakter.',
            'berlaku_mulai.required' => 'Tanggal berlaku wajib diisi.',
            'berlaku_mulai.before_or_equal' => 'Tanggal berlaku tidak boleh melewati hari ini.',
        ]);

        $statusService->change(
            $registration,
            $validated['status_akademik'],
            $validated['alasan'],
            CarbonImmutable::parse($validated['berlaku_mulai']),
            $request->user()
        );

        Alert::success('Status akademik diperbarui', 'Perubahan status mahasiswa telah dicatat.');

        return back();
    }
}
