<?php

namespace App\Models;

use Database\Factories\RegistrasiMahasiswaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrasiMahasiswa extends Model
{
    /** @use HasFactory<RegistrasiMahasiswaFactory> */
    use Concerns\HasPeriodeAkademik, HasFactory;

    public const STATUS_AKADEMIK_AKTIF = 'aktif';

    public const STATUS_AKADEMIK_CUTI = 'cuti';

    public const STATUS_AKADEMIK_NONAKTIF = 'nonaktif';

    public const STATUS_AKADEMIK_LULUS = 'lulus';

    public const STATUS_AKADEMIK_DROP_OUT = 'drop_out';

    public const STATUS_AKADEMIK_MENGUNDURKAN_DIRI = 'mengundurkan_diri';

    public const STATUS_REGISTRASI_TERDAFTAR = 'terdaftar';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'semester_mahasiswa' => 'integer',
            'batas_sks' => 'integer',
        ];
    }

    public static function academicStatuses(): array
    {
        return array_keys(self::academicStatusLabels());
    }

    public static function academicStatusLabels(): array
    {
        return [
            self::STATUS_AKADEMIK_AKTIF => 'Aktif',
            self::STATUS_AKADEMIK_CUTI => 'Cuti',
            self::STATUS_AKADEMIK_NONAKTIF => 'Nonaktif',
            self::STATUS_AKADEMIK_LULUS => 'Lulus',
            self::STATUS_AKADEMIK_DROP_OUT => 'Drop Out',
            self::STATUS_AKADEMIK_MENGUNDURKAN_DIRI => 'Mengundurkan Diri',
        ];
    }

    public static function academicStatusLabel(string $status): string
    {
        return self::academicStatusLabels()[$status] ?? 'Tidak diketahui';
    }

    public static function terminalAcademicStatuses(): array
    {
        return [
            self::STATUS_AKADEMIK_LULUS,
            self::STATUS_AKADEMIK_DROP_OUT,
            self::STATUS_AKADEMIK_MENGUNDURKAN_DIRI,
        ];
    }

    public function hasTerminalAcademicStatus(): bool
    {
        return in_array($this->status_akademik, self::terminalAcademicStatuses(), true);
    }

    public function getAcademicStatusLabelAttribute(): string
    {
        return self::academicStatusLabel($this->status_akademik);
    }

    public function allowedAcademicStatusTransitions(): array
    {
        return match ($this->status_akademik) {
            self::STATUS_AKADEMIK_AKTIF => [
                self::STATUS_AKADEMIK_CUTI,
                self::STATUS_AKADEMIK_NONAKTIF,
                self::STATUS_AKADEMIK_LULUS,
                self::STATUS_AKADEMIK_DROP_OUT,
                self::STATUS_AKADEMIK_MENGUNDURKAN_DIRI,
            ],
            self::STATUS_AKADEMIK_CUTI => [
                self::STATUS_AKADEMIK_AKTIF,
                self::STATUS_AKADEMIK_NONAKTIF,
                self::STATUS_AKADEMIK_DROP_OUT,
                self::STATUS_AKADEMIK_MENGUNDURKAN_DIRI,
            ],
            self::STATUS_AKADEMIK_NONAKTIF => [
                self::STATUS_AKADEMIK_AKTIF,
                self::STATUS_AKADEMIK_CUTI,
                self::STATUS_AKADEMIK_DROP_OUT,
                self::STATUS_AKADEMIK_MENGUNDURKAN_DIRI,
            ],
            default => [],
        };
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedAcademicStatusTransitions(), true);
    }

    public function scopeForAcademicPeriod(Builder $query, PeriodeAkademik|int|null $period): Builder
    {
        $periodId = $period instanceof PeriodeAkademik ? $period->getKey() : $period;

        return $periodId
            ? $query->where('taka_id', $periodId)
            : $query->whereRaw('1 = 0');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function taka(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function dosenWali(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_wali_id');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusAkademikMahasiswa::class, 'registrasi_mahasiswa_id');
    }

    public function krs()
    {
        return $this->hasOne(Krs::class);
    }
}
