<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Kolom taka_id, years_id, dan class_id adalah fallback legacy selama migrasi TA-306.
 * Pembacaan akademik baru wajib menggunakan registrasiAkademik atau StudentAcademicContext.
 */
class Mahasiswa extends Authenticatable
{
    use HasFactory;

    protected $guard = 'admin';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getMhsStatAttribute($value)
    {
        $mhsstats = [
            0 => 'Calon Mahasiswa',
            1 => 'Mahasiswa Aktif',
            2 => 'Mahasiswa Non-Aktif',
            3 => 'Mahasiswa Alumni',
        ];

        return isset($mhsstats[$value]) ? $mhsstats[$value] : 'Unknown';
    }

    public function getRawMhsStatAttribute()
    {
        return $this->attributes['mhs_stat'];
    }

    public function getAgamaAttribute($value)
    {
        $mhsrelis = [
            0 => 'Belum Memilih',
            1 => 'Agama Islam',
            2 => 'Agama Kristen Katholik',
            3 => 'Agama Kristen Protestan',
            4 => 'Agama Hindu',
            5 => 'Agama Buddha',
            6 => 'Agama Konghuchu',
            7 => 'Kepercayaan Lainnya',
        ];

        return isset($mhsrelis[$value]) ? $mhsrelis[$value] : 'Unknown';
    }

    public function getRawMhsReliAttribute()
    {
        return $this->attributes['mhs_reli'];
    }

    public function getMhsPhoneAttribute($value)
    {
        // Periksa apakah nomor telepon dimulai dengan "0"
        if (strpos($value, '0') === 0) {
            // Jika ya, ubah menjadi "+62" dan hapus angka "0" di awal
            return '62'.substr($value, 1);
        }

        // Jika tidak dimulai dengan "0", biarkan seperti itu
        return $value;
    }

    /**
     * @deprecated Gunakan registrasiAkademik atau StudentAcademicContext. Hapus setelah audit TA-306 selesai.
     */
    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'class_id');
    }

    /**
     * @deprecated Gunakan registrasiAkademik atau StudentAcademicContext. Hapus setelah audit TA-306 selesai.
     */
    public function taka()
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function nilais()
    {
        return $this->hasMany(NilaiMahasiswa::class);
    }

    public function nilaiMahasiswa()
    {
        return $this->hasOne(NilaiMahasiswa::class, 'mahasiswa_id')
            ->where('mata_kuliah_id', $this->mata_kuliah_id ?? request('mata_kuliah_id'));
    }

    public function registrasiAkademik()
    {
        return $this->hasMany(RegistrasiMahasiswa::class);
    }

    public function registrasiAwal(): HasOne
    {
        return $this->hasOne(RegistrasiMahasiswa::class)->oldestOfMany();
    }

    public function scopeForAcademicClass(
        Builder $query,
        TahunAkademik|int|null $period,
        int $classId,
        bool $withLegacyFallback = true
    ): Builder {
        $periodId = $period instanceof TahunAkademik ? $period->id : $period;

        if (! $periodId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($periodId, $classId, $withLegacyFallback): void {
            $query->whereHas('registrasiAkademik', fn (Builder $registration) => $registration
                ->where('taka_id', $periodId)
                ->where('kelas_id', $classId));

            if ($withLegacyFallback) {
                $query->orWhere(function (Builder $legacy) use ($periodId, $classId): void {
                    $legacy->where('taka_id', $periodId)
                        ->where('class_id', $classId)
                        ->whereDoesntHave('registrasiAkademik', fn (Builder $registration) => $registration
                            ->where('taka_id', $periodId));
                });
            }
        });
    }

    public function scopeForApprovedOffering(Builder $query, PenawaranMataKuliah|int $offering): Builder
    {
        $offeringId = $offering instanceof PenawaranMataKuliah ? $offering->id : $offering;

        return $query->whereHas('registrasiAkademik.krs', fn (Builder $krs) => $krs
            ->whereIn('status', [Krs::STATUS_APPROVED, Krs::STATUS_LOCKED])
            ->whereHas('items', fn (Builder $items) => $items->where('penawaran_mata_kuliah_id', $offeringId)));
    }
}
