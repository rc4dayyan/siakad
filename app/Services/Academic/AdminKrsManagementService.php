<?php

namespace App\Services\Academic;

use App\Models\Krs;
use App\Models\Notification;
use App\Models\PenawaranMataKuliah;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminKrsManagementService
{
    public function __construct(
        private readonly KrsService $krsService,
        private readonly AcademicAuditService $audit
    ) {}

    public function add(Krs $krs, PenawaranMataKuliah $offering, User $actor, string $reason): Krs
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($krs, $offering, $actor, $reason): Krs {
            $before = $this->snapshot($krs);
            $updated = $this->krsService->addForAdministration($krs, $offering);

            $this->audit->record('krs.admin_item_added', $updated, $updated->registrasiMahasiswa->taka_id, $actor,
                $before, $this->snapshot($updated), ['reason' => $reason, 'offering_id' => $offering->id]);
            $this->notify($updated, $actor, 'KRS diubah oleh Akademik', "Mata kuliah {$offering->masterMataKuliah->name} ditambahkan. Alasan: {$reason}");

            return $updated;
        });
    }

    public function remove(Krs $krs, int $itemId, User $actor, string $reason): Krs
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($krs, $itemId, $actor, $reason): Krs {
            $item = $krs->items()->with('penawaranMataKuliah.masterMataKuliah')->findOrFail($itemId);
            $courseName = $item->penawaranMataKuliah->masterMataKuliah->name;
            $offeringId = $item->penawaran_mata_kuliah_id;
            $before = $this->snapshot($krs);
            $updated = $this->krsService->removeForAdministration($krs, $itemId);

            $this->audit->record('krs.admin_item_removed', $updated, $updated->registrasiMahasiswa->taka_id, $actor,
                $before, $this->snapshot($updated), ['reason' => $reason, 'offering_id' => $offeringId]);
            $this->notify($updated, $actor, 'KRS diubah oleh Akademik', "Mata kuliah {$courseName} dihapus. Alasan: {$reason}");

            return $updated;
        });
    }

    public function reopen(Krs $krs, User $actor, string $reason): Krs
    {
        $this->authorize($actor);
        $this->assertReopenable($krs);

        return DB::transaction(function () use ($krs, $actor, $reason): Krs {
            $before = $this->snapshot($krs);
            $krs->update([
                'status' => Krs::STATUS_REJECTED,
                'catatan_keputusan' => 'Dibuka kembali oleh administrator: '.$reason,
                'diputuskan_at' => null,
                'diputuskan_oleh' => null,
            ]);
            $updated = $krs->fresh(['items.penawaranMataKuliah.masterMataKuliah', 'registrasiMahasiswa']);
            $this->audit->record('krs.admin_reopened', $updated, $updated->registrasiMahasiswa->taka_id, $actor,
                $before, $this->snapshot($updated), ['reason' => $reason]);
            $this->notify($updated, $actor, 'KRS dibuka kembali', 'KRS perlu diperbaiki kembali. Alasan: '.$reason);

            return $updated;
        });
    }

    public function approve(Krs $krs, User $actor, ?string $note = null): Krs
    {
        $this->authorizeApproval($actor);
        $this->assertApprovable($krs);

        return DB::transaction(function () use ($krs, $actor, $note): Krs {
            $before = $this->snapshot($krs);
            $krs->update([
                'status' => Krs::STATUS_APPROVED,
                'catatan_keputusan' => $note,
                'diputuskan_at' => now(),
                'diputuskan_oleh' => null,
            ]);
            $updated = $krs->fresh(['items.penawaranMataKuliah.masterMataKuliah', 'registrasiMahasiswa']);
            $this->audit->record('krs.admin_approved', $updated, $updated->registrasiMahasiswa->taka_id, $actor,
                $before, $this->snapshot($updated), ['has_note' => filled($note)]);
            $description = 'KRS telah disetujui oleh administrator.';
            if (filled($note)) {
                $description .= ' Catatan: '.$note;
            }
            $this->notify($updated, $actor, 'KRS disetujui oleh administrator', $description);

            return $updated;
        });
    }

    public function approveMany(Collection $krsCollection, User $actor, ?string $note = null): int
    {
        $this->authorizeApproval($actor);
        $krsCollection->each(fn (Krs $krs) => $this->assertApprovable($krs));

        return DB::transaction(function () use ($krsCollection, $actor, $note): int {
            $krsCollection->each(fn (Krs $krs) => $this->approve($krs->fresh(['registrasiMahasiswa.taka']), $actor, $note));

            return $krsCollection->count();
        });
    }

    public function reopenMany(Collection $krsCollection, User $actor, string $reason): int
    {
        $this->authorize($actor);
        $krsCollection->each(fn (Krs $krs) => $this->assertReopenable($krs));

        return DB::transaction(function () use ($krsCollection, $actor, $reason): int {
            $krsCollection->each(fn (Krs $krs) => $this->reopen($krs->fresh(['registrasiMahasiswa.taka']), $actor, $reason));

            return $krsCollection->count();
        });
    }

    private function authorize(User $actor): void
    {
        abort_unless(in_array((int) $actor->raw_type, [0, 3], true), 403);
    }

    private function authorizeApproval(User $actor): void
    {
        abort_unless(in_array((int) $actor->raw_type, [0, 4], true), 403);
    }

    private function assertApprovable(Krs $krs): void
    {
        if ($krs->status !== Krs::STATUS_SUBMITTED) {
            throw ValidationException::withMessages(['krs' => 'Semua KRS yang dipilih harus berstatus diajukan agar dapat disetujui.']);
        }
        if (! $krs->registrasiMahasiswa->taka?->isWritable()) {
            throw ValidationException::withMessages(['academic_period' => 'KRS pada periode yang ditutup atau diarsipkan tidak dapat disetujui.']);
        }
    }

    private function assertReopenable(Krs $krs): void
    {
        if ($krs->isEditable()) {
            throw ValidationException::withMessages(['krs' => 'Semua KRS yang dipilih harus berstatus diajukan, disetujui, atau dikunci agar dapat dibuka kembali.']);
        }
        if (! $krs->registrasiMahasiswa->taka?->isWritable()) {
            throw ValidationException::withMessages(['academic_period' => 'KRS pada periode yang ditutup atau diarsipkan tidak dapat dibuka kembali.']);
        }
    }

    private function snapshot(Krs $krs): array
    {
        $krs->loadMissing('items.penawaranMataKuliah.masterMataKuliah');

        return [
            'status' => $krs->status,
            'total_sks' => $krs->total_sks,
            'items' => $krs->items->map(fn ($item) => [
                'item_id' => $item->id,
                'offering_id' => $item->penawaran_mata_kuliah_id,
                'course' => $item->penawaranMataKuliah?->masterMataKuliah?->name,
                'sks' => $item->sks,
            ])->values()->all(),
        ];
    }

    private function notify(Krs $krs, User $actor, string $title, string $description): void
    {
        $registration = $krs->registrasiMahasiswa()->with(['mahasiswa', 'dosenWali'])->firstOrFail();
        $attributes = [
            'auth_id' => $actor->id,
            'name' => $title,
            'type' => 'krs',
            'desc' => $description,
        ];

        Notification::create($attributes + [
            'send_to' => 3,
            'student_id' => $registration->mahasiswa_id,
            'slug' => route('mahasiswa.akademik.krs-index', absolute: false),
            'code' => 'KRS-ADM-STU-'.Str::upper(Str::random(16)),
        ]);

        if ($registration->dosen_wali_id) {
            Notification::create($attributes + [
                'send_to' => 2,
                'lecture_id' => $registration->dosen_wali_id,
                'slug' => route('dosen.akademik.krs-index', absolute: false),
                'code' => 'KRS-ADM-DSN-'.Str::upper(Str::random(16)),
            ]);
        }
    }
}
