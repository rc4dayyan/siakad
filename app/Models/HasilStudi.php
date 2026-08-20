<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilStudi extends Model
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

    public function student()
    {
        return $this->belongsTo(Mahasiswa::class, 'student_id');
    }

    public function taka()
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }
}
