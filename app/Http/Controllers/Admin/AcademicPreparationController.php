<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\JadwalMingguan;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Kurikulum;
use App\Models\PenawaranMataKuliah;
use App\Models\PertemuanKuliah;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AcademicScheduleGeneratorService;
use App\Services\Academic\AdminKrsManagementService;
use App\Services\Academic\MeetingGeneratorService;
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
            'meetings' => $selectedPeriodId
                ? PertemuanKuliah::query()
                    ->whereHas('jadwalMingguan', fn ($query) => $query->forAcademicPeriod($selectedPeriodId))
                    ->count()
                : 0,
            'unscheduled_offerings' => max(0, $requiredOfferingCount - $scheduledOfferingCount),
            'rooms' => $availableRoomCount,
            'estimated_rooms' => $defaultRequiredRooms,
            'room_shortage' => max(0, $defaultRequiredRooms - $availableRoomCount),
            'largest_capacity' => $largestClassCapacity,
            'adequate_rooms' => $largestClassCapacity > 0
                ? Ruang::query()->whereIn('type', [0, 1])->where('kapasitas', '>=', $largestClassCapacity)->count()
                : $availableRoomCount,
        ];
        $advisorSummary = [
            'registrations' => 0,
            'without_class' => 0,
            'without_advisor' => 0,
            'not_synchronized' => 0,
            'classes_without_advisor' => 0,
        ];
        if ($selectedPeriodId) {
            $periodRegistrations = RegistrasiMahasiswa::query()->forAcademicPeriod($selectedPeriodId);
            $advisorSummary = [
                'registrations' => (clone $periodRegistrations)->count(),
                'without_class' => (clone $periodRegistrations)->whereNull('kelas_id')->count(),
                'without_advisor' => (clone $periodRegistrations)->whereNull('dosen_wali_id')->count(),
                'not_synchronized' => RegistrasiMahasiswa::query()
                    ->where('registrasi_mahasiswas.taka_id', $selectedPeriodId)
                    ->join('kelas', 'kelas.id', '=', 'registrasi_mahasiswas.kelas_id')
                    ->where(fn ($query) => $query
                        ->whereNull('registrasi_mahasiswas.dosen_wali_id')
                        ->orWhereColumn('registrasi_mahasiswas.dosen_wali_id', '!=', 'kelas.dosen_id'))
                    ->count(),
                'classes_without_advisor' => Kelas::query()
                    ->forAcademicPeriod($selectedPeriodId)
                    ->whereNull('dosen_id')
                    ->count(),
            ];
        }
        $advisorClasses = $selectedPeriodId
            ? Kelas::query()->forAcademicPeriod($selectedPeriodId)->with('dosen')->orderBy('name')->get()
            : collect();
        $academicAdvisors = Dosen::query()->where('dsn_stat', 1)->orderBy('dsn_name')->get();

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
            'advisorSummary' => $advisorSummary,
            'advisorClasses' => $advisorClasses,
            'academicAdvisors' => $academicAdvisors,
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
        AcademicScheduleGeneratorService $generator,
        MeetingGeneratorService $meetingGenerator
    ): RedirectResponse {
        $validated = $request->validate([
            'taka_id' => ['required', 'integer', Rule::exists('tahun_akademiks', 'id')],
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*' => ['required', 'integer', 'distinct', 'between:0,6'],
            'day_starts_at' => ['required', 'date_format:H:i'],
            'day_ends_at' => ['required', 'date_format:H:i', 'after:day_starts_at'],
            'minutes_per_credit' => ['required', 'integer', 'between:30,60'],
            'gap_minutes' => ['required', 'integer', 'between:0,60'],
            'meeting_count' => ['required', 'integer', 'between:1,20'],
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
            'meeting_count.between' => 'Jumlah pertemuan harus antara 1 sampai 20.',
        ]);

        $period = TahunAkademik::findOrFail($validated['taka_id']);
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'taka_id' => 'Jadwal pada periode yang ditutup atau diarsipkan tidak dapat dibuat.',
            ]);
        }
        if (! $period->starts_at || ! $period->ends_at) {
            throw ValidationException::withMessages([
                'taka_id' => 'Tanggal mulai dan selesai periode wajib dilengkapi sebelum membuat pertemuan.',
            ]);
        }

        $excludedOfferingIds = array_map('intval', $validated['excluded_offering_ids'] ?? []);
        $dryRun = $request->boolean('dry_run');
        DB::beginTransaction();
        try {
            $configurableOfferings = PenawaranMataKuliah::query()
                ->forAcademicPeriod($period)
                ->whereDoesntHave('jadwalMingguans');
            (clone $configurableOfferings)->update(['wajib_dijadwalkan' => true]);
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
                $dryRun
            );
            $meetingResult = $dryRun
                ? ['created' => 0, 'skipped' => 0, 'schedules' => 0]
                : $this->generateCompatibleMeetings($period, $validated['meeting_count'], $meetingGenerator);

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }
        session()->put(AcademicPeriodContext::SESSION_KEY, $period->id);

        $message = $dryRun
            ? "Validasi berhasil: {$result['created']} jadwal dapat dibuat, {$result['skipped']} sudah terjadwal, dan ".count($excludedOfferingIds).' penawaran dikecualikan. Pertemuan akan dibuat setelah jadwal disimpan.'
            : "Generator selesai: {$result['created']} jadwal dibuat; {$meetingResult['created']} pertemuan dan jadwal kompatibilitas dibuat, {$meetingResult['skipped']} pertemuan lama dilewati.";

        return redirect()
            ->route('web-admin.academic-preparation.index', ['step' => 7, 'taka_id' => $period->id])
            ->with('status', $message)
            ->with('schedules_generated', ! $dryRun);
    }

    public function generateScheduleMeetings(Request $request, MeetingGeneratorService $generator): RedirectResponse
    {
        $validated = $request->validate([
            'taka_id' => ['required', 'integer', Rule::exists('tahun_akademiks', 'id')],
            'meeting_count' => ['required', 'integer', 'between:1,20'],
            'confirmation' => ['accepted'],
        ], [
            'meeting_count.between' => 'Jumlah pertemuan harus antara 1 sampai 20.',
            'confirmation.accepted' => 'Konfirmasi pembuatan pertemuan wajib dicentang.',
        ]);
        $period = TahunAkademik::findOrFail($validated['taka_id']);
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'taka_id' => 'Pertemuan pada periode yang ditutup atau diarsipkan tidak dapat dibuat.',
            ]);
        }
        if (! $period->starts_at || ! $period->ends_at) {
            throw ValidationException::withMessages([
                'taka_id' => 'Tanggal mulai dan selesai periode wajib dilengkapi sebelum membuat pertemuan.',
            ]);
        }

        $result = DB::transaction(fn () => $this->generateCompatibleMeetings(
            $period,
            $validated['meeting_count'],
            $generator
        ));

        return redirect()
            ->route('web-admin.academic-preparation.index', ['step' => 7, 'taka_id' => $period->id])
            ->with('status', "Sinkronisasi selesai: {$result['created']} pertemuan dan jadwal lama dibuat untuk {$result['schedules']} jadwal mingguan; {$result['skipped']} pertemuan yang sudah ada dilewati.");
    }

    public function synchronizeAcademicAdvisors(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'taka_id' => ['required', 'integer', Rule::exists('tahun_akademiks', 'id')],
            'confirmation' => ['accepted'],
            'class_advisors' => ['required', 'array', 'min:1'],
            'class_advisors.*' => [
                'required',
                'integer',
                Rule::exists('dosens', 'id')->where('dsn_stat', 1),
            ],
        ], [
            'taka_id.required' => 'Periode akademik wajib dipilih.',
            'taka_id.exists' => 'Periode akademik yang dipilih tidak tersedia.',
            'confirmation.accepted' => 'Konfirmasi sinkronisasi dosen wali wajib dicentang.',
            'class_advisors.required' => 'Wali dosen setiap kelas wajib dipilih.',
            'class_advisors.*.required' => 'Wali dosen setiap kelas wajib dipilih.',
            'class_advisors.*.exists' => 'Dosen wali yang dipilih harus berstatus aktif.',
        ]);

        $period = TahunAkademik::findOrFail($validated['taka_id']);
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'taka_id' => 'Dosen wali pada periode yang ditutup atau diarsipkan tidak dapat disinkronkan.',
            ]);
        }

        $registrations = RegistrasiMahasiswa::query()->forAcademicPeriod($period)->get();
        $registrationsWithoutClass = $registrations->whereNull('kelas_id')->count();
        if ($registrationsWithoutClass > 0) {
            throw ValidationException::withMessages([
                'taka_id' => "Terdapat {$registrationsWithoutClass} registrasi yang belum memiliki kelas. Tentukan kelas terlebih dahulu.",
            ]);
        }

        $classIds = $registrations->pluck('kelas_id')->filter()->unique();
        $classes = Kelas::query()
            ->forAcademicPeriod($period)
            ->get()
            ->keyBy('id');
        $invalidClassIds = $classIds->diff($classes->keys());
        if ($invalidClassIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'taka_id' => 'Terdapat registrasi dengan kelas yang bukan bagian dari periode akademik ini.',
            ]);
        }

        $advisorAssignments = collect($validated['class_advisors'])
            ->mapWithKeys(fn ($advisorId, $classId) => [(int) $classId => (int) $advisorId]);
        if ($advisorAssignments->keys()->diff($classes->keys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'class_advisors' => 'Terdapat kelas yang bukan bagian dari periode akademik ini.',
            ]);
        }
        $missingClassAssignments = $classes->keys()->diff($advisorAssignments->keys());
        if ($missingClassAssignments->isNotEmpty()) {
            $classNames = $classes
                ->filter(fn (Kelas $class) => $missingClassAssignments->contains($class->id))
                ->pluck('name')
                ->take(5)
                ->implode(', ');

            throw ValidationException::withMessages([
                'class_advisors' => "Wali dosen belum dipilih untuk kelas {$classNames}.",
            ]);
        }

        [$updatedClasses, $updatedRegistrations] = DB::transaction(function () use ($advisorAssignments, $classes, $period): array {
            $updatedClasses = 0;
            $updatedRegistrations = 0;

            foreach ($classes as $class) {
                $advisorId = $advisorAssignments->get($class->id);
                if ((int) $class->dosen_id !== $advisorId) {
                    $class->update(['dosen_id' => $advisorId]);
                    $updatedClasses++;
                }

                $updatedRegistrations += RegistrasiMahasiswa::query()
                    ->forAcademicPeriod($period)
                    ->where('kelas_id', $class->id)
                    ->where(fn ($query) => $query
                        ->whereNull('dosen_wali_id')
                        ->orWhere('dosen_wali_id', '!=', $advisorId))
                    ->update(['dosen_wali_id' => $advisorId]);
            }

            return [$updatedClasses, $updatedRegistrations];
        });
        session()->put(AcademicPeriodContext::SESSION_KEY, $period->id);

        return redirect()
            ->route('web-admin.academic-preparation.index', ['step' => 7, 'taka_id' => $period->id])
            ->with('status', "Sinkronisasi selesai: wali dosen {$updatedClasses} kelas dan {$updatedRegistrations} registrasi mahasiswa diperbarui.")
            ->with('academic_advisors_synchronized', true);
    }

    private function legacySemesterValue(string $term): int
    {
        return match ($term) {
            TahunAkademik::TERM_GANJIL => 1,
            TahunAkademik::TERM_GENAP => 2,
            TahunAkademik::TERM_PENDEK => 0,
        };
    }

    private function generateCompatibleMeetings(
        TahunAkademik $period,
        int $meetingCount,
        MeetingGeneratorService $generator
    ): array {
        $result = ['created' => 0, 'skipped' => 0, 'schedules' => 0];
        $schedules = JadwalMingguan::query()
            ->forAcademicPeriod($period)
            ->whereHas('penawaranMataKuliah', fn ($query) => $query->where('wajib_dijadwalkan', true))
            ->with(['penawaranMataKuliah.masterMataKuliah'])
            ->orderBy('id')
            ->get();

        foreach ($schedules as $schedule) {
            $generated = $generator->generate(
                $schedule,
                $period->starts_at->toDateString(),
                $period->ends_at->toDateString(),
                $meetingCount
            );
            $result['created'] += $generated['created'];
            $result['skipped'] += $generated['skipped'];
            $result['schedules']++;
        }

        return $result;
    }
}
