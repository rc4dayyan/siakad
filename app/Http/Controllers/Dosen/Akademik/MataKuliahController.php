<?php

namespace App\Http\Controllers\Dosen\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MateriAjar;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\TahunAkademik;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\GradeConversionService;
use App\Services\Imports\OfferingGradeFileService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Rap2hpoutre\FastExcel\FastExcel;

class MataKuliahController extends Controller
{
    public function index(AcademicPeriodContext $context): View
    {
        $lecturer = auth('dosen')->user();
        $period = $context->published();
        $offerings = $this->ownedOfferings($lecturer->id, $period)
            ->with(['masterMataKuliah', 'pstudi', 'kelas'])
            ->withCount([
                'krsItems as peserta_count' => fn ($query) => $query->whereHas(
                    'krs',
                    fn ($krs) => $krs->whereIn('status', [Krs::STATUS_APPROVED, Krs::STATUS_LOCKED])
                ),
                'nilais as nilai_terisi_count' => fn ($query) => $query->whereNotNull('nilai'),
                'materiAjars as materi_count',
            ])
            ->orderBy('code')
            ->get();

        return view('dosen.pages.mata-kuliah-index', [
            'period' => $period,
            'offerings' => $offerings,
        ]);
    }

    public function materials(PenawaranMataKuliah $penawaran, AcademicPeriodContext $context): View
    {
        $period = $context->published();
        $this->ensureOwned($penawaran, $period);
        $penawaran->load(['masterMataKuliah', 'taka', 'kelas', 'pstudi']);

        return view('dosen.pages.materi-ajar-index', [
            'penawaran' => $penawaran,
            'period' => $period,
            'materials' => $penawaran->materiAjars()->with('dosen')->latest()->get(),
            'canManage' => $period->isWritable(),
        ]);
    }

