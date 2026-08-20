<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function markAsRead()
    {
        $this->read = true;
        $this->save();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auth_id')->withDefault([
            'name' => 'Pengguna tidak tersedia',
        ]);
    }
}
