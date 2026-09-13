<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nilai_mahasiswas', function (Blueprint $table) {
            $table->decimal('nilai_indeks', 5, 2)->nullable();
            $table->decimal('nilai_angka', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('nilai_mahasiswas', function (Blueprint $table) {
            $table->dropColumn(['nilai_indeks', 'nilai_angka']);
        });
    }
};
