<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Representasi domain periode akademik.
 *
 * Nama tabel `tahun_akademiks` dan foreign key `taka_id` dipertahankan sebagai
 * kontrak database legacy. Setiap record tetap mewakili satu periode/term.
 */
class PeriodeAkademik extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ARCHIVED = 'archived';

    public const TERM_GANJIL = 'ganjil';

    public const TERM_GENAP = 'genap';

    public const TERM_PENDEK = 'pendek';

    protected $table = 'tahun_akademiks';

    protected $guarded = [];

    protected $casts = [
        'year_start' => 'integer',
        'year_end' => 'integer',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'activated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
        ];
    }

    public static function terms(): array
    {
        return [
            self::TERM_GANJIL,
            self::TERM_GENAP,
            self::TERM_PENDEK,
        ];
    }

    public function isWritable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ACTIVE], true);
    }

    public function getTermLabelAttribute(): string
    {
        return match ($this->term) {
            self::TERM_GANJIL => 'Ganjil',
            self::TERM_GENAP => 'Genap',
            self::TERM_PENDEK => 'Semester Pendek',
            default => 'Belum ditentukan',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_CLOSED => 'Ditutup',
            self::STATUS_ARCHIVED => 'Diarsipkan',
            default => 'Tidak diketahui',
        };
    }

    public function getSemesterAttribute($value)
    {
        $semesters = [
            0 => 'Belum dipilih',
            1 => 'Semester I',
            2 => 'Semester II',
            3 => 'Semester III',
            4 => 'Semester IV',
            5 => 'semester V',
            6 => 'Semester VI',
            7 => 'Semester VII',
            8 => 'Semester VIII',
        ];

        return $semesters[$value] ?? 'Unknown';
    }

    public function getRawSemesterAttribute()
    {
        return $this->attributes['semester'];
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademikInduk::class, 'tid');
    }

    public function programKuliahs(): HasMany
    {
        return $this->hasMany(ProgramKuliah::class, 'taka_id');
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'taka_id');
    }

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(MataKuliah::class, 'taka_id');
    }

    public function mahasiswas(): HasMany
    {
        return $this->hasMany(Mahasiswa::class, 'taka_id');
    }

    public function hasilStudis(): HasMany
    {
        return $this->hasMany(HasilStudi::class, 'taka_id');
    }

    public function registrasiMahasiswas(): HasMany
    {
        return $this->hasMany(RegistrasiMahasiswa::class, 'taka_id');
    }

    public function hasAcademicData(): bool
    {
        return $this->registrasiMahasiswas()->exists()
            || $this->programKuliahs()->exists()
            || $this->kelas()->exists()
            || $this->mataKuliahs()->exists()
            || $this->mahasiswas()->exists()
            || $this->hasilStudis()->exists()
            || (Schema::hasTable('period_readiness_snapshots')
                && DB::table('period_readiness_snapshots')->where('taka_id', $this->id)->exists())
            || (Schema::hasTable('period_copy_runs')
                && DB::table('period_copy_runs')->where('source_period_id', $this->id)
                    ->orWhere('target_period_id', $this->id)->exists());
    }
}
