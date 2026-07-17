<?php

namespace App\Services\Academic;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicIntegrityService
{
    public function report(): array
    {
        $relations = [
            ['registrasi_mahasiswas', 'taka_id', 'tahun_akademiks'],
            ['registrasi_mahasiswas', 'mahasiswa_id', 'mahasiswas'],
            ['registrasi_mahasiswas', 'kelas_id', 'kelas'],
            ['penawaran_mata_kuliahs', 'taka_id', 'tahun_akademiks'],
            ['penawaran_mata_kuliahs', 'kelas_id', 'kelas'],
            ['jadwal_mingguans', 'penawaran_mata_kuliah_id', 'penawaran_mata_kuliahs'],
            ['tagihan_kuliahs', 'taka_id', 'tahun_akademiks'],
            ['history_tagihans', 'tagihan_kuliah_id', 'tagihan_kuliahs'],
        ];
        $items = [];

        foreach ($relations as [$table, $column, $parent]) {
            if (! Schema::hasTable($table) || ! Schema::hasTable($parent) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $orphans = DB::table($table.' as child')
                ->leftJoin($parent.' as parent', 'parent.id', '=', 'child.'.$column)
                ->whereNotNull('child.'.$column)->whereNull('parent.id')->count();
            $items[] = ['relation' => "{$table}.{$column} → {$parent}.id", 'orphans' => $orphans,
                'policy' => $this->policy($table, $column)];
        }

        return [
            'status' => collect($items)->sum('orphans') === 0 ? 'siap' : 'gagal',
            'orphan_count' => collect($items)->sum('orphans'),
            'relations' => $items,
            'business_constraints' => [
                'Satu registrasi per mahasiswa/periode',
                'Satu penawaran per mata kuliah/periode/prodi/kurikulum/kelas',
                'Satu item mata kuliah per KRS',
                'Satu jenis tagihan per mahasiswa/periode',
            ],
        ];
    }

    private function policy(string $table, string $column): string
    {
        return match ([$table, $column]) {
            ['history_tagihans', 'tagihan_kuliah_id'], ['jadwal_mingguans', 'penawaran_mata_kuliah_id'] => 'set null/restrict sesuai histori',
            ['registrasi_mahasiswas', 'mahasiswa_id'] => 'cascade hanya saat data mahasiswa belum bertransaksi',
            default => 'restrict',
        };
    }
}
