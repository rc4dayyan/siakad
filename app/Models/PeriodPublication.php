<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodPublication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['summary' => 'array', 'published_at' => 'datetime'];
    }
}
