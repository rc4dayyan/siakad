<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Kurikulum;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Imports\DosenPengajarOpenFeederImportService;
use App\Services\Imports\KelasOpenFeederImportService;
use App\Services\Imports\KrsOpenFeederImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Rap2hpoutre\FastExcel\FastExcel;

class AcademicPreparationController extends Controller
{
    use roleTrait;

    public function index(Request $request): View
    {
        $step = (int) old('_wizard_step', $request->integer('step', 1));
        $step = in_array($step, [1, 2, 3, 4, 5], true) ? $step : 1;

        $academicYears = TahunAkademikInduk::query()
            ->withCount('periodeAkademiks')
            ->orderByDesc('year_start')
            ->get();

        $selectedAcademicYearId = old('tid', $request->integer('tid'));
        if (! $selectedAcademicYearId && $step === 2) {
            $selectedAcademicYearId = $academicYears->first()?->id;
        }

        $periods = TahunAkademik::query()
            ->with('tahunAkademik')
            ->whereIn('status', [TahunAkademik::STATUS_DRAFT, TahunAkademik::STATUS_ACTIVE])
            ->orderByDesc('year_start')
            ->orderByDesc('starts_at')
            ->get();
        $selectedPeriodId = old('taka_id', $request->integer('taka_id'));
        if (! $selectedPeriodId && $step === 3) {
            $selectedPeriodId = $periods->first()?->id;
        }

        return view('user.admin.academic-preparation-wizard', [
            'academicYears' => $academicYears,
            'prefix' => $this->setPrefix(),
            'selectedAcademicYearId' => $selectedAcademicYearId,
            'selectedPeriodId' => $selectedPeriodId,
            'step' => $step,
            'terms' => TahunAkademik::terms(),
            'periods' => $periods,
            'curricula' => Kurikulum::query()->orderByDesc('year_start')->orderBy('name')->get(),
            'programs' => ProgramStudi::query()->orderBy('name')->get(),
        ]);
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('tahun_akademik', 'code')],
            'year_start' => [
                'required',
                'integer',
                'between:2000,2100',
                Rule::unique('tahun_akademik', 'year_start')
                    ->where(fn ($query) => $query->where('year_end', $request->input('year_end'))),
            ],
            'year_end' => ['required', 'integer', 'between:2001,2101', 'gt:year_start'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, dan tanda hubung.',
            'code.unique' => 'Kode tahun akademik sudah digunakan.',
            'year_start.unique' => 'Rentang tahun akademik tersebut sudah tersedia.',
            'year_end.gt' => 'Tahun selesai harus setelah tahun mulai.',
        ]);

        $academicYear = TahunAkademikInduk::create($validated);

        return redirect()
            ->route('web-admin.academic-preparation.index', ['step' => 2, 'tid' => $academicYear->id])
            ->with('status', 'Tahun akademik berhasil ditambahkan. Lanjutkan dengan membuat periode akademik.');
    }

    public function storeAcademicPeriod(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'tid' => ['required', 'integer', Rule::exists('tahun_akademik', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('tahun_akademiks', 'code')],
            'term' => [
                'required',
                Rule::in(TahunAkademik::terms()),
                Rule::unique('tahun_akademiks', 'term')
                    ->where(fn ($query) => $query->where('tid', $request->integer('tid'))),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ], [
            'tid.required' => 'Tahun akademik wajib dipilih.',
            'tid.exists' => 'Tahun akademik yang dipilih tidak tersedia.',
            'code.regex' => 'Kode periode hanya boleh berisi huruf, angka, dan tanda hubung.',
            'code.unique' => 'Kode periode akademik sudah digunakan.',
            'term.in' => 'Jenis periode akademik tidak valid.',
            'term.unique' => 'Jenis periode tersebut sudah tersedia pada tahun akademik yang dipilih.',
            'ends_at.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $academicYear = TahunAkademikInduk::findOrFail($validated['tid']);

        $period = DB::transaction(fn () => TahunAkademik::create([
            ...$validated,
            'semester' => $this->legacySemesterValue($validated['term']),
            'year_start' => $academicYear->year_start,
            'year_end' => $academicYear->year_end,
            'is_active' => false,
            'status' => TahunAkademik::STATUS_DRAFT,
        ]));

        session()->put(AcademicPeriodContext::SESSION_KEY, $period->id);

        return redirect()
            ->route('web-admin.academic-preparation.index', ['step' => 3, 'taka_id' => $period->id])
            ->with('status', 'Periode akademik berhasil disimpan sebagai draft.')
            ->with('created_period_code', $period->code);
    }

    public function importClasses(Request $request, KelasOpenFeederImportService $importer): RedirectResponse
    {
        $validated = $request->validate([
            'taka_id' => ['required', 'integer', Rule::exists('tahun_akademiks', 'id')],
            'capacity' => ['required', 'integer', 'between:1,100'],
            'import' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'taka_id.required' => 'Periode akademik wajib dipilih.',
            'taka_id.exists' => 'Periode akademik yang dipilih tidak tersedia.',
            'capacity.between' => 'Kapasitas kelas harus antara 1 sampai 100.',
            'import.required' => 'File kelas wajib diunggah.',
            'import.mimes' => 'File kelas harus menggunakan format xlsx atau csv.',
            'import.max' => 'Ukuran file kelas tidak boleh melebihi 2MB.',
        ]);

        $period = TahunAkademik::findOrFail($validated['taka_id']);
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'taka_id' => 'Periode yang ditutup atau diarsipkan tidak dapat menerima import kelas.',
            ]);
        }

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan file menggunakan format xlsx atau csv yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $result = $importer->import(
            $rows,
            $period,
            $validated['capacity'],
            $request->boolean('dry_run')
        );
        session()->put(AcademicPeriodContext::SESSION_KEY, $period->id);

        $message = $request->boolean('dry_run')
            ? "Validasi berhasil: {$result['courses_created']} mata kuliah dan {$result['imported']} kelas baru dapat dibuat; {$result['skipped']} baris duplikat/kelas lama akan dilewati."
            : "Import selesai: {$result['courses_created']} mata kuliah dan {$result['imported']} kelas baru dibuat; {$result['skipped']} baris duplikat/kelas lama dilewati.";

        return redirect()
            ->route('web-admin.academic-preparation.index', [
                'step' => $request->boolean('dry_run') ? 3 : 4,
                'taka_id' => $period->id,
            ])
            ->with('status', $message)
            ->with('classes_imported', ! $request->boolean('dry_run'));
    }

    public function importTeachingLecturers(
        Request $request,
        DosenPengajarOpenFeederImportService $importer
    ): RedirectResponse {
        $validated = $request->validate([
            'taka_id' => ['required', 'integer', Rule::exists('tahun_akademiks', 'id')],
            'kuri_id' => ['required', 'integer', Rule::exists('kurikulums', 'id')],
            'import' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'taka_id.required' => 'Periode akademik wajib dipilih.',
            'taka_id.exists' => 'Periode akademik yang dipilih tidak tersedia.',
            'kuri_id.required' => 'Kurikulum wajib dipilih.',
            'kuri_id.exists' => 'Kurikulum yang dipilih tidak tersedia.',
            'import.required' => 'File dosen pengajar wajib diunggah.',
            'import.mimes' => 'File dosen pengajar harus menggunakan format xlsx atau csv.',
            'import.max' => 'Ukuran file dosen pengajar tidak boleh melebihi 2MB.',
        ]);

        $period = TahunAkademik::findOrFail($validated['taka_id']);
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'taka_id' => 'Periode yang ditutup atau diarsipkan tidak dapat menerima import dosen pengajar.',
            ]);
        }

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan file menggunakan format xlsx atau csv yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $result = $importer->import(
            $rows,
            $period,
            Kurikulum::findOrFail($validated['kuri_id']),
            $request->boolean('dry_run')
        );
        session()->put(AcademicPeriodContext::SESSION_KEY, $period->id);

        $message = $request->boolean('dry_run')
            ? "Validasi berhasil: {$result['lecturers_created']} dosen baru, {$result['offerings_created']} penawaran baru, dan {$result['offerings_updated']} pembaruan penawaran dapat diproses."
            : "Import berhasil: {$result['lecturers_created']} dosen baru dibuat, {$result['offerings_created']} penawaran baru dibuat, dan {$result['offerings_updated']} penawaran diperbarui.";

        return redirect()
            ->route('web-admin.academic-preparation.index', [
                'step' => $request->boolean('dry_run') ? 4 : 5,
                'taka_id' => $period->id,
            ])
            ->with('status', $message)
            ->with('teaching_lecturers_imported', ! $request->boolean('dry_run'));
    }

    public function importKrs(Request $request, KrsOpenFeederImportService $importer): RedirectResponse
    {
        $validated = $request->validate([
            'taka_id' => ['required', 'integer', Rule::exists('tahun_akademiks', 'id')],
            'pstudi_id' => ['required', 'integer', Rule::exists('program_studis', 'id')],
            'student_semester' => ['required', 'integer', 'between:1,14'],
            'import' => ['required', 'file', 'mimes:xlsx,csv', 'max:4096'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'taka_id.required' => 'Periode akademik wajib dipilih.',
            'taka_id.exists' => 'Periode akademik yang dipilih tidak tersedia.',
            'pstudi_id.required' => 'Program studi wajib dipilih.',
            'pstudi_id.exists' => 'Program studi yang dipilih tidak tersedia.',
            'student_semester.required' => 'Semester mahasiswa wajib dipilih.',
            'student_semester.between' => 'Semester mahasiswa harus antara 1 sampai 14.',
            'import.required' => 'File KRS wajib diunggah.',
            'import.mimes' => 'File KRS harus menggunakan format xlsx atau csv.',
            'import.max' => 'Ukuran file KRS tidak boleh melebihi 4MB.',
        ]);

        $period = TahunAkademik::findOrFail($validated['taka_id']);
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'taka_id' => 'Periode yang ditutup atau diarsipkan tidak dapat menerima import KRS.',
            ]);
        }

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan file menggunakan format xlsx atau csv yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $result = $importer->import(
            $rows,
            $period,
            ProgramStudi::findOrFail($validated['pstudi_id']),
            $validated['student_semester'],
            $request->boolean('dry_run')
        );
        session()->put(AcademicPeriodContext::SESSION_KEY, $period->id);

        $message = $request->boolean('dry_run')
            ? "Validasi berhasil: {$result['students_created']} mahasiswa, {$result['registrations_created']} registrasi, {$result['krs_created']} KRS, dan {$result['items_created']} item KRS dapat dibuat; kapasitas {$result['classes_resized']} kelas akan disesuaikan."
            : "Import berhasil: {$result['students_created']} mahasiswa, {$result['registrations_created']} registrasi, {$result['krs_created']} KRS, dan {$result['items_created']} item KRS dibuat; {$result['items_skipped']} item lama dilewati dan kapasitas {$result['classes_resized']} kelas disesuaikan.";

        return redirect()
            ->route('web-admin.academic-preparation.index', ['step' => 5, 'taka_id' => $period->id])
            ->with('status', $message)
            ->with('krs_imported', ! $request->boolean('dry_run'));
    }

    private function legacySemesterValue(string $term): int
    {
        return match ($term) {
            TahunAkademik::TERM_GANJIL => 1,
            TahunAkademik::TERM_GENAP => 2,
            TahunAkademik::TERM_PENDEK => 0,
        };
    }
}
