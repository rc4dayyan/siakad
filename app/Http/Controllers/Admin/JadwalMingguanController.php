<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\JadwalMingguan;
use App\Models\KalenderAkademik;
use App\Models\PenawaranMataKuliah;
use App\Models\PertemuanKuliah;
use App\Models\ProgramStudi;
use App\Models\Ruang;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\MeetingGeneratorService;
use App\Services\Academic\ScheduleConflictService;
use App\Services\Academic\ScheduleNotificationService;
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

class JadwalMingguanController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $context): View
    {
        return view('user.admin.master.jadwal-mingguan-index', $this->formData($context, $request));
    }

    public function recap(AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        $meetings = PertemuanKuliah::query()
            ->whereHas('jadwalMingguan', fn ($query) => $query->forAcademicPeriod($period))
            ->with(['jadwalMingguan.penawaranMataKuliah.masterMataKuliah', 'jadwalMingguan.kelas', 'absensis.mahasiswa'])
            ->orderBy('tanggal')->orderBy('pertemuan_ke')->get();

        return view('base.cetak.cetak-rekap-presensi-periode', compact('period', 'meetings'));
    }

    public function printTimetable(Request $request, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent($request->user());
        $validated = $request->validate([
            'pstudi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
        ]);
        $program = isset($validated['pstudi_id'])
            ? ProgramStudi::findOrFail($validated['pstudi_id'])
            : ProgramStudi::query()->orderBy('name')->first();

        $schedules = JadwalMingguan::query()
            ->forAcademicPeriod($period)
            ->when($program, fn ($query) => $query->whereHas(
                'penawaranMataKuliah',
                fn ($offering) => $offering->where('pstudi_id', $program->id)
            ))
            ->with(['penawaranMataKuliah.masterMataKuliah', 'kelas', 'dosen', 'ruang.gedung'])
            ->orderBy('hari')
            ->orderBy('mulai')
            ->get();

        $semesters = $schedules
            ->pluck('penawaranMataKuliah.masterMataKuliah.semester')
            ->filter()
            ->map(fn ($semester) => (int) $semester)
            ->unique()
            ->sort()
            ->values();

        $days = $schedules->groupBy('hari')->map(function ($daySchedules, $day) use ($semesters): array {
            $slots = $daySchedules
                ->groupBy(fn (JadwalMingguan $schedule) => substr($schedule->mulai, 0, 5).'|'.substr($schedule->selesai, 0, 5))
                ->sortKeys()
                ->values();
            $rows = [];

            foreach ($slots as $index => $slotSchedules) {
                $first = $slotSchedules->first();
                $rows[] = [
                    'type' => 'schedule',
                    'start' => substr($first->mulai, 0, 5),
                    'end' => substr($first->selesai, 0, 5),
                    'cells' => $semesters->mapWithKeys(fn (int $semester) => [
                        $semester => $slotSchedules->filter(
                            fn (JadwalMingguan $schedule) => (int) $schedule->penawaranMataKuliah?->masterMataKuliah?->semester === $semester
                        )->values(),
                    ]),
                ];

                $next = $slots->get($index + 1)?->first();
                if ($next && substr($next->mulai, 0, 5) > substr($first->selesai, 0, 5)) {
                    $rows[] = [
                        'type' => 'break',
                        'start' => substr($first->selesai, 0, 5),
                        'end' => substr($next->mulai, 0, 5),
                    ];
                }
            }

            return [
                'day' => (int) $day,
                'label' => ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', "JUM'AT", 'SABTU'][(int) $day] ?? '-',
                'rows' => $rows,
            ];
        })->values();

        return view('base.cetak.cetak-jadwal-perkuliahan', [
            'period' => $period,
            'program' => $program,
            'semesters' => $semesters,
            'days' => $days,
            'web' => webSettings::query()->first(),
            'summary' => [
                'schedules' => $schedules->count(),
                'classes' => $schedules->pluck('kelas_id')->filter()->unique()->count(),
                'lecturers' => $schedules->pluck('dosen_id')->filter()->unique()->count(),
                'rooms' => $schedules->pluck('ruang_id')->filter()->unique()->count(),
            ],
            'printedAt' => now(),
        ]);
    }

    public function store(Request $request, AcademicPeriodContext $context, ScheduleConflictService $conflicts): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        [$offering, $data] = $this->validatedSchedule($request, $period->id);
        $override = $conflicts->validate(
            $offering,
            $data,
            actor: $request->user(),
            override: $request->boolean('override_conflict'),
            reason: $request->string('alasan_pengecualian')->toString()
        );
        JadwalMingguan::create([
            ...$data,
            ...$override,
            'code' => 'JMG-'.Str::upper(Str::random(12)),
            'fingerprint' => JadwalMingguan::fingerprint($data),
        ]);

        return back()->with('success', 'Jadwal mingguan berhasil dibuat.');
    }

    public function export(Request $request, AcademicPeriodContext $context)
    {
        $period = $context->requireCurrent($request->user());
        $schedules = $this->applyScheduleFilters(
            JadwalMingguan::query()->forAcademicPeriod($period),
            $this->scheduleFilters($request)
        )
            ->with(['penawaranMataKuliah.masterMataKuliah', 'kelas', 'dosen', 'ruang'])
            ->orderBy('hari')
            ->orderBy('mulai')
            ->get();

        return (new FastExcel($schedules))->download(
            'jadwal-mingguan-'.$period->code.'-'.now()->format('Ymd-His').'.xlsx',
            fn (JadwalMingguan $schedule) => [
                'Kode Jadwal' => $schedule->code,
                'Kode Periode' => $period->code,
                'Kode Penawaran' => $schedule->penawaranMataKuliah?->code,
                'Nama Mata Kuliah' => $schedule->penawaranMataKuliah?->masterMataKuliah?->name,
                'Kode Kelas' => $schedule->kelas?->code,
                'Nama Kelas' => $schedule->kelas?->name,
                'NIDN Dosen' => $schedule->dosen?->dsn_nidn,
                'Nama Dosen' => $schedule->dosen?->dsn_name,
                'Kode Ruang' => $schedule->ruang?->code,
                'Nama Ruang' => $schedule->ruang?->name,
                'Hari' => $schedule->hari_label,
                'Jam Mulai' => substr($schedule->mulai, 0, 5),
                'Jam Selesai' => substr($schedule->selesai, 0, 5),
                'Alasan Pengecualian' => $schedule->alasan_pengecualian,
            ]
        );
    }

    public function import(
        Request $request,
        AcademicPeriodContext $context,
        ScheduleConflictService $conflicts
    ): RedirectResponse {
        $period = $context->requireWritableCurrent($request->user());
        $request->validate([
            '_form' => ['required', 'in:import-weekly-schedules'],
            'import' => [
                'required',
                'file',
                'extensions:xlsx,csv',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-zip-compressed,text/csv,text/plain,application/csv',
                'max:5120',
            ],
        ], [
            'import.required' => 'File jadwal mingguan wajib diunggah.',
            'import.extensions' => 'File harus berformat XLSX atau CSV.',
            'import.mimetypes' => 'Isi file harus berupa XLSX atau CSV yang valid.',
            'import.max' => 'Ukuran file tidak boleh melebihi 5 MB.',
        ]);

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Gunakan hasil export jadwal mingguan berformat XLSX atau CSV.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $headers = [
            'Kode Jadwal', 'Kode Periode', 'Kode Penawaran', 'Nama Mata Kuliah',
            'Kode Kelas', 'Nama Kelas', 'NIDN Dosen', 'Nama Dosen', 'Kode Ruang',
            'Nama Ruang', 'Hari', 'Jam Mulai', 'Jam Selesai', 'Alasan Pengecualian',
        ];

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['import' => 'File import tidak berisi data jadwal mingguan.']);
        }

        $missingHeaders = array_diff($headers, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $prepared = [];

        foreach ($rows as $index => $row) {
            $prepared[] = $this->prepareImportedSchedule($row, $index + 2, $period->id, $period->code);
        }

        $result = DB::transaction(function () use ($prepared, $request, $conflicts): array {
            $created = 0;
            $skipped = 0;

            foreach ($prepared as $attributes) {
                $rowNumber = $attributes['row_number'];
                $reason = $attributes['alasan_pengecualian'];
                unset($attributes['row_number'], $attributes['alasan_pengecualian']);

                $code = $attributes['code'];
                $fingerprint = JadwalMingguan::fingerprint($attributes);
                $byFingerprint = JadwalMingguan::where('fingerprint', $fingerprint)->first();
                $byCode = $code ? JadwalMingguan::where('code', $code)->first() : null;

                if ($byFingerprint && (! $byCode || $byCode->is($byFingerprint))) {
                    $skipped++;

                    continue;
                }

                if ($byCode) {
                    $this->rejectImportRow(
                        $rowNumber,
                        "Kode Jadwal {$code} sudah digunakan oleh jadwal dengan data berbeda."
                    );
                }

                $offering = PenawaranMataKuliah::findOrFail($attributes['penawaran_mata_kuliah_id']);
                $override = $conflicts->validate(
                    $offering,
                    $attributes,
                    actor: $request->user(),
                    override: $reason !== '',
                    reason: $reason
                );

                JadwalMingguan::create([
                    ...$attributes,
                    ...$override,
                    'code' => $code ?: 'JMG-'.Str::upper(Str::random(12)),
                    'fingerprint' => $fingerprint,
                ]);
                $created++;
            }

            return compact('created', 'skipped');
        });

        return back()->with(
            'success',
            "Import selesai: {$result['created']} jadwal dibuat dan {$result['skipped']} duplikat dilewati."
        );
    }

    public function storeHoliday(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'mulai_at' => ['required', 'date'],
            'selesai_at' => ['required', 'date', 'after_or_equal:mulai_at'],
        ]);
        KalenderAkademik::create([
            'taka_id' => $period->id,
            'kategori' => 'libur',
            'dipublikasikan' => true,
            ...$data,
        ]);

        return back()->with('success', 'Hari libur kalender akademik berhasil ditambahkan.');
    }

    public function update(
        Request $request,
        JadwalMingguan $jadwal,
        AcademicPeriodContext $context,
        ScheduleConflictService $conflicts,
        ScheduleNotificationService $notifications
    ): RedirectResponse {
        $period = $context->requireWritableCurrent($request->user());
        abort_unless((int) $jadwal->penawaranMataKuliah?->taka_id === $period->id, 404);
        [$offering, $data] = $this->validatedSchedule($request, $period->id);
        $override = $conflicts->validate(
            $offering,
            $data,
            $jadwal,
            $request->user(),
            $request->boolean('override_conflict'),
            $request->string('alasan_pengecualian')->toString()
        );
        $before = $jadwal->only(['hari', 'mulai', 'selesai', 'ruang_id', 'dosen_id']);
        $jadwal->update([...$data, ...$override, 'fingerprint' => JadwalMingguan::fingerprint($data)]);

        if ($before !== $jadwal->fresh()->only(array_keys($before))) {
            $notifications->changed(
                $jadwal->fresh(['penawaranMataKuliah']),
                "Jadwal {$offering->masterMataKuliah->name} berubah menjadi {$jadwal->hari_label}, {$jadwal->mulai}-{$jadwal->selesai}."
            );
        }

        return back()->with('success', 'Jadwal mingguan berhasil diperbarui tanpa mengubah riwayat pertemuan.');
    }

    public function destroy(JadwalMingguan $jadwal, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent(auth()->user());
        abort_unless((int) $jadwal->penawaranMataKuliah?->taka_id === $period->id, 404);
        if ($jadwal->pertemuans()->exists()) {
            throw ValidationException::withMessages(['jadwal' => 'Jadwal yang sudah memiliki pertemuan tidak dapat dihapus.']);
        }
        $jadwal->delete();

        return back()->with('success', 'Jadwal mingguan berhasil dihapus.');
    }

    public function preview(Request $request, JadwalMingguan $jadwal, AcademicPeriodContext $context, MeetingGeneratorService $generator): View
    {
        $period = $context->requireCurrent($request->user());
        abort_unless((int) $jadwal->penawaranMataKuliah?->taka_id === $period->id, 404);
        $request->mergeIfMissing([
            'mulai_tanggal' => $period->starts_at?->toDateString(),
            'selesai_tanggal' => $period->ends_at?->toDateString(),
            'jumlah_pertemuan' => 16,
        ]);
        $data = $this->validateGeneration($request, $period->id);
        $viewData = $this->formData($context, $request);
        $viewData['preview'] = $generator->preview($jadwal, $data['mulai_tanggal'], $data['selesai_tanggal'], $data['jumlah_pertemuan']);
        $viewData['previewSchedule'] = $jadwal;
        $viewData['generationInput'] = $data;

        return view('user.admin.master.jadwal-mingguan-index', $viewData);
    }

    public function generate(Request $request, JadwalMingguan $jadwal, AcademicPeriodContext $context, MeetingGeneratorService $generator): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        abort_unless((int) $jadwal->penawaranMataKuliah?->taka_id === $period->id, 404);
        $data = $this->validateGeneration($request, $period->id);
        $result = $generator->generate($jadwal, $data['mulai_tanggal'], $data['selesai_tanggal'], $data['jumlah_pertemuan']);

        return back()->with('success', "Generator selesai: {$result['created']} pertemuan dibuat dan {$result['skipped']} duplikat dilewati.");
    }

    private function formData(AcademicPeriodContext $context, Request $request): array
    {
        $period = $context->current(auth()->user());
        $filters = $this->scheduleFilters($request);
        $offerings = PenawaranMataKuliah::query()->forAcademicPeriod($period)
            ->with(['masterMataKuliah', 'kelas', 'dosenUtama'])->orderBy('code')->get();
        $preferredOffering = $offerings->firstWhere('id', $request->integer('penawaran_id'));

        return [
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'canManage' => $period?->isWritable() ?? false,
            'schedules' => $this->applyScheduleFilters(
                JadwalMingguan::query()->forAcademicPeriod($period),
                $filters
            )
                ->with(['penawaranMataKuliah.masterMataKuliah', 'kelas', 'dosen', 'ruang', 'pertemuans'])
                ->orderBy('hari')->orderBy('mulai')->get(),
            'offerings' => $offerings,
            'preferredOffering' => $preferredOffering,
            'openCreateModal' => $request->boolean('buat') && $preferredOffering && ($period?->isWritable() ?? false),
            'filterClasses' => $offerings->pluck('kelas')->filter()->unique('id')->sortBy('name')->values(),
            'lecturers' => Dosen::query()->orderBy('dsn_name')->get(),
            'rooms' => Ruang::query()->orderBy('name')->get(),
            'studyPrograms' => ProgramStudi::query()->orderBy('name')->get(),
            'holidays' => $period ? KalenderAkademik::query()->where('taka_id', $period->id)
                ->where('kategori', 'libur')->orderBy('mulai_at')->get() : collect(),
            'preview' => null,
            'previewSchedule' => null,
            'generationInput' => null,
            'filters' => $filters,
        ];
    }

    private function scheduleFilters(Request $request): array
    {
        $day = $request->filled('hari') ? $request->integer('hari') : null;

        return [
            'q' => trim((string) $request->query('q', '')),
            'pstudi_id' => $request->integer('pstudi_id') ?: null,
            'kelas_id' => $request->integer('kelas_id') ?: null,
            'dosen_id' => $request->integer('dosen_id') ?: null,
            'ruang_id' => $request->integer('ruang_id') ?: null,
            'hari' => $day !== null && $day >= 0 && $day <= 6 ? $day : null,
        ];
    }

    private function applyScheduleFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'], fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                $query->where('code', 'like', '%'.$search.'%')
                    ->orWhereHas('penawaranMataKuliah', fn (Builder $offering) => $offering
                        ->where('code', 'like', '%'.$search.'%')
                        ->orWhereHas('masterMataKuliah', fn (Builder $master) => $master
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('code', 'like', '%'.$search.'%')))
                    ->orWhereHas('kelas', fn (Builder $class) => $class->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('dosen', fn (Builder $lecturer) => $lecturer->where('dsn_name', 'like', '%'.$search.'%'))
                    ->orWhereHas('ruang', fn (Builder $room) => $room->where('name', 'like', '%'.$search.'%'));
            }))
            ->when($filters['pstudi_id'], fn (Builder $query, int $programId) => $query->whereHas(
                'penawaranMataKuliah',
                fn (Builder $offering) => $offering->where('pstudi_id', $programId)
            ))
            ->when($filters['kelas_id'], fn (Builder $query, int $classId) => $query->where('kelas_id', $classId))
            ->when($filters['dosen_id'], fn (Builder $query, int $lecturerId) => $query->where('dosen_id', $lecturerId))
            ->when($filters['ruang_id'], fn (Builder $query, int $roomId) => $query->where('ruang_id', $roomId))
            ->when($filters['hari'] !== null, fn (Builder $query) => $query->where('hari', $filters['hari']));
    }

    private function validatedSchedule(Request $request, int $periodId): array
    {
        $data = $request->validate([
            'penawaran_mata_kuliah_id' => ['required', Rule::exists('penawaran_mata_kuliahs', 'id')->where(fn ($query) => $query->where('taka_id', $periodId))],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'dosen_id' => ['required', 'exists:dosens,id'],
            'ruang_id' => ['required', 'exists:ruangs,id'],
            'hari' => ['required', 'integer', 'between:0,6'],
            'mulai' => ['required', 'date_format:H:i'],
            'selesai' => ['required', 'date_format:H:i', 'after:mulai'],
        ]);
        $offering = PenawaranMataKuliah::with('masterMataKuliah')->findOrFail($data['penawaran_mata_kuliah_id']);
        if ((int) $offering->kelas_id !== (int) $data['kelas_id']) {
            throw ValidationException::withMessages(['kelas_id' => 'Kelas harus sama dengan kelas pada penawaran mata kuliah.']);
        }
        $lecturers = array_map('intval', array_filter([
            $offering->dosen_utama_id, $offering->dosen_pendamping_1_id, $offering->dosen_pendamping_2_id,
        ]));
        if (! in_array((int) $data['dosen_id'], $lecturers, true)) {
            throw ValidationException::withMessages(['dosen_id' => 'Dosen harus merupakan pengampu pada penawaran mata kuliah.']);
        }

        return [$offering, $data];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function prepareImportedSchedule(array $row, int $rowNumber, int $periodId, string $periodCode): array
    {
        if ($this->importedText($row['Kode Periode'] ?? null) !== $periodCode) {
            $this->rejectImportRow($rowNumber, "Kode Periode harus {$periodCode}.");
        }

        $offeringCode = $this->importedText($row['Kode Penawaran'] ?? null);
        $offering = PenawaranMataKuliah::query()
            ->where('taka_id', $periodId)
            ->where('code', $offeringCode)
            ->first();

        if (! $offering) {
            $this->rejectImportRow($rowNumber, "Kode Penawaran {$offeringCode} tidak ditemukan pada periode aktif.");
        }

        $classCode = $this->importedText($row['Kode Kelas'] ?? null);

        if ($classCode === '' || $offering->kelas?->code !== $classCode) {
            $this->rejectImportRow($rowNumber, "Kode Kelas {$classCode} tidak sesuai dengan penawaran.");
        }

        $nidn = $this->importedText($row['NIDN Dosen'] ?? null);
        $lecturer = Dosen::query()->where('dsn_nidn', $nidn)->first();
        $offeringLecturers = array_map('intval', array_filter([
            $offering->dosen_utama_id,
            $offering->dosen_pendamping_1_id,
            $offering->dosen_pendamping_2_id,
        ]));

        if (! $lecturer || ! in_array($lecturer->id, $offeringLecturers, true)) {
            $this->rejectImportRow($rowNumber, "NIDN Dosen {$nidn} bukan pengampu pada penawaran.");
        }

        $roomCode = $this->importedText($row['Kode Ruang'] ?? null);
        $room = Ruang::query()->where('code', $roomCode)->first();

        if (! $room) {
            $this->rejectImportRow($rowNumber, "Kode Ruang {$roomCode} tidak ditemukan.");
        }

        $start = $this->importedTime($row['Jam Mulai'] ?? null, $rowNumber, 'Jam Mulai');
        $end = $this->importedTime($row['Jam Selesai'] ?? null, $rowNumber, 'Jam Selesai');

        if ($end <= $start) {
            $this->rejectImportRow($rowNumber, 'Jam Selesai harus setelah Jam Mulai.');
        }

        $code = $this->importedText($row['Kode Jadwal'] ?? null);
        $reason = $this->importedText($row['Alasan Pengecualian'] ?? null);

        if (mb_strlen($code) > 255) {
            $this->rejectImportRow($rowNumber, 'Kode Jadwal maksimal 255 karakter.');
        }

        if ($reason !== '' && mb_strlen($reason) < 10) {
            $this->rejectImportRow($rowNumber, 'Alasan Pengecualian minimal 10 karakter jika diisi.');
        }

        return [
            'row_number' => $rowNumber,
            'penawaran_mata_kuliah_id' => $offering->id,
            'kelas_id' => $offering->kelas_id,
            'dosen_id' => $lecturer->id,
            'ruang_id' => $room->id,
            'hari' => $this->importedDay($row['Hari'] ?? null, $rowNumber),
            'mulai' => $start,
            'selesai' => $end,
            'code' => $code === '' ? null : $code,
            'alasan_pengecualian' => $reason,
        ];
    }

    private function importedDay(mixed $value, int $rowNumber): int
    {
        $day = Str::lower($this->importedText($value));
        $days = [
            'minggu' => 0, 'ahad' => 0, 'senin' => 1, 'selasa' => 2, 'rabu' => 3,
            'kamis' => 4, "jum'at" => 5, 'jumat' => 5, 'jum’at' => 5, 'sabtu' => 6,
        ];

        if (isset($days[$day])) {
            return $days[$day];
        }

        $integer = filter_var($day, FILTER_VALIDATE_INT);

        if ($integer === false || $integer < 0 || $integer > 6) {
            $this->rejectImportRow($rowNumber, 'Hari harus berupa nama hari atau angka 0 sampai 6.');
        }

        return $integer;
    }

    private function importedTime(mixed $value, int $rowNumber, string $column): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        if (is_numeric($value) && (float) $value >= 0 && (float) $value < 1) {
            $minutes = (int) round((float) $value * 1440) % 1440;

            return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }

        $time = $this->importedText($value);

        if (! preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d(?::[0-5]\\d)?$/', $time)) {
            $this->rejectImportRow($rowNumber, "{$column} harus menggunakan format HH:MM.");
        }

        return substr($time, 0, 5);
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

    private function validateGeneration(Request $request, int $periodId): array
    {
        return $request->validate([
            'mulai_tanggal' => ['required', 'date'],
            'selesai_tanggal' => ['required', 'date', 'after_or_equal:mulai_tanggal'],
            'jumlah_pertemuan' => ['required', 'integer', 'between:1,20'],
        ]);
    }
}
