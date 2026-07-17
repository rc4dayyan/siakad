<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PertemuanKuliah extends Model
{
    public const STATUS_SCHEDULED = 'terjadwal';

    public const STATUS_COMPLETED = 'selesai';

    public const STATUS_CANCELLED = 'dibatalkan';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['pertemuan_ke' => 'integer', 'tanggal' => 'date'];
    }

    public function jadwalMingguan()
    {
        return $this->belongsTo(JadwalMingguan::class);
    }

    public function legacyJadwalKuliah()
    {
        return $this->belongsTo(JadwalKuliah::class, 'legacy_jadwal_kuliah_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function ruang()
    {
        return $this->belongsTo(Ruang::class);
    }

    public function absensis()
    {
        return $this->hasMany(AbsensiMahasiswa::class);
    }
}
