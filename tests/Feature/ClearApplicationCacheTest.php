<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClearApplicationCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
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

    public function test_web_administrator_can_clear_application_caches(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear')
            ->andReturn(0);

        $response = $this
            ->actingAs($this->staffUser(0, 'WEBADMIN'))
            ->from('/web-admin/home')
            ->post(route('web-admin.system.cache-clear'));

        $response->assertRedirect('/web-admin/home');
    }

    public function test_other_staff_cannot_clear_application_caches(): void
    {
        Artisan::shouldReceive('call')->never();

        $response = $this
            ->actingAs($this->staffUser(4, 'ADMIN'))
            ->post(route('web-admin.system.cache-clear'));

        $response->assertRedirect(route('error.access'));
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
