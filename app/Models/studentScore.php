<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class studentScore extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeForAcademicPeriod(Builder $query, TahunAkademik|int|null $period): Builder
    {
        return $query->whereHas('task', fn (Builder $query) => $query->forAcademicPeriod($period));
    }

    public function scopeForLecturer(Builder $query, int $lecturerId): Builder
    {
        return $query->whereHas('task', fn (Builder $query) => $query->forLecturer($lecturerId));
    }

    public function task()
    {
        return $this->belongsTo(studentTask::class, 'stask_id');
    }

    public function student()
    {
        return $this->belongsTo(Mahasiswa::class, 'student_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'author_id');
    }
}
