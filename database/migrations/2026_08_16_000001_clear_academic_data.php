<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $academicTables = [
            // Audit dan workflow periode.
            'academic_workflow_audits',
            'period_copy_runs',
            'period_publications',
            'period_readiness_snapshots',

            // Keuangan yang terikat langsung ke mahasiswa dan periode akademik.
            'override_keuangan_krs',
            'history_tagihans',
            'penerbitan_tagihan_batches',
            'tagihan_kuliahs',
            // 'template_tagihans',

            // Aktivitas perkuliahan, KRS, nilai, dan presensi.
            'absensi_mahasiswas',
            'pertemuan_kuliahs',
            'jadwal_mingguans',
            'jadwal_kuliahs',
            'nilai_mahasiswas',
            'krs_items',
            'krs',
            'penawaran_mata_kuliahs',
            'kalender_akademiks',
            'riwayat_status_akademik_mahasiswas',
            'proses_kenaikan_semesters',
            'registrasi_mahasiswas',
            'hasil_studis',
            'student_scores',
            'student_tasks',
            'f_b_perkuliahans',

            // Data induk akademik.
            'mata_kuliahs',
            // 'master_mata_kuliahs',
            'mahasiswas',
            'kelas',
            'dosens',
            'ruangs',
            'gedungs',
            // 'kurikulums',
            // 'program_kuliahs',
            'tahun_akademiks',
            // 'program_studis',
            // 'fakultas',
        ];

        Schema::withoutForeignKeyConstraints(function () use ($academicTables): void {
            DB::transaction(function () use ($academicTables): void {
                foreach ($academicTables as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data yang telah dihapus tidak dapat dipulihkan oleh migration.
    }
};
