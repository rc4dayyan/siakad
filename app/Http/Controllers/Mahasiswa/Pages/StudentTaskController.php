<?php

namespace App\Http\Controllers\Mahasiswa\Pages;

use Alert;
use App\Http\Controllers\Controller;
// SECTION ADDONS SYSTEM
use App\Models\HasilStudi;
use App\Models\Settings\webSettings;
use App\Models\studentScore;
// SECTION ADDONS EXTERNAL
use App\Models\studentTask;
// SECTION MODELS
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\StudentAcademicContext;
use Auth;
use Carbon\Carbon;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Str;

class StudentTaskController extends Controller
{
    public function index(
        Request $request,
        AcademicPeriodContext $periodContext,
        StudentAcademicContext $studentContext
    ) {
        $user = Auth::guard('mahasiswa')->user();
        $period = $periodContext->published();
        $classId = $studentContext->classIdFor($user, $period);
        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => in_array($request->query('status'), ['aktif', 'segera', 'terlambat', 'dikumpulkan'], true)
                ? $request->query('status')
                : null,
        ];
        $data['web'] = webSettings::where('id', 1)->first();
        $tasks = StudentTask::query()
            ->forAcademicPeriod($period)
            ->when($classId, fn ($query) => $query->whereHas('jadkul', fn ($schedule) => $schedule->where('kelas_id', $classId)))
            ->when(! $classId, fn ($query) => $query->whereRaw('1 = 0'))
            ->with(['dosen', 'jadkul.matkul'])
            ->get();
        $submittedTaskIds = studentScore::query()
            ->where('student_id', $user->id)
            ->whereIn('stask_id', $tasks->modelKeys())
            ->pluck('stask_id')
            ->flip();

        $tasks->each(function (studentTask $task) use ($submittedTaskIds): void {
            $deadline = Carbon::parse($task->exp_date.' '.$task->exp_time);
            $status = match (true) {
                $submittedTaskIds->has($task->id) => 'dikumpulkan',
                $deadline->isPast() => 'terlambat',
                now()->diffInHours($deadline, false) <= 72 => 'segera',
                default => 'aktif',
            };

            $task->setAttribute('deadline_at', $deadline);
            $task->setAttribute('student_status', $status);
        });

        $data['taskSummary'] = [
            'total' => $tasks->count(),
            'aktif' => $tasks->whereIn('student_status', ['aktif', 'segera'])->count(),
            'terlambat' => $tasks->where('student_status', 'terlambat')->count(),
            'dikumpulkan' => $tasks->where('student_status', 'dikumpulkan')->count(),
        ];
        $data['stask'] = $tasks
            ->when($filters['q'], function ($items, $search) {
                $needle = mb_strtolower($search);

                return $items->filter(fn (studentTask $task) => str_contains(mb_strtolower(implode(' ', [
                    $task->title,
                    $task->dosen?->dsn_name,
                    $task->jadkul?->matkul?->name,
                ])), $needle));
            })
            ->when($filters['status'], fn ($items, $status) => $items->where('student_status', $status))
            ->sortBy('deadline_at')
            ->values();
        $data['filters'] = $filters;
        $data['period'] = $period;

