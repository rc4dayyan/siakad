<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class studentTask extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeForAcademicPeriod(Builder $query, PeriodeAkademik|int|null $period): Builder
    {
        return $query->whereHas('jadkul', fn (Builder $query) => $query->forAcademicPeriod($period));
    }

    public function scopeForLecturer(Builder $query, int $lecturerId): Builder
    {
        return $query->where('dosen_id', $lecturerId);
    }

    public function jadkul()
    {
        return $this->belongsTo(JadwalKuliah::class, 'jadkul_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }
}
