<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_tagihans', function (Blueprint $table): void {
            $table->string('bukti_path')->nullable()->after('snap_token');
            $table->date('tanggal_transfer')->nullable()->after('bukti_path');
            $table->string('nama_pengirim')->nullable()->after('tanggal_transfer');
            $table->timestamp('diajukan_at')->nullable()->after('nama_pengirim');
            $table->timestamp('ditinjau_at')->nullable()->after('diajukan_at');
            $table->foreignId('ditinjau_oleh')->nullable()->after('ditinjau_at')->constrained('users')->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable()->after('ditinjau_oleh');
            $table->index(['status', 'diajukan_at'], 'pembayaran_manual_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('history_tagihans', function (Blueprint $table): void {
            $table->dropIndex('pembayaran_manual_status_index');
            $table->dropConstrainedForeignId('ditinjau_oleh');
            $table->dropColumn([
                'bukti_path',
                'tanggal_transfer',
                'nama_pengirim',
                'diajukan_at',
                'ditinjau_at',
                'catatan_verifikasi',
            ]);
        });
    }
};
