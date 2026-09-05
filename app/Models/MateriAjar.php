<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriAjar extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function penawaranMataKuliah()
    {
        return $this->belongsTo(PenawaranMataKuliah::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function getFileSizeLabelAttribute(): ?string
    {
        if ($this->file_size === null) {
            return null;
        }

        if ($this->file_size >= 1024 * 1024) {
            return number_format($this->file_size / (1024 * 1024), 1, ',', '.').' MB';
        }

        return number_format(max(1, $this->file_size / 1024), 0, ',', '.').' KB';
    }
}
