<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Pages\Core\TahunAkademikIndukController;
use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TahunAkademikIndukManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createUsersTable();
        Schema::create('web_settings', fn (Blueprint $table) => $table->id());
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        (require database_path('migrations/2026_08_20_000001_create_tahun_akademik_and_link_periods.php'))->up();
    }

    public function test_web_administrator_can_create_update_and_delete_unused_academic_year(): void
    {
        $admin = $this->webAdministrator();

        $this->actingAs($admin)->post(route('web-admin.master.tahun-akademik-store'), [
            '_form' => 'create-academic-year',
            'name' => 'Tahun Akademik 2027/2028',
            'code' => 'ta-2027-2028',
            'year_start' => 2027,
            'year_end' => 2028,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tahun_akademik', [
            'name' => 'Tahun Akademik 2027/2028',
            'code' => 'TA-2027-2028',
        ]);

        $this->actingAs($admin)->patch(route('web-admin.master.tahun-akademik-update', 'TA-2027-2028'), [
            '_form' => 'edit-academic-year-1',
            'name' => 'Tahun Akademik 2027/2028 Revisi',
            'code' => 'TA-2027-2028',
            'year_start' => 2027,
            'year_end' => 2028,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tahun_akademik', ['name' => 'Tahun Akademik 2027/2028 Revisi']);

        $this->actingAs($admin)
            ->delete(route('web-admin.master.tahun-akademik-destroy', 'TA-2027-2028'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('tahun_akademik', 0);
    }

    public function test_used_academic_year_cannot_change_range_or_be_deleted(): void
    {
        $admin = $this->webAdministrator();
        $academicYear = $this->academicYear(2025, 2026);
        TahunAkademik::create([
            'tid' => $academicYear->id,
            'name' => '2025/2026 Ganjil',
            'code' => '2025-GANJIL',
            'semester' => 1,
            'year_start' => 2025,
            'year_end' => 2026,
            'term' => TahunAkademik::TERM_GANJIL,
            'starts_at' => '2025-08-01',
            'ends_at' => '2025-12-31',
        ]);

        $this->actingAs($admin)->patch(route('web-admin.master.tahun-akademik-update', $academicYear->code), [
            '_form' => 'edit-academic-year-'.$academicYear->id,
            'name' => $academicYear->name,
            'code' => $academicYear->code,
            'year_start' => 2026,
            'year_end' => 2027,
        ])->assertSessionHasErrors('year_start');

        $this->actingAs($admin)
            ->delete(route('web-admin.master.tahun-akademik-destroy', $academicYear->code));

        $this->assertDatabaseHas('tahun_akademik', ['id' => $academicYear->id]);
    }

    public function test_academic_year_code_is_case_insensitive_and_must_be_unique(): void
    {
        $admin = $this->webAdministrator();
        $this->academicYear(2025, 2026)->update(['code' => 'TA-2025-2026']);

        $this->actingAs($admin)->post(route('web-admin.master.tahun-akademik-store'), [
            '_form' => 'create-academic-year',
            'name' => 'Tahun Akademik Lain',
            'code' => 'ta-2025-2026',
            'year_start' => 2027,
            'year_end' => 2028,
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseCount('tahun_akademik', 1);
    }

    public function test_index_filters_academic_years_by_keyword_year_and_usage(): void
    {
        $this->actingAs($this->webAdministrator());
        $used = $this->academicYear(2025, 2026);
        $this->academicYear(2026, 2027);
        TahunAkademik::create([
            'tid' => $used->id,
            'name' => '2025/2026 Ganjil',
            'code' => '2025-GANJIL',
            'semester' => 1,
            'year_start' => 2025,
            'year_end' => 2026,
            'term' => TahunAkademik::TERM_GANJIL,
        ]);

        $request = Request::create('/master/tahun-akademik', 'GET', [
            'q' => '2025',
            'year_start' => 2025,
            'usage' => 'used',
        ]);
        $view = app(TahunAkademikIndukController::class)->index($request);

        $this->assertSame(1, $view->getData()['academicYears']->total());
        $this->assertSame($used->id, $view->getData()['academicYears']->first()->id);
    }

    private function academicYear(int $start, int $end): TahunAkademikInduk
    {
        return TahunAkademikInduk::create([
            'name' => "Tahun Akademik {$start}/{$end}",
            'code' => "{$start}-{$end}",
            'year_start' => $start,
            'year_end' => $end,
        ]);
    }

    private function webAdministrator(): User
    {
        return User::create([
            'type' => 0,
            'code' => 'WEBADMIN',
            'name' => 'Web Administrator',
            'user' => 'webadmin',
            'phone' => '081234567890',
            'email' => 'webadmin@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
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
}
