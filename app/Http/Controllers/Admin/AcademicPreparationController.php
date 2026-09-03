<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\JadwalMingguan;
use App\Models\Krs;
use App\Models\Kurikulum;
use App\Models\PenawaranMataKuliah;
use App\Models\ProgramStudi;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AcademicScheduleGeneratorService;
use App\Services\Academic\AdminKrsManagementService;
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
        $step = in_array($step, [1, 2, 3, 4, 5, 6, 7], true) ? $step : 1;

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
        if (! $selectedPeriodId && $step >= 3) {
            $selectedPeriodId = $periods->first()?->id;
        }

        $krsStatusCounts = $selectedPeriodId
            ? Krs::query()
                ->whereHas('registrasiMahasiswa', fn ($query) => $query->where('taka_id', $selectedPeriodId))
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
            : collect();
        $actionableStatuses = [Krs::STATUS_DRAFT, Krs::STATUS_REJECTED, Krs::STATUS_SUBMITTED];
        $krsSummary = [
            'total' => (int) $krsStatusCounts->sum(),
            'editable' => (int) $krsStatusCounts->only([Krs::STATUS_DRAFT, Krs::STATUS_REJECTED])->sum(),
            'submitted' => (int) $krsStatusCounts->get(Krs::STATUS_SUBMITTED, 0),
            'completed' => (int) $krsStatusCounts->only([Krs::STATUS_APPROVED, Krs::STATUS_LOCKED])->sum(),
            'actionable' => (int) $krsStatusCounts->only($actionableStatuses)->sum(),
            'empty' => $selectedPeriodId
                ? Krs::query()
                    ->whereIn('status', $actionableStatuses)
                    ->whereHas('registrasiMahasiswa', fn ($query) => $query->where('taka_id', $selectedPeriodId))
                    ->whereDoesntHave('items')
                    ->count()
                : 0,
        ];
        $scheduleOfferings = $selectedPeriodId
            ? PenawaranMataKuliah::query()
                ->forAcademicPeriod($selectedPeriodId)
                ->with(['masterMataKuliah', 'kelas'])
                ->withCount('jadwalMingguans')
                ->orderBy('kelas_id')
                ->orderBy('code')
                ->get()
            : collect();
        $requiredScheduleOfferings = $scheduleOfferings->where('wajib_dijadwalkan', true);
        $offeringCount = $scheduleOfferings->count();
        $requiredOfferingCount = $requiredScheduleOfferings->count();
        $totalOfferingCredits = (int) $requiredScheduleOfferings->sum('sks');
        $largestClassCapacity = $selectedPeriodId
            ? (int) PenawaranMataKuliah::query()
                ->forAcademicPeriod($selectedPeriodId)
                ->where('wajib_dijadwalkan', true)
                ->whereDoesntHave('jadwalMingguans')
                ->max('kapasitas')
            : 0;
        $scheduleCount = $selectedPeriodId
            ? JadwalMingguan::query()->forAcademicPeriod($selectedPeriodId)->count()
            : 0;
        $scheduledOfferingCount = $selectedPeriodId
            ? JadwalMingguan::query()
                ->forAcademicPeriod($selectedPeriodId)
                ->whereHas('penawaranMataKuliah', fn ($query) => $query->where('wajib_dijadwalkan', true))
                ->whereNotNull('penawaran_mata_kuliah_id')
                ->distinct()
                ->count('penawaran_mata_kuliah_id')
            : 0;
        $availableRoomCount = Ruang::query()->whereIn('type', [0, 1])->count();
        $defaultWeeklyMinutesPerRoom = 6 * 9 * 60;
        $defaultRequiredRooms = $requiredOfferingCount > 0
            ? (int) ceil((($totalOfferingCredits * 50) + ($requiredOfferingCount * 10)) / $defaultWeeklyMinutesPerRoom)
            : 0;
        $scheduleSummary = [
            'offerings' => $offeringCount,
            'required_offerings' => $requiredOfferingCount,
            'excluded_offerings' => $offeringCount - $requiredOfferingCount,
            'total_credits' => $totalOfferingCredits,
            'schedules' => $scheduleCount,
            'scheduled_offerings' => $scheduledOfferingCount,
            'unscheduled_offerings' => max(0, $requiredOfferingCount - $scheduledOfferingCount),
            'rooms' => $availableRoomCount,
            'estimated_rooms' => $defaultRequiredRooms,
            'room_shortage' => max(0, $defaultRequiredRooms - $availableRoomCount),
            'largest_capacity' => $largestClassCapacity,
            'adequate_rooms' => $largestClassCapacity > 0
                ? Ruang::query()->whereIn('type', [0, 1])->where('kapasitas', '>=', $largestClassCapacity)->count()
                : $availableRoomCount,
        ];

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
            'krsSummary' => $krsSummary,
            'scheduleSummary' => $scheduleSummary,
            'scheduleOfferings' => $scheduleOfferings,
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
            ->route('web-admin.academic-preparation.index', ['step' => 6, 'taka_id' => $period->id])
            ->with('status', $message)
            ->with('krs_imported', ! $request->boolean('dry_run'));
    }

    public function finalizeKrs(Request $request, AdminKrsManagementService $management): RedirectResponse
    {
        $validated = $request->validate([
            'taka_id' => ['required', 'integer', Rule::exists('tahun_akademiks', 'id')],
            'confirmation' => ['accepted'],
        ], [
            'taka_id.required' => 'Periode akademik wajib dipilih.',
            'taka_id.exists' => 'Periode akademik yang dipilih tidak tersedia.',
            'confirmation.accepted' => 'Konfirmasi pengajuan dan persetujuan seluruh KRS wajib dicentang.',
        ]);

        $period = TahunAkademik::findOrFail($validated['taka_id']);
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'taka_id' => 'KRS pada periode yang ditutup atau diarsipkan tidak dapat diproses.',
            ]);
        }

        $krsCollection = Krs::query()
            ->whereHas('registrasiMahasiswa', fn ($query) => $query->where('taka_id', $period->id))
            ->with(['registrasiMahasiswa.taka', 'items.penawaranMataKuliah.masterMataKuliah'])
            ->orderBy('id')
            ->get();
        $result = $management->submitAndApproveAll($krsCollection, $request->user());
        session()->put(AcademicPeriodContext::SESSION_KEY, $period->id);

        return redirect()
            ->route('web-admin.academic-preparation.index', ['step' => 7, 'taka_id' => $period->id])
            ->with('status', "Proses selesai: {$result['submitted']} KRS diajukan dan {$result['approved']} KRS disetujui; {$result['skipped']} KRS yang sudah selesai dilewati.")
            ->with('krs_finalized', true);
    }

    public function generateSchedules(
        Request $request,
        AcademicScheduleGeneratorService $generator
    ): RedirectResponse {
        $validated = $request->validate([
            'taka_id' => ['required', 'integer', Rule::exists('tahun_akademiks', 'id')],
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*' => ['required', 'integer', 'distinct', 'between:0,6'],
            'day_starts_at' => ['required', 'date_format:H:i'],
            'day_ends_at' => ['required', 'date_format:H:i', 'after:day_starts_at'],
            'minutes_per_credit' => ['required', 'integer', 'between:30,60'],
            'gap_minutes' => ['required', 'integer', 'between:0,60'],
            'dry_run' => ['nullable', 'boolean'],
            'excluded_offering_ids' => ['nullable', 'array'],
            'excluded_offering_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('penawaran_mata_kuliahs', 'id')->where(
                    fn ($query) => $query->where('taka_id', $request->integer('taka_id'))
                ),
            ],
        ], [
            'taka_id.required' => 'Periode akademik wajib dipilih.',
            'taka_id.exists' => 'Periode akademik yang dipilih tidak tersedia.',
            'days.required' => 'Pilih minimal satu hari kuliah.',
            'days.min' => 'Pilih minimal satu hari kuliah.',
            'days.*.distinct' => 'Hari kuliah tidak boleh duplikat.',
            'day_ends_at.after' => 'Jam selesai operasional harus setelah jam mulai.',
            'minutes_per_credit.between' => 'Durasi per SKS harus antara 30 sampai 60 menit.',
            'gap_minutes.between' => 'Jeda antarjadwal harus antara 0 sampai 60 menit.',
        ]);

        $period = TahunAkademik::findOrFail($validated['taka_id']);
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'taka_id' => 'Jadwal pada periode yang ditutup atau diarsipkan tidak dapat dibuat.',
            ]);
        }

        $configurableOfferings = PenawaranMataKuliah::query()
            ->forAcademicPeriod($period)
            ->whereDoesntHave('jadwalMingguans');
        (clone $configurableOfferings)->update(['wajib_dijadwalkan' => true]);
        $excludedOfferingIds = array_map('intval', $validated['excluded_offering_ids'] ?? []);
        if ($excludedOfferingIds !== []) {
            (clone $configurableOfferings)
                ->whereIn('id', $excludedOfferingIds)
                ->update(['wajib_dijadwalkan' => false]);
        }

        $result = $generator->generate(
            $period,
            array_map('intval', $validated['days']),
            $validated['day_starts_at'],
            $validated['day_ends_at'],
            $validated['minutes_per_credit'],
            $validated['gap_minutes'],
            $request->boolean('dry_run')
        );
        session()->put(AcademicPeriodContext::SESSION_KEY, $period->id);

        $message = $request->boolean('dry_run')
            ? "Validasi berhasil: {$result['created']} jadwal dapat dibuat, {$result['skipped']} sudah terjadwal, dan ".count($excludedOfferingIds).' penawaran dikecualikan.'
            : "Generator selesai: {$result['created']} jadwal dibuat, {$result['skipped']} sudah terjadwal, dan ".count($excludedOfferingIds).' penawaran dikecualikan.';

        return redirect()
            ->route('web-admin.academic-preparation.index', ['step' => 7, 'taka_id' => $period->id])
            ->with('status', $message)
            ->with('schedules_generated', ! $request->boolean('dry_run'));
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
