<?php

namespace App\Services\Academic;

use App\Models\Krs;
use App\Models\PenawaranMataKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KrsBulkImportService
{
    public const ACTION_APPROVE = 'setujui';

    public const ACTION_REOPEN = 'buka_kembali';

    public const ACTION_PREPARE_APPROVE = 'ajukan_setujui';

    public function __construct(
        private readonly AdminKrsManagementService $management,
        private readonly KrsService $krsService
    ) {}

    public function prepare(Collection $rows, TahunAkademik $period, User $actor): array
    {
        if ($rows->isEmpty()) {
            $this->reject('File import tidak berisi data.');
        }
        $normalizedRows = $rows->values()->map(function ($row, int $index): array {
            $normalized = collect($row)->mapWithKeys(fn ($value, $key) => [Str::lower(trim((string) $key)) => $value]);
            $missing = collect(['nim', 'nama', 'aksi'])->reject(fn (string $header) => $normalized->has($header));
            if ($missing->isNotEmpty()) {
                $this->reject('Kolom wajib tidak ditemukan: '.implode(', ', $missing->all()).'.');
            }

            return [
                'row' => $index + 2,
                'nim' => trim((string) $normalized->get('nim')),
                'nama' => trim((string) $normalized->get('nama')),
                'aksi' => $this->normalizeAction((string) $normalized->get('aksi')),
                'aksi_input' => trim((string) $normalized->get('aksi')),
            ];
        })->filter(fn (array $row) => $row['aksi_input'] !== '')->values();

        if ($normalizedRows->isEmpty()) {
            $this->reject('Tidak ada aksi KRS yang diisi. Isi setujui, buka_kembali, atau ajukan_setujui pada kolom Aksi.');
        }
        if ($normalizedRows->count() > 100) {
            $this->reject('Maksimal 100 baris dengan aksi dapat diproses dalam satu file.');
        }

        foreach ($normalizedRows as $row) {
            if ($row['nim'] === '' || $row['nama'] === '') {
                $this->reject("Baris {$row['row']}: NIM, nama, dan aksi wajib diisi.");
            }
            if ($row['aksi'] === '') {
                $this->reject("Baris {$row['row']}: aksi {$row['aksi_input']} tidak valid. Gunakan setujui, buka_kembali, atau ajukan_setujui.");
            }
        }

        $duplicateNims = $normalizedRows->groupBy(fn (array $row) => Str::lower($row['nim']))
            ->filter(fn (Collection $items) => $items->count() > 1)
            ->keys();
        if ($duplicateNims->isNotEmpty()) {
            $this->reject('NIM duplikat dalam file: '.$duplicateNims->implode(', ').'.');
        }

        $registrations = RegistrasiMahasiswa::query()
            ->forAcademicPeriod($period)
            ->whereHas('mahasiswa', fn ($query) => $query->whereIn('mhs_nim', $normalizedRows->pluck('nim')))
            ->with(['mahasiswa', 'kelas', 'krs.items', 'taka'])
            ->get()
            ->keyBy(fn (RegistrasiMahasiswa $registration) => Str::lower($registration->mahasiswa->mhs_nim));

        return $normalizedRows->map(function (array $row) use ($registrations, $actor, $period): array {
            $registration = $registrations->get(Str::lower($row['nim']));
            if (! $registration) {
                $this->reject("Baris {$row['row']}: NIM {$row['nim']} tidak terdaftar pada periode yang dipilih.");
            }
            if ($this->normalizeName($registration->mahasiswa->mhs_name) !== $this->normalizeName($row['nama'])) {
                $this->reject("Baris {$row['row']}: nama tidak cocok dengan NIM {$row['nim']}.");
            }
            if (! $registration->krs && $row['aksi'] !== self::ACTION_PREPARE_APPROVE) {
                $this->reject("Baris {$row['row']}: mahasiswa {$row['nim']} belum memiliki KRS.");
            }

            $this->assertActionAllowed($row, $registration->krs, $actor);
            $offeringCount = null;
            if ($row['aksi'] === self::ACTION_PREPARE_APPROVE) {
                $offeringCount = PenawaranMataKuliah::query()
                    ->forAcademicPeriod($period)
                    ->where('pstudi_id', $registration->kelas?->pstudi_id)
                    ->where('kelas_id', $registration->kelas_id)
                    ->count();
                if ($offeringCount === 0) {
                    $this->reject("Baris {$row['row']}: tidak ada penawaran mata kuliah untuk kelas mahasiswa {$row['nim']}.");
                }
            }

            return $row + [
                'registration_id' => $registration->id,
                'krs_id' => $registration->krs?->id,
                'status' => $registration->krs?->status ?? 'belum dibuat',
                'aksi_label' => match ($row['aksi']) {
                    self::ACTION_APPROVE => 'Setujui dan kunci',
                    self::ACTION_REOPEN => 'Buka kembali',
                    self::ACTION_PREPARE_APPROVE => "Isi {$offeringCount} penawaran, ajukan, dan setujui",
                },
            ];
        })->all();
    }

    public function execute(array $rows, TahunAkademik $period, User $actor, ?string $note): array
    {
        $prepared = $this->prepare(collect($rows), $period, $actor);
        $grouped = collect($prepared)->groupBy('aksi');
        if ($grouped->has(self::ACTION_PREPARE_APPROVE) && Str::length(trim((string) $note)) < 10) {
            $this->reject('Catatan minimal 10 karakter wajib diisi untuk aksi ajukan_setujui.');
        }

        return DB::transaction(function () use ($grouped, $period, $actor, $note): array {
            $approved = 0;
            $reopened = 0;
            $preparedApproved = 0;

            if ($grouped->has(self::ACTION_APPROVE)) {
                $items = Krs::query()->whereKey($grouped->get(self::ACTION_APPROVE)->pluck('krs_id'))
                    ->with('registrasiMahasiswa.taka')->get();
                $approved = $this->management->approveMany($items, $actor, $note);
            }
            if ($grouped->has(self::ACTION_REOPEN)) {
                $items = Krs::query()->whereKey($grouped->get(self::ACTION_REOPEN)->pluck('krs_id'))
                    ->with('registrasiMahasiswa.taka')->get();
                $reopened = $this->management->reopenMany($items, $actor, (string) $note);
            }

            if ($grouped->has(self::ACTION_PREPARE_APPROVE)) {
                $rows = $grouped->get(self::ACTION_PREPARE_APPROVE);
                $registrations = RegistrasiMahasiswa::query()
                    ->whereKey($rows->pluck('registration_id'))
                    ->with(['kelas', 'taka'])
                    ->get()
                    ->keyBy('id');

                foreach ($rows as $row) {
                    $registration = $registrations->get($row['registration_id']);
                    if (! $registration || $registration->taka_id !== $period->id) {
                        $this->reject("Baris {$row['row']}: registrasi mahasiswa tidak lagi tersedia pada periode yang dipilih.");
                    }

                    $krs = $this->krsService->forRegistration($registration)->load('items');
                    $offerings = PenawaranMataKuliah::query()
                        ->forAcademicPeriod($period)
                        ->where('pstudi_id', $registration->kelas?->pstudi_id)
                        ->where('kelas_id', $registration->kelas_id)
                        ->with('masterMataKuliah')
                        ->orderBy('code')
                        ->get();
                    $existingOfferingIds = $krs->items->pluck('penawaran_mata_kuliah_id');
                    $missingOfferings = $offerings->whereNotIn('id', $existingOfferingIds)->values();
                    if ($missingOfferings->isNotEmpty()) {
                        $krs = $this->management->addMany($krs, $missingOfferings, $actor, (string) $note);
                    }
                    if (! $krs->items()->exists()) {
                        $this->reject("Baris {$row['row']}: KRS tidak memiliki mata kuliah yang dapat diajukan.");
                    }

                    $submitted = $this->management->submit($krs->fresh('registrasiMahasiswa.taka'), $actor, (string) $note);
                    $this->management->approve($submitted->load('registrasiMahasiswa.taka'), $actor, $note);
                    $preparedApproved++;
                }
            }

            return [
                'approved' => $approved,
                'reopened' => $reopened,
                'prepared_approved' => $preparedApproved,
                'total' => $approved + $reopened + $preparedApproved,
            ];
        });
    }

    private function assertActionAllowed(array $row, ?Krs $krs, User $actor): void
    {
        $rawType = (int) $actor->raw_type;
        if ($row['aksi'] === self::ACTION_PREPARE_APPROVE) {
            if ($rawType !== 0) {
                $this->reject("Baris {$row['row']}: hanya Web Administrator yang dapat mengisi, mengajukan, dan menyetujui KRS sekaligus.");
            }
            if ($krs && ! $krs->isEditable()) {
                $this->reject("Baris {$row['row']}: aksi ajukan_setujui hanya berlaku untuk KRS belum dibuat, draft, atau ditolak.");
            }

            return;
        }

        if ($row['aksi'] === self::ACTION_APPROVE) {
            if (! in_array($rawType, [0, 4], true)) {
                $this->reject("Baris {$row['row']}: akun ini tidak berwenang menyetujui KRS.");
            }
            if ($krs->status !== Krs::STATUS_SUBMITTED) {
                $this->reject("Baris {$row['row']}: aksi setujui hanya berlaku untuk KRS berstatus diajukan.");
            }

            return;
        }

        if (! in_array($rawType, [0, 3], true)) {
            $this->reject("Baris {$row['row']}: akun ini tidak berwenang membuka kembali KRS.");
        }
        if ($krs->isEditable()) {
            $this->reject("Baris {$row['row']}: KRS harus berstatus diajukan, disetujui, atau dikunci untuk dibuka kembali.");
        }
    }

    private function normalizeAction(string $action): string
    {
        return match (Str::lower(trim($action))) {
            'setujui', 'approve', 'approved' => self::ACTION_APPROVE,
            'buka_kembali', 'buka kembali', 'reopen' => self::ACTION_REOPEN,
            'ajukan_setujui', 'ajukan setujui', 'submit_approve', 'submit approve' => self::ACTION_PREPARE_APPROVE,
            default => '',
        };
    }

    private function normalizeName(string $name): string
    {
        return Str::lower((string) preg_replace('/\s+/', ' ', trim($name)));
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['import' => $message]);
    }
}
