<?php

namespace App\Services\Academic;

use App\Models\JadwalMingguan;
use App\Models\PenawaranMataKuliah;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ScheduleConflictService
{
    public function validate(
        PenawaranMataKuliah $offering,
        array $attributes,
        ?JadwalMingguan $except = null,
        ?User $actor = null,
        bool $override = false,
        ?string $reason = null
    ): array {
        $conflicts = $this->conflicts($offering, $attributes, $except);
        $room = Ruang::findOrFail($attributes['ruang_id']);
        $requiredCapacity = max($offering->kapasitas, $offering->pesertaDisetujui()->count());
        if ($room->kapasitas < $requiredCapacity) {
            $conflicts['ruang_id'][] = "Kapasitas ruang {$room->kapasitas} tidak mencukupi kebutuhan {$requiredCapacity} peserta.";
        }

        if ($conflicts === []) {
            return ['alasan_pengecualian' => null, 'pengecualian_oleh' => null];
        }

        $canOverride = $override && $actor && (int) $actor->raw_type === 0 && mb_strlen(trim((string) $reason)) >= 10;
        if (! $canOverride) {
            throw ValidationException::withMessages($conflicts + [
                'override_conflict' => 'Bentrok hanya dapat dikecualikan oleh Web Administrator dengan alasan minimal 10 karakter.',
            ]);
        }

        return ['alasan_pengecualian' => trim((string) $reason), 'pengecualian_oleh' => $actor->id];
    }

    public function conflicts(PenawaranMataKuliah $offering, array $attributes, ?JadwalMingguan $except = null): array
    {
        $query = JadwalMingguan::query()
            ->forAcademicPeriod($offering->taka_id)
            ->where('hari', $attributes['hari'])
            ->where('mulai', '<', $attributes['selesai'])
            ->where('selesai', '>', $attributes['mulai'])
            ->when($except, fn ($query) => $query->whereKeyNot($except->id));

        $conflicts = [];
        if ((clone $query)->where('dosen_id', $attributes['dosen_id'])->exists()) {
            $conflicts['dosen_id'][] = 'Dosen memiliki jadwal lain pada hari dan rentang waktu yang bertumpuk.';
        }
        if ((clone $query)->where('kelas_id', $attributes['kelas_id'])->exists()) {
            $conflicts['kelas_id'][] = 'Kelas memiliki jadwal lain pada hari dan rentang waktu yang bertumpuk.';
        }
        if ((clone $query)->where('ruang_id', $attributes['ruang_id'])->exists()) {
            $conflicts['ruang_id'][] = 'Ruang telah digunakan pada hari dan rentang waktu yang bertumpuk.';
        }

        return $conflicts;
    }
}
