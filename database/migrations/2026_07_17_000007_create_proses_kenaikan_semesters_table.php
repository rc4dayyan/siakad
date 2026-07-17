<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proses_kenaikan_semesters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('periode_sumber_id');
            $table->foreignId('periode_tujuan_id');
            $table->foreignId('kelas_sumber_id');
            $table->foreignId('kelas_tujuan_id');
            $table->foreignId('dosen_wali_id');
            $table->foreignId('diproses_oleh');
            $table->string('keputusan_cuti', 24);
            $table->string('keputusan_nonaktif', 24);
            $table->string('status_proses', 32)->default('running');
            $table->unsignedInteger('jumlah_sumber')->default(0);
            $table->unsignedInteger('jumlah_berhasil')->default(0);
            $table->unsignedInteger('jumlah_dilewati')->default(0);
            $table->unsignedInteger('jumlah_gagal')->default(0);
            $table->json('ringkasan')->nullable();
            $table->timestamps();

            $table->foreign('periode_sumber_id', 'kenaikan_periode_sumber_fk')->references('id')->on('tahun_akademiks')->restrictOnDelete();
            $table->foreign('periode_tujuan_id', 'kenaikan_periode_tujuan_fk')->references('id')->on('tahun_akademiks')->restrictOnDelete();
            $table->foreign('kelas_sumber_id', 'kenaikan_kelas_sumber_fk')->references('id')->on('kelas')->restrictOnDelete();
            $table->foreign('kelas_tujuan_id', 'kenaikan_kelas_tujuan_fk')->references('id')->on('kelas')->restrictOnDelete();
            $table->foreign('dosen_wali_id', 'kenaikan_dosen_wali_fk')->references('id')->on('dosens')->restrictOnDelete();
            $table->foreign('diproses_oleh', 'kenaikan_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $table->index(['periode_sumber_id', 'periode_tujuan_id'], 'kenaikan_periode_index');
            $table->index(['kelas_sumber_id', 'kelas_tujuan_id'], 'kenaikan_kelas_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proses_kenaikan_semesters');
    }
};
