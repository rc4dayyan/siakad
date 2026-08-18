<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Kurikulum;
use App\Models\MasterMataKuliah;
use App\Models\MataKuliah;
use App\Models\PenawaranMataKuliah;
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MataKuliahController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $context): View
    {
        $period = $context->current($request->user());
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'pstudi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'kelas_id' => [
                'nullable',
                'integer',
                Rule::exists('kelas', 'id')->where(
                    fn ($query) => $query->where('taka_id', $period?->id ?? 0)
                ),
            ],
        ]);
        $offerings = PenawaranMataKuliah::query()
            ->forAcademicPeriod($period)
            ->with(['masterMataKuliah', 'pstudi', 'kelas', 'dosenUtama'])
            ->withCount([
                'krsItems as peserta_count' => fn ($query) => $query->whereHas(
                    'krs',
                    fn ($krs) => $krs->whereIn('status', [Krs::STATUS_APPROVED, Krs::STATUS_LOCKED])
                ),
                'nilais as nilai_terisi_count' => fn ($query) => $query->whereNotNull('nilai'),
            ])
            ->when($filters['q'] ?? null, function ($query, string $keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('masterMataKuliah', fn ($master) => $master
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%"))
                        ->orWhereHas('kelas', fn ($class) => $class->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['pstudi_id'] ?? null, fn ($query, $programId) => $query->where('pstudi_id', $programId))
            ->when($filters['kelas_id'] ?? null, fn ($query, $classId) => $query->where('kelas_id', $classId))
            ->orderBy('code')
            ->get();

        return view('user.admin.master.admin-matkul-index', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'selectedPeriod' => $period,
            'offerings' => $offerings,
            'programs' => ProgramStudi::query()->orderBy('name')->get(),
            'classes' => Kelas::query()
                ->forAcademicPeriod($period)
                ->when($filters['pstudi_id'] ?? null, fn ($query, $programId) => $query->where('pstudi_id', $programId))
                ->orderBy('name')
                ->get(),
            'filters' => $filters,
        ]);
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
}
