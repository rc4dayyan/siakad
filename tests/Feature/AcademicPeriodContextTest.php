<?php

namespace Tests\Feature;

use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicPeriodContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');

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

        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
    }

    public function test_context_defaults_to_the_active_period_and_stores_it_in_session(): void
    {
        $active = $this->period('2026-GANJIL', TahunAkademik::STATUS_ACTIVE, true);
        $user = $this->staffUser(3, 'ACADEMIC');

        $current = app(AcademicPeriodContext::class)->current($user);

        $this->assertTrue($current->is($active));
        $this->assertSame($active->id, session(AcademicPeriodContext::SESSION_KEY));
    }

    public function test_invalid_session_selection_falls_back_to_the_active_period(): void
    {
        $active = $this->period('2026-GENAP', TahunAkademik::STATUS_ACTIVE, true);
        $user = $this->staffUser(1, 'FINANCE');
        session()->put(AcademicPeriodContext::SESSION_KEY, 999999);

        $current = app(AcademicPeriodContext::class)->current($user);

        $this->assertTrue($current->is($active));
        $this->assertSame($active->id, session(AcademicPeriodContext::SESSION_KEY));
    }

    public function test_authorized_staff_can_select_a_historical_period(): void
    {
        $historical = $this->period('2025-GENAP', TahunAkademik::STATUS_CLOSED);

        $response = $this
            ->actingAs($this->staffUser(3, 'ACADEMIC'))
            ->patch(route('academic.academic-period.select'), [
                'code' => $historical->code,
            ]);

        $response->assertSessionHas(AcademicPeriodContext::SESSION_KEY, $historical->id);
    }

    public function test_support_staff_cannot_select_a_historical_period(): void
    {
        $historical = $this->period('2025-GANJIL', TahunAkademik::STATUS_CLOSED);

        $response = $this
            ->actingAs($this->staffUser(5, 'SUPPORT'))
            ->patch(route('support.academic-period.select'), [
                'code' => $historical->code,
            ]);

        $response->assertForbidden();
        $response->assertSessionMissing(AcademicPeriodContext::SESSION_KEY);
    }

    public function test_support_staff_only_receives_the_active_period(): void
    {
        $active = $this->period('2026-GANJIL', TahunAkademik::STATUS_ACTIVE, true);
        $this->period('2025-GENAP', TahunAkademik::STATUS_CLOSED);
        $support = $this->staffUser(5, 'SUPPORT');

        $available = app(AcademicPeriodContext::class)->availableFor($support);

        $this->assertCount(1, $available);
        $this->assertTrue($available->first()->is($active));
    }

    public function test_context_is_null_when_there_is_no_active_period(): void
    {
        $this->period('2026-GANJIL', TahunAkademik::STATUS_DRAFT);
        $user = $this->staffUser(0, 'WEBADMIN');

        $this->assertNull(app(AcademicPeriodContext::class)->current($user));
        $this->assertFalse(session()->has(AcademicPeriodContext::SESSION_KEY));
    }

    private function period(string $code, string $status, bool $isActive = false): TahunAkademik
    {
        return TahunAkademik::create([
            'name' => 'Periode '.$code,
            'code' => $code,
            'semester' => str_contains($code, 'GENAP') ? 2 : 1,
            'year_start' => (int) substr($code, 0, 4),
            'year_end' => (int) substr($code, 0, 4) + 1,
            'term' => str_contains($code, 'GENAP') ? TahunAkademik::TERM_GENAP : TahunAkademik::TERM_GANJIL,
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-12-31',
            'status' => $status,
            'is_active' => $isActive,
        ]);
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
}
