<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KalenderAkademik extends Model
{
    public const KATEGORI_KRS = 'krs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mulai_at' => 'datetime',
            'selesai_at' => 'datetime',
            'dipublikasikan' => 'boolean',
        ];
    }

    public function scopeKrsAktif(Builder $query, int $periodId): Builder
    {
        return $query->where('taka_id', $periodId)
            ->where('kategori', self::KATEGORI_KRS)
            ->where('dipublikasikan', true)
            ->where('mulai_at', '<=', now())
            ->where('selesai_at', '>=', now());
    }

    public function taka()
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }
}
