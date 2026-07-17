<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMataKuliah extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'sks' => 'integer',
        ];
    }

    public function mataKuliahs()
    {
        return $this->hasMany(MataKuliah::class, 'mid');
    }
}
