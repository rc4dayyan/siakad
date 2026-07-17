<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\JadwalMingguan;
use App\Models\KalenderAkademik;
use App\Models\PenawaranMataKuliah;
use App\Models\PertemuanKuliah;
use App\Models\Ruang;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\MeetingGeneratorService;
use App\Services\Academic\ScheduleConflictService;
use App\Services\Academic\ScheduleNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JadwalMingguanController extends Controller
{
    use roleTrait;

    public function index(AcademicPeriodContext $context): View
    {
        return view('user.admin.master.jadwal-mingguan-index', $this->formData($context));
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
        $data = $this->validateGeneration($request, $period->id);
        $viewData = $this->formData($context);
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

    private function formData(AcademicPeriodContext $context): array
    {
        $period = $context->current(auth()->user());

        return [
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'canManage' => $period?->isWritable() ?? false,
            'schedules' => JadwalMingguan::query()->forAcademicPeriod($period)
                ->with(['penawaranMataKuliah.masterMataKuliah', 'kelas', 'dosen', 'ruang', 'pertemuans'])
                ->orderBy('hari')->orderBy('mulai')->get(),
            'offerings' => PenawaranMataKuliah::query()->forAcademicPeriod($period)
                ->with(['masterMataKuliah', 'kelas', 'dosenUtama'])->orderBy('code')->get(),
            'lecturers' => Dosen::query()->orderBy('dsn_name')->get(),
            'rooms' => Ruang::query()->orderBy('name')->get(),
            'holidays' => $period ? KalenderAkademik::query()->where('taka_id', $period->id)
                ->where('kategori', 'libur')->orderBy('mulai_at')->get() : collect(),
            'preview' => null,
            'previewSchedule' => null,
            'generationInput' => null,
        ];
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

    private function validateGeneration(Request $request, int $periodId): array
    {
        return $request->validate([
            'mulai_tanggal' => ['required', 'date'],
            'selesai_tanggal' => ['required', 'date', 'after_or_equal:mulai_tanggal'],
            'jumlah_pertemuan' => ['required', 'integer', 'between:1,20'],
        ]);
    }
}
