<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\KalenderAkademik;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Rap2hpoutre\FastExcel\FastExcel;

class PenawaranMataKuliahController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $context): View
    {
        $period = $context->current(auth()->user());
        $filters = $this->offeringFilters($request);
        $selectedProgramId = $filters['pstudi_id'];
        $programs = ProgramStudi::query()->orderBy('name')->get();
        $selectedProgram = $programs->firstWhere('id', $selectedProgramId);
        $masterProgramNames = [];

        foreach ($programs as $program) {
            foreach ($program->masterMataKuliahCodes() as $code) {
                $masterProgramNames[$code] = $program->name;
            }
        }

        return view('user.admin.master.penawaran-matkul-index', [
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'periods' => TahunAkademik::query()->latest('year_start')->get(),
            'offerings' => $this->applyOfferingFilters(
                PenawaranMataKuliah::query()->forAcademicPeriod($period),
                $filters
            )
                ->with(['masterMataKuliah', 'kelas', 'pstudi', 'kurikulum', 'dosenUtama'])
                ->withCount('krsItems')
                ->orderBy('code')
                ->get(),
            'masters' => MasterMataKuliah::query()
                ->when($selectedProgram, fn ($query, $program) => $query->whereIn(
                    'program_studi',
                    $program->masterMataKuliahCodes()
                ))
                ->orderBy('semester')
                ->orderBy('name')
                ->get(),
            'masterProgramNames' => $masterProgramNames,
            'programs' => $programs,
            'curricula' => Kurikulum::query()->orderBy('name')->get(),
            'classes' => Kelas::query()
                ->forAcademicPeriod($period)
                ->when($selectedProgramId, fn ($query, $programId) => $query->where('pstudi_id', $programId))
                ->orderBy('name')
                ->get(),
            'lecturers' => Dosen::query()->orderBy('dsn_name')->get(),
            'canManage' => $period?->isWritable() ?? false,
            'filters' => $filters,
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

        try {
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
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'kelas_ids' => 'Penawaran mata kuliah tersebut sudah tersedia pada salah satu kelas yang dipilih.',
            ]);
        }

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

        try {
            $penawaran->update($data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'kelas_id' => 'Penawaran mata kuliah tersebut sudah tersedia pada kelas yang dipilih.',
            ]);
        }

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

    public function export(Request $request, AcademicPeriodContext $context)
    {
        $period = $context->requireCurrent($request->user());
        $offerings = $this->applyOfferingFilters(
            PenawaranMataKuliah::query()->forAcademicPeriod($period),
            $this->offeringFilters($request)
        )
            ->with([
                'masterMataKuliah',
                'pstudi',
                'kurikulum',
                'kelas',
                'dosenUtama',
                'dosenPendamping1',
                'dosenPendamping2',
                'prasyaratMaster',
            ])
            ->orderBy('code')
            ->get();

        return (new FastExcel($offerings))->download(
            'penawaran-mata-kuliah-'.$period->code.'-'.now()->format('Ymd-His').'.xlsx',
            fn (PenawaranMataKuliah $offering) => [
                'Kode Penawaran' => $offering->code,
                'Kode Periode' => $period->code,
                'Kode Program Studi' => $offering->pstudi?->code,
                'Kode Kurikulum' => $offering->kurikulum?->code,
                'Kode Kelas' => $offering->kelas?->code,
                'Semester Mata Kuliah' => $offering->masterMataKuliah?->semester,
                'Nama Mata Kuliah' => $offering->masterMataKuliah?->name,
                'NIDN Dosen Utama' => $offering->dosenUtama?->dsn_nidn,
                'NIDN Dosen Pendamping 1' => $offering->dosenPendamping1?->dsn_nidn,
                'NIDN Dosen Pendamping 2' => $offering->dosenPendamping2?->dsn_nidn,
                'Semester Prasyarat' => $offering->prasyaratMaster?->semester,
                'Nama Mata Kuliah Prasyarat' => $offering->prasyaratMaster?->name,
                'Kapasitas' => $offering->kapasitas,
                'Deskripsi' => $offering->deskripsi,
            ]
        );
    }

    public function import(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $request->validate([
            '_form' => ['required', 'in:import-offerings'],
            'import' => [
                'required',
                'file',
                'extensions:xlsx,csv',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-zip-compressed,text/csv,text/plain,application/csv',
                'max:5120',
            ],
        ], [
            'import.required' => 'File penawaran mata kuliah wajib diunggah.',
            'import.extensions' => 'File harus berformat XLSX atau CSV.',
            'import.mimetypes' => 'Isi file harus berupa XLSX atau CSV yang valid.',
            'import.max' => 'Ukuran file tidak boleh melebihi 5 MB.',
        ]);

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Gunakan hasil ekspor penawaran berformat XLSX atau CSV.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $headers = [
            'Kode Penawaran',
            'Kode Periode',
            'Kode Program Studi',
            'Kode Kurikulum',
            'Kode Kelas',
            'Semester Mata Kuliah',
            'Nama Mata Kuliah',
            'NIDN Dosen Utama',
            'NIDN Dosen Pendamping 1',
            'NIDN Dosen Pendamping 2',
            'Semester Prasyarat',
            'Nama Mata Kuliah Prasyarat',
            'Kapasitas',
            'Deskripsi',
        ];

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['import' => 'File import tidak berisi data penawaran.']);
        }

        $missingHeaders = array_diff($headers, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $prepared = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $prepared[] = $this->prepareImportedOffering($row, $rowNumber, $period);
        }

        $result = DB::transaction(function () use ($prepared, $period): array {
            $created = 0;
            $skipped = 0;
            $seen = [];

            foreach ($prepared as $attributes) {
                $combination = implode(':', [
                    $attributes['master_mata_kuliah_id'],
                    $attributes['pstudi_id'],
                    $attributes['kuri_id'],
                    $attributes['kelas_id'],
                ]);

                $exists = isset($seen[$combination]) || PenawaranMataKuliah::query()
                    ->where('taka_id', $period->id)
                    ->where('master_mata_kuliah_id', $attributes['master_mata_kuliah_id'])
                    ->where('pstudi_id', $attributes['pstudi_id'])
                    ->where('kuri_id', $attributes['kuri_id'])
                    ->where('kelas_id', $attributes['kelas_id'])
                    ->exists();

                if ($exists || ($attributes['code'] && PenawaranMataKuliah::where('code', $attributes['code'])->exists())) {
                    $skipped++;

                    continue;
                }

                $seen[$combination] = true;
                $attributes['code'] ??= $this->newCode(
                    $period->id,
                    $attributes['master_mata_kuliah_id'],
                    $attributes['kelas_id']
                );
                PenawaranMataKuliah::create(['taka_id' => $period->id, ...$attributes]);
                $created++;
            }

            return compact('created', 'skipped');
        });

        return back()->with(
            'success',
            "Import selesai: {$result['created']} penawaran dibuat dan {$result['skipped']} duplikat dilewati."
        );
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

    public function participants(PenawaranMataKuliah $penawaran, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        abort_unless($penawaran->taka_id === $period->id, 404);

        return view('user.admin.master.penawaran-matkul-participants', [
            'prefix' => $this->setPrefix(),
            'penawaran' => $penawaran->load(['masterMataKuliah', 'taka', 'kelas', 'dosenUtama']),
            'students' => $penawaran->pesertaDisetujui()->orderBy('mhs_name')->get(),
        ]);
    }

    public function grades(PenawaranMataKuliah $penawaran, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        abort_unless($penawaran->taka_id === $period->id, 404);
        $penawaran->load(['masterMataKuliah', 'taka', 'kelas', 'dosenUtama']);

        return view('user.admin.master.admin-matkul-nilai', [
            'prefix' => $this->setPrefix(),
            'penawaran' => $penawaran,
            'period' => $period,
            'canManageNilai' => $period->isWritable(),
            'mahasiswas' => $this->approvedParticipants($penawaran)->orderBy('mhs_name')->get(),
            'existingNilais' => NilaiMahasiswa::query()
                ->forAcademicPeriod($period)
                ->where('penawaran_mata_kuliah_id', $penawaran->id)
                ->get()
                ->keyBy('mahasiswa_id'),
        ]);
    }

    public function storeGrades(
        Request $request,
        PenawaranMataKuliah $penawaran,
        AcademicPeriodContext $context
    ): RedirectResponse {
        $period = $context->requireWritableCurrent($request->user());
        abort_unless($penawaran->taka_id === $period->id, 404);
        $allowedStudentIds = $this->approvedParticipants($penawaran)->pluck('mahasiswas.id')->all();

        $validated = $request->validate([
            'nilai' => ['required', 'array'],
            'nilai.*.mahasiswa_id' => ['required', 'integer', Rule::in($allowedStudentIds)],
            'nilai.*.nilai' => ['nullable', 'in:A,B,C,D,E'],
        ], [
            'nilai.*.mahasiswa_id.in' => 'Mahasiswa harus tercatat pada KRS yang telah disetujui untuk penawaran ini.',
        ]);

        DB::transaction(fn () => $this->persistOfferingGrades($validated['nilai'], $period->id, $penawaran));

        return redirect()->route($this->setPrefix().'master.penawaran-grades', $penawaran)
            ->with('success', 'Nilai mahasiswa berhasil disimpan.');
    }

    public function exportGrades(
        PenawaranMataKuliah $penawaran,
        AcademicPeriodContext $context
    ) {
        $period = $context->requireCurrent(auth()->user());
        abort_unless($penawaran->taka_id === $period->id, 404);
        $penawaran->load(['masterMataKuliah', 'kelas']);
        $grades = NilaiMahasiswa::query()
            ->forAcademicPeriod($period)
            ->where('penawaran_mata_kuliah_id', $penawaran->id)
            ->pluck('nilai', 'mahasiswa_id');
        $participants = $this->approvedParticipants($penawaran)->orderBy('mhs_nim')->get();
        $filename = 'nilai-'.$penawaran->code.'-'.$penawaran->kelas->code.'-'.$period->code.'.xlsx';

        return (new FastExcel($participants))->download(
            $filename,
            fn (Mahasiswa $student) => [
                'NIM' => (string) $student->mhs_nim,
                'Nama Mahasiswa' => $student->mhs_name,
                'Nilai' => $grades->get($student->id),
            ]
        );
    }

    public function importGrades(
        Request $request,
        PenawaranMataKuliah $penawaran,
        AcademicPeriodContext $context
    ): RedirectResponse {
        $period = $context->requireWritableCurrent($request->user());
        abort_unless($penawaran->taka_id === $period->id, 404);
        $request->validate([
            '_form' => ['required', 'in:import-nilai'],
            'import' => [
                'required',
                'file',
                'extensions:xlsx,csv',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-zip-compressed,text/csv,text/plain,application/csv',
                'max:5120',
            ],
        ], [
            'import.required' => 'File nilai wajib dipilih.',
            'import.extensions' => 'File harus berformat XLSX atau CSV.',
            'import.mimetypes' => 'Isi file harus berupa XLSX atau CSV yang valid.',
            'import.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Gunakan file hasil ekspor nilai berformat XLSX atau CSV.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['import' => 'File import tidak berisi data nilai.']);
        }

        $requiredHeaders = ['NIM', 'Nama Mahasiswa', 'Nilai'];
        $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $participants = $this->approvedParticipants($penawaran)
            ->get(['mahasiswas.id', 'mhs_nim'])
            ->keyBy(fn (Mahasiswa $student) => trim((string) $student->mhs_nim));
        $prepared = [];
        $seen = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $nim = trim((string) ($row['NIM'] ?? ''));
            $grade = strtoupper(trim((string) ($row['Nilai'] ?? '')));

            if ($nim === '') {
                $errors[] = "Baris {$rowNumber}: NIM wajib diisi.";

                continue;
            }

            if (isset($seen[$nim])) {
                $errors[] = "Baris {$rowNumber}: NIM {$nim} muncul lebih dari satu kali.";

                continue;
            }

            $seen[$nim] = true;
            $student = $participants->get($nim);

            if (! $student) {
                $errors[] = "Baris {$rowNumber}: NIM {$nim} bukan peserta KRS penawaran ini.";

                continue;
            }

            if ($grade !== '' && ! in_array($grade, ['A', 'B', 'C', 'D', 'E'], true)) {
                $errors[] = "Baris {$rowNumber}: nilai {$grade} tidak valid. Gunakan A, B, C, D, E, atau kosong.";

                continue;
            }

            $prepared[] = [
                'mahasiswa_id' => $student->id,
                'nilai' => $grade !== '' ? $grade : null,
            ];
        }

        if ($errors !== []) {
            $message = implode(' ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= ' Serta '.(count($errors) - 5).' kesalahan lainnya.';
            }

            throw ValidationException::withMessages(['import' => $message]);
        }

        DB::transaction(fn () => $this->persistOfferingGrades($prepared, $period->id, $penawaran));

        return redirect()->route($this->setPrefix().'master.penawaran-grades', $penawaran)
            ->with('success', count($prepared).' nilai mahasiswa berhasil diimpor.');
    }

    private function approvedParticipants(PenawaranMataKuliah $penawaran)
    {
        return Mahasiswa::query()->forApprovedOffering($penawaran);
    }

    private function persistOfferingGrades(
        array $grades,
        int $periodId,
        PenawaranMataKuliah $penawaran
    ): void {
        foreach ($grades as $grade) {
            NilaiMahasiswa::updateOrCreate(
                [
                    'mahasiswa_id' => $grade['mahasiswa_id'],
                    'penawaran_mata_kuliah_id' => $penawaran->id,
                ],
                [
                    'taka_id' => $periodId,
                    'mata_kuliah_id' => $penawaran->legacy_mata_kuliah_id,
                    'kelas_id' => $penawaran->kelas_id,
                    'dosen_id' => $penawaran->dosen_utama_id,
                    'nilai' => $grade['nilai'] ?? null,
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function prepareImportedOffering(array $row, int $rowNumber, TahunAkademik $period): array
    {
        $periodCode = $this->importedText($row['Kode Periode'] ?? null);

        if ($periodCode !== $period->code) {
            $this->rejectImportRow($rowNumber, "Kode Periode harus {$period->code}.");
        }

        $programCode = $this->importedText($row['Kode Program Studi'] ?? null);
        $program = ProgramStudi::query()->where('code', $programCode)->first();

        if (! $program) {
            $this->rejectImportRow($rowNumber, "Kode Program Studi {$programCode} tidak ditemukan.");
        }

        $curriculumCode = $this->importedText($row['Kode Kurikulum'] ?? null);
        $curriculum = Kurikulum::query()->where('code', $curriculumCode)->first();

        if (! $curriculum) {
            $this->rejectImportRow($rowNumber, "Kode Kurikulum {$curriculumCode} tidak ditemukan.");
        }

        $classCode = $this->importedText($row['Kode Kelas'] ?? null);
        $class = Kelas::query()
            ->forAcademicPeriod($period)
            ->where('pstudi_id', $program->id)
            ->where('code', $classCode)
            ->first();

        if (! $class) {
            $this->rejectImportRow(
                $rowNumber,
                "Kelas {$classCode} tidak ditemukan pada periode dan program studi tersebut."
            );
        }

        $semester = $this->importedSemester(
            $row['Semester Mata Kuliah'] ?? null,
            $rowNumber,
            'Semester Mata Kuliah'
        );
        $courseName = $this->importedText($row['Nama Mata Kuliah'] ?? null);
        $master = MasterMataKuliah::query()
            ->whereIn('program_studi', $program->masterMataKuliahCodes())
            ->where('semester', $semester)
            ->where('name', $courseName)
            ->get();

        if ($courseName === '' || $master->count() !== 1) {
            $this->rejectImportRow(
                $rowNumber,
                "Mata kuliah {$courseName} semester {$semester} harus cocok tepat dengan satu data master."
            );
        }

        $mainLecturer = $this->importedLecturer($row['NIDN Dosen Utama'] ?? null, $rowNumber, 'Dosen Utama');
        $assistant1 = $this->importedLecturer(
            $row['NIDN Dosen Pendamping 1'] ?? null,
            $rowNumber,
            'Dosen Pendamping 1',
            required: false
        );
        $assistant2 = $this->importedLecturer(
            $row['NIDN Dosen Pendamping 2'] ?? null,
            $rowNumber,
            'Dosen Pendamping 2',
            required: false
        );
        $prerequisiteSemesterValue = $this->importedText($row['Semester Prasyarat'] ?? null);
        $prerequisiteName = $this->importedText($row['Nama Mata Kuliah Prasyarat'] ?? null);
        $prerequisite = null;

        if ($prerequisiteSemesterValue !== '' || $prerequisiteName !== '') {
            if ($prerequisiteSemesterValue === '' || $prerequisiteName === '') {
                $this->rejectImportRow(
                    $rowNumber,
                    'Semester dan Nama Mata Kuliah Prasyarat harus diisi bersama-sama.'
                );
            }

            $prerequisiteSemester = $this->importedSemester(
                $prerequisiteSemesterValue,
                $rowNumber,
                'Semester Prasyarat'
            );
            $prerequisites = MasterMataKuliah::query()
                ->whereIn('program_studi', $program->masterMataKuliahCodes())
                ->where('semester', $prerequisiteSemester)
                ->where('name', $prerequisiteName)
                ->get();

            if ($prerequisites->count() !== 1) {
                $this->rejectImportRow(
                    $rowNumber,
                    "Prasyarat {$prerequisiteName} semester {$prerequisiteSemester} tidak ditemukan secara unik."
                );
            }

            $prerequisite = $prerequisites->first();

            if ($prerequisite->is($master->first())) {
                $this->rejectImportRow($rowNumber, 'Mata kuliah tidak dapat menjadi prasyarat bagi dirinya sendiri.');
            }
        }

        $capacity = $this->importedInteger($row['Kapasitas'] ?? null, $rowNumber, 'Kapasitas', 1, 1000);
        $code = $this->importedText($row['Kode Penawaran'] ?? null);

        if (mb_strlen($code) > 255) {
            $this->rejectImportRow($rowNumber, 'Kode Penawaran maksimal 255 karakter.');
        }

        return [
            'master_mata_kuliah_id' => $master->first()->id,
            'pstudi_id' => $program->id,
            'kuri_id' => $curriculum->id,
            'kelas_id' => $class->id,
            'dosen_utama_id' => $mainLecturer->id,
            'dosen_pendamping_1_id' => $assistant1?->id,
            'dosen_pendamping_2_id' => $assistant2?->id,
            'prasyarat_master_id' => $prerequisite?->id,
            'code' => $code === '' ? null : $code,
            'sks' => $master->first()->sks,
            'kapasitas' => $capacity,
            'deskripsi' => $this->importedText($row['Deskripsi'] ?? null) ?: null,
        ];
    }

    private function importedLecturer(
        mixed $value,
        int $rowNumber,
        string $column,
        bool $required = true
    ): ?Dosen {
        $nidn = $this->importedText($value);

        if ($nidn === '' && ! $required) {
            return null;
        }

        $lecturer = Dosen::query()->where('dsn_nidn', $nidn)->first();

        if (! $lecturer) {
            $this->rejectImportRow($rowNumber, "NIDN {$column} {$nidn} tidak ditemukan.");
        }

        return $lecturer;
    }

    private function importedInteger(
        mixed $value,
        int $rowNumber,
        string $column,
        int $minimum,
        int $maximum
    ): int {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if ($integer === false || $integer < $minimum || $integer > $maximum) {
            $this->rejectImportRow(
                $rowNumber,
                "{$column} harus berupa angka antara {$minimum} sampai {$maximum}."
            );
        }

        return $integer;
    }

    private function importedSemester(mixed $value, int $rowNumber, string $column): int
    {
        $semester = strtoupper($this->importedText($value));
        $romanSemesters = array_flip([
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII',
            8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV',
        ]);

        if (isset($romanSemesters[$semester])) {
            return $romanSemesters[$semester];
        }

        $integer = filter_var($semester, FILTER_VALIDATE_INT);

        if ($integer === false || $integer < 1 || $integer > 14) {
            $this->rejectImportRow(
                $rowNumber,
                "{$column} harus berupa angka 1 sampai 14 atau Romawi I sampai XIV."
            );
        }

        return $integer;
    }

    private function importedText(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function rejectImportRow(int $rowNumber, string $message): never
    {
        throw ValidationException::withMessages([
            'import' => "Baris {$rowNumber}: {$message}",
        ]);
    }

    private function offeringFilters(Request $request): array
    {
        $semester = $request->integer('semester');

        return [
            'q' => trim((string) $request->query('q', '')),
            'pstudi_id' => $request->integer('pstudi_id') ?: null,
            'kuri_id' => $request->integer('kuri_id') ?: null,
            'kelas_id' => $request->integer('kelas_id') ?: null,
            'dosen_id' => $request->integer('dosen_id') ?: null,
            'semester' => $semester >= 1 && $semester <= 14 ? $semester : null,
        ];
    }

    private function applyOfferingFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'], fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                $query->where('code', 'like', '%'.$search.'%')
                    ->orWhereHas('masterMataKuliah', fn (Builder $masterQuery) => $masterQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%'));
            }))
            ->when($filters['pstudi_id'], fn (Builder $query, int $programId) => $query->where('pstudi_id', $programId))
            ->when($filters['kuri_id'], fn (Builder $query, int $curriculumId) => $query->where('kuri_id', $curriculumId))
            ->when($filters['kelas_id'], fn (Builder $query, int $classId) => $query->where('kelas_id', $classId))
            ->when($filters['dosen_id'], fn (Builder $query, int $lecturerId) => $query->where('dosen_utama_id', $lecturerId))
            ->when($filters['semester'], fn (Builder $query, int $semester) => $query->whereHas(
                'masterMataKuliah',
                fn (Builder $masterQuery) => $masterQuery->where('semester', $semester)
            ));
    }

    private function validateOffering(Request $request, int $periodId, PenawaranMataKuliah|bool|null $offering = null, bool $multipleClasses = false): array
    {
        if (is_bool($offering)) {
            $multipleClasses = $offering;
            $offering = null;
        }
        $programStudi = ProgramStudi::query()->find($request->integer('pstudi_id'));
        $masterProgramCodes = $programStudi?->masterMataKuliahCodes() ?? [];
        $classRule = Rule::exists('kelas', 'id')->where(fn ($query) => $query->where('taka_id', $periodId)->where('pstudi_id', $request->integer('pstudi_id')));
        $rules = [
            'master_mata_kuliah_id' => [
                'required',
                Rule::exists('master_mata_kuliahs', 'id')->where(fn ($query) => $query
                    ->whereIn('program_studi', $masterProgramCodes)),
            ],
            'pstudi_id' => ['required', 'exists:program_studis,id'],
            'kuri_id' => ['required', 'exists:kurikulums,id'],
            'dosen_utama_id' => ['required', 'exists:dosens,id'],
            'dosen_pendamping_1_id' => ['nullable', 'exists:dosens,id'],
            'dosen_pendamping_2_id' => ['nullable', 'exists:dosens,id'],
            'prasyarat_master_id' => [
                'nullable',
                'different:master_mata_kuliah_id',
                Rule::exists('master_mata_kuliahs', 'id')->where(fn ($query) => $query
                    ->whereIn('program_studi', $masterProgramCodes)),
            ],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:1000'],
            'deskripsi' => ['nullable', 'string'],
        ];
        $rules[$multipleClasses ? 'kelas_ids' : 'kelas_id'] = $multipleClasses
            ? ['required', 'array', 'min:1'] : ['required', $classRule];
        if ($multipleClasses) {
            $rules['kelas_ids.*'] = ['required', 'distinct', $classRule];
        }

        $data = $request->validate($rules, [
            'master_mata_kuliah_id.exists' => 'Master mata kuliah tidak sesuai dengan program studi yang dipilih.',
            'kelas_ids.*.exists' => 'Kelas tidak sesuai dengan periode atau program studi yang dipilih.',
        ]);

        $classIds = $multipleClasses ? $data['kelas_ids'] : [$data['kelas_id']];
        $duplicateClasses = PenawaranMataKuliah::query()
            ->where('master_mata_kuliah_id', $data['master_mata_kuliah_id'])
            ->where('taka_id', $periodId)
            ->where('pstudi_id', $data['pstudi_id'])
            ->where('kuri_id', $data['kuri_id'])
            ->whereIn('kelas_id', $classIds)
            ->when($offering, fn ($query, PenawaranMataKuliah $currentOffering) => $query->whereKeyNot($currentOffering->getKey()))
            ->with('kelas:id,name')
            ->get()
            ->map(fn (PenawaranMataKuliah $duplicate) => $duplicate->kelas?->name ?? 'kelas #'.$duplicate->kelas_id)
            ->unique()
            ->values();

        if ($duplicateClasses->isNotEmpty()) {
            throw ValidationException::withMessages([
                $multipleClasses ? 'kelas_ids' : 'kelas_id' => 'Penawaran mata kuliah tersebut sudah tersedia untuk kelas: '.$duplicateClasses->implode(', ').'.',
            ]);
        }

        return $data;
    }

    private function newCode(int $periodId, int $masterId, int $classId): string
    {
        return 'OF-'.$periodId.'-'.$masterId.'-'.$classId.'-'.Str::upper(Str::random(5));
    }
}
