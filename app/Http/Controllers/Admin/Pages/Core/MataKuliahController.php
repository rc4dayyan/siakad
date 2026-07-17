<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\MataKuliah;
use App\Models\NilaiMahasiswa;
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RealRashid\SweetAlert\Facades\Alert as FacadesAlert;

class MataKuliahController extends Controller
{
    use roleTrait;

    public function index(AcademicPeriodContext $context): View
    {
        return view('user.admin.master.admin-matkul-index', $this->formData($context));
    }

    public function create(AcademicPeriodContext $context): View
    {
        return view('user.admin.master.admin-matkul-create', $this->formData($context));
    }

    public function store(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $validated = $this->validateMataKuliah($request, $period->id);
        $master = MasterMataKuliah::findOrFail($validated['mid']);

        MataKuliah::create([
            ...$validated,
            'name' => $master->name,
            'taka_id' => $period->id,
        ]);

        Alert::success('Berhasil', 'Mata kuliah berhasil ditambahkan.');

        return back();
    }

    public function update(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $mataKuliah = MataKuliah::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();
        $validated = $this->validateMataKuliah($request, $period->id, $mataKuliah);
        $master = MasterMataKuliah::findOrFail($validated['mid']);

        $mataKuliah->update([
            ...$validated,
            'name' => $master->name,
        ]);

        Alert::success('Berhasil', 'Mata kuliah berhasil diperbarui.');

        return back();
    }

    public function destroy(string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent(auth()->user());
        $mataKuliah = MataKuliah::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();

        $mataKuliah->delete();

        Alert::success('Berhasil', 'Mata kuliah berhasil dihapus.');

        return back();
    }

