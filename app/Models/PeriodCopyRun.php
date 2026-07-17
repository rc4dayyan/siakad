<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodCopyRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['selections' => 'array', 'details' => 'array'];
    }
}
