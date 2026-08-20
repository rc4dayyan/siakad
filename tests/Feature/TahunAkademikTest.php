<?php

namespace Tests\Feature;

use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TahunAkademikTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');

        $this->createUsersTable();
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        (require database_path('migrations/2026_08_20_000001_create_tahun_akademik_and_link_periods.php'))->up();
        $this->createAcademicDependencyTables();
    }

    public function test_web_administrator_can_create_a_draft_period(): void
    {
        $payload = $this->periodPayload();
        $payload['code'] = '2026-ganjil';
        $payload['year_start'] = 1999;
        $payload['year_end'] = 2000;

        $response = $this
            ->actingAs($this->staffUser(0, 'WEBADMIN'))
            ->post(route('web-admin.master.taka-store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tahun_akademiks', [
            'code' => '2026-GANJIL',
            'term' => TahunAkademik::TERM_GANJIL,
            'semester' => 1,
            'status' => TahunAkademik::STATUS_DRAFT,
            'is_active' => 0,
            'year_start' => 2026,
            'year_end' => 2027,
        ]);
        $this->assertDatabaseHas('tahun_akademik', [
            'name' => 'Tahun Akademik 2026/2027',
            'code' => '2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
        ]);
        $this->assertNotNull(TahunAkademik::where('code', '2026-GANJIL')->value('tid'));
    }

    public function test_period_validation_rejects_duplicate_code_invalid_parent_and_invalid_dates(): void
    {
        TahunAkademik::create([
            ...$this->periodPayload(),
            'semester' => 1,
        ]);

        $response = $this
            ->actingAs($this->staffUser(0, 'WEBADMIN'))
            ->post(route('web-admin.master.taka-store'), $this->periodPayload([
                'tid' => 999999,
                'starts_at' => '2026-12-31',
                'ends_at' => '2026-08-01',
            ]));

        $response->assertSessionHasErrors(['code', 'tid', 'ends_at']);
        $this->assertDatabaseCount('tahun_akademiks', 1);
    }

    public function test_same_term_cannot_be_created_twice_in_one_academic_year(): void
    {
        TahunAkademik::create([
            ...$this->periodPayload(),
            'semester' => 1,
        ]);

        $response = $this
            ->actingAs($this->staffUser(0, 'WEBADMIN'))
            ->post(route('web-admin.master.taka-store'), $this->periodPayload([
                'name' => 'Duplikat Ganjil',
                'code' => '2026-GANJIL-LAIN',
            ]));

        $response->assertSessionHasErrors('term');
        $this->assertDatabaseCount('tahun_akademiks', 1);
    }

    public function test_draft_period_can_move_to_another_academic_year_and_inherits_its_range(): void
    {
        $period = TahunAkademik::create([
            ...$this->periodPayload(),
            'semester' => 1,
        ]);
        $targetYearId = $this->academicYearId(2027, 2028);
        $payload = $this->periodPayload([
            'tid' => $targetYearId,
            'name' => '2027/2028 Genap',
            'code' => '2027-2028-GENAP',
            'term' => TahunAkademik::TERM_GENAP,
        ]);
        $payload['year_start'] = 1900;
        $payload['year_end'] = 1901;

        $this
            ->actingAs($this->staffUser(0, 'WEBADMIN'))
            ->patch(route('web-admin.master.taka-update', $period->code), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tahun_akademiks', [
            'id' => $period->id,
            'tid' => $targetYearId,
            'year_start' => 2027,
            'year_end' => 2028,
            'term' => TahunAkademik::TERM_GENAP,
            'semester' => 2,
        ]);
    }

    public function test_activating_a_period_closes_the_previous_active_period(): void
    {
        $administrator = $this->staffUser(0, 'WEBADMIN');
        $previous = TahunAkademik::create([
            ...$this->periodPayload([
                'name' => 'Tahun Akademik 2025/2026 Genap',
                'code' => '2025-GENAP',
                'term' => TahunAkademik::TERM_GENAP,
                'year_start' => 2025,
                'year_end' => 2026,
                'starts_at' => '2026-01-12',
                'ends_at' => '2026-06-30',
            ]),
            'semester' => 2,
            'status' => TahunAkademik::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $next = TahunAkademik::create([
            ...$this->periodPayload(),
            'tid' => $this->academicYearId(2026, 2027),
            'semester' => 1,
        ]);

        $response = $this
            ->actingAs($administrator)
            ->patch(route('web-admin.master.taka-activate', $next->code));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tahun_akademiks', [
            'id' => $previous->id,
            'status' => TahunAkademik::STATUS_CLOSED,
            'is_active' => 0,
        ]);
        $this->assertDatabaseHas('tahun_akademiks', [
            'id' => $next->id,
            'status' => TahunAkademik::STATUS_ACTIVE,
            'is_active' => 1,
            'activated_by' => $administrator->id,
        ]);
    }

    public function test_non_web_administrator_cannot_activate_a_period(): void
    {
        $period = TahunAkademik::create([
            ...$this->periodPayload(),
            'semester' => 1,
        ]);

        $response = $this
            ->actingAs($this->staffUser(3, 'ACADEMIC'))
            ->patch(route('web-admin.master.taka-activate', $period->code));

        $response->assertRedirect(route('error.access'));
        $this->assertDatabaseHas('tahun_akademiks', [
            'id' => $period->id,
            'status' => TahunAkademik::STATUS_DRAFT,
            'is_active' => 0,
        ]);
    }

    public function test_active_period_cannot_be_changed_or_deleted_through_draft_crud(): void
    {
        $period = TahunAkademik::create([
            ...$this->periodPayload(),
            'semester' => 1,
            'status' => TahunAkademik::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $administrator = $this->staffUser(0, 'WEBADMIN');

        $this
            ->actingAs($administrator)
            ->patch(route('web-admin.master.taka-update', $period->code), $this->periodPayload([
                'name' => 'Nama yang tidak boleh tersimpan',
            ]));
        $this
            ->actingAs($administrator)
            ->delete(route('web-admin.master.taka-destroy', $period->code));

        $this->assertDatabaseHas('tahun_akademiks', [
            'id' => $period->id,
            'name' => 'Tahun Akademik 2026/2027 Ganjil',
            'status' => TahunAkademik::STATUS_ACTIVE,
        ]);
    }

    public function test_unknown_period_code_uses_the_application_not_found_handler(): void
    {
        $this
            ->actingAs($this->staffUser(0, 'WEBADMIN'))
            ->patch(route('web-admin.master.taka-activate', 'TIDAK-ADA'))
            ->assertRedirect(route('error.notfound'));
    }

    public function test_period_with_academic_data_cannot_be_deleted(): void
    {
        $period = TahunAkademik::create([
            ...$this->periodPayload(),
            'semester' => 1,
        ]);
        DB::table('kelas')->insert(['taka_id' => $period->id]);

        $this
            ->actingAs($this->staffUser(0, 'WEBADMIN'))
            ->delete(route('web-admin.master.taka-destroy', $period->code));

        $this->assertDatabaseHas('tahun_akademiks', ['id' => $period->id]);
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('type');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('user');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('status')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    private function createAcademicDependencyTables(): void
    {
        foreach (['program_kuliahs', 'kelas', 'mata_kuliahs', 'mahasiswas', 'hasil_studis'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('taka_id')->nullable();
            });
        }
    }

    private function staffUser(int $type, string $code): User
    {
        return User::create([
            'type' => $type,
            'code' => $code,
            'name' => 'Test User',
            'user' => strtolower($code),
            'phone' => '08'.$type.'123456789',
            'email' => strtolower($code).'@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }

    private function academicYearId(int $yearStart, int $yearEnd): int
    {
        return TahunAkademikInduk::firstOrCreate(
            ['year_start' => $yearStart, 'year_end' => $yearEnd],
            [
                'name' => "Tahun Akademik {$yearStart}/{$yearEnd}",
                'code' => "{$yearStart}-{$yearEnd}",
            ]
        )->id;
    }

    private function periodPayload(array $overrides = []): array
    {
        $yearStart = $overrides['year_start'] ?? 2026;
        $yearEnd = $overrides['year_end'] ?? 2027;

        return array_merge([
            'tid' => $this->academicYearId($yearStart, $yearEnd),
            'name' => 'Tahun Akademik 2026/2027 Ganjil',
            'code' => '2026-GANJIL',
            'term' => TahunAkademik::TERM_GANJIL,
            'year_start' => 2026,
            'year_end' => 2027,
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-12-31',
            'status' => TahunAkademik::STATUS_DRAFT,
            'is_active' => false,
        ], $overrides);
    }
}
