<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class GalleryAlbumUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('type')->default(0);
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

        Schema::create('gallery_albums', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->string('name');
            $table->string('slug');
            $table->string('desc');
            $table->string('cover');
            $table->integer('isPublish')->default(1);
            foreach (range(1, 20) as $slot) {
                $table->string('file_'.$slot)->nullable($slot !== 1);
            }
            $table->timestamps();
        });

        Schema::create('web_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('school_apps');
            $table->string('school_name');
            $table->string('school_logo');
            $table->timestamps();
        });

        DB::table('web_settings')->insert([
            'id' => 1,
            'school_apps' => 'SIAKAD',
            'school_name' => 'Kampus Uji',
            'school_logo' => 'website/site-logo.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('ticket_supports', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Storage::fake('public');
    }

    public function test_index_displays_album_information_and_management_actions(): void
    {
        $this->mock(AcademicPeriodContext::class, function (MockInterface $mock): void {
            $mock->shouldReceive('availableFor')->once()->andReturn(new Collection);
            $mock->shouldReceive('current')->once()->andReturnNull();
            $mock->shouldReceive('active')->once()->andReturnNull();
        });

        $user = $this->webAdministrator();
        $album = $this->album($user);

        $response = $this->actingAs($user)->get(route('web-admin.publish.album-index'));

        $response
            ->assertOk()
            ->assertSee($album->name)
            ->assertSee('2 foto')
            ->assertSee($user->name)
            ->assertSee(route('web-admin.publish.album-show', $album->slug))
            ->assertSee(route('web-admin.publish.album-edit', $album->slug));
    }

    public function test_update_can_remove_an_existing_photo_and_add_multiple_new_photos(): void
    {
        $user = $this->webAdministrator();
        $album = $this->album($user);
        Storage::disk('public')->put('images/gallery/old-1.jpg', 'old-one');
        Storage::disk('public')->put('images/gallery/old-2.jpg', 'old-two');

        $response = $this->actingAs($user)->patch(route('web-admin.publish.album-update', $album->slug), [
            'name' => 'Album Diperbarui',
            'desc' => 'Deskripsi album yang sudah diperbarui.',
            'remove_photos' => [1],
            'photos' => [
                UploadedFile::fake()->image('foto-baru-1.jpg', 800, 600),
                UploadedFile::fake()->image('foto-baru-2.png', 800, 600),
            ],
        ]);

        $album->refresh();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('web-admin.publish.album-edit', 'album-diperbarui'));
        $this->assertSame('images/gallery/old-2.jpg', $album->file_1);
        $this->assertNotNull($album->file_2);
        $this->assertNotNull($album->file_3);
        $this->assertNull($album->file_4);
        Storage::disk('public')->assertMissing('images/gallery/old-1.jpg');
        Storage::disk('public')->assertExists('images/gallery/old-2.jpg');
        Storage::disk('public')->assertExists($album->file_2);
        Storage::disk('public')->assertExists($album->file_3);
    }

    public function test_update_rejects_removing_every_photo_without_a_replacement(): void
    {
        $user = $this->webAdministrator();
        $album = $this->album($user);
        Storage::disk('public')->put('images/gallery/old-1.jpg', 'old-one');
        Storage::disk('public')->put('images/gallery/old-2.jpg', 'old-two');

        $response = $this->actingAs($user)->from(route('web-admin.publish.album-edit', $album->slug))->patch(
            route('web-admin.publish.album-update', $album->slug),
            [
                'name' => $album->name,
                'desc' => $album->desc,
                'remove_photos' => [1, 2],
            ],
        );

        $response
            ->assertSessionHasErrors('photos')
            ->assertRedirect(route('web-admin.publish.album-edit', $album->slug));
        $this->assertSame('images/gallery/old-1.jpg', $album->fresh()->file_1);
        Storage::disk('public')->assertExists('images/gallery/old-1.jpg');
        Storage::disk('public')->assertExists('images/gallery/old-2.jpg');
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

    private function album(User $user): GalleryAlbum
    {
        return GalleryAlbum::create([
            'author_id' => $user->id,
            'name' => 'Album Lama',
            'slug' => 'album-lama',
            'desc' => 'Deskripsi album lama.',
            'cover' => 'images/gallery/cover.jpg',
            'file_1' => 'images/gallery/old-1.jpg',
            'file_2' => 'images/gallery/old-2.jpg',
        ]);
    }
}