    public function nilai(int $mataKuliahId, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        $mataKuliah = MataKuliah::query()
            ->forAcademicPeriod($period)
            ->with('kelas')
            ->findOrFail($mataKuliahId);
        $kelas = $mataKuliah->kelas_id
            ? Kelas::query()->forAcademicPeriod($period)->find($mataKuliah->kelas_id)
            : null;

        return view('user.admin.master.admin-matkul-nilai', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'mataKuliah' => $mataKuliah,
            'period' => $period,
            'canManageNilai' => $period->isWritable() && $kelas !== null,
            'mahasiswas' => $kelas ? $this->participantQuery($mataKuliah, $period->id, $kelas->id)->get() : collect(),
            'existingNilais' => NilaiMahasiswa::query()
                ->forAcademicPeriod($period)
                ->where('mata_kuliah_id', $mataKuliahId)
                ->when($kelas, fn ($query) => $query->where('kelas_id', $kelas->id))
                ->get()
                ->keyBy('mahasiswa_id'),
        ]);
    }

    public function storenilai(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $mataKuliah = MataKuliah::query()
            ->forAcademicPeriod($period)
            ->findOrFail($request->integer('mata_kuliah_id'));
        $kelas = $mataKuliah->kelas_id
            ? Kelas::query()->forAcademicPeriod($period)->find($mataKuliah->kelas_id)
            : null;

        if (! $kelas) {
            throw ValidationException::withMessages([
                'kelas_id' => 'Mata kuliah harus terhubung dengan kelas pada periode yang sedang dipilih.',
            ]);
        }

        $allowedStudentIds = $this->participantQuery($mataKuliah, $period->id, $kelas->id)
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'nilai' => ['required', 'array'],
            'nilai.*.mahasiswa_id' => [
                'required',
                'integer',
                Rule::in($allowedStudentIds),
            ],
            'nilai.*.nilai' => ['nullable', 'in:A,B,C,D,E'],
        ], [
            'nilai.*.mahasiswa_id.exists' => 'Mahasiswa harus terdaftar pada kelas mata kuliah di periode ini.',
        ]);

        DB::transaction(function () use ($validated, $period, $mataKuliah, $kelas): void {
            foreach ($validated['nilai'] as $data) {
                NilaiMahasiswa::updateOrCreate(
                    [
                        'mahasiswa_id' => $data['mahasiswa_id'],
                        'mata_kuliah_id' => $mataKuliah->id,
                        'kelas_id' => $kelas->id,
                    ],
                    [
                        'taka_id' => $period->id,
                        ...(Schema::hasColumn('nilai_mahasiswas', 'penawaran_mata_kuliah_id')
                            ? ['penawaran_mata_kuliah_id' => $mataKuliah->penawaran?->id]
                            : []),
                        'dosen_id' => $mataKuliah->dosen_1,
                        'nilai' => $data['nilai'] ?? null,
                    ]
                );
            }
        });

        FacadesAlert::success('Berhasil', 'Nilai berhasil disimpan untuk mata kuliah '.$mataKuliah->name.'.');

        return redirect()->route($this->setPrefix().'master.matkul-index');
    }

    private function formData(AcademicPeriodContext $context): array
    {
        $period = $context->current(auth()->user());

        return [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'selectedPeriod' => $period,
            'canManageMataKuliah' => $period?->isWritable() ?? false,
            'kuri' => Kurikulum::query()->orderBy('name')->get(),
            'pstudi' => ProgramStudi::query()->orderBy('name')->get(),
            'dosen' => Dosen::query()->orderBy('dsn_name')->get(),
            'matkul' => MataKuliah::query()
                ->forAcademicPeriod($period)
                ->with(['masterMataKuliah', 'kuri', 'taka', 'pstudi', 'requ', 'dosen1', 'dosen2', 'dosen3', 'kelas'])
                ->orderBy('name')
                ->get(),
            'masterMatkul' => MasterMataKuliah::query()
                ->orderBy('program_studi')
                ->orderBy('semester')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function validateMataKuliah(Request $request, int $periodId, ?MataKuliah $mataKuliah = null): array
    {
        $programStudiCode = ProgramStudi::query()
            ->whereKey($request->integer('pstudi_id'))
            ->value('code');

        return $request->validate([
            'mid' => [
                'required',
                'integer',
                Rule::exists('master_mata_kuliahs', 'id')->where(fn ($query) => $query->where('program_studi', $programStudiCode)),
            ],
            'code' => ['required', 'string', 'max:255', Rule::unique('mata_kuliahs', 'code')->ignore($mataKuliah?->id)],
            'bsks' => ['required', 'integer', 'min:1', 'max:40'],
            'desc' => ['required', 'string'],
            'pstudi_id' => ['required', 'integer', 'exists:program_studis,id'],
            'kuri_id' => ['required', 'integer', 'exists:kurikulums,id'],
            'dosen_1' => ['required', 'integer', 'exists:dosens,id'],
            'dosen_2' => ['nullable', 'integer', 'exists:dosens,id'],
            'dosen_3' => ['nullable', 'integer', 'exists:dosens,id'],
            'requ_id' => [
                'nullable',
                'integer',
                Rule::exists('mata_kuliahs', 'id')->where(fn ($query) => $query
                    ->where('taka_id', $periodId)
                    ->where('pstudi_id', $request->integer('pstudi_id'))),
                Rule::notIn(array_filter([$mataKuliah?->id])),
            ],
        ], [
            'mid.exists' => 'Mata kuliah master harus sesuai dengan program studi yang dipilih.',
            'requ_id.exists' => 'Mata kuliah prasyarat harus berasal dari periode dan program studi yang sedang dipilih.',
            'requ_id.not_in' => 'Mata kuliah tidak dapat menjadi prasyarat bagi dirinya sendiri.',
        ]);
    }

    private function participantQuery(MataKuliah $mataKuliah, int $periodId, int $classId)
    {
        if (Schema::hasTable('penawaran_mata_kuliahs')) {
            $offering = $mataKuliah->penawaran()->first();
            if ($offering) {
                return Mahasiswa::query()->forApprovedOffering($offering);
            }
        }

        return Mahasiswa::query()->forAcademicClass($periodId, $classId);
    }
}
