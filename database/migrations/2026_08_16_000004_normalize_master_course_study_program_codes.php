<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private const FEEDER_CODES = [
        'PGPAI' => '86208',
        'PAI' => '86208',
        'PGPBA' => '88204',
        'PGBA' => '88204',
        'PBA' => '88204',
        'PGRA' => '86233',
        'RA' => '86233',
        'PIAUD' => '86233',
    ];

    public function up(): void
    {
        foreach (self::FEEDER_CODES as $legacyCode => $feederCode) {
            DB::table('master_mata_kuliahs')
                ->where('program_studi', $legacyCode)
                ->update(['program_studi' => $feederCode]);
        }
    }

    public function down(): void
    {
        $legacyCodes = [
            '86208' => 'PGPAI',
            '88204' => 'PGPBA',
            '86233' => 'PGRA',
        ];

        foreach ($legacyCodes as $feederCode => $legacyCode) {
            DB::table('master_mata_kuliahs')
                ->where('program_studi', $feederCode)
                ->update(['program_studi' => $legacyCode]);
        }
    }
};
