<?php

namespace App\Services\Academic;

use App\Models\Krs;
use App\Models\KrsItem;
use App\Models\Mahasiswa;
use App\Models\PertemuanKuliah;
use Illuminate\Validation\ValidationException;

class AttendanceEligibilityService
{
    public function eligibleKrsItem(PertemuanKuliah $meeting, Mahasiswa $student): KrsItem
    {
        $offeringId = $meeting->jadwalMingguan->penawaran_mata_kuliah_id;
        $item = KrsItem::query()
            ->where('penawaran_mata_kuliah_id', $offeringId)
            ->whereHas('krs', fn ($query) => $query
                ->whereIn('status', [Krs::STATUS_APPROVED, Krs::STATUS_LOCKED])
                ->whereHas('registrasiMahasiswa', fn ($registration) => $registration
                    ->where('mahasiswa_id', $student->id)))
            ->first();

        if (! $item) {
            throw ValidationException::withMessages([
                'presensi' => 'Presensi hanya dapat diambil oleh peserta KRS yang telah disetujui.',
            ]);
        }

        return $item;
    }
}
