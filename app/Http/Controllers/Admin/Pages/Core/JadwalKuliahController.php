<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\AbsensiMahasiswa;
use App\Models\Dosen;
use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Ruang;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PDF;
use Str;

class JadwalKuliahController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->current(auth()->user());
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'pstudi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'kelas_id' => [
                'nullable',
                'integer',
                Rule::exists('kelas', 'id')->where(fn ($query) => $query->where('taka_id', $period?->id ?? 0)),
            ],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'ruang_id' => ['nullable', 'integer', 'exists:ruangs,id'],
            'meth_id' => ['nullable', 'integer', 'in:0,1'],
            'days_id' => ['nullable', 'integer', 'between:0,6'],
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                Rule::when($request->filled('date_from'), ['after_or_equal:date_from']),
            ],
        ], [
            'kelas_id.exists' => 'Kelas tidak tersedia pada periode akademik yang dipilih.',
            'date_to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
        ]);

        $weeklyFilters = array_filter([
            'q' => $filters['q'] ?? null,
            'pstudi_id' => $filters['pstudi_id'] ?? null,
            'kelas_id' => $filters['kelas_id'] ?? null,
            'dosen_id' => $filters['dosen_id'] ?? null,
            'ruang_id' => $filters['ruang_id'] ?? null,
            'hari' => $filters['days_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return redirect()->route($this->setPrefix().'master.jadwal-mingguan-index', $weeklyFilters);
    }

    public function create(AcademicPeriodContext $context): View
    {
        $data = $this->formData($context);
        $data['jadkul'] = JadwalKuliah::query()
            ->forAcademicPeriod($data['selectedPeriod'])
            ->with(['matkul', 'kelas.pstudi', 'dosen', 'ruang.gedung'])
            ->latest()
            ->paginate(2);

        return view('user.admin.master.admin-jadkul-create', $data);
    }

    public function viewAbsen(string $code, AcademicPeriodContext $context): View
    {
        $period = $context->requireCurrent(auth()->user());
        $jadwal = $this->scheduleInPeriod($code, $period->id);

        return view('user.admin.master.admin-jadkul-view-absen', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'jadkul' => $jadwal,
            'absen' => AbsensiMahasiswa::query()
                ->when(
                    Schema::hasTable('pertemuan_kuliahs') && $jadwal->pertemuanKuliah,
                    fn ($query) => $query->where('pertemuan_kuliah_id', $jadwal->pertemuanKuliah->id),
                    fn ($query) => $query->where('jadkul_code', $jadwal->code)
                )
                ->with('mahasiswa')
                ->get(),
            'kelas' => collect([$jadwal->kelas]),
            'canManageJadwal' => $period->isWritable(),
        ]);
    }

    public function updateAbsen(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $validated = $request->validate(['absen_desc' => ['nullable', 'string', 'max:4000']]);
        $absen = AbsensiMahasiswa::query()
            ->forAcademicPeriod($period)
            ->where('code', $code)
            ->firstOrFail();
        $absen->update($validated);

        Alert::success('Berhasil', 'Keterangan absensi berhasil diperbarui.');

        return back();
    }

    public function cetakAbsen(Request $request, string $code, AcademicPeriodContext $context)
    {
        $period = $context->requireCurrent($request->user());
        $jadwal = $this->scheduleInPeriod($code, $period->id);
        $request->validate([
            'kode_kelas' => ['required', Rule::in([$jadwal->kelas->code])],
        ], [
            'kode_kelas.in' => 'Kelas harus sesuai dengan jadwal yang dipilih.',
        ]);

        $pdf = PDF::loadView('base.cetak.cetak-data-absensi', [
            'web' => webSettings::where('id', 1)->first(),
            'jadkul' => $jadwal,
            'absen' => AbsensiMahasiswa::query()
                ->when(
                    Schema::hasTable('pertemuan_kuliahs') && $jadwal->pertemuanKuliah,
                    fn ($query) => $query->where('pertemuan_kuliah_id', $jadwal->pertemuanKuliah->id),
                    fn ($query) => $query->where('jadkul_code', $jadwal->code)
                )->with('mahasiswa')->get(),
            'student' => $this->participantQuery($jadwal, $period->id)->get(),
        ]);

        return $pdf->download('Daftar-Absen-'.$jadwal->matkul->name.'-'.$jadwal->raw_pert_id.'-'.$jadwal->kelas->code.'.pdf');
    }

    public function store(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $validated = $this->validateSchedule($request, $period->id);

        JadwalKuliah::create(['code' => Str::random(6), ...$validated]);

        Alert::success('Berhasil', 'Jadwal kuliah berhasil ditambahkan.');

        return back();
    }

    public function update(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $jadwal = $this->scheduleInPeriod($code, $period->id);
        $jadwal->update($this->validateSchedule($request, $period->id));

        Alert::success('Berhasil', 'Jadwal kuliah berhasil diperbarui.');

        return back();
    }

    public function destroy(Request $request, string $code, AcademicPeriodContext $context): RedirectResponse
    {
        $period = $context->requireWritableCurrent($request->user());
        $this->scheduleInPeriod($code, $period->id)->delete();

        Alert::success('Berhasil', 'Jadwal kuliah berhasil dihapus.');

        return back();
    }

    private function formData(AcademicPeriodContext $context, array $filters = []): array
    {
        $period = $context->current(auth()->user());

        $schedules = JadwalKuliah::query()
            ->forAcademicPeriod($period)
            ->with(['matkul', 'kelas.pstudi.fakultas', 'dosen', 'ruang.gedung'])
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('matkul', fn ($query) => $query
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%"))
                        ->orWhereHas('kelas', fn ($query) => $query
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%"))
                        ->orWhereHas('dosen', fn ($query) => $query->where('dsn_name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['pstudi_id'] ?? null, fn ($query, $studyProgramId) => $query
                ->whereHas('kelas', fn ($class) => $class->where('pstudi_id', $studyProgramId)))
            ->when($filters['kelas_id'] ?? null, fn ($query, $classId) => $query->where('kelas_id', $classId))
            ->when($filters['dosen_id'] ?? null, fn ($query, $lecturerId) => $query->where('dosen_id', $lecturerId))
            ->when($filters['ruang_id'] ?? null, fn ($query, $roomId) => $query->where('ruang_id', $roomId))
            ->when(isset($filters['meth_id']) && $filters['meth_id'] !== null, fn ($query) => $query->where('meth_id', $filters['meth_id']))
            ->when(isset($filters['days_id']) && $filters['days_id'] !== null, fn ($query) => $query->where('days_id', $filters['days_id']))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->orderBy('date')
            ->orderBy('start')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'selectedPeriod' => $period,
            'canManageJadwal' => $period?->isWritable() ?? false,
            'kuri' => Kurikulum::all(),
            'taka' => $period ? collect([$period]) : collect(),
            'dosen' => Dosen::query()->orderBy('dsn_name')->get(),
            'pstudi' => ProgramStudi::query()->orderBy('name')->get(),
            'matkul' => MataKuliah::query()->forAcademicPeriod($period)->with(['dosen1', 'dosen2', 'dosen3'])->get(),
            'jadkul' => $schedules,
            'ruang' => Ruang::query()->orderBy('name')->get(),
            'kelas' => Kelas::query()->forAcademicPeriod($period)->orderBy('name')->get(),
            'filters' => $filters,
        ];
    }

    private function validateSchedule(Request $request, int $periodId): array
    {
        foreach (['start', 'ended'] as $timeField) {
            $time = $request->input($timeField);

            if (is_string($time) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
                $request->merge([$timeField => substr($time, 0, 5)]);
            }
        }

        $validated = $request->validate([
            'bsks' => ['required', 'integer', 'min:1', 'max:8'],
            'makul_id' => ['required', 'integer', Rule::exists('mata_kuliahs', 'id')->where(fn ($query) => $query->where('taka_id', $periodId))],
            'kelas_id' => ['required', 'integer', Rule::exists('kelas', 'id')->where(fn ($query) => $query->where('taka_id', $periodId))],
            'dosen_id' => ['required', 'integer', 'exists:dosens,id'],
            'ruang_id' => ['required', 'integer', 'exists:ruangs,id'],
            'pert_id' => ['required', 'integer', 'between:1,16'],
            'meth_id' => ['required', 'integer', 'in:0,1'],
            'days_id' => ['required', 'integer', 'between:0,6'],
            'start' => ['required', 'date_format:H:i'],
            'ended' => ['required', 'date_format:H:i', 'after:start'],
            'date' => ['required', 'date'],
        ], [
            'makul_id.exists' => 'Mata kuliah harus berasal dari periode yang sedang dipilih.',
            'kelas_id.exists' => 'Kelas harus berasal dari periode yang sedang dipilih.',
        ]);

        $mataKuliah = MataKuliah::findOrFail($validated['makul_id']);
        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $dosenPengampu = array_map('intval', array_filter([$mataKuliah->dosen_1, $mataKuliah->dosen_2, $mataKuliah->dosen_3]));

        if ((int) $mataKuliah->pstudi_id !== (int) $kelas->pstudi_id) {
            throw ValidationException::withMessages(['kelas_id' => 'Kelas harus sesuai dengan program studi mata kuliah.']);
        }

        if (! in_array((int) $validated['dosen_id'], $dosenPengampu, true)) {
            throw ValidationException::withMessages(['dosen_id' => 'Dosen harus termasuk pengampu mata kuliah.']);
        }

        return $validated;
    }

    private function participantQuery(JadwalKuliah $jadwal, int $periodId)
    {
        if (Schema::hasColumn('jadwal_kuliahs', 'penawaran_mata_kuliah_id') && $jadwal->penawaran_mata_kuliah_id) {
            return Mahasiswa::query()->forApprovedOffering($jadwal->penawaran_mata_kuliah_id);
        }

        return Mahasiswa::query()->forAcademicClass($periodId, $jadwal->kelas_id);
    }

    private function scheduleInPeriod(string $code, int $periodId): JadwalKuliah
    {
        return JadwalKuliah::query()
            ->forAcademicPeriod($periodId)
            ->with(['matkul', 'kelas'])
            ->where('code', $code)
            ->firstOrFail();
    }
}
