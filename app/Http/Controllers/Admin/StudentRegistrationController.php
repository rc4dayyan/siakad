<?php

namespace App\Http\Controllers\Admin;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Services\Academic\AcademicAuditService;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\StudentRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentRegistrationController extends Controller
{
    public function store(
        Request $request,
        string $code,
        AcademicPeriodContext $periodContext,
        StudentRegistrationService $registrationService,
        AcademicAuditService $audit
    ): RedirectResponse {
        $period = $periodContext->requireWritableCurrent($request->user());
        $student = Mahasiswa::query()->where('mhs_code', $code)->firstOrFail();

        $validated = $request->validate([
            'semester_mahasiswa' => ['required', 'integer', 'between:1,14'],
            'status_akademik' => ['required', 'string', Rule::in(RegistrasiMahasiswa::academicStatuses())],
            'kelas_id' => [
                'required',
                'integer',
                Rule::exists('kelas', 'id')->where(fn ($query) => $query->where('taka_id', $period->id)),
            ],
            'dosen_wali_id' => ['required', 'integer', 'exists:dosens,id'],
            'batas_sks' => ['required', 'integer', 'between:1,24'],
        ], [
            'semester_mahasiswa.required' => 'Semester mahasiswa wajib dipilih.',
            'semester_mahasiswa.between' => 'Semester mahasiswa harus antara 1 sampai 14.',
            'status_akademik.required' => 'Status akademik wajib dipilih.',
            'status_akademik.in' => 'Status akademik yang dipilih tidak valid.',
            'kelas_id.required' => 'Kelas wajib dipilih.',
            'kelas_id.exists' => 'Kelas tidak ditemukan pada periode akademik yang dipilih.',
            'dosen_wali_id.required' => 'Dosen wali wajib dipilih.',
            'dosen_wali_id.exists' => 'Dosen wali yang dipilih tidak ditemukan.',
            'batas_sks.required' => 'Batas SKS wajib diisi.',
            'batas_sks.between' => 'Batas SKS harus antara 1 sampai 24.',
        ]);

        $registration = $registrationService->register(
            $student,
            $period,
            (int) $validated['semester_mahasiswa'],
            $validated['status_akademik'],
            Kelas::findOrFail($validated['kelas_id']),
            Dosen::findOrFail($validated['dosen_wali_id']),
            (int) $validated['batas_sks']
        );
        $audit->record('student.registered', $registration, $period->id, $request->user(), null,
            ['student_id' => $student->id, 'class_id' => $registration->kelas_id, 'status' => $registration->status_akademik]);

        Alert::success('Registrasi berhasil', 'Mahasiswa telah diregistrasikan pada '.$period->name.'.');

        return back();
    }
}
