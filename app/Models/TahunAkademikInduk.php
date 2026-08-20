<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAkademikInduk extends Model
{
    protected $table = 'tahun_akademik';

    protected $guarded = [];

    protected $casts = [
        'year_start' => 'integer',
        'year_end' => 'integer',
    ];

    public function periodeAkademiks(): HasMany
    {
        return $this->hasMany(PeriodeAkademik::class, 'tid');
    }
}
