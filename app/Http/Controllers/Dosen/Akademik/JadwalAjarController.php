<?php

namespace App\Http\Controllers\Dosen\Akademik;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\AbsensiMahasiswa;
use App\Models\FeedBack\FBPerkuliahan;
use App\Models\JadwalKuliah;
use App\Models\JadwalMingguan;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsItem;
use App\Models\PertemuanKuliah;
use App\Models\Ruang;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PDF;

class JadwalAjarController extends Controller
{
    public function index(Request $request, AcademicPeriodContext $context): View
    {
        $dosen = auth('dosen')->user();
        $period = $context->published();
        $baseQuery = JadwalMingguan::query()
            ->forAcademicPeriod($period)
            ->where('dosen_id', $dosen->id);
        $classIds = (clone $baseQuery)->distinct()->pluck('kelas_id');
        $roomIds = (clone $baseQuery)->distinct()->pluck('ruang_id');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kelas_id' => ['nullable', 'integer', Rule::in($classIds->all())],
            'ruang_id' => ['nullable', 'integer', Rule::in($roomIds->all())],
            'days_id' => ['nullable', 'integer', 'between:0,6'],
        ]);

        $schedules = (clone $baseQuery)
            ->with(['penawaranMataKuliah.masterMataKuliah', 'kelas', 'ruang.gedung', 'pertemuans'])
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('penawaranMataKuliah', fn ($offering) => $offering
                            ->where('code', 'like', "%{$keyword}%")
                            ->orWhereHas('masterMataKuliah', fn ($course) => $course
                                ->where('name', 'like', "%{$keyword}%")
                                ->orWhere('code', 'like', "%{$keyword}%")))
                        ->orWhereHas('kelas', fn ($class) => $class
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['kelas_id'] ?? null, fn ($query, $classId) => $query->where('kelas_id', $classId))
            ->when($filters['ruang_id'] ?? null, fn ($query, $roomId) => $query->where('ruang_id', $roomId))
            ->when(isset($filters['days_id']) && $filters['days_id'] !== null, fn ($query) => $query->where('hari', $filters['days_id']))
            ->orderBy('hari')
            ->orderBy('mulai')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('dosen.pages.jadwal-index', [
            'web' => webSettings::where('id', 1)->first(),
            'jadkul' => $schedules,
            'filters' => $filters,
            'filterClasses' => Kelas::query()->whereIn('id', $classIds)->orderBy('name')->get(),
            'filterRooms' => Ruang::query()->whereIn('id', $roomIds)->orderBy('name')->get(),
            'selectedPeriod' => $period,
        ]);
    }

    public function viewAbsen(string $code, AcademicPeriodContext $context): View
    {
        $jadwal = $this->ownedActiveSchedule($code, $context);

        return view('dosen.pages.jadwal-absen', [
            'web' => webSettings::where('id', 1)->first(),
            'absen' => AbsensiMahasiswa::where('jadkul_code', $jadwal->code)->with('mahasiswa')->get(),
        ]);
    }

    public function downloadWeeklySchedule(AcademicPeriodContext $context)
    {
        $dosen = auth('dosen')->user();
        $period = $context->published();
        $schedules = JadwalMingguan::query()
            ->forAcademicPeriod($period)
            ->where('dosen_id', $dosen->id)
            ->with(['penawaranMataKuliah.masterMataKuliah', 'kelas', 'ruang.gedung'])
            ->orderBy('hari')
            ->orderBy('mulai')
            ->get();
        $lecturerIdentifier = preg_replace('/[^A-Za-z0-9_-]+/', '-', $dosen->dsn_nidn ?: $dosen->id);
        $periodCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', $period?->code ?? 'periode-aktif');

        return PDF::loadView('base.cetak.cetak-jadwal-mingguan-dosen', [
            'lecturer' => $dosen,
            'period' => $period,
            'schedules' => $schedules,
            'web' => webSettings::query()->first(),
            'printedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download("jadwal-mingguan-dosen-{$lecturerIdentifier}-{$periodCode}.pdf");
    }

    public function meetings(string $scheduleCode, AcademicPeriodContext $context): View
    {
        $schedule = $this->ownedWeeklySchedule($scheduleCode, $context)
            ->load([
                'penawaranMataKuliah.masterMataKuliah',
                'kelas',
                'ruang.gedung',
                'pertemuans' => fn ($query) => $query
                    ->with('legacyJadwalKuliah')
                    ->withCount('absensis')
                    ->orderBy('pertemuan_ke'),
            ]);

        return view('dosen.pages.jadwal-pertemuan-index', [
            'web' => webSettings::query()->first(),
            'schedule' => $schedule,
            'selectedPeriod' => $context->published(),
        ]);
    }

    public function meetingAttendance(
        string $scheduleCode,
        string $meetingCode,
        AcademicPeriodContext $context
    ): View {
        [$schedule, $meeting] = $this->ownedWeeklyMeeting($scheduleCode, $meetingCode, $context);
        $participants = $this->approvedParticipants($schedule);
        $attendances = AbsensiMahasiswa::query()
            ->where('pertemuan_kuliah_id', $meeting->id)
            ->whereIn('author_id', $participants->pluck('student.id'))
            ->get()
            ->keyBy('author_id');

        return view('dosen.pages.jadwal-pertemuan-presensi', [
            'web' => webSettings::query()->first(),
            'schedule' => $schedule,
            'meeting' => $meeting,
            'participants' => $participants,
            'attendances' => $attendances,
            'attendanceStatuses' => [
                'H' => 'Hadir',
                'I' => 'Izin',
                'S' => 'Sakit',
                'A' => 'Alpa',
            ],
        ]);
    }

    public function updateMeetingAttendance(
        Request $request,
        string $scheduleCode,
        string $meetingCode,
        AcademicPeriodContext $context
    ): RedirectResponse {
        [$schedule, $meeting] = $this->ownedWeeklyMeeting($scheduleCode, $meetingCode, $context);
        $validated = $request->validate([
            'presences' => ['required', 'array', 'min:1'],
            'presences.*.status' => ['required', Rule::in(['H', 'I', 'S', 'A'])],
            'presences.*.description' => ['nullable', 'string', 'max:1000'],
        ], [
            'presences.required' => 'Data presensi mahasiswa wajib diisi.',
            'presences.*.status.required' => 'Status presensi setiap mahasiswa wajib dipilih.',
            'presences.*.status.in' => 'Status presensi mahasiswa tidak valid.',
            'presences.*.description.max' => 'Keterangan presensi maksimal 1000 karakter.',
        ]);
        $participants = $this->approvedParticipants($schedule)->keyBy('student.id');
        $submittedStudentIds = collect(array_keys($validated['presences']))->map(fn ($id) => (int) $id);
        if ($submittedStudentIds->diff($participants->keys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'presences' => 'Terdapat mahasiswa yang bukan peserta KRS pada pertemuan ini.',
            ]);
        }
        if ($participants->keys()->diff($submittedStudentIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'presences' => 'Status presensi seluruh peserta wajib diisi.',
            ]);
        }

        DB::transaction(function () use ($meeting, $participants, $validated): void {
            foreach ($validated['presences'] as $studentId => $presence) {
                $participant = $participants->get((int) $studentId);
                $attendance = AbsensiMahasiswa::query()->firstOrNew([
                    'pertemuan_kuliah_id' => $meeting->id,
                    'author_id' => (int) $studentId,
                ]);
                if (! $attendance->exists) {
                    $attendance->code = 'ABS-'.Str::upper(Str::random(12));
                    $attendance->absen_proof = 'dicatat-dosen';
                }
                $attendance->fill([
                    'krs_item_id' => $participant['krs_item']->id,
                    'jadkul_code' => $meeting->code,
                    'absen_type' => $presence['status'],
                    'absen_date' => $meeting->tanggal->toDateString(),
                    'absen_time' => now()->format('H:i:s'),
                    'absen_desc' => $presence['description'] ?? null,
                ])->save();
            }

            $meeting->update(['status' => PertemuanKuliah::STATUS_COMPLETED]);
        });

        return redirect()
            ->route('dosen.akademik.jadwal-meeting-attendance', [$schedule->code, $meeting->code])
            ->with('success', 'Presensi seluruh peserta berhasil disimpan.');
    }

    public function viewFeedBack(string $code, AcademicPeriodContext $context): View
    {
        $jadwal = $this->ownedActiveSchedule($code, $context)
            ->load(['matkul', 'kelas', 'ruang.gedung']);
        $evaluation = config('lecturer_evaluation');
        $feedbackQuery = FBPerkuliahan::query()->where('fb_jakul_code', $jadwal->code);
        $analyticsRows = (clone $feedbackQuery)->get(['fb_score', 'fb_answers', 'fb_average_score']);
        $structuredRows = $analyticsRows->filter(fn (FBPerkuliahan $item) => is_array(data_get($item->fb_answers, 'ratings')));
        $allRatingValues = $structuredRows
            ->flatMap(fn (FBPerkuliahan $item) => array_values(data_get($item->fb_answers, 'ratings', [])))
            ->filter(fn ($value) => is_numeric($value));
        $sectionStats = collect($evaluation['sections'])->map(function (array $section) use ($structuredRows): array {
            $questionStats = collect($section['questions'])->map(function (string $question, string $key) use ($structuredRows): array {
                $values = $structuredRows
                    ->map(fn (FBPerkuliahan $item) => data_get($item->fb_answers, 'ratings.'.$key))
                    ->filter(fn ($value) => is_numeric($value));

                return [
                    'question' => $question,
                    'average' => $values->isEmpty() ? null : round($values->average(), 2),
                    'responses' => $values->count(),
                ];
            });
            $values = $questionStats->pluck('average')->filter(fn ($value) => $value !== null);

            return [
                'title' => $section['title'],
                'average' => $values->isEmpty() ? null : round($values->average(), 2),
                'questions' => $questionStats,
            ];
        });

        return view('dosen.pages.jadwal-feedback', [
            'web' => webSettings::where('id', 1)->first(),
            'jadwal' => $jadwal,
            'feedback' => (clone $feedbackQuery)->latest()->paginate(8)->withQueryString(),
            'evaluation' => $evaluation,
            'analytics' => [
                'total' => $analyticsRows->count(),
                'structured' => $structuredRows->count(),
                'average' => $allRatingValues->isEmpty() ? null : round($allRatingValues->average(), 2),
                'distribution' => collect(['Tidak Puas', 'Cukup Puas', 'Sangat Puas'])
                    ->mapWithKeys(fn (string $score) => [$score => $analyticsRows->where('fb_score', $score)->count()]),
                'sections' => $sectionStats,
            ],
        ]);
    }

    public function updateAbsen(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->published();
        abort_unless($period?->isWritable(), 404);

        $validated = $request->validate(['absen_desc' => ['nullable', 'string', 'max:4000']]);
        $dosen = auth('dosen')->user();
        $absen = AbsensiMahasiswa::query()
            ->where('code', $code)
            ->whereHas('jadkul', fn ($query) => $query
                ->forAcademicPeriod($period)
                ->forLecturer($dosen->id))
            ->firstOrFail();
        $absen->update($validated);

        Alert::success('Berhasil', 'Keterangan absensi berhasil diperbarui.');

        return back();
    }

    private function ownedActiveSchedule(string $code, AcademicPeriodContext $context): JadwalKuliah
    {
        $dosen = auth('dosen')->user();

        return JadwalKuliah::query()
            ->forAcademicPeriod($context->published())
            ->forLecturer($dosen->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function ownedWeeklySchedule(string $code, AcademicPeriodContext $context): JadwalMingguan
    {
        $dosen = auth('dosen')->user();

        return JadwalMingguan::query()
            ->forAcademicPeriod($context->published())
            ->where('dosen_id', $dosen->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function ownedWeeklyMeeting(
        string $scheduleCode,
        string $meetingCode,
        AcademicPeriodContext $context
    ): array {
        $schedule = $this->ownedWeeklySchedule($scheduleCode, $context)
            ->load(['penawaranMataKuliah.masterMataKuliah', 'kelas', 'ruang.gedung']);
        $meeting = $schedule->pertemuans()->where('code', $meetingCode)->firstOrFail();

        return [$schedule, $meeting];
    }

    private function approvedParticipants(JadwalMingguan $schedule)
    {
        return KrsItem::query()
            ->where('penawaran_mata_kuliah_id', $schedule->penawaran_mata_kuliah_id)
            ->whereHas('krs', fn ($query) => $query->whereIn('status', [Krs::STATUS_APPROVED, Krs::STATUS_LOCKED]))
            ->with('krs.registrasiMahasiswa.mahasiswa')
            ->get()
            ->map(fn (KrsItem $item) => [
                'krs_item' => $item,
                'student' => $item->krs->registrasiMahasiswa->mahasiswa,
            ])
            ->filter(fn (array $participant) => $participant['student'] !== null)
            ->sortBy('student.mhs_name')
            ->values();
    }
}
