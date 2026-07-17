<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_status_akademik_mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registrasi_mahasiswa_id');
            $table->string('status_sebelumnya', 32);
            $table->string('status_baru', 32);
            $table->text('alasan');
            $table->date('berlaku_mulai');
            $table->foreignId('changed_by');
            $table->timestamps();

            $table->foreign('registrasi_mahasiswa_id', 'riwayat_status_registrasi_fk')
                ->references('id')
                ->on('registrasi_mahasiswas')
                ->restrictOnDelete();
            $table->foreign('changed_by', 'riwayat_status_actor_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index(
                ['registrasi_mahasiswa_id', 'berlaku_mulai'],
                'riwayat_status_registrasi_berlaku_index'
            );
            $table->index(['status_baru', 'berlaku_mulai'], 'riwayat_status_baru_berlaku_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_akademik_mahasiswas');
    }
};
