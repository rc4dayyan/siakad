<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_mata_kuliahs', function (Blueprint $table) {
            $table->dropUnique('master_matkul_prodi_semester_name_unique');
            $table->index(['program_studi', 'semester', 'name'], 'master_matkul_prodi_semester_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('master_mata_kuliahs', function (Blueprint $table) {
            $table->dropIndex('master_matkul_prodi_semester_name_index');
            $table->unique(['program_studi', 'semester', 'name'], 'master_matkul_prodi_semester_name_unique');
        });
    }
};
