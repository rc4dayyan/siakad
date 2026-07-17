<?php

namespace App\Http\Controllers\Dosen\Akademik;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\HasilStudi;
use App\Models\JadwalKuliah;
use App\Models\Settings\webSettings;
use App\Models\studentScore;
use App\Models\studentTask;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Str;

class StudentTaskController extends Controller
{
    public function index(AcademicPeriodContext $context): View
    {
        return view('dosen.pages.student-task-index', $this->pageData($context));
    }

    public function create(AcademicPeriodContext $context): View
    {
        $data = $this->pageData($context);
        $data['stask'] = $this->taskQuery($context)->latest()->paginate(5);

        return view('dosen.pages.student-task-create', $data);
    }

    public function view(string $code, AcademicPeriodContext $context): View
    {
        $task = $this->ownedActiveTask($code, $context);

        return view('dosen.pages.student-task-view', [
            ...$this->pageData($context),
            'task' => $task,
            'score' => studentScore::where('stask_id', $task->id)->with('student')->get(),
        ]);
    }

    public function viewDetail(string $code, AcademicPeriodContext $context): View
    {
        return view('dosen.pages.student-task-view-score', [
            'web' => webSettings::where('id', 1)->first(),
            'stask' => $this->taskQuery($context)->latest()->paginate(5),
            'score' => $this->ownedActiveScore($code, $context),
        ]);
    }

    public function edit(string $code, AcademicPeriodContext $context): View
    {
        return view('dosen.pages.student-task-edit', [
            ...$this->pageData($context),
            'task' => $this->ownedActiveTask($code, $context),
        ]);
    }

    public function store(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->published();
        abort_unless($period?->isWritable(), 404);
        $validated = $this->validateTask($request, $context);

        studentTask::create([
            ...$validated,
            'dosen_id' => auth('dosen')->id(),
            'code' => Str::random(6),
        ]);

        Alert::success('Berhasil', 'Tugas berhasil ditambahkan.');

        return back();
    }

    public function update(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->published();
        abort_unless($period?->isWritable(), 404);
        $this->ownedActiveTask($code, $context)->update($this->validateTask($request, $context));

        Alert::success('Berhasil', 'Tugas berhasil diperbarui.');

        return back();
    }

    public function updateScore(string $code, Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->published();
        abort_unless($period?->isWritable(), 404);
        $validated = $request->validate(['score' => ['required', 'integer', 'between:0,10']]);

        DB::transaction(function () use ($code, $context, $period, $validated): void {
            $score = $this->ownedActiveScore($code, $context, true);

            if ($score->score !== null) {
                throw ValidationException::withMessages([
                    'score' => 'Nilai tugas yang sudah disimpan memerlukan prosedur koreksi khusus.',
                ]);
            }

            $score->update(['score' => $validated['score']]);
            $hasilStudi = HasilStudi::firstOrCreate([
                'student_id' => $score->student_id,
                'taka_id' => $period->id,
            ], [
                'smt_id' => $period->raw_semester,
                'code' => Str::random(6),
            ]);
            $hasilStudi->increment('score_tugas', $validated['score']);
            $hasilStudi->increment('max_tugas');
        });

        Alert::success('Berhasil', 'Nilai tugas berhasil disimpan.');

        return back();
    }

    public function destroy(string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->published();
        abort_unless($period?->isWritable(), 404);
        $this->ownedActiveTask($code, $context)->delete();

        Alert::success('Berhasil', 'Tugas berhasil dihapus.');

        return back();
    }

    private function pageData(AcademicPeriodContext $context): array
    {
        return [
            'web' => webSettings::where('id', 1)->first(),
            'jadkul' => JadwalKuliah::query()
                ->forAcademicPeriod($context->published())
                ->forLecturer(auth('dosen')->id())
                ->get(),
            'stask' => $this->taskQuery($context)->get(),
        ];
    }

    private function taskQuery(AcademicPeriodContext $context)
    {
        return studentTask::query()
            ->forAcademicPeriod($context->published())
            ->forLecturer(auth('dosen')->id());
    }

    private function ownedActiveTask(string $code, AcademicPeriodContext $context): studentTask
    {
        return $this->taskQuery($context)->where('code', $code)->firstOrFail();
    }

    private function ownedActiveScore(string $code, AcademicPeriodContext $context, bool $lock = false): studentScore
    {
        return studentScore::query()
            ->forAcademicPeriod($context->published())
            ->forLecturer(auth('dosen')->id())
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->where('code', $code)
            ->with(['student', 'task.jadkul'])
            ->firstOrFail();
    }

    private function validateTask(Request $request, AcademicPeriodContext $context): array
    {
        $allowedScheduleIds = JadwalKuliah::query()
            ->forAcademicPeriod($context->published())
            ->forLecturer(auth('dosen')->id())
            ->pluck('id');

        return $request->validate([
            'jadkul_id' => ['required', 'integer', Rule::in($allowedScheduleIds)],
            'exp_date' => ['required', 'date'],
            'exp_time' => ['required', 'date_format:H:i'],
            'title' => ['required', 'string', 'max:255'],
            'detail_task' => ['required', 'string'],
        ], [
            'jadkul_id.in' => 'Jadwal harus berasal dari periode aktif dan diampu oleh dosen yang sedang masuk.',
        ]);
    }
}
