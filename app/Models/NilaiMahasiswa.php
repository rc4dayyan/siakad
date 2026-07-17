<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NilaiMahasiswa extends Model
{
    protected $fillable = [
        'mahasiswa_id',
        'taka_id',
        'mata_kuliah_id',
        'penawaran_mata_kuliah_id',
        'kelas_id',
        'dosen_id',
        'nilai',
        'keterangan',
    ];

    public function scopeForAcademicPeriod(Builder $query, TahunAkademik|int|null $period): Builder
    {
        $periodId = $period instanceof TahunAkademik ? $period->getKey() : $period;

        return $periodId
            ? $query->where('taka_id', $periodId)
            : $query->whereRaw('1 = 0');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function taka()
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function penawaranMataKuliah()
    {
        return $this->belongsTo(PenawaranMataKuliah::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}
