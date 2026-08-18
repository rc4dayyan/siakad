<?php

namespace App\Http\Controllers\Mahasiswa\Pages;

use Alert;
use App\Http\Controllers\Controller;
// SECTION ADDONS SYSTEM
use App\Models\HasilStudi;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\Settings\webSettings;
use App\Models\studentScore;
use App\Models\studentTask;
// SECTION ADDONS EXTERNAL
// SECTION MODELS
use App\Models\TahunAkademik;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\StudentAcademicContext;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Str;

class StudentNilaiController extends Controller
{
    public function index(
        Request $request,
        AcademicPeriodContext $context,
        StudentAcademicContext $studentContext
    ) {
        $user = FacadesAuth::guard('mahasiswa')->user();
        $period = $context->published();
        $hasAcademicContext = $studentContext->classFor($user, $period) !== null;
        $isTranscript = $request->boolean('transkrip');
        $data['web'] = webSettings::where('id', 1)->first();
        $data['period'] = $period;
        $data['isTranscript'] = $isTranscript;
        $data['nilai'] = $this->courseRows($user, $period, $isTranscript, $studentContext);
        $data['hasilStudi'] = HasilStudi::query()
            ->where('student_id', $user->id)
            ->when(! $isTranscript, fn ($query) => $query->forAcademicPeriod($period))
            ->when(! $isTranscript && ! $hasAcademicContext, fn ($query) => $query->whereRaw('1 = 0'))
            ->with('taka')
            ->orderBy('taka_id')
            ->get();

        return view('mahasiswa.pages.nilai-index', $data);
    }

    private function courseRows(
        Mahasiswa $student,
        ?TahunAkademik $period,
        bool $isTranscript,
        StudentAcademicContext $studentContext
    ): Collection {
        if (! $isTranscript && ! $period) {
            return collect();
        }

        $grades = NilaiMahasiswa::query()
            ->where('mahasiswa_id', $student->id)
            ->when(! $isTranscript, fn ($query) => $query->forAcademicPeriod($period))
            ->with(['taka', 'mataKuliah.taka', 'kelas', 'dosen', 'penawaranMataKuliah'])
            ->get();
        $rows = collect();
        $usedGradeIds = [];
        $approvedPeriodIds = collect();
        $offeringLegacyIds = collect();

        if (Schema::hasTable('krs') && Schema::hasTable('krs_items') && Schema::hasTable('penawaran_mata_kuliahs')) {
            $approvedKrs = Krs::query()
                ->whereHas('registrasiMahasiswa', fn ($query) => $query
                    ->where('mahasiswa_id', $student->id)
                    ->when(! $isTranscript, fn ($registration) => $registration->where('taka_id', $period?->id)))
                ->whereIn('status', [Krs::STATUS_APPROVED, Krs::STATUS_LOCKED])
                ->with('registrasiMahasiswa')
                ->get();
            $approvedPeriodIds = $approvedKrs->pluck('registrasiMahasiswa.taka_id')->filter()->unique();

            $offerings = PenawaranMataKuliah::query()
                ->whereHas('krsItems', fn ($query) => $query->whereIn('krs_id', $approvedKrs->modelKeys()))
                ->with(['taka', 'kelas', 'masterMataKuliah', 'dosenUtama', 'legacyMataKuliah'])
                ->get();
            $offeringLegacyIds = $offerings->pluck('legacy_mata_kuliah_id')->filter();

            foreach ($offerings as $offering) {
                $grade = $grades->first(fn (NilaiMahasiswa $item) => (int) $item->penawaran_mata_kuliah_id === (int) $offering->id
                    || ($offering->legacy_mata_kuliah_id
                        && (int) $item->mata_kuliah_id === (int) $offering->legacy_mata_kuliah_id)
                );

                if ($grade) {
                    $usedGradeIds[] = $grade->id;
                }

                $rows->push($this->courseRow(
                    $offering->taka,
                    $offering->kelas,
                    $offering->masterMataKuliah?->code ?? $offering->code,
                    $offering->masterMataKuliah?->name ?? $offering->legacyMataKuliah?->name,
                    $offering->dosenUtama?->dsn_name,
                    $grade?->nilai,
                    $offering->sks,
                    'KRS Disetujui'
                ));
            }
        }

        $registrations = $student->registrasiAkademik()
            ->when(! $isTranscript, fn ($query) => $query->where('taka_id', $period?->id))
            ->with(['taka', 'kelas'])
            ->get()
            ->reject(fn ($registration) => $approvedPeriodIds->contains($registration->taka_id));
        $legacyContexts = $registrations
            ->map(fn ($registration) => ['period' => $registration->taka, 'class' => $registration->kelas])
            ->filter(fn ($context) => $context['period'] && $context['class']);

        $legacyClass = $studentContext->classFor($student, $period);
        if ($period && $legacyClass && ! $approvedPeriodIds->contains($period->id)) {
            $legacyContexts->push(['period' => $period, 'class' => $legacyClass]);
        }

        foreach ($legacyContexts->unique(fn ($context) => $context['period']->id.':'.$context['class']->id) as $context) {
            $courses = MataKuliah::query()
                ->forAcademicPeriod($context['period'])
                ->where('kelas_id', $context['class']->id)
                ->whereNotIn('id', $offeringLegacyIds)
                ->with(['taka', 'kelas', 'dosen1'])
                ->get();

            foreach ($courses as $course) {
                $grade = $grades->first(fn (NilaiMahasiswa $item) => (int) $item->mata_kuliah_id === (int) $course->id
                );

                if ($grade) {
                    $usedGradeIds[] = $grade->id;
                }

                $rows->push($this->courseRow(
                    $course->taka,
                    $course->kelas,
                    $course->code,
                    $course->name,
                    $course->dosen1?->dsn_name,
                    $grade?->nilai,
                    $course->bsks,
                    'Data Lama'
                ));
            }
        }

        foreach ($grades->whereNotIn('id', $usedGradeIds) as $grade) {
            $rows->push($this->courseRow(
                $grade->taka ?? $grade->mataKuliah?->taka,
                $grade->kelas,
                $grade->mataKuliah?->code,
                $grade->mataKuliah?->name,
                $grade->dosen?->dsn_name,
                $grade->nilai
            ));
        }

        return $rows
            ->filter(fn (array $row) => filled($row['mata_kuliah']))
            ->sortBy(fn (array $row) => sprintf('%010d-%s', $row['periode_id'], $row['mata_kuliah']))
            ->values();
    }

