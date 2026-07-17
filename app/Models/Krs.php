<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class Krs extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LOCKED = 'locked';

    protected $table = 'krs';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['total_sks' => 'integer', 'diajukan_at' => 'datetime', 'diputuskan_at' => 'datetime'];
    }

    public function registrasiMahasiswa()
    {
        return $this->belongsTo(RegistrasiMahasiswa::class);
    }

    public function items()
    {
        return $this->hasMany(KrsItem::class);
    }

    public function diputuskanOleh()
    {
        return $this->belongsTo(Dosen::class, 'diputuskan_oleh');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function recalculateTotal(): int
    {
        if (! $this->isEditable()) {
            throw new LogicException('Total SKS KRS yang telah diajukan tidak dapat diubah.');
        }

        $total = (int) $this->items()->sum('sks');
        $this->forceFill(['total_sks' => $total])->save();

        return $total;
    }
}
