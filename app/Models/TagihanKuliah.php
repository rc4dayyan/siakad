<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagihanKuliah extends Model
{
    use Concerns\HasPeriodeAkademik, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_TERBIT = 'terbit';

    public const STATUS_DIBATALKAN = 'dibatalkan';

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

    public function scopeForAcademicPeriod(Builder $query, PeriodeAkademik|int|null $period): Builder
    {
        $periodId = $period instanceof PeriodeAkademik ? $period->getKey() : $period;

        return $periodId ? $query->where('taka_id', $periodId) : $query->whereRaw('1 = 0');
    }

    public function scopeForStudent(Builder $query, Mahasiswa|int $student): Builder
    {
        $studentId = $student instanceof Mahasiswa ? $student->getKey() : $student;

        return $query->where('target_type', 'mahasiswa')->where('target_mahasiswa_id', $studentId);
    }

    public function prokuu()
    {
        return $this->belongsTo(ProgramKuliah::class, 'proku_id');
    }

    public function prodi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'users_id');
    }

    public function taka(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateTagihan::class, 'template_tagihan_id');
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

    public function pembayarans(): HasMany
    {
        return $this->hasMany(HistoryTagihan::class, 'tagihan_kuliah_id');
    }
}
