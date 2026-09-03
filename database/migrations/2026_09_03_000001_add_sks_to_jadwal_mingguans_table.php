<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_mingguans', function (Blueprint $table): void {
            $table->unsignedTinyInteger('sks')->nullable()->after('penawaran_mata_kuliah_id');
        });

        DB::table('jadwal_mingguans')
            ->whereNotNull('penawaran_mata_kuliah_id')
            ->orderBy('id')
            ->chunkById(250, function ($schedules): void {
                foreach ($schedules as $schedule) {
                    $credits = DB::table('penawaran_mata_kuliahs')
                        ->where('id', $schedule->penawaran_mata_kuliah_id)
                        ->value('sks');

                    if ($credits !== null) {
                        DB::table('jadwal_mingguans')
                            ->where('id', $schedule->id)
                            ->update(['sks' => $credits]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('jadwal_mingguans', function (Blueprint $table): void {
            $table->dropColumn('sks');
        });
    }
};
