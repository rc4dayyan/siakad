<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsesKenaikanSemester extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ringkasan' => 'array',
        ];
    }

    public function periodeSumber(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'periode_sumber_id');
    }

    public function periodeTujuan(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'periode_tujuan_id');
    }

    public function kelasSumber(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_sumber_id');
    }

    public function kelasTujuan(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_tujuan_id');
    }

    public function dosenWali(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_wali_id');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
