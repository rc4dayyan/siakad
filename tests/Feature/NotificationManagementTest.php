<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        foreach ([
            '2014_10_12_000000_create_users_table.php',
            '2024_04_26_060533_create_tahun_akademiks_table.php',
            '2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php',
            '2024_05_23_095204_create_ticket_supports_table.php',
            '2024_05_30_004205_create_notifications_table.php',
            '2024_06_26_050556_create_web_settings_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        DB::table('web_settings')->insert([
            'school_apps' => 'SIAKAD',
            'school_name' => 'Kampus Test',
            'school_head' => 'Ketua Test',
            'school_link' => 'https://example.test',
            'school_desc' => 'Kampus untuk pengujian.',
            'school_email' => 'kampus@example.test',
            'school_phone' => '0800000000',
            'social_fb' => '-',
            'social_ig' => '-',
            'social_in' => '-',
            'social_tw' => '-',
        ]);
    }

    public function test_notification_page_handles_a_missing_author(): void
    {
        Notification::create([
            'auth_id' => 999,
            'send_to' => 0,
            'name' => 'Pengumuman Lama',
            'slug' => 'pengumuman-lama',
            'type' => 'Informasi',
            'desc' => 'Pengumuman dengan akun pembuat yang sudah tidak tersedia.',
            'code' => 'NTF-ORPHAN',
        ]);

        $response = $this
            ->actingAs($this->webAdministrator())
            ->get(route('web-admin.system.notify-index'));

        $response->assertOk();
        $response->assertSee('Pengumuman Lama');
        $response->assertSee('Pengguna tidak tersedia');
    }

    private function webAdministrator(): User
    {
        return User::create([
            'type' => 0,
            'code' => 'WEBADMIN-NOTIFY',
            'name' => 'Web Administrator',
            'user' => 'webadmin.notify',
            'phone' => '0800000002',
            'email' => 'webadmin.notify@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
