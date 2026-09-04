<?php

namespace App\Models\FeedBack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FBPerkuliahan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fb_answers' => 'array',
            'fb_average_score' => 'decimal:2',
        ];
    }

    public function jadkul()
    {
        return $this->belongsTo(\App\Models\JadwalKuliah::class, 'fb_jakul_code', 'code');
    }
}
