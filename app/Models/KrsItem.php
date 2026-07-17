<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KrsItem extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (KrsItem $item): void {
            if (! $item->krs->isEditable()) {
                throw new \LogicException('Item KRS yang telah diajukan tidak dapat diubah.');
            }
        });

        static::deleting(function (KrsItem $item): void {
            if (! $item->krs->isEditable()) {
                throw new \LogicException('Item KRS yang telah diajukan tidak dapat dihapus.');
            }
        });
    }

    public function krs()
    {
        return $this->belongsTo(Krs::class);
    }

    public function penawaranMataKuliah()
    {
        return $this->belongsTo(PenawaranMataKuliah::class);
    }
}
