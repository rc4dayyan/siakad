<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusAkademikMahasiswa extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'date',
        ];
    }

    public function registrasi(): BelongsTo
    {
        return $this->belongsTo(RegistrasiMahasiswa::class, 'registrasi_mahasiswa_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