    public function storeMaterial(
        Request $request,
        PenawaranMataKuliah $penawaran,
        AcademicPeriodContext $context
    ): RedirectResponse {
        $period = $this->writablePeriod($context);
        $this->ensureOwned($penawaran, $period);
        $validated = $request->validate([
            '_form' => ['required', 'in:create-materi'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:10000', 'required_without:file'],
            'file' => [
                'nullable',
                'file',
                'required_without:deskripsi',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,zip',
                'max:20480',
            ],
        ], [
            'judul.required' => 'Judul materi wajib diisi.',
            'judul.max' => 'Judul materi maksimal 255 karakter.',
            'deskripsi.required_without' => 'Isi deskripsi atau unggah sebuah file materi.',
            'deskripsi.max' => 'Deskripsi materi maksimal 10.000 karakter.',
            'file.required_without' => 'Unggah file atau isi deskripsi materi.',
            'file.mimes' => 'File harus berupa PDF, Word, Excel, PowerPoint, teks, gambar, atau ZIP.',
            'file.max' => 'Ukuran file materi maksimal 20 MB.',
        ]);
        $file = $request->file('file');
        $path = $file?->store('materi-ajar/'.$penawaran->id, 'local');

        try {
            $material = MateriAjar::create([
                'penawaran_mata_kuliah_id' => $penawaran->id,
                'dosen_id' => auth('dosen')->id(),
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'file_path' => $path,
                'file_name' => $file ? $this->safeOriginalName($file->getClientOriginalName()) : null,
                'mime_type' => $file?->getMimeType(),
                'file_size' => $file?->getSize(),
            ]);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return redirect()->route('dosen.akademik.matkul-materi', $penawaran)
            ->with('success', "Materi {$material->judul} berhasil ditambahkan.");
    }

    public function downloadMaterial(
        PenawaranMataKuliah $penawaran,
        MateriAjar $materi,
        AcademicPeriodContext $context
    ) {
        $this->ensureOwned($penawaran, $context->published());
        $this->ensureMaterialBelongsToOffering($materi, $penawaran);
        abort_unless($materi->file_path && Storage::disk('local')->exists($materi->file_path), 404);

        return Storage::disk('local')->download(
            $materi->file_path,
            $materi->file_name,
            ['Content-Type' => $materi->mime_type ?: 'application/octet-stream']
        );
    }

    public function destroyMaterial(
        PenawaranMataKuliah $penawaran,
        MateriAjar $materi,
        AcademicPeriodContext $context
    ): RedirectResponse {
        $period = $this->writablePeriod($context);
        $this->ensureOwned($penawaran, $period);
        $this->ensureMaterialBelongsToOffering($materi, $penawaran);
        $path = $materi->file_path;
        $title = $materi->judul;
        $materi->delete();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return redirect()->route('dosen.akademik.matkul-materi', $penawaran)
            ->with('success', "Materi {$title} berhasil dihapus.");
    }

    public function grades(PenawaranMataKuliah $penawaran, AcademicPeriodContext $context): View
    {
        $period = $context->published();
        $this->ensureOwned($penawaran, $period);
        $penawaran->load(['masterMataKuliah', 'taka', 'kelas', 'dosenUtama']);

        return view('user.admin.master.admin-matkul-nilai', [
            'penawaran' => $penawaran,
            'period' => $period,
            'canManageNilai' => $period->isWritable(),
            'mahasiswas' => $this->participants($penawaran)->orderBy('mhs_name')->get(),
            'existingNilais' => NilaiMahasiswa::query()
                ->forAcademicPeriod($period)
                ->where('penawaran_mata_kuliah_id', $penawaran->id)
                ->get()
                ->keyBy('mahasiswa_id'),
            'gradeBackUrl' => route('dosen.akademik.matkul-index'),
            'gradeExportUrl' => route('dosen.akademik.matkul-nilai-export', $penawaran),
            'gradeStoreUrl' => route('dosen.akademik.matkul-nilai-store', $penawaran),
            'gradeImportUrl' => route('dosen.akademik.matkul-nilai-import', $penawaran),
        ]);
    }

    public function storeGrades(
        Request $request,
        PenawaranMataKuliah $penawaran,
        AcademicPeriodContext $context
    ): RedirectResponse {
        $period = $this->writablePeriod($context);
        $this->ensureOwned($penawaran, $period);
        $allowedStudentIds = $this->participants($penawaran)->pluck('mahasiswas.id')->all();
        $validated = $request->validate([
            'nilai' => ['required', 'array'],
            'nilai.*.mahasiswa_id' => ['required', 'integer', Rule::in($allowedStudentIds)],
            'nilai.*.nilai' => ['nullable', 'in:A,B,C,D,E'],
            'nilai.*.nilai_indeks' => ['nullable', 'numeric', 'between:0,4'],
            'nilai.*.nilai_angka' => ['nullable', 'numeric', 'between:0,100'],
        ], [
            'nilai.*.nilai.in' => 'Nilai huruf harus A, B, C, D, E, atau kosong.',
            'nilai.*.nilai_indeks.numeric' => 'Nilai indeks harus berupa angka.',
            'nilai.*.nilai_indeks.between' => 'Nilai indeks harus antara 0 dan 4.',
            'nilai.*.nilai_angka.numeric' => 'Nilai angka harus berupa angka.',
            'nilai.*.nilai_angka.between' => 'Nilai angka harus antara 0 dan 100.',
            'nilai.*.mahasiswa_id.in' => 'Mahasiswa harus tercatat pada KRS yang disetujui untuk mata kuliah ini.',
        ]);

        DB::transaction(fn () => $this->persistGrades(GradeConversionService::apply($validated['nilai']), $period, $penawaran));

        return redirect()->route('dosen.akademik.matkul-nilai', $penawaran)
            ->with('success', 'Nilai mahasiswa berhasil disimpan.');
    }

    public function exportGrades(PenawaranMataKuliah $penawaran, AcademicPeriodContext $context)
    {
        $period = $context->published();
        $this->ensureOwned($penawaran, $period);
        $penawaran->load(['masterMataKuliah', 'kelas', 'pstudi', 'legacyMataKuliah']);
        $grades = NilaiMahasiswa::query()
            ->forAcademicPeriod($period)
            ->where('penawaran_mata_kuliah_id', $penawaran->id)
            ->get()->keyBy('mahasiswa_id');
        $participants = $this->participants($penawaran)->orderBy('mhs_nim')->get();

        return (new FastExcel($participants))->download(
            'nilai-'.$penawaran->code.'-'.$penawaran->kelas->code.'-'.$period->code.'.xlsx',
            fn (Mahasiswa $student) => OfferingGradeFileService::exportRow($student, $grades->get($student->id), $penawaran, $period)
        );
    }

    public function importGrades(
        Request $request,
        PenawaranMataKuliah $penawaran,
        AcademicPeriodContext $context
    ): RedirectResponse {
        $period = $this->writablePeriod($context);
        $this->ensureOwned($penawaran, $period);
        $request->validate([
            '_form' => ['required', 'in:import-nilai'],
            'import' => [
                'required',
                'file',
                'extensions:xlsx,csv',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-zip-compressed,text/csv,text/plain,application/csv',
                'max:5120',
            ],
        ]);
        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Gunakan hasil ekspor nilai berformat XLSX atau CSV.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['import' => 'File import tidak berisi data nilai.']);
        }

        $rows = OfferingGradeFileService::normalize($rows, $penawaran, $period);

        $missingHeaders = array_diff(['NIM', 'Nama Mahasiswa', 'Nilai'], array_keys($rows->first()));
        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $participants = $this->participants($penawaran)
            ->get(['mahasiswas.id', 'mhs_nim'])
            ->keyBy(fn (Mahasiswa $student) => trim((string) $student->mhs_nim));
        $prepared = [];
        $seen = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $nim = trim((string) ($row['NIM'] ?? ''));
            $grade = strtoupper(trim((string) ($row['Nilai'] ?? '')));
            $numericGrades = [];
            foreach (['Nilai Indeks' => ['nilai_indeks', 4], 'Nilai Angka' => ['nilai_angka', 100]] as $column => [$field, $maximum]) {
                if (! array_key_exists($column, $row)) {
                    continue;
                }
                $value = str_replace(',', '.', trim((string) ($row[$column] ?? '')));
                if ($value !== '' && (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0 || (float) $value > $maximum)) {
                    $errors[] = "Baris {$rowNumber}: {$column} harus antara 0 dan {$maximum}, atau kosong.";
                }
                $numericGrades[$field] = $value === '' ? null : round((float) $value, 2);
            }

            if ($nim === '') {
                $errors[] = "Baris {$rowNumber}: NIM wajib diisi.";
            } elseif (isset($seen[$nim])) {
                $errors[] = "Baris {$rowNumber}: NIM {$nim} muncul lebih dari satu kali.";
            } elseif (! $participants->has($nim)) {
                $errors[] = "Baris {$rowNumber}: NIM {$nim} bukan peserta KRS mata kuliah ini.";
            } elseif ($grade !== '' && ! in_array($grade, ['A', 'B', 'C', 'D', 'E'], true)) {
                $errors[] = "Baris {$rowNumber}: nilai {$grade} tidak valid.";
            } else {
                $prepared[] = [
                    'mahasiswa_id' => $participants->get($nim)->id,
                    'nilai' => $grade !== '' ? $grade : null,
                    ...$numericGrades,
                ];
            }

            $seen[$nim] = true;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'import' => implode(' ', array_slice($errors, 0, 5)),
            ]);
        }

        DB::transaction(fn () => $this->persistGrades(GradeConversionService::apply($prepared), $period, $penawaran));

        return redirect()->route('dosen.akademik.matkul-nilai', $penawaran)
            ->with('success', count($prepared).' nilai mahasiswa berhasil diimpor.');
    }

    private function ownedOfferings(int $lecturerId, ?TahunAkademik $period): Builder
    {
        return PenawaranMataKuliah::query()
            ->forAcademicPeriod($period)
            ->where(function (Builder $query) use ($lecturerId): void {
                $query->where('dosen_utama_id', $lecturerId)
                    ->orWhere('dosen_pendamping_1_id', $lecturerId)
                    ->orWhere('dosen_pendamping_2_id', $lecturerId);
            });
    }

    private function ensureOwned(PenawaranMataKuliah $penawaran, ?TahunAkademik $period): void
    {
        abort_unless(
            $period && $this->ownedOfferings(auth('dosen')->id(), $period)->whereKey($penawaran->id)->exists(),
            404
        );
    }

    private function ensureMaterialBelongsToOffering(MateriAjar $material, PenawaranMataKuliah $offering): void
    {
        abort_unless((int) $material->penawaran_mata_kuliah_id === (int) $offering->id, 404);
    }

    private function safeOriginalName(string $name): string
    {
        $safeName = trim((string) preg_replace('/[^\pL\pN._ -]+/u', '-', basename($name)), '. -');

        return Str::limit($safeName ?: 'materi', 255, '');
    }

    private function writablePeriod(AcademicPeriodContext $context): TahunAkademik
    {
        $period = $context->published();

        if (! $period?->isWritable()) {
            throw ValidationException::withMessages([
                'academic_period' => 'Periode akademik tidak tersedia atau berada dalam mode hanya-baca.',
            ]);
        }

        return $period;
    }

    private function participants(PenawaranMataKuliah $penawaran): Builder
    {
        return Mahasiswa::query()->forApprovedOffering($penawaran);
    }

    private function persistGrades(
        array $grades,
        TahunAkademik $period,
        PenawaranMataKuliah $penawaran
    ): void {
        foreach ($grades as $grade) {
            NilaiMahasiswa::updateOrCreate(
                [
                    'mahasiswa_id' => $grade['mahasiswa_id'],
                    'penawaran_mata_kuliah_id' => $penawaran->id,
                ],
                [
                    'taka_id' => $period->id,
                    'mata_kuliah_id' => $penawaran->legacy_mata_kuliah_id,
                    'kelas_id' => $penawaran->kelas_id,
                    'dosen_id' => auth('dosen')->id(),
                    'nilai' => $grade['nilai'] ?? null,
                    ...array_intersect_key($grade, array_flip(['nilai_indeks', 'nilai_angka'])),
                ]
            );
        }
    }
}
