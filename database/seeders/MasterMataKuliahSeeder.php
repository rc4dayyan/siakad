<?php

namespace Database\Seeders;

use App\Models\MasterMataKuliah;
use Illuminate\Database\Seeder;
use RuntimeException;

class MasterMataKuliahSeeder extends Seeder
{
    /**
     * Katalog terkompresi berisi 186 mata kuliah dari tiga program studi.
     * Data PBA menggunakan katalog-kelas.xlsx sebagai sumber terbaru.
     */
    private const SOURCE_DATA = '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';

    public function run(): void
    {
        MasterMataKuliah::upsert(
            self::rows(),
            ['code'],
            ['program_studi', 'name', 'sks', 'semester', 'updated_at'],
        );
    }

    /** @return list<array<string, int|string|\Illuminate\Support\Carbon>> */
    public static function rows(): array
    {
        $decoded = base64_decode(self::SOURCE_DATA, true);
        $data = $decoded === false ? false : gzinflate($decoded);

        if ($data === false) {
            throw new RuntimeException('Data master mata kuliah tertanam tidak dapat dibaca.');
        }

        $now = now();
        $rows = [];

        foreach (preg_split('/\R/', trim($data)) as $line) {
            [$programStudi, $code, $name, $sks, $semester] = explode('|', $line, 5);
            $rows[] = [
                'program_studi' => $programStudi,
                'code' => $code,
                'name' => $name,
                'sks' => (int) $sks,
                'semester' => (int) $semester,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }
}
