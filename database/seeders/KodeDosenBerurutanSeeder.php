<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KodeDosenBerurutanSeeder extends Seeder
{
    public function run(): void
    {
        $count = DB::transaction(function (): int {
            $lecturers = DB::table('dosens')->orderBy('dsn_name')->orderBy('id')->lockForUpdate()->get(['id']);

            foreach ($lecturers as $index => $lecturer) {
                DB::table('dosens')->where('id', $lecturer->id)->update([
                    'dsn_code' => (string) ($index + 1),
                ]);
            }

            return $lecturers->count();
        });

        $this->command?->info("Kode {$count} dosen diperbarui menjadi nomor 1 sampai {$count} berdasarkan urutan nama A–Z.");
    }
}
