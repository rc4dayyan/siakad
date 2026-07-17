<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->foreignId('mid')
                ->nullable()
                ->after('id')
                ->constrained('master_mata_kuliahs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mid');
        });
    }
};
