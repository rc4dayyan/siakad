<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicYearParentMigrationTest extends TestCase
{
    public function test_existing_periods_are_linked_to_one_academic_year(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();

        DB::table('tahun_akademiks')->insert([
            $this->legacyPeriod('2025-GANJIL', 'ganjil', 1),
            $this->legacyPeriod('2025-GENAP', 'genap', 2),
        ]);

        (require database_path('migrations/2026_08_20_000001_create_tahun_akademik_and_link_periods.php'))->up();

        $this->assertTrue(Schema::hasTable('tahun_akademik'));
        $this->assertTrue(Schema::hasColumn('tahun_akademiks', 'tid'));
        $this->assertDatabaseHas('tahun_akademik', [
            'name' => 'Tahun Akademik 2025/2026',
            'code' => '2025-2026',
            'year_start' => 2025,
            'year_end' => 2026,
        ]);
        $this->assertDatabaseCount('tahun_akademik', 1);

        $academicYearId = DB::table('tahun_akademik')->value('id');
        $this->assertSame(2, DB::table('tahun_akademiks')->where('tid', $academicYearId)->count());
    }

    private function legacyPeriod(string $code, string $term, int $semester): array
    {
        return [
            'name' => "2025/2026 {$term}",
            'code' => $code,
            'semester' => $semester,
            'year_start' => 2025,
            'year_end' => 2026,
            'term' => $term,
            'starts_at' => '2025-08-01',
            'ends_at' => '2026-07-31',
            'is_active' => 0,
            'status' => 'closed',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
