<?php

namespace App\Http\Controllers\Dosen\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\TahunAkademik;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ])
            ->orderBy('code')
            ->get();

        return view('dosen.pages.mata-kuliah-index', [
            'period' => $period,
            'offerings' => $offerings,
        ]);
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
        ], [
            'nilai.*.mahasiswa_id.in' => 'Mahasiswa harus tercatat pada KRS yang disetujui untuk mata kuliah ini.',
        ]);

        DB::transaction(fn () => $this->persistGrades($validated['nilai'], $period, $penawaran));

        return redirect()->route('dosen.akademik.matkul-nilai', $penawaran)
            ->with('success', 'Nilai mahasiswa berhasil disimpan.');
    }

    public function exportGrades(PenawaranMataKuliah $penawaran, AcademicPeriodContext $context)
    {
        $period = $context->published();
        $this->ensureOwned($penawaran, $period);
        $penawaran->load(['masterMataKuliah', 'kelas']);
        $grades = NilaiMahasiswa::query()
            ->forAcademicPeriod($period)
            ->where('penawaran_mata_kuliah_id', $penawaran->id)
            ->pluck('nilai', 'mahasiswa_id');
        $participants = $this->participants($penawaran)->orderBy('mhs_nim')->get();

        return (new FastExcel($participants))->download(
            'nilai-'.$penawaran->code.'-'.$penawaran->kelas->code.'-'.$period->code.'.xlsx',
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
                ];
            }

            $seen[$nim] = true;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'import' => implode(' ', array_slice($errors, 0, 5)),
            ]);
        }

        DB::transaction(fn () => $this->persistGrades($prepared, $period, $penawaran));

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
                ]
            );
        }
    }
}
