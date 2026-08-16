<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_mata_kuliahs', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->unique()->after('program_studi');
        });
    }

    public function down(): void
    {
        Schema::table('master_mata_kuliahs', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
