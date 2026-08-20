<?php

namespace App\Models\Concerns;

use App\Models\PeriodeAkademik;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPeriodeAkademik
{
    /**
     * Relasi domain periode akademik melalui foreign key legacy `taka_id`.
     */
    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class, 'taka_id');
    }
}
