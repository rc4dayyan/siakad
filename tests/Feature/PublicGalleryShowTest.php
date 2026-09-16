<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicGalleryShowTest extends TestCase
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

        Schema::create('fakultas', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
        Schema::create('program_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->integer('send_to');
            $table->timestamps();
        });
        Schema::create('web_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('school_apps');
            $table->string('school_name');
            $table->string('school_logo');
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
                $table->string('file_'.$slot)->nullable();
            }
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
    }

    public function test_published_album_page_displays_gallery_controls_and_related_albums(): void
    {
        $album = $this->album('Aktifitas Akademik', 'aktifitas-akademik', true, [
            'file_1' => 'images/gallery/photo-1.jpg',
            'file_2' => 'images/gallery/photo-2.jpg',
        ]);
        $related = $this->album('Wisuda Mahasiswa', 'wisuda-mahasiswa');
        $hidden = $this->album('Album Internal', 'album-internal', false);

        $response = $this->get(route('root.gallery-show', $album->slug));

        $response
            ->assertOk()
            ->assertSee($album->name)
            ->assertSee('2 foto')
            ->assertSee('dist/custom/home.css?v=', false)
            ->assertSee('galleryPrevious')
            ->assertSee('galleryNext')
            ->assertSee($related->name)
            ->assertDontSee($hidden->name);
    }

    public function test_unpublished_album_is_not_publicly_accessible(): void
    {
        $album = $this->album('Album Internal', 'album-internal', false);

        $this->get(route('root.gallery-show', $album->slug))
            ->assertRedirect(route('error.notfound'));
    }

    private function album(string $name, string $slug, bool $published = true, array $attributes = []): GalleryAlbum
    {
        return GalleryAlbum::create(array_merge([
            'author_id' => 1,
            'name' => $name,
            'slug' => $slug,
            'desc' => 'Dokumentasi kegiatan kampus.',
            'cover' => 'images/gallery/cover.jpg',
            'isPublish' => $published,
            'file_1' => 'images/gallery/default.jpg',
        ], $attributes));
    }
}
