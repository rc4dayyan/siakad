<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PurgeAcademicData extends Command
{
    protected $signature = 'academic:purge
                            {--preview : Tampilkan data yang akan dihapus tanpa mengubah database}
                            {--confirm : Hapus seluruh data transaksi akademik}';

    protected $description = 'Membersihkan data transaksi akademik sambil mempertahankan akun dan data master';

    /**
     * Urutan harus mengikuti dependensi foreign key, dari anak ke induk.
     *
     * @var array<string, string>
     */
    private array $tables = [
        'academic_workflow_audits' => 'Audit workflow akademik',
        'period_publications' => 'Publikasi periode',
        'period_readiness_snapshots' => 'Snapshot kesiapan periode',
        'period_copy_runs' => 'Riwayat salin periode',
        'proses_kenaikan_semesters' => 'Proses kenaikan semester',
        'history_tagihans' => 'Riwayat pembayaran',
        'penerbitan_tagihan_batches' => 'Riwayat penerbitan tagihan',
        'tagihan_kuliahs' => 'Tagihan kuliah',
        'template_tagihans' => 'Template tagihan',
        'absensi_mahasiswas' => 'Presensi mahasiswa',
        'pertemuan_kuliahs' => 'Pertemuan kuliah',
        'jadwal_mingguans' => 'Jadwal mingguan',
        'student_scores' => 'Nilai tugas mahasiswa',
        'student_tasks' => 'Tugas mahasiswa',
        'nilai_mahasiswas' => 'Nilai mata kuliah',
        'hasil_studis' => 'Hasil studi/KHS',
        'override_keuangan_krs' => 'Override keuangan KRS',
        'krs_items' => 'Item KRS',
        'krs' => 'KRS',
        'riwayat_status_akademik_mahasiswas' => 'Riwayat status akademik',
        'jadwal_kuliahs' => 'Jadwal kuliah legacy',
        'penawaran_mata_kuliahs' => 'Penawaran mata kuliah',
        'registrasi_mahasiswas' => 'Registrasi mahasiswa',
        'mata_kuliahs' => 'Mata kuliah per periode',
        'kalender_akademiks' => 'Kalender akademik',
        'kelas' => 'Kelas akademik',
        'program_kuliahs' => 'Program kuliah per periode',
        'tahun_akademiks' => 'Tahun akademik',
    ];

    public function handle(): int
    {
        $preview = (bool) $this->option('preview');
        $confirm = (bool) $this->option('confirm');

        if ($preview === $confirm) {
            $this->error('Gunakan tepat satu opsi: --preview untuk pemeriksaan atau --confirm untuk menghapus.');

            return self::FAILURE;
        }

        $counts = $this->counts();
        $this->table(['Data', 'Tabel', 'Jumlah'], array_map(
            fn (string $table, string $label): array => [$label, $table, $counts[$table]],
            array_keys($this->tables),
            array_values($this->tables),
        ));
        $total = array_sum($counts);

        if ($preview) {
            $this->newLine();
            $this->info("PREVIEW selesai: {$total} record transaksi akademik akan dihapus.");
            $this->comment('Tidak ada data yang diubah. Akun, mahasiswa, dosen, program studi, kurikulum, mata kuliah master, gedung, dan ruang dipertahankan.');

            return self::SUCCESS;
        }

        if ($total === 0) {
            $this->info('Data transaksi akademik sudah kosong.');

            return self::SUCCESS;
        }

        $this->warn("Menghapus {$total} record transaksi akademik. Tindakan ini tidak dapat dibatalkan tanpa backup.");

        try {
            DB::transaction(function (): void {
                if (Schema::hasTable('mahasiswas')) {
                    DB::table('mahasiswas')->update(['taka_id' => 0, 'class_id' => 0]);
                }

                foreach (array_keys($this->tables) as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
            });
        } catch (Throwable $exception) {
            $this->error('Pembersihan dibatalkan dan seluruh perubahan di-rollback: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Pembersihan selesai: {$total} record transaksi akademik dihapus.");
        $this->comment('Jalankan: php artisan db:seed --class=DemoDuaTahunAkademikSeeder --force');

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        $counts = [];

        foreach (array_keys($this->tables) as $table) {
            $counts[$table] = Schema::hasTable($table) ? DB::table($table)->count() : 0;
        }

        return $counts;
    }
}
