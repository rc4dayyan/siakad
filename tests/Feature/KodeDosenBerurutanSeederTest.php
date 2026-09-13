<?php

namespace Tests\Feature;

use Database\Seeders\KodeDosenBerurutanSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KodeDosenBerurutanSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        (require database_path('migrations/2024_03_09_024021_create_dosens_table.php'))->up();
    }

    public function test_codes_are_sequential_by_name_with_id_tiebreak_and_other_attributes_are_preserved(): void
    {
        foreach ([8 => 'Zainal', 2 => 'Budi', 14 => 'Ahmad', 20 => 'Budi'] as $id => $name) {
            DB::table('dosens')->insert([
                'id' => $id,
                'dsn_nidn' => 'TEST-'.$id,
                'dsn_name' => $name,
                'dsn_code' => 'LAMA-'.$id,
                'dsn_user' => 'dosen.uji.'.$id,
                'dsn_mail' => 'dosen.'.$id.'@example.test',
                'dsn_phone' => 'TEST-PHONE-'.$id,
                'password' => 'test-hash',
            ]);
        }
        $before = DB::table('dosens')->orderBy('dsn_name')->orderBy('id')->get()->map(fn ($row) => (array) $row);
        $this->seed(KodeDosenBerurutanSeeder::class);
        $after = DB::table('dosens')->orderBy('dsn_name')->orderBy('id')->get()->map(fn ($row) => (array) $row);
        $this->assertSame([14, 2, 20, 8], $after->pluck('id')->all());
        $this->assertSame(['1', '2', '3', '4'], $after->pluck('dsn_code')->all());
        foreach ($before as $index => $attributes) {
            $attributes['dsn_code'] = (string) ($index + 1);
            $this->assertSame($attributes, $after[$index]);
        }
        $this->seed(KodeDosenBerurutanSeeder::class);
        $this->assertSame($after->all(), DB::table('dosens')->orderBy('dsn_name')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function test_empty_lecturer_table_is_not_populated(): void
    {
        $this->seed(KodeDosenBerurutanSeeder::class);
        $this->assertDatabaseCount('dosens', 0);
    }
}
