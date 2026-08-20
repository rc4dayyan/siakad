<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataKuliah extends Model
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

    public function masterMataKuliah()
    {
        return $this->belongsTo(MasterMataKuliah::class, 'mid');
    }

    public function kuri()
    {
        return $this->belongsTo(Kurikulum::class, 'kuri_id');
    }

    public function taka()
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function requ()
    {
        return $this->belongsTo(MataKuliah::class, 'requ_id');
    }

    public function pstudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'pstudi_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function dosen1()
    {
        return $this->belongsTo(Dosen::class, 'dosen_1');
    }

    public function dosen2()
    {
        return $this->belongsTo(Dosen::class, 'dosen_2');
    }

    public function dosen3()
    {
        return $this->belongsTo(Dosen::class, 'dosen_3');
    }

    public function nilais()
    {
        return $this->hasMany(NilaiMahasiswa::class);
    }

    public function penawaran()
    {
        return $this->hasOne(PenawaranMataKuliah::class, 'legacy_mata_kuliah_id');
    }
}