    private function courseRow(
        ?TahunAkademik $period,
        mixed $class,
        ?string $courseCode,
        ?string $courseName,
        ?string $lecturerName,
        ?string $grade,
        ?int $sks = null,
        ?string $registrationStatus = null
    ): array {
        return [
            'periode_id' => (int) ($period?->id ?? 0),
            'periode' => $period?->name ?? '-',
            'kelas' => $class?->name ?? '-',
            'kode_mata_kuliah' => $courseCode,
            'mata_kuliah' => $courseName,
            'dosen' => $lecturerName ?? '-',
            'nilai' => $grade,
            'sks' => $sks,
            'status' => $registrationStatus,
        ];
    }

    public function view($code)
    {

        $data['stask'] = StudentTask::where('code', $code)->first();
        $data['web'] = webSettings::where('id', 1)->first();
        $score = studentScore::where('stask_id', $data['stask']->id)->where('student_id', Auth::guard('mahasiswa')->user()->id)->get();
        if ($score->count() == 1) {
            Alert::error('Error', 'Kamu sudah mengumpulkan tugas ini.');

            return back();
        } else {
            return view('mahasiswa.pages.stask-view', $data);
        }

    }

    public function store(Request $request, $code)
    {
        $request->validate([
            'desc' => 'required',
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
            'file_1.required' => 'File 1 harus diunggah.',
            'file_1.mimes' => 'File 1 harus berupa file dokumen PDF, Word, Excel, atau gambar.',
            'file_1.max' => 'File 1 tidak boleh lebih dari 20 MB.',
            // Add similar messages for other files if needed
        ]);

        $stask = studentTask::where('code', $code)->first();
        $user = Auth::guard('mahasiswa')->user();

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
}
