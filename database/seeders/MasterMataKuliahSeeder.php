<?php

namespace Database\Seeders;

use App\Models\MasterMataKuliah;
use Illuminate\Database\Seeder;
use RuntimeException;

class MasterMataKuliahSeeder extends Seeder
{
    /**
     * Salinan terkompresi dari 186 baris pada
     * master-mata-kuliah-20260821-094126.xlsx agar seeder portabel.
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
