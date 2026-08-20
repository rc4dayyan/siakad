<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JadwalMingguan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['hari' => 'integer'];
    }

    public function scopeForAcademicPeriod(Builder $query, PeriodeAkademik|int|null $period): Builder
    {
        $periodId = $period instanceof PeriodeAkademik ? $period->id : $period;

        return $periodId ? $query->where(function (Builder $query) use ($periodId): void {
            $query->whereHas('penawaranMataKuliah', fn (Builder $offering) => $offering->where('taka_id', $periodId))
                ->orWhere(function (Builder $legacy) use ($periodId): void {
                    $legacy->whereNull('penawaran_mata_kuliah_id')
                        ->whereHas('kelas', fn (Builder $class) => $class->where('taka_id', $periodId));
                });
        }) : $query->whereRaw('1 = 0');
    }

    public static function fingerprint(array $attributes): string
    {
        return hash('sha256', implode('|', [
            $attributes['penawaran_mata_kuliah_id'] ?? '',
            $attributes['kelas_id'],
            $attributes['dosen_id'],
            $attributes['ruang_id'],
            $attributes['hari'],
            $attributes['mulai'],
            $attributes['selesai'],
        ]));
    }

    public function penawaranMataKuliah()
    {
        return $this->belongsTo(PenawaranMataKuliah::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function ruang()
    {
        return $this->belongsTo(Ruang::class);
    }

    public function pengecualianOleh()
    {
        return $this->belongsTo(User::class, 'pengecualian_oleh');
    }

    public function pertemuans()
    {
        return $this->hasMany(PertemuanKuliah::class);
    }

    public function getHariLabelAttribute(): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'][$this->hari] ?? '-';
    }
}
