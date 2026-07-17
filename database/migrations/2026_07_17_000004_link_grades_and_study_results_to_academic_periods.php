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
            $table->foreignId('taka_id')
                ->nullable()
                ->after('mahasiswa_id')
                ->constrained('tahun_akademiks')
                ->restrictOnDelete();
            $table->index(['taka_id', 'mahasiswa_id'], 'nilai_taka_mahasiswa_index');
        });

        DB::table('nilai_mahasiswas')
            ->select(['id', 'mata_kuliah_id'])
            ->orderBy('id')
            ->chunkById(500, function ($grades): void {
                $periods = DB::table('mata_kuliahs')
                    ->whereIn('id', $grades->pluck('mata_kuliah_id'))
                    ->pluck('taka_id', 'id');

                foreach ($grades as $grade) {
                    $periodId = $periods[$grade->mata_kuliah_id] ?? null;

                    if ($periodId) {
                        DB::table('nilai_mahasiswas')->where('id', $grade->id)->update(['taka_id' => $periodId]);
                    }
                }
            });

        Schema::table('hasil_studis', function (Blueprint $table): void {
            $table->dropUnique('hasil_studis_student_id_smt_id_unique');
            $table->unique(['student_id', 'taka_id'], 'hasil_studi_student_taka_unique');
            $table->index('taka_id', 'hasil_studi_taka_index');
        });
    }

    public function down(): void
    {
        Schema::table('hasil_studis', function (Blueprint $table): void {
            $table->dropIndex('hasil_studi_taka_index');
            $table->dropUnique('hasil_studi_student_taka_unique');
            $table->unique(['student_id', 'smt_id']);
        });

        Schema::table('nilai_mahasiswas', function (Blueprint $table): void {
            $table->dropIndex('nilai_taka_mahasiswa_index');
            $table->dropConstrainedForeignId('taka_id');
        });
    }
};
