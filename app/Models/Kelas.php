<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    use Concerns\HasPeriodeAkademik, HasFactory;

    protected $guarded = [];

    public function scopeForAcademicPeriod(Builder $query, PeriodeAkademik|int|null $period): Builder
    {
        $periodId = $period instanceof PeriodeAkademik ? $period->getKey() : $period;

        return $periodId
            ? $query->where('taka_id', $periodId)
            : $query->whereRaw('1 = 0');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function pstudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'pstudi_id');
    }

    public function proku(): BelongsTo
    {
        return $this->belongsTo(ProgramKuliah::class, 'proku_id');
    }

    public function taka(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(MataKuliah::class);
    }

    public function mahasiswas(): HasMany
    {
        return $this->hasMany(Mahasiswa::class, 'class_id');
    }

    public function registrasiMahasiswas(): HasMany
    {
        return $this->hasMany(RegistrasiMahasiswa::class);
    }
}
