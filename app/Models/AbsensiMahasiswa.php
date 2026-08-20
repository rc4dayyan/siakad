<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiMahasiswa extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeForAcademicPeriod(Builder $query, PeriodeAkademik|int|null $period): Builder
    {
        return $query->whereHas('jadkul', fn (Builder $query) => $query->forAcademicPeriod($period));
    }

    public function getAbsenTypeAttribute($value)
    {
        $absentypes = [
            'H' => 'Hadir',
            'S' => 'Sakit',
            'I' => 'Izin',
        ];

        return isset($absentypes[$value]) ? $absentypes[$value] : 'Unknown';
    }

    public function getRawAbsenTypeAttribute()
    {
        return $this->attributes['dsn_stat'];
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'author_id');
    }

    public function jadkul()
    {
        return $this->belongsTo(JadwalKuliah::class, 'jadkul_code', 'code');
    }

    public function pertemuanKuliah()
    {
        return $this->belongsTo(PertemuanKuliah::class);
    }

    public function krsItem()
    {
        return $this->belongsTo(KrsItem::class);
    }
}
