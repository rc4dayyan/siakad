<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverrideKeuanganKrs extends Model
{
    protected $table = 'override_keuangan_krs';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['berlaku_sampai' => 'datetime'];
    }

    public function registrasi(): BelongsTo
    {
        return $this->belongsTo(RegistrasiMahasiswa::class, 'registrasi_mahasiswa_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
