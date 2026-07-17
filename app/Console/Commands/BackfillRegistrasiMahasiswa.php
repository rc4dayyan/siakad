<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Console\Command;
use Throwable;

class BackfillRegistrasiMahasiswa extends Command
{
    protected $signature = 'academic:backfill-registrations
                            {--dry-run : Tampilkan hasil tanpa menyimpan registrasi}
                            {--chunk=500 : Jumlah mahasiswa yang diproses per batch}';

    protected $description = 'Membentuk registrasi per periode dari taka_id dan class_id mahasiswa lama';

    public function handle(): int
    {
        $chunkSize = filter_var($this->option('chunk'), FILTER_VALIDATE_INT);

        if ($chunkSize === false || $chunkSize < 1 || $chunkSize > 5000) {
            $this->error('Opsi --chunk harus berupa angka antara 1 sampai 5000.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $counts = ['success' => 0, 'skipped' => 0, 'failed' => 0];
        $periodCache = [];
        $classCache = [];

        $this->components->info($dryRun
            ? 'Memeriksa backfill registrasi mahasiswa dalam mode DRY RUN.'
            : 'Memulai backfill registrasi mahasiswa.');

        Mahasiswa::query()
            ->select(['id', 'mhs_code', 'mhs_nim', 'mhs_stat', 'taka_id', 'class_id'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($students) use ($dryRun, &$counts, &$periodCache, &$classCache): void {
                foreach ($students as $student) {
                    try {
                        if (RegistrasiMahasiswa::where('mahasiswa_id', $student->id)->where('taka_id', $student->taka_id)->exists()) {
                            $counts['skipped']++;

                            continue;
                        }

                        $period = $this->cachedPeriod((int) $student->taka_id, $periodCache);
                        $class = $this->cachedClass((int) $student->class_id, $classCache);
                        $this->validateLegacyReferences($student, $period, $class);

                        if (! $dryRun) {
                            $registration = RegistrasiMahasiswa::firstOrCreate(
                                ['mahasiswa_id' => $student->id, 'taka_id' => $period->id],
                                [
                                    'semester_mahasiswa' => $this->semesterFrom($period),
                                    'status_akademik' => $this->academicStatusFrom((int) $student->getRawOriginal('mhs_stat')),
                                    'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
                                    'kelas_id' => $class->id,
                                    'dosen_wali_id' => $class->dosen_id ?: null,
                                    'batas_sks' => 24,
                                ]
                            );

                            if (! $registration->wasRecentlyCreated) {
                                $counts['skipped']++;

                                continue;
                            }
                        }

                        $counts['success']++;
                    } catch (Throwable $exception) {
                        $counts['failed']++;
                        $identity = $student->mhs_nim ?: $student->mhs_code ?: '#'.$student->id;
                        $this->warn("Mahasiswa {$identity} dilewati: {$exception->getMessage()}");
                    }
                }
            });

        $this->newLine();
        $this->table(['Mode', 'Berhasil', 'Dilewati', 'Gagal'], [[
            $dryRun ? 'DRY RUN' : 'SIMPAN',
            $counts['success'],
            $counts['skipped'],
            $counts['failed'],
        ]]);

        if ($dryRun) {
            $this->comment("{$counts['success']} registrasi dapat dibuat; tidak ada data yang disimpan.");
        }

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function cachedPeriod(int $periodId, array &$cache): ?TahunAkademik
    {
        if (! array_key_exists($periodId, $cache)) {
            $cache[$periodId] = $periodId > 0 ? TahunAkademik::find($periodId) : null;
        }

        return $cache[$periodId];
    }

    private function cachedClass(int $classId, array &$cache): ?Kelas
    {
        if (! array_key_exists($classId, $cache)) {
            $cache[$classId] = $classId > 0 ? Kelas::find($classId) : null;
        }

        return $cache[$classId];
    }

    private function validateLegacyReferences(Mahasiswa $student, ?TahunAkademik $period, ?Kelas $class): void
    {
        if (! $period) {
            throw new \RuntimeException('tahun akademik lama tidak ditemukan.');
        }

        if (! $class) {
            throw new \RuntimeException('kelas lama tidak ditemukan.');
        }

        if ((int) $class->taka_id !== (int) $period->id) {
            throw new \RuntimeException('kelas lama tidak berasal dari tahun akademik mahasiswa.');
        }
    }

    private function semesterFrom(TahunAkademik $period): int
    {
        return max(1, min(14, (int) $period->raw_semester));
    }

    private function academicStatusFrom(int $legacyStatus): string
    {
        return match ($legacyStatus) {
            1 => 'aktif',
            2 => 'nonaktif',
            3 => 'lulus',
            default => 'nonaktif',
        };
    }
}
