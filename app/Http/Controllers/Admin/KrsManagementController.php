<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsItem;
use App\Models\PenawaranMataKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AdminKrsManagementService;
use App\Services\Academic\KrsBulkImportService;
use App\Services\Academic\KrsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Rap2hpoutre\FastExcel\FastExcel;

class KrsManagementController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $periods, KrsService $krsService): View
    {
        $this->authorizeViewRole($request);
        $period = $periods->requireCurrent($request->user());
        $rawType = (int) $request->user()->raw_type;
        $canManageKrs = in_array($rawType, [0, 3], true);
        $canApproveKrs = in_array($rawType, [0, 4], true);
        $search = trim($request->string('q')->value());
        $allowedStatuses = [
            Krs::STATUS_DRAFT,
            Krs::STATUS_SUBMITTED,
            Krs::STATUS_APPROVED,
            Krs::STATUS_REJECTED,
            Krs::STATUS_LOCKED,
            'none',
        ];
        $status = $request->string('status')->value();
        $status = in_array($status, $allowedStatuses, true) ? $status : '';
        $classes = Kelas::query()
            ->forAcademicPeriod($period)
            ->orderBy('name')
            ->get();
        $requestedClassId = $request->integer('kelas_id');
        $classId = $classes->contains('id', $requestedClassId) ? $requestedClassId : null;
        $importPreview = $request->session()->get('krs_bulk_import');
        if (! is_array($importPreview)
            || (int) ($importPreview['user_id'] ?? 0) !== $request->user()->id
            || (int) ($importPreview['period_id'] ?? 0) !== $period->id
            || (int) ($importPreview['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('krs_bulk_import');
            $importPreview = null;
        }
        $registrations = RegistrasiMahasiswa::query()
            ->forAcademicPeriod($period)
            ->with(['mahasiswa', 'kelas', 'dosenWali', 'krs'])
            ->when($search !== '', fn ($query) => $query->whereHas('mahasiswa', fn ($student) => $student
                ->where('mhs_name', 'like', '%'.$search.'%')
                ->orWhere('mhs_nim', 'like', '%'.$search.'%')))
            ->when($classId, fn ($query, $selectedClassId) => $query->where('kelas_id', $selectedClassId))
            ->when($status === 'none', fn ($query) => $query->whereDoesntHave('krs'))
            ->when($status !== '' && $status !== 'none', fn ($query) => $query
                ->whereHas('krs', fn ($krs) => $krs->where('status', $status)))
            ->orderBy('id')
            ->paginate(25, ['*'], 'students_page')
            ->withQueryString();

        $selected = null;
        $krs = null;
        $offerings = collect();
        if ($request->integer('registration')) {
            $selected = RegistrasiMahasiswa::query()
                ->forAcademicPeriod($period)
                ->with(['mahasiswa', 'kelas.pstudi', 'dosenWali'])
                ->findOrFail($request->integer('registration'));
            $krs = $krsService->forRegistration($selected)->load('items.penawaranMataKuliah.masterMataKuliah');
            if ($canManageKrs) {
                $offerings = PenawaranMataKuliah::query()
                    ->forAcademicPeriod($period)
                    ->where('pstudi_id', $selected->kelas?->pstudi_id)
                    ->where('kelas_id', $selected->kelas_id)
                    ->with(['masterMataKuliah', 'dosenUtama', 'prasyaratMaster'])
                    ->orderBy('code')
                    ->get();
            }
        }

        return view('user.admin.krs-management-index', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'registrations' => $registrations,
            'selected' => $selected,
            'krs' => $krs,
            'offerings' => $offerings,
            'search' => $search,
            'status' => $status,
            'classes' => $classes,
            'classId' => $classId,
            'canManageKrs' => $canManageKrs,
            'canApproveKrs' => $canApproveKrs,
            'importPreview' => $importPreview,
        ]);
    }

    public function add(Request $request, RegistrasiMahasiswa $registration, AcademicPeriodContext $periods, KrsService $krsService, AdminKrsManagementService $management): RedirectResponse
    {
        $this->authorizeRole($request);
        $period = $periods->requireWritableCurrent($request->user());
        abort_unless($registration->taka_id === $period->id, 404);
        $data = $request->validate([
            'penawaran_mata_kuliah_id' => ['required', 'integer', 'exists:penawaran_mata_kuliahs,id'],
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $registration->loadMissing('kelas');
        $offering = PenawaranMataKuliah::query()
            ->forAcademicPeriod($period)
            ->where('pstudi_id', $registration->kelas?->pstudi_id)
            ->where('kelas_id', $registration->kelas_id)
            ->findOrFail($data['penawaran_mata_kuliah_id']);
        $management->add($krsService->forRegistration($registration), $offering, $request->user(), $data['alasan']);

        return back()->with('success', 'Mata kuliah berhasil ditambahkan ke KRS dan perubahan telah diaudit.');
    }

    public function addMany(Request $request, RegistrasiMahasiswa $registration, AcademicPeriodContext $periods, KrsService $krsService, AdminKrsManagementService $management): RedirectResponse
    {
        $this->authorizeRole($request);
        $period = $periods->requireWritableCurrent($request->user());
        abort_unless($registration->taka_id === $period->id, 404);
        $data = $request->validate([
            'penawaran_ids' => ['required', 'array', 'min:1', 'max:50'],
            'penawaran_ids.*' => ['required', 'integer', 'distinct', 'exists:penawaran_mata_kuliahs,id'],
            'alasan_penambahan' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'penawaran_ids.required' => 'Pilih minimal satu mata kuliah yang akan ditambahkan.',
            'penawaran_ids.min' => 'Pilih minimal satu mata kuliah yang akan ditambahkan.',
            'penawaran_ids.max' => 'Maksimal 50 mata kuliah dapat ditambahkan sekaligus.',
            'penawaran_ids.*.distinct' => 'Pilihan mata kuliah tidak boleh duplikat.',
            'penawaran_ids.*.exists' => 'Salah satu penawaran mata kuliah tidak ditemukan.',
            'alasan_penambahan.required' => 'Alasan penambahan mata kuliah wajib diisi.',
            'alasan_penambahan.min' => 'Alasan penambahan minimal 10 karakter.',
            'alasan_penambahan.max' => 'Alasan penambahan maksimal 1.000 karakter.',
        ]);

        $registration->loadMissing('kelas');
        $ids = collect($data['penawaran_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $offerings = PenawaranMataKuliah::query()
            ->forAcademicPeriod($period)
            ->where('pstudi_id', $registration->kelas?->pstudi_id)
            ->where('kelas_id', $registration->kelas_id)
            ->whereKey($ids)
            ->with('masterMataKuliah')
            ->get()
            ->keyBy('id');
        if ($offerings->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'penawaran_ids' => 'Sebagian penawaran tidak sesuai dengan periode, program studi, atau kelas mahasiswa.',
            ]);
        }
        $orderedOfferings = $ids->map(fn (int $id) => $offerings->get($id));

        $management->addMany(
            $krsService->forRegistration($registration),
            $orderedOfferings,
            $request->user(),
            $data['alasan_penambahan']
        );

        return back()->with('success', $orderedOfferings->count().' mata kuliah berhasil ditambahkan ke KRS dan perubahan telah diaudit.');
    }

    public function remove(Request $request, KrsItem $item, AcademicPeriodContext $periods, AdminKrsManagementService $management): RedirectResponse
    {
        $this->authorizeRole($request);
        $period = $periods->requireWritableCurrent($request->user());
        $item->load('krs.registrasiMahasiswa');
        abort_unless($item->krs->registrasiMahasiswa->taka_id === $period->id, 404);
        $data = $request->validate(['alasan' => ['required', 'string', 'min:10', 'max:1000']]);
        $management->remove($item->krs, $item->id, $request->user(), $data['alasan']);

        return back()->with('success', 'Mata kuliah berhasil dihapus dari KRS dan perubahan telah diaudit.');
    }

    public function reopen(Request $request, Krs $krs, AcademicPeriodContext $periods, AdminKrsManagementService $management): RedirectResponse
    {
        $this->authorizeRole($request);
        $period = $periods->requireWritableCurrent($request->user());
        $krs->load('registrasiMahasiswa');
        abort_unless($krs->registrasiMahasiswa->taka_id === $period->id, 404);
        $data = $request->validate(['alasan' => ['required', 'string', 'min:10', 'max:1000']]);
        $management->reopen($krs, $request->user(), $data['alasan']);

        return back()->with('success', 'KRS berhasil dibuka kembali dan mahasiswa serta dosen wali telah diberi notifikasi.');
    }

    public function submitOnBehalf(Request $request, Krs $krs, AcademicPeriodContext $periods, AdminKrsManagementService $management): RedirectResponse
    {
        $this->authorizeRole($request);
        $period = $periods->requireWritableCurrent($request->user());
        $krs->load('registrasiMahasiswa.taka');
        abort_unless($krs->registrasiMahasiswa->taka_id === $period->id, 404);
        $data = $request->validate([
            'alasan_pengajuan' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'alasan_pengajuan.required' => 'Alasan pengajuan atas nama mahasiswa wajib diisi.',
            'alasan_pengajuan.min' => 'Alasan pengajuan minimal 10 karakter.',
            'alasan_pengajuan.max' => 'Alasan pengajuan maksimal 1.000 karakter.',
        ]);

        $management->submit($krs, $request->user(), $data['alasan_pengajuan']);

        return back()->with('success', 'KRS berhasil diajukan atas nama mahasiswa dan dosen wali telah diberi notifikasi.');
    }

    public function approve(Request $request, Krs $krs, AcademicPeriodContext $periods, AdminKrsManagementService $management): RedirectResponse
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 4], true), 403);
        $period = $periods->requireWritableCurrent($request->user());
        $krs->load('registrasiMahasiswa.taka');
        abort_unless($krs->registrasiMahasiswa->taka_id === $period->id, 404);
        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        $management->approve($krs, $request->user(), $data['catatan'] ?? null);

        return back()->with('success', 'KRS berhasil disetujui oleh administrator.');
    }

    public function bulk(Request $request, AcademicPeriodContext $periods, AdminKrsManagementService $management): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reopen'],
            'krs_ids' => ['required', 'array', 'min:1', 'max:100'],
            'krs_ids.*' => ['required', 'integer', 'distinct', 'exists:krs,id'],
            'catatan' => ['nullable', 'string', 'max:2000', 'required_if:action,reopen', 'min:10'],
        ], [
            'action.required' => 'Pilih aksi massal yang akan dijalankan.',
            'action.in' => 'Aksi massal KRS tidak valid.',
            'krs_ids.required' => 'Pilih minimal satu KRS.',
            'krs_ids.array' => 'Daftar KRS yang dipilih tidak valid.',
            'krs_ids.min' => 'Pilih minimal satu KRS.',
            'krs_ids.max' => 'Maksimal 100 KRS dapat diproses sekaligus.',
            'krs_ids.*.distinct' => 'Terdapat pilihan KRS yang duplikat.',
            'krs_ids.*.exists' => 'Salah satu KRS yang dipilih tidak ditemukan.',
            'catatan.required_if' => 'Alasan wajib diisi untuk membuka kembali KRS.',
            'catatan.min' => 'Catatan atau alasan minimal 10 karakter.',
            'catatan.max' => 'Catatan atau alasan maksimal 2.000 karakter.',
        ]);
        $rawType = (int) $request->user()->raw_type;
        if ($data['action'] === 'approve') {
            abort_unless(in_array($rawType, [0, 4], true), 403);
        } else {
            abort_unless(in_array($rawType, [0, 3], true), 403);
        }

        $period = $periods->requireWritableCurrent($request->user());
        $ids = collect($data['krs_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $krsCollection = Krs::query()
            ->whereKey($ids)
            ->whereHas('registrasiMahasiswa', fn ($query) => $query->where('taka_id', $period->id))
            ->with('registrasiMahasiswa.taka')
            ->get();
        if ($krsCollection->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'krs_ids' => 'Sebagian KRS tidak ditemukan pada periode akademik yang sedang dipilih.',
            ]);
        }

        if ($data['action'] === 'approve') {
            $count = $management->approveMany($krsCollection, $request->user(), $data['catatan'] ?? null);
            $message = $count.' KRS berhasil disetujui dan dikunci.';
        } else {
            $count = $management->reopenMany($krsCollection, $request->user(), $data['catatan']);
            $message = $count.' KRS berhasil dibuka kembali.';
        }

        return back()->with('success', $message);
    }

    public function importTemplate(Request $request, AcademicPeriodContext $periods)
    {
        $this->authorizeViewRole($request);
        $period = $periods->requireCurrent($request->user());
        $search = trim($request->string('q')->value());
        $requestedClassId = $request->integer('kelas_id');
        $classId = Kelas::query()
            ->forAcademicPeriod($period)
            ->whereKey($requestedClassId)
            ->value('id');
        $status = $request->string('status')->value();
        $status = in_array($status, [
            Krs::STATUS_DRAFT,
            Krs::STATUS_SUBMITTED,
            Krs::STATUS_APPROVED,
            Krs::STATUS_REJECTED,
            Krs::STATUS_LOCKED,
            'none',
        ], true) ? $status : '';
        $registrations = RegistrasiMahasiswa::query()
            ->forAcademicPeriod($period)
            ->with('mahasiswa')
            ->when($search !== '', fn ($query) => $query->whereHas('mahasiswa', fn ($student) => $student
                ->where('mhs_name', 'like', '%'.$search.'%')
                ->orWhere('mhs_nim', 'like', '%'.$search.'%')))
            ->when($classId, fn ($query, $selectedClassId) => $query->where('kelas_id', $selectedClassId))
            ->when($status === 'none', fn ($query) => $query->whereDoesntHave('krs'))
            ->when($status !== '' && $status !== 'none', fn ($query) => $query
                ->whereHas('krs', fn ($krs) => $krs->where('status', $status)))
            ->orderBy('id')
            ->get();
        $actionDescription = match ((int) $request->user()->raw_type) {
            3 => 'buka_kembali = membuka KRS diajukan/disetujui/dikunci agar dapat diperbaiki',
            4 => 'setujui = menyetujui dan mengunci KRS berstatus diajukan',
            default => 'ajukan_setujui = isi semua penawaran kelas, ajukan, dan setujui; setujui = menyetujui KRS diajukan; buka_kembali = membuka KRS',
        };
        $rows = $registrations->map(fn (RegistrasiMahasiswa $registration) => [
            'NIM' => $registration->mahasiswa?->mhs_nim,
            'Nama' => $registration->mahasiswa?->mhs_name,
            'Aksi' => '',
            'Keterangan Aksi' => $actionDescription,
        ]);
        if ($rows->isEmpty()) {
            $rows->push(['NIM' => '', 'Nama' => '', 'Aksi' => '', 'Keterangan Aksi' => $actionDescription]);
        }

        return (new FastExcel($rows))->download('template-update-krs-'.$period->code.'.xlsx');
    }

    public function importPreview(Request $request, AcademicPeriodContext $periods, KrsBulkImportService $importer): RedirectResponse
    {
        $this->authorizeViewRole($request);
        $period = $periods->requireWritableCurrent($request->user());
        $request->session()->forget('krs_bulk_import');
        $request->validate([
            'import' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048'],
        ], [
            'import.required' => 'File Excel KRS wajib diunggah.',
            'import.file' => 'Berkas import KRS tidak valid.',
            'import.mimes' => 'File harus berformat XLSX atau CSV.',
            'import.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $path = $request->file('import')->store('excel-files', 'local');
        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Gunakan template XLSX atau CSV yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $prepared = $importer->prepare($rows, $period, $request->user());
        $token = (string) Str::uuid();
        $preview = [
            'token' => $token,
            'user_id' => $request->user()->id,
            'period_id' => $period->id,
            'expires_at' => now()->addMinutes(15)->timestamp,
            'rows' => collect($prepared)->map(fn (array $row) => [
                'NIM' => $row['nim'],
                'Nama' => $row['nama'],
                'Aksi' => $row['aksi'],
            ])->all(),
            'prepared' => $prepared,
        ];
        $request->session()->put('krs_bulk_import', $preview);

        return back()->with('success', 'File valid. Periksa pratinjau sebelum menjalankan update KRS.');
    }

    public function importExecute(Request $request, AcademicPeriodContext $periods, KrsBulkImportService $importer): RedirectResponse
    {
        $this->authorizeViewRole($request);
        $period = $periods->requireWritableCurrent($request->user());
        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'token.required' => 'Pratinjau import tidak ditemukan. Unggah ulang file.',
            'token.uuid' => 'Token pratinjau import tidak valid.',
            'catatan.max' => 'Catatan atau alasan maksimal 2.000 karakter.',
        ]);
        $preview = $request->session()->get('krs_bulk_import');
        if (! is_array($preview)
            || ! hash_equals((string) ($preview['token'] ?? ''), $data['token'])
            || (int) ($preview['user_id'] ?? 0) !== $request->user()->id
            || (int) ($preview['period_id'] ?? 0) !== $period->id
            || (int) ($preview['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('krs_bulk_import');
            throw ValidationException::withMessages([
                'import' => 'Pratinjau import sudah tidak berlaku. Unggah ulang file pada periode yang benar.',
            ]);
        }

        $requiresReason = collect($preview['rows'])->contains(fn (array $row) => in_array($row['Aksi'], [
            KrsBulkImportService::ACTION_REOPEN,
            KrsBulkImportService::ACTION_PREPARE_APPROVE,
        ], true));
        if ($requiresReason && Str::length(trim((string) ($data['catatan'] ?? ''))) < 10) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan atau alasan minimal 10 karakter wajib diisi untuk aksi buka_kembali atau ajukan_setujui.',
            ]);
        }

        $result = $importer->execute($preview['rows'], $period, $request->user(), $data['catatan'] ?? null);
        $request->session()->forget('krs_bulk_import');

        $message = "Import selesai: {$result['approved']} KRS disetujui dan {$result['reopened']} KRS dibuka kembali.";
        if ($result['prepared_approved'] > 0) {
            $message = "Import selesai: {$result['approved']} KRS disetujui, {$result['reopened']} KRS dibuka kembali, dan {$result['prepared_approved']} KRS diisi seluruh penawarannya, diajukan, serta disetujui.";
        }

        return back()->with('success', $message);
    }

    private function authorizeRole(Request $request): void
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 3], true), 403);
    }

    private function authorizeViewRole(Request $request): void
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 3, 4], true), 403);
    }
}
