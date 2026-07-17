<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateTagihan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'tanggal_terbit' => 'date',
            'jatuh_tempo' => 'date',
            'wajib_lunas_krs' => 'boolean',
        ];
    }

    public function scopeForAcademicPeriod(Builder $query, TahunAkademik|int|null $period): Builder
    {
        $periodId = $period instanceof TahunAkademik ? $period->getKey() : $period;

        return $periodId ? $query->where('taka_id', $periodId) : $query->whereRaw('1 = 0');
    }

    public function taka(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function targetMahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'target_mahasiswa_id');
    }

    public function targetProdi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'target_prodi_id');
    }

    public function targetProku(): BelongsTo
    {
        return $this->belongsTo(ProgramKuliah::class, 'target_proku_id');
    }

    public function tagihans(): HasMany
    {
        return $this->hasMany(TagihanKuliah::class);
    }
}
