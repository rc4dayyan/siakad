<?php

namespace App\Services\Academic;

use App\Models\Dosen;
use App\Models\KalenderAkademik;
use App\Models\Krs;
use App\Models\NilaiMahasiswa;
use App\Models\Notification;
use App\Models\PenawaranMataKuliah;
use App\Models\RegistrasiMahasiswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KrsService
{
    public function __construct(
        private readonly KrsEligibilityService $eligibility,
        private readonly AcademicAuditService $audit
    ) {}

    public function forRegistration(RegistrasiMahasiswa $registration): Krs
    {
        return $registration->krs()->firstOrCreate([], ['status' => Krs::STATUS_DRAFT, 'total_sks' => 0]);
    }

    public function assertWindowOpen(RegistrasiMahasiswa $registration): void
    {
        if (! KalenderAkademik::query()->krsAktif($registration->taka_id)->exists()) {
            throw ValidationException::withMessages([
                'krs' => 'Pengisian KRS belum dibuka atau jadwal pengisian telah berakhir.',
            ]);
        }
    }

    public function add(Krs $krs, PenawaranMataKuliah $offering): Krs
    {
        $this->assertEditableAndEligible($krs);

        return $this->addValidated($krs, $offering);
    }

    public function addForAdministration(Krs $krs, PenawaranMataKuliah $offering): Krs
    {
        $this->assertEditablePeriod($krs);
        $this->eligibility->ensureEligible($krs->registrasiMahasiswa);

        return $this->addValidated($krs, $offering);
    }

    private function addValidated(Krs $krs, PenawaranMataKuliah $offering): Krs
    {
        $registration = $krs->registrasiMahasiswa;

        if ($offering->taka_id !== $registration->taka_id || $offering->pstudi_id !== $registration->kelas?->pstudi_id) {
            $this->reject('penawaran', 'Penawaran mata kuliah tidak sesuai dengan periode atau program studi mahasiswa.');
        }
        if ($krs->items()->where('penawaran_mata_kuliah_id', $offering->id)->exists()) {
            $this->reject('penawaran', 'Mata kuliah tersebut sudah ada di KRS.');
        }
        if ($this->participantCount($offering) >= $offering->kapasitas) {
            $this->reject('penawaran', 'Kapasitas kelas mata kuliah sudah penuh.');
        }
        if ($this->hasPassed($registration, $offering->master_mata_kuliah_id)) {
            $this->reject('penawaran', 'Mata kuliah ini sudah pernah dinyatakan lulus.');
        }
        if ($offering->prasyarat_master_id && ! $this->hasPassed($registration, $offering->prasyarat_master_id)) {
            $this->reject('penawaran', 'Prasyarat mata kuliah belum dinyatakan lulus.');
        }
        if ($krs->items()->sum('sks') + $offering->sks > $registration->batas_sks) {
            $this->reject('penawaran', 'Penambahan mata kuliah melebihi batas '.$registration->batas_sks.' SKS.');
        }

        return DB::transaction(function () use ($krs, $offering): Krs {
            $krs->items()->create(['penawaran_mata_kuliah_id' => $offering->id, 'sks' => $offering->sks]);
            $krs->recalculateTotal();

            return $krs->fresh('items.penawaranMataKuliah.masterMataKuliah');
        });
    }

    public function remove(Krs $krs, int $itemId): Krs
    {
        $this->assertEditableAndEligible($krs);

        return $this->removeValidated($krs, $itemId);
    }

    public function removeForAdministration(Krs $krs, int $itemId): Krs
    {
        $this->assertEditablePeriod($krs);

        return $this->removeValidated($krs, $itemId);
    }

    private function removeValidated(Krs $krs, int $itemId): Krs
    {

        return DB::transaction(function () use ($krs, $itemId): Krs {
            $krs->items()->whereKey($itemId)->firstOrFail()->delete();
            $krs->recalculateTotal();

            return $krs->fresh('items');
        });
    }

    public function submit(Krs $krs, ?string $note = null): Krs
    {
        $this->assertEditableAndEligible($krs);
        if (! $krs->items()->exists()) {
            $this->reject('krs', 'KRS belum memiliki mata kuliah.');
        }

        foreach ($krs->items()->with('penawaranMataKuliah')->get() as $item) {
            if ($this->participantCount($item->penawaranMataKuliah) >= $item->penawaranMataKuliah->kapasitas) {
                $this->reject('krs', 'Kapasitas '.$item->penawaranMataKuliah->masterMataKuliah->name.' sudah penuh.');
            }
        }

        $krs->update([
            'status' => Krs::STATUS_SUBMITTED,
            'catatan_mahasiswa' => $note,
            'diajukan_at' => now(),
            'catatan_keputusan' => null,
            'diputuskan_at' => null,
            'diputuskan_oleh' => null,
        ]);

        return $krs->fresh();
    }

    public function decide(Krs $krs, Dosen $advisor, string $decision, ?string $note): Krs
    {
        if ($krs->registrasiMahasiswa->dosen_wali_id !== $advisor->id) {
            abort(403, 'KRS ini bukan milik mahasiswa bimbingan Anda.');
        }
        if ($krs->status !== Krs::STATUS_SUBMITTED) {
            $this->reject('krs', 'Hanya KRS yang telah diajukan yang dapat diputuskan.');
        }
        if (! in_array($decision, [Krs::STATUS_APPROVED, Krs::STATUS_REJECTED], true)) {
            $this->reject('status', 'Keputusan KRS tidak valid.');
        }
        if ($decision === Krs::STATUS_REJECTED && blank($note)) {
            $this->reject('catatan', 'Catatan wajib diisi ketika KRS ditolak.');
        }

        return DB::transaction(function () use ($krs, $advisor, $decision, $note): Krs {
            $krs->update([
                'status' => $decision,
                'catatan_keputusan' => $note,
                'diputuskan_at' => now(),
                'diputuskan_oleh' => $advisor->id,
            ]);

            $student = $krs->registrasiMahasiswa->mahasiswa;
            Notification::create([
                'auth_id' => 0,
                'send_to' => 3,
                'student_id' => $student->id,
                'name' => $decision === Krs::STATUS_APPROVED ? 'KRS disetujui' : 'KRS perlu diperbaiki',
                'slug' => route('mahasiswa.akademik.krs-index', absolute: false),
                'type' => 'krs',
                'desc' => $note ?: 'KRS Anda telah disetujui oleh dosen wali.',
                'code' => 'KRS-'.Str::upper(Str::random(20)),
            ]);
            $this->audit->record('krs.decided', $krs, $krs->registrasiMahasiswa->taka_id, $advisor,
                ['status' => Krs::STATUS_SUBMITTED], ['status' => $decision], ['has_note' => filled($note)]);

            return $krs->fresh();
        });
    }

    private function assertEditableAndEligible(Krs $krs): void
    {
        $this->assertEditablePeriod($krs);
        $this->assertWindowOpen($krs->registrasiMahasiswa);
        $this->eligibility->ensureEligible($krs->registrasiMahasiswa);
    }

    private function assertEditablePeriod(Krs $krs): void
    {
        if (! $krs->isEditable()) {
            $this->reject('krs', 'KRS yang telah diajukan atau disetujui tidak dapat diubah.');
        }
        if (! $krs->registrasiMahasiswa->taka?->isWritable()) {
            $this->reject('academic_period', 'KRS pada periode yang telah ditutup atau diarsipkan tidak dapat diubah.');
        }
    }

    private function participantCount(PenawaranMataKuliah $offering): int
    {
        return $offering->krsItems()->whereHas('krs', fn ($query) => $query
            ->whereIn('status', [Krs::STATUS_SUBMITTED, Krs::STATUS_APPROVED, Krs::STATUS_LOCKED]))->count();
    }

    private function hasPassed(RegistrasiMahasiswa $registration, int $masterId): bool
    {
        return NilaiMahasiswa::query()
            ->where('mahasiswa_id', $registration->mahasiswa_id)
            ->whereIn('nilai', ['A', 'B', 'C', 'D'])
            ->whereHas('penawaranMataKuliah', fn ($query) => $query->where('master_mata_kuliah_id', $masterId))
            ->exists();
    }

    private function reject(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
