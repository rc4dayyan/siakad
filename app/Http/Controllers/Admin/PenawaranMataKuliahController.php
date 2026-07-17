<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\KalenderAkademik;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\MasterMataKuliah;
use App\Models\PenawaranMataKuliah;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenawaranMataKuliahController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $context): View
    {
        $period = $context->current(auth()->user());

        return view('user.admin.master.penawaran-matkul-index', [
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'periods' => TahunAkademik::query()->latest('year_start')->get(),
            'offerings' => PenawaranMataKuliah::query()->forAcademicPeriod($period)
                ->when($request->integer('pstudi_id'), fn ($query, $programId) => $query->where('pstudi_id', $programId))
                ->when($request->integer('kuri_id'), fn ($query, $curriculumId) => $query->where('kuri_id', $curriculumId))
                ->with(['masterMataKuliah', 'kelas', 'pstudi', 'kurikulum', 'dosenUtama'])->orderBy('code')->get(),
            'masters' => MasterMataKuliah::query()->orderBy('name')->get(),
            'programs' => ProgramStudi::query()->orderBy('name')->get(),
            'curricula' => Kurikulum::query()->orderBy('name')->get(),
            'classes' => Kelas::query()->forAcademicPeriod($period)->orderBy('name')->get(),
            'lecturers' => Dosen::query()->orderBy('dsn_name')->get(),
            'canManage' => $period?->isWritable() ?? false,
            'filters' => $request->only(['pstudi_id', 'kuri_id']),
            'krsWindow' => $period ? KalenderAkademik::query()->where('taka_id', $period->id)
                ->where('kategori', KalenderAkademik::KATEGORI_KRS)->first() : null,
        ]);
    }

    public function updateKrsWindow(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $data = $request->validate([
            'mulai_at' => ['required', 'date'],
            'selesai_at' => ['required', 'date', 'after:mulai_at'],
            'dipublikasikan' => ['nullable', 'boolean'],
        ], ['selesai_at.after' => 'Waktu selesai KRS harus setelah waktu mulai.']);

        KalenderAkademik::updateOrCreate(
            ['taka_id' => $period->id, 'kategori' => KalenderAkademik::KATEGORI_KRS],
            ['nama' => 'Pengisian KRS', ...$data, 'dipublikasikan' => $request->boolean('dipublikasikan')]
        );

        return back()->with('success', 'Jadwal pengisian KRS berhasil diperbarui.');
    }

    public function store(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $data = $this->validateOffering($request, $period->id, multipleClasses: true);
        $master = MasterMataKuliah::findOrFail($data['master_mata_kuliah_id']);

        DB::transaction(function () use ($data, $master, $period): void {
            foreach ($data['kelas_ids'] as $classId) {
                PenawaranMataKuliah::create([
                    ...collect($data)->except('kelas_ids')->all(),
                    'taka_id' => $period->id,
                    'kelas_id' => $classId,
                    'sks' => $master->sks,
                    'code' => $this->newCode($period->id, $master->id, $classId),
                ]);
            }
        });

        return back()->with('success', 'Penawaran berhasil dibuat untuk '.count($data['kelas_ids']).' kelas.');
    }

    public function update(Request $request, PenawaranMataKuliah $penawaran, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        abort_unless($penawaran->taka_id === $period->id, 404);

        if ($penawaran->hasParticipants()) {
            $data = $request->validate([
                'dosen_utama_id' => ['required', 'exists:dosens,id'],
                'dosen_pendamping_1_id' => ['nullable', 'exists:dosens,id'],
                'dosen_pendamping_2_id' => ['nullable', 'exists:dosens,id'],
                'kapasitas' => ['required', 'integer', 'min:'.$penawaran->krsItems()->count(), 'max:1000'],
                'deskripsi' => ['nullable', 'string'],
            ], ['kapasitas.min' => 'Kapasitas tidak boleh lebih kecil dari jumlah peserta KRS.']);
        } else {
            $data = $this->validateOffering($request, $period->id, $penawaran);
            $data['sks'] = MasterMataKuliah::findOrFail($data['master_mata_kuliah_id'])->sks;
        }

        $penawaran->update($data);

        return back()->with('success', 'Penawaran mata kuliah berhasil diperbarui.');
    }

    public function destroy(PenawaranMataKuliah $penawaran, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent(auth()->user());
        abort_unless($penawaran->taka_id === $period->id, 404);
        if ($penawaran->hasParticipants()) {
            throw ValidationException::withMessages(['penawaran' => 'Penawaran yang sudah memiliki peserta KRS tidak dapat dihapus.']);
        }
        $penawaran->delete();

        return back()->with('success', 'Penawaran mata kuliah berhasil dihapus.');
    }

    public function copyPreview(Request $request): View
    {
        $data = $request->validate([
            'source_period_id' => ['required', 'different:target_period_id', 'exists:tahun_akademiks,id'],
            'target_period_id' => ['required', 'exists:tahun_akademiks,id'],
        ]);
        $target = TahunAkademik::findOrFail($data['target_period_id']);

        return view('user.admin.master.penawaran-matkul-copy', [
            'prefix' => $this->setPrefix(),
            'source' => TahunAkademik::findOrFail($data['source_period_id']),
            'target' => $target,
            'offerings' => PenawaranMataKuliah::query()->forAcademicPeriod($data['source_period_id'])
                ->with(['masterMataKuliah', 'kelas', 'dosenUtama'])->get(),
            'targetClasses' => Kelas::query()->forAcademicPeriod($target)->orderBy('name')->get(),
            'lecturers' => Dosen::query()->orderBy('dsn_name')->get(),
        ]);
    }

    public function copyExecute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source_period_id' => ['required', 'different:target_period_id', 'exists:tahun_akademiks,id'],
            'target_period_id' => ['required', 'exists:tahun_akademiks,id'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:penawaran_mata_kuliahs,id'],
            'class_map' => ['required', 'array'],
            'class_map.*' => ['required', 'integer', 'exists:kelas,id'],
            'lecturer_map' => ['nullable', 'array'],
            'lecturer_map.*' => ['nullable', 'integer', 'exists:dosens,id'],
        ]);
        $target = TahunAkademik::findOrFail($data['target_period_id']);
        abort_unless($target->isWritable(), 422, 'Periode tujuan tidak dapat diubah.');
        $result = ['copied' => 0, 'skipped' => 0, 'failed' => 0];

        DB::transaction(function () use ($data, $target, &$result): void {
            foreach (PenawaranMataKuliah::query()->whereIn('id', $data['selected'])->where('taka_id', $data['source_period_id'])->get() as $source) {
                $classId = (int) ($data['class_map'][$source->id] ?? 0);
                $class = Kelas::query()->forAcademicPeriod($target)->whereKey($classId)->first();
                $lecturerId = (int) ($data['lecturer_map'][$source->id] ?? $source->dosen_utama_id);
                if (! $class || ! Dosen::whereKey($lecturerId)->exists()) {
                    $result['failed']++;

                    continue;
                }
                $exists = PenawaranMataKuliah::query()->where([
                    'master_mata_kuliah_id' => $source->master_mata_kuliah_id,
                    'taka_id' => $target->id,
                    'pstudi_id' => $class->pstudi_id,
                    'kuri_id' => $source->kuri_id,
                    'kelas_id' => $class->id,
                ])->exists();
                if ($exists) {
                    $result['skipped']++;

                    continue;
                }
                PenawaranMataKuliah::create([
                    ...$source->only(['master_mata_kuliah_id', 'kuri_id', 'dosen_pendamping_1_id', 'dosen_pendamping_2_id', 'prasyarat_master_id', 'sks', 'kapasitas', 'deskripsi']),
                    'taka_id' => $target->id,
                    'pstudi_id' => $class->pstudi_id,
                    'kelas_id' => $class->id,
                    'dosen_utama_id' => $lecturerId,
                    'code' => $this->newCode($target->id, $source->master_mata_kuliah_id, $class->id),
                ]);
                $result['copied']++;
            }
        });

        return redirect()->route($this->setPrefix().'master.penawaran-index')
            ->with('success', "Salin selesai: {$result['copied']} berhasil, {$result['skipped']} duplikat dilewati, {$result['failed']} gagal referensi.");
    }

    public function participants(PenawaranMataKuliah $penawaran): View
    {
        return view('user.admin.master.penawaran-matkul-participants', [
            'prefix' => $this->setPrefix(),
            'penawaran' => $penawaran->load(['masterMataKuliah', 'taka', 'kelas', 'dosenUtama']),
            'students' => $penawaran->pesertaDisetujui()->orderBy('mhs_name')->get(),
        ]);
    }

    private function validateOffering(Request $request, int $periodId, PenawaranMataKuliah|bool|null $offering = null, bool $multipleClasses = false): array
    {
        if (is_bool($offering)) {
            $multipleClasses = $offering;
            $offering = null;
        }
        $classRule = Rule::exists('kelas', 'id')->where(fn ($query) => $query->where('taka_id', $periodId)->where('pstudi_id', $request->integer('pstudi_id')));
        $rules = [
            'master_mata_kuliah_id' => [
                'required',
                Rule::exists('master_mata_kuliahs', 'id')->where(fn ($query) => $query->where(
                    'program_studi',
                    ProgramStudi::query()->whereKey($request->integer('pstudi_id'))->value('code')
                )),
            ],
            'pstudi_id' => ['required', 'exists:program_studis,id'],
            'kuri_id' => ['required', 'exists:kurikulums,id'],
            'dosen_utama_id' => ['required', 'exists:dosens,id'],
            'dosen_pendamping_1_id' => ['nullable', 'exists:dosens,id'],
            'dosen_pendamping_2_id' => ['nullable', 'exists:dosens,id'],
            'prasyarat_master_id' => ['nullable', 'different:master_mata_kuliah_id', 'exists:master_mata_kuliahs,id'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:1000'],
            'deskripsi' => ['nullable', 'string'],
        ];
        $rules[$multipleClasses ? 'kelas_ids' : 'kelas_id'] = $multipleClasses
            ? ['required', 'array', 'min:1'] : ['required', $classRule];
        if ($multipleClasses) {
            $rules['kelas_ids.*'] = ['required', 'distinct', $classRule];
        }

        return $request->validate($rules);
    }

    private function newCode(int $periodId, int $masterId, int $classId): string
    {
        return 'OF-'.$periodId.'-'.$masterId.'-'.$classId.'-'.Str::upper(Str::random(5));
    }
}
