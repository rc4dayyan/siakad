<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodReadinessSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['checks' => 'array', 'checked_at' => 'datetime'];
    }
}
