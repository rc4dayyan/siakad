<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryTagihan extends Model
{
    use Concerns\HasPeriodeAkademik, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'lunas';

    public const STATUS_REJECTED = 'ditolak';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'tanggal_transfer' => 'date',
            'diajukan_at' => 'datetime',
            'ditinjau_at' => 'datetime',
            'dibayar_at' => 'datetime',
        ];
    }

    public function users()
    {
        return $this->belongsTo(Mahasiswa::class, 'users_id');
    }

    public function tagihan()
    {
        return $this->belongsTo(TagihanKuliah::class, 'tagihan_code', 'code');
    }

    public function tagihanKuliah(): BelongsTo
    {
        return $this->belongsTo(TagihanKuliah::class, 'tagihan_kuliah_id');
    }

    public function taka(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function ditinjauOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function getPriceAttribute($value)
    {
        // Hapus aksesor ini jika Anda ingin mengakses nilai asli tanpa format tambahan
        $this->attributes['price'] = str_replace(['Rp.', ' ', '.'], '', $value);

    }

    public function getRawPriceAttribute()
    {
        return $this->attributes['price'];
    }
}
