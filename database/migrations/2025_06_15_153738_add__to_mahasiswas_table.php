<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->string('mhs_nik')->nullable()->after('mhs_nim');
            $table->dateTime('mhs_register_date')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('mhs_status')->nullable()->default('AKTIF');
            $table->string('mhs_register_type')->nullable()->default('Peserta didik baru');
            $table->float('mhs_register_amount')->nullable()->default(3000000);
            $table->string('mhs_sync_status')->nullable()->default('Belum Sync');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->dropColumn('mhs_nik');
            $table->dropColumn('mhs_register_date');
            $table->dropColumn('mhs_status');
            $table->dropColumn('mhs_register_type');
            $table->dropColumn('mhs_register_amount');
            $table->dropColumn('mhs_sync_status');
        });
    }
};
