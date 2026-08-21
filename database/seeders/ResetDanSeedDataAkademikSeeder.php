<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ResetDanSeedDataAkademikSeeder extends Seeder
{
    /**
     * Urutan tabel mengikuti dependensi dari data anak ke data induk.
     * Data institusi (fakultas, program studi, dan staf non-demo) dipertahankan.
     *
     * @var list<string>
     */
    private const ACADEMIC_TABLES = [
        'academic_workflow_audits',
        'period_copy_runs',
        'period_publications',
        'period_readiness_snapshots',
        'proses_kenaikan_semesters',
        'history_tagihans',
        'penerbitan_tagihan_batches',
        'tagihan_kuliahs',
        'template_tagihans',
        'absensi_mahasiswas',
        'pertemuan_kuliahs',
        'jadwal_mingguans',
        'student_scores',
        'student_tasks',
        'nilai_mahasiswas',
        'hasil_studis',
        'override_keuangan_krs',
        'krs_items',
        'krs',
        'riwayat_status_akademik_mahasiswas',
        'f_b_perkuliahans',
        'jadwal_kuliahs',
        'penawaran_mata_kuliahs',
        'registrasi_mahasiswas',
        'mata_kuliahs',
        'kalender_akademiks',
        'kelas',
        'program_kuliahs',
        'mahasiswas',
        'master_mata_kuliahs',
        'kurikulums',
        'ruangs',
        'gedungs',
        'tahun_akademiks',
        'tahun_akademik',
    ];

    public function run(): void
    {
        if (! Schema::hasTable('program_studis') || DB::table('program_studis')->doesntExist()) {
            throw new RuntimeException(
                'Reset dibatalkan: siapkan minimal satu program studi sebelum menjalankan seeder akademik.'
            );
        }

        Schema::withoutForeignKeyConstraints(function (): void {
            DB::transaction(function (): void {
                $this->removeStudentNotificationsAndSupportTickets();

                foreach (self::ACADEMIC_TABLES as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                if (Schema::hasTable('users')) {
                    DB::table('users')->where('code', 'like', 'DEMO-STAFF-%')->delete();
                }

                $this->call(DemoDuaTahunAkademikSeeder::class);
            });
        });
    }

    private function removeStudentNotificationsAndSupportTickets(): void
    {
        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->whereNotNull('student_id')
                ->orWhereNotNull('lecture_id')
                ->orWhereNotNull('class_id')
                ->orWhereNotNull('proku_id')
                ->delete();
        }

        if (Schema::hasTable('ticket_supports')) {
            DB::table('ticket_supports')->whereNotNull('users_id')->delete();
        }
    }
}
