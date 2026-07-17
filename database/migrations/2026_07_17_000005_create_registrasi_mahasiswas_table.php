<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrasi_mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->foreignId('taka_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->unsignedTinyInteger('semester_mahasiswa');
            $table->string('status_akademik', 32)->default('aktif');
            $table->string('status_registrasi', 32)->default('terdaftar');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->restrictOnDelete();
            $table->foreignId('dosen_wali_id')->nullable()->constrained('dosens')->restrictOnDelete();
            $table->unsignedTinyInteger('batas_sks')->default(24);
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'taka_id'], 'registrasi_mahasiswa_taka_unique');
            $table->index(['taka_id', 'status_registrasi'], 'registrasi_taka_status_index');
            $table->index(['kelas_id', 'taka_id'], 'registrasi_kelas_taka_index');
            $table->index(['dosen_wali_id', 'taka_id'], 'registrasi_dosen_taka_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrasi_mahasiswas');
    }
};
