<?php

namespace App\Http\Controllers\Admin\Pages\Publikasi;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\Settings\webSettings;
// SECTION ADDONS EXTERNAL
use Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
// SECTION MODELS
use Storage;
use Str;
use Throwable;

class GalleryController extends Controller
{
    use roleTrait;

    public function index()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['album'] = GalleryAlbum::latest()->paginate(24);

        return view('user.pages.publikasi.gallery-index', $data);
    }

    public function search(Request $request)
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $search = $request->input('search');
        $album = GalleryAlbum::where('name', 'like', "%$search%")->paginate(24);

        return view('user.pages.publikasi.gallery-index', ['album' => $album], $data);
    }

    public function create()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();

        return view('user.pages.publikasi.gallery-create', $data);
    }

    public function show($slug)
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['album'] = GalleryAlbum::where('slug', $slug)->first();

        return view('user.pages.publikasi.gallery-show', $data);
    }

    public function edit($slug)
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['album'] = GalleryAlbum::where('slug', $slug)->first();

        return view('user.pages.publikasi.gallery-edit', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'required|string',
            'cover' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'photos' => 'required|array|min:1|max:20',
            'photos.*' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'name.required' => 'Nama album wajib diisi.',
            'desc.required' => 'Deskripsi album wajib diisi.',
            'cover.required' => 'Sampul album wajib dipilih.',
            'cover.image' => 'Sampul album harus berupa gambar.',
            'cover.mimes' => 'Format sampul harus JPG, JPEG, PNG, GIF, atau SVG.',
            'cover.max' => 'Ukuran sampul maksimal 2 MB.',
            'photos.required' => 'Pilih minimal satu foto untuk album.',
            'photos.array' => 'Daftar foto album tidak valid.',
            'photos.max' => 'Maksimal 20 foto dalam satu album.',
            'photos.*.mimes' => 'Setiap foto harus berformat JPG, JPEG, PNG, GIF, atau SVG.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
        ]);

        $coverPath = $request->file('cover')->store('images/gallery', 'public');

        $album = new GalleryAlbum;
        $album->author_id = Auth::user()->id;
        $album->name = $request->name;
        $album->slug = Str::slug($request->name);
        $album->desc = $request->desc;
        $album->cover = $coverPath;
        foreach ($request->file('photos') as $index => $image) {
            $imageName = 'file_'.($index + 1);
            $album->{$imageName} = $image->store('images/gallery', 'public');
        }
        $album->save();

        Alert::success('Success', 'Data berhasil ditambahkan');

        return redirect()->route($this->setPrefix().'publish.album-show', $album->slug);
    }

    public function update(Request $request, $slug)
    {
        $prefix = $this->setPrefix();
        $album = GalleryAlbum::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'required|string',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'photos' => 'nullable|array|max:20',
            'photos.*' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'remove_photos' => 'nullable|array',
            'remove_photos.*' => 'integer|between:1,20|distinct',
        ], [
            'name.required' => 'Nama album wajib diisi.',
            'desc.required' => 'Deskripsi album wajib diisi.',
            'cover.image' => 'Sampul album harus berupa gambar.',
            'cover.mimes' => 'Format sampul harus JPG, JPEG, PNG, GIF, atau SVG.',
            'cover.max' => 'Ukuran sampul maksimal 2 MB.',
            'photos.max' => 'Maksimal 20 foto baru dapat dipilih.',
            'photos.*.mimes' => 'Setiap foto harus berformat JPG, JPEG, PNG, GIF, atau SVG.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
            'remove_photos.*.between' => 'Pilihan foto yang akan dihapus tidak valid.',
        ]);

        $removedSlots = collect($validated['remove_photos'] ?? [])->map(fn ($slot) => (int) $slot);
        $currentPhotos = collect(range(1, 20))
            ->mapWithKeys(fn ($slot) => [$slot => $album->{'file_'.$slot}])
            ->filter();
        $retainedPhotos = $currentPhotos->reject(fn ($path, $slot) => $removedSlots->contains($slot))->values();
        $newPhotos = $request->file('photos', []);

        if ($retainedPhotos->count() + count($newPhotos) > 20) {
            throw ValidationException::withMessages([
                'photos' => 'Jumlah foto lama dan foto baru tidak boleh melebihi 20 foto.',
            ]);
        }

        if ($retainedPhotos->isEmpty() && count($newPhotos) === 0) {
            throw ValidationException::withMessages([
                'photos' => 'Album harus memiliki minimal satu foto.',
            ]);
        }

        $uploadedPaths = [];
        $oldCover = $album->cover;

        try {
            if ($request->hasFile('cover')) {
                $album->cover = $request->file('cover')->store('images/gallery', 'public');
                $uploadedPaths[] = $album->cover;
            }

            foreach ($newPhotos as $photo) {
                $path = $photo->store('images/gallery', 'public');
                $uploadedPaths[] = $path;
                $retainedPhotos->push($path);
            }

            $album->author_id = Auth::user()->id;
            $album->name = $validated['name'];
            $album->slug = Str::slug($validated['name']);
            $album->desc = $validated['desc'];

            foreach (range(1, 20) as $slot) {
                $album->{'file_'.$slot} = $retainedPhotos->get($slot - 1);
            }

            $album->save();
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($uploadedPaths);

            throw $exception;
        }

        $protectedImages = [
            'gallery_image.png',
            'images/gallery/album-a.jpg',
            'images/gallery/album-b.jpg',
            'images/gallery/album-c.jpg',
        ];
        $deletedPhotos = $currentPhotos->diff($retainedPhotos)->reject(fn ($path) => in_array($path, $protectedImages, true));
        Storage::disk('public')->delete($deletedPhotos->all());

        if ($request->hasFile('cover') && $oldCover && ! in_array($oldCover, $protectedImages, true)) {
            Storage::disk('public')->delete($oldCover);
        }

        Alert::success('Success', 'Data berhasil diupdate');

        return redirect()->route($prefix.'publish.album-edit', $album->slug);
    }
}