        return view('mahasiswa.pages.stask-index', $data);
    }

    public function view($code, AcademicPeriodContext $periodContext, StudentAcademicContext $studentContext)
    {
        $user = Auth::guard('mahasiswa')->user();
        $data['stask'] = $this->taskForStudent($code, $user, $periodContext, $studentContext);
        $data['web'] = webSettings::where('id', 1)->first();
        $deadline = Carbon::parse($data['stask']->exp_date.' '.$data['stask']->exp_time)->locale('id');
        $purifierConfig = HTMLPurifier_Config::createDefault();
        $data['deadline'] = $deadline;
        $data['isOverdue'] = $deadline->isPast();
        $data['safeTaskDescription'] = (new HTMLPurifier($purifierConfig))->purify($data['stask']->detail_task);
        $hasSubmitted = studentScore::where('stask_id', $data['stask']->id)
            ->where('student_id', $user->id)
            ->exists();

        if ($hasSubmitted) {
            Alert::error('Error', 'Kamu sudah mengumpulkan tugas ini.');

            return back();
        } else {
            return view('mahasiswa.pages.stask-view', $data);
        }

    }

    public function store(
        Request $request,
        $code,
        AcademicPeriodContext $periodContext,
        StudentAcademicContext $studentContext
    ) {
        $request->validate([
            'desc' => 'required|string|max:10000',
            'file_1' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:20480',
            'file_2' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:20480',
            'file_3' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:20480',
            'file_4' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:20480',
            'file_5' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:20480',
            'file_6' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:20480',
            'file_7' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:20480',
            'file_8' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:20480',
        ], [
            'desc.required' => 'Deskripsi Jawaban tugas harus diisi.',
            'desc.max' => 'Deskripsi jawaban maksimal 10.000 karakter.',
            'file_1.required' => 'File 1 harus diunggah.',
            'file_1.mimes' => 'File 1 harus berupa file dokumen PDF, Word, Excel, atau gambar.',
            'file_1.max' => 'File 1 tidak boleh lebih dari 20 MB.',
            // Add similar messages for other files if needed
        ]);

        $user = Auth::guard('mahasiswa')->user();
        $stask = $this->taskForStudent($code, $user, $periodContext, $studentContext);

        $task = new studentScore;

        for ($i = 1; $i <= 8; $i++) {
            $fileKey = 'file_'.$i;

            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $filename = time().'-part-'.$i.'.'.$file->getClientOriginalExtension(); // Menggunakan ekstensi file asli
                $path = $file->storeAs('public/uploads/tugas', $filename); // Menyimpan file ke dalam direktori public/uploads/tugas
                $task->{'file_'.$i} = 'tugas/'.$filename; // Menyimpan path relatif dari file ke dalam properti dinamis $task
            }
            // Setelah menangani file, atur nilai-nilai lainnya
            $task->stask_id = $stask->id;  // Pastikan variabel $stask telah didefinisikan sebelumnya
            $task->desc = $request->desc;
            $task->code = Str::of(mt_rand(100000, 999999))->limit(6, '');
            $task->student_id = $user->id;

            // Simpan $task untuk setiap iterasi
            $task->save();
        }

        // $khs = HasilStudi::where('student_id', $user->id)->where('smt_id', $user->taka->raw_semester)->first();
        // // dd($khs->count())

        // if ($khs === null) {
        //     $ckhs = new HasilStudi;
        //     $ckhs->student_id = $user->id;
        //     $ckhs->taka_id = $user->taka->id;
        //     $ckhs->smt_id = $user->taka->raw_semester;
        //     $ckhs->score_tugas = 10;
        //     $ckhs->max_tugas = 1;
        //     $ckhs->code = Str::random(6);
        //     $ckhs->save();
        // } elseif ($khs !== null) {
        //     $ukhs = HasilStudi::where('student_id', $user->id)->where('smt_id', $user->taka->raw_semester)->first();
        //     $ukhs->score_tugas += 10;
        //     $ukhs->max_tugas += 1;
        //     $ukhs->save();
        // }

        Alert::success('Sukses', 'Tugas berhasil disimpan');

        return redirect()->route('mahasiswa.akademik.tugas-index');
    }

    private function taskForStudent(
        string $code,
        $student,
        AcademicPeriodContext $periodContext,
        StudentAcademicContext $studentContext
    ): studentTask {
        $period = $periodContext->published();
        $classId = $studentContext->classIdFor($student, $period);

        return studentTask::query()
            ->forAcademicPeriod($period)
            ->when($classId, fn ($query) => $query->whereHas('jadkul', fn ($schedule) => $schedule->where('kelas_id', $classId)))
            ->when(! $classId, fn ($query) => $query->whereRaw('1 = 0'))
            ->where('code', $code)
            ->with(['jadkul.matkul', 'jadkul.kelas', 'dosen'])
            ->firstOrFail();
    }
}
