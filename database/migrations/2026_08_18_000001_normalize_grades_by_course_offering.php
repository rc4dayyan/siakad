<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nilai_mahasiswas', function (Blueprint $table): void {
            $table->unsignedBigInteger('mata_kuliah_id')->nullable()->change();
            $table->unique(
                ['mahasiswa_id', 'penawaran_mata_kuliah_id'],
                'nilai_mahasiswa_penawaran_unique'
            );
        });
    }

    public function down(): void
    {
        if (DB::table('nilai_mahasiswas')->whereNull('mata_kuliah_id')->exists()) {
            throw new \RuntimeException(
                'Rollback tidak dapat dilakukan karena terdapat nilai penawaran tanpa mata kuliah legacy.'
            );
        }

        Schema::table('nilai_mahasiswas', function (Blueprint $table): void {
            $table->dropUnique('nilai_mahasiswa_penawaran_unique');
            $table->unsignedBigInteger('mata_kuliah_id')->nullable(false)->change();
        });
    }
};
