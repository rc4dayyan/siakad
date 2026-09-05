<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materi_ajars', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('penawaran_mata_kuliah_id')->constrained('penawaran_mata_kuliahs')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->restrictOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index(['penawaran_mata_kuliah_id', 'created_at'], 'materi_ajar_penawaran_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi_ajars');
    }
};
