<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawaran_mata_kuliahs', function (Blueprint $table): void {
            $table->boolean('wajib_dijadwalkan')->default(true)->after('kapasitas')->index();
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_mata_kuliahs', function (Blueprint $table): void {
            $table->dropColumn('wajib_dijadwalkan');
        });
    }
};
