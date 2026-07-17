<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_mata_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->string('program_studi', 10);
            $table->unsignedTinyInteger('semester');
            $table->string('name');
            $table->unsignedTinyInteger('sks');
            $table->timestamps();

            $table->unique(['program_studi', 'semester', 'name'], 'master_matkul_prodi_semester_name_unique');
            $table->index(['program_studi', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_mata_kuliahs');
    }
};
