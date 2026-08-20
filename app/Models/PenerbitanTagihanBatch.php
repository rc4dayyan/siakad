<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenerbitanTagihanBatch extends Model
{
    use Concerns\HasPeriodeAkademik;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['konfigurasi' => 'array'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateTagihan::class, 'template_tagihan_id');
    }

    public function taka(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
