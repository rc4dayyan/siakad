<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\ProgramKuliah;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicAuditService;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\StudentRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $context): View
    {
        $period = $context->current(auth()->user());
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'pstudi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'proku_id' => [
                'nullable',
                'integer',
                Rule::exists('program_kuliahs', 'id')->where(
                    fn ($query) => $query->where('taka_id', $period?->id ?? 0)
                ),
            ],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
        ]);

        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->with(['taka', 'pstudi', 'proku', 'dosen'])
            ->withCount(['registrasiMahasiswas as mahasiswas_count' => fn ($query) => $query->where('taka_id', $period?->id)])
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhereHas('proku', fn ($query) => $query->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['pstudi_id'] ?? null, fn ($query, $studyProgramId) => $query->where('pstudi_id', $studyProgramId))
            ->when($filters['proku_id'] ?? null, fn ($query, $programId) => $query->where('proku_id', $programId))
            ->when($filters['dosen_id'] ?? null, fn ($query, $lecturerId) => $query->where('dosen_id', $lecturerId))
            ->orderBy('name')
            ->get();

        return view('user.admin.master.admin-kelas-index', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'selectedPeriod' => $period,
            'canManageClasses' => $period?->isWritable() ?? false,
            'kelas' => $kelas,
            'filters' => $filters,
            'pstudi' => ProgramStudi::query()->orderBy('name')->get(),
            'proku' => ProgramKuliah::query()
                ->when($period, fn ($query) => $query->where('taka_id', $period->id))
                ->when(! $period, fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('name')
                ->get(),
            'dosen' => Dosen::query()->orderBy('dsn_name')->get(),
        ]);
    }

    public function viewMahasiswa(Request $request, string $code, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', Rule::in(['L', 'P'])],
            'sort' => ['nullable', 'string', Rule::in(['name_asc', 'name_desc', 'nim_asc', 'nim_desc'])],
        ]);
        $classStudentsQuery = Mahasiswa::query()->forAcademicClass($period, $kelas->id);
        $classStudentCount = (clone $classStudentsQuery)->count();
        $classStudents = $classStudentsQuery
            ->when($filters['q'] ?? null, function ($query, string $keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query->where('mhs_nim', 'like', "%{$keyword}%")
                        ->orWhere('mhs_name', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['gender'] ?? null, fn ($query, string $gender) => $query->where('mhs_gend', $gender))
            ->when(
                in_array($filters['sort'] ?? 'name_asc', ['nim_asc', 'nim_desc'], true),
                fn ($query) => $query->orderBy('mhs_nim', ($filters['sort'] ?? null) === 'nim_desc' ? 'desc' : 'asc'),
                fn ($query) => $query->orderBy('mhs_name', ($filters['sort'] ?? null) === 'name_desc' ? 'desc' : 'asc')
            )
            ->get();

        $availableStudents = Mahasiswa::query()
            ->whereDoesntHave('registrasiAkademik', fn ($query) => $query
                ->where('taka_id', $period->id)
                ->where('kelas_id', $kelas->id))
            ->where(function ($query) use ($period, $kelas): void {
                $query->whereHas('registrasiAkademik', fn ($registration) => $registration
                    ->where('taka_id', $period->id)
                    ->whereHas('kelas', fn ($class) => $class->where('pstudi_id', $kelas->pstudi_id)))
                    ->orWhere(function ($legacy) use ($period, $kelas): void {
                        $legacy->whereDoesntHave('registrasiAkademik', fn ($registration) => $registration
                            ->where('taka_id', $period->id))
                            ->whereHas('kelas', fn ($class) => $class->where('pstudi_id', $kelas->pstudi_id));
                    });
            })
            ->with([
                'kelas',
                'registrasiAkademik' => fn ($query) => $query
                    ->with(['kelas', 'taka'])
                    ->orderByDesc('id'),
            ])
            ->orderBy('mhs_name')
            ->get();

        return view('user.admin.master.admin-kelas-view-mahasiswa', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'kelas' => $kelas,
            'mahasiswa' => $classStudents,
            'classStudentCount' => $classStudentCount,
            'filters' => $filters,
            'availableStudents' => $availableStudents,
            'academicAdvisors' => Dosen::query()->where('dsn_stat', 1)->orderBy('dsn_name')->get(),
            'period' => $period,
            'canAssignStudents' => $period->isWritable(),
        ]);
    }

    public function assignMahasiswa(
        Request $request,
        string $code,
        AcademicPeriodContext $context,
        StudentRegistrationService $registrationService,
        AcademicAuditService $audit
    ): RedirectResponse {
        $period = $context->requireWritableCurrent($request->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();
        $studentData = $request->validate([
            'mahasiswa_id' => ['required', 'integer', 'exists:mahasiswas,id'],
        ], [
            'mahasiswa_id.required' => 'Mahasiswa wajib dipilih.',
            'mahasiswa_id.exists' => 'Mahasiswa yang dipilih tidak ditemukan.',
        ]);
        $student = Mahasiswa::query()->findOrFail($studentData['mahasiswa_id']);
        $existingRegistration = RegistrasiMahasiswa::query()
            ->where('mahasiswa_id', $student->id)
            ->where('taka_id', $period->id)
            ->with('dosenWali')
            ->first();

        $validated = $existingRegistration ? [] : $request->validate([
            'semester_mahasiswa' => ['required', 'integer', 'between:1,14'],
            'status_akademik' => ['required', 'string', Rule::in(RegistrasiMahasiswa::academicStatuses())],
            'dosen_wali_id' => ['required', 'integer', Rule::exists('dosens', 'id')->where('dsn_stat', 1)],
            'batas_sks' => ['required', 'integer', 'between:1,24'],
        ], [
            'semester_mahasiswa.between' => 'Semester mahasiswa harus antara 1 sampai 14.',
            'status_akademik.in' => 'Status akademik yang dipilih tidak valid.',
            'dosen_wali_id.required' => 'Dosen wali wajib dipilih.',
            'dosen_wali_id.exists' => 'Dosen wali aktif yang dipilih tidak ditemukan.',
            'batas_sks.between' => 'Batas SKS harus antara 1 sampai 24.',
        ]);

        $result = $registrationService->assignToClass(
            $student,
            $period,
            $kelas,
            $existingRegistration?->dosenWali ?? Dosen::query()->find($validated['dosen_wali_id'] ?? null),
            (int) ($existingRegistration?->semester_mahasiswa ?? $validated['semester_mahasiswa']),
            $existingRegistration?->status_akademik ?? $validated['status_akademik'],
            (int) ($existingRegistration?->batas_sks ?? $validated['batas_sks'])
        );

        $audit->record(
            $result['moved'] ? 'student.class_moved' : 'student.registered',
            $result['registration'],
            $period->id,
            $request->user(),
            ['class_id' => $result['previous_class_id']],
            ['student_id' => $student->id, 'class_id' => $kelas->id]
        );

        Alert::success(
            $result['moved'] ? 'Mahasiswa dipindahkan' : 'Mahasiswa ditambahkan',
            $student->mhs_name.' berhasil ditempatkan pada kelas '.$kelas->name.'.'
        );

        return back();
    }

    public function cetakMahasiswa(string $code, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();

        return view('base.cetak.cetak-data-kehadiran', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'kelas' => $kelas,
            'mahasiswa' => Mahasiswa::query()->forAcademicClass($period, $kelas->id)->get(),
        ]);
    }

    public function store(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $validated = $this->validateKelas($request, $period->id);

        Kelas::create([
            ...$validated,
            'taka_id' => $period->id,
        ]);

        Alert::success('Berhasil', 'Data kelas berhasil disimpan.');

        return back();
    }

    public function update(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();
        $validated = $this->validateKelas($request, $period->id, $kelas);

        $kelas->update($validated);

        Alert::success('Berhasil', 'Data kelas berhasil diperbarui.');

        return back();
    }

    public function destroy(string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent(auth()->user());
        $kelas = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();

        $kelas->delete();

        Alert::success('Berhasil', 'Data kelas berhasil dihapus.');

        return back();
    }

    private function validateKelas(Request $request, int $periodId, ?Kelas $kelas = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', Rule::unique('kelas', 'code')->ignore($kelas?->id)],
            'capacity' => ['required', 'integer', 'between:1,100'],
            'pstudi_id' => ['required', 'integer', 'exists:program_studis,id'],
            'proku_id' => [
                'nullable',
                'integer',
                Rule::exists('program_kuliahs', 'id')->where(fn ($query) => $query
                    ->where('taka_id', $periodId)
                    ->where('pstudi_id', $request->integer('pstudi_id'))),
            ],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
        ], [
            'capacity.between' => 'Kapasitas kelas harus antara 1 sampai 100 mahasiswa.',
            'proku_id.exists' => 'Program kuliah tidak tersedia pada periode dan program studi yang dipilih.',
        ]);
    }
}
