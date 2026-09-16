<?php

namespace Tests\Feature;

use App\Models\Settings\webSettings;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebSettingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('type')->default(0);
            $table->string('code')->unique();
            $table->string('name');
            $table->string('user');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('status')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('web_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('school_apps');
            $table->string('school_name');
            $table->string('school_head');
            $table->string('school_head_photo')->nullable();
            $table->string('school_logo')->default('website/site-logo.png');
            $table->string('school_link');
            $table->longText('school_desc');
            $table->string('school_email');
            $table->string('school_phone');
            $table->string('social_fb');
            $table->string('social_ig');
            $table->string('social_in');
            $table->string('social_tw');
            $table->timestamps();
        });
    }

    public function test_web_administrator_can_upload_school_head_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/website/pimpinan-lama.jpg', 'old-photo');

        $web = $this->webSetting(['school_head_photo' => 'website/pimpinan-lama.jpg']);
        $administrator = $this->webAdministrator();

        $response = $this->actingAs($administrator)->patch(route('web-admin.system.setting-update'), [
            ...$this->validData($web),
            'school_head_photo' => UploadedFile::fake()->image('pimpinan-baru.jpg', 600, 800),
        ]);

        $response->assertRedirect();

        $photo = $web->fresh()->school_head_photo;
        $this->assertStringStartsWith('website/pimpinan-', $photo);
        Storage::disk('public')->assertExists('images/'.$photo);
        Storage::disk('public')->assertMissing('images/website/pimpinan-lama.jpg');
    }

    public function test_school_head_photo_must_be_a_supported_image(): void
    {
        Storage::fake('public');

        $web = $this->webSetting();
        $administrator = $this->webAdministrator();

        $response = $this->actingAs($administrator)
            ->from(route('web-admin.system.setting-index'))
            ->patch(route('web-admin.system.setting-update'), [
                ...$this->validData($web),
                'school_head_photo' => UploadedFile::fake()->create('pimpinan.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('web-admin.system.setting-index'));
        $response->assertSessionHasErrors('school_head_photo');
        $this->assertNull($web->fresh()->school_head_photo);
    }

    private function webAdministrator(): User
    {
        return User::forceCreate([
            'type' => 0,
            'code' => 'WEB-ADMIN',
            'name' => 'Web Administrator',
            'user' => 'webadmin',
            'phone' => '081234567890',
            'email' => 'webadmin@example.test',
            'password' => 'password',
            'status' => 1,
        ]);
    }

    private function webSetting(array $attributes = []): webSettings
    {
        return webSettings::forceCreate(array_merge([
            'school_apps' => 'SIAKAD',
            'school_name' => 'Kampus Uji',
            'school_head' => 'Ketua Uji',
            'school_link' => 'https://kampus.example.test',
            'school_desc' => 'Sambutan pimpinan kampus.',
            'school_email' => 'info@example.test',
            'school_phone' => '021123456',
            'social_fb' => '#',
            'social_ig' => '#',
            'social_in' => '#',
            'social_tw' => '#',
        ], $attributes));
    }

    private function validData(webSettings $web): array
    {
        return [
            'school_apps' => $web->school_apps,
            'school_name' => $web->school_name,
            'school_head' => $web->school_head,
            'school_link' => $web->school_link,
            'school_desc' => $web->school_desc,
            'school_email' => $web->school_email,
            'school_phone' => $web->school_phone,
        ];
    }
}
