<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    use HasFactory;

    protected $guarded = [];

    /** @return array<int, string> */
    public function masterMataKuliahCodes(): array
    {
        $originalCode = trim((string) $this->code);
        $code = strtoupper($originalCode);

        $aliases = match ($code) {
            '86208', 'PGPAI', 'PAI' => ['86208', 'PGPAI', 'PAI'],
            '88204', 'PGPBA', 'PGBA', 'PBA' => ['88204', 'PGPBA', 'PGBA', 'PBA'],
            '86233', 'PGRA', 'RA', 'PIAUD' => ['86233', 'PGRA', 'RA', 'PIAUD'],
            default => [$originalCode, $code],
        };

        return array_values(array_unique($aliases));
    }

    public function head()
    {
        return $this->belongsTo(Dosen::class, 'head_id');
    }

    public function fakultas()
    {
        return $this->belongsTo(Fakultas::class, 'faku_id');
    }
}
