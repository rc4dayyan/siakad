@extends('base.base-dash-index')

@section('menu', 'Album Foto')
@section('submenu', 'Edit Album')
@section('subdesc', 'Perbarui informasi album dan kelola seluruh foto dengan lebih mudah.')
@section('urlmenu')
    {{ route($prefix.'publish.album-show', $album->slug) }}
@endsection

@section('custom-css')
<style>
    .album-form { --album-primary: #435ebe; --album-muted: #667085; }
    .album-form .card { border: 0; border-radius: 18px; box-shadow: 0 8px 30px rgba(35, 46, 80, .07); }
    .album-form .card-header { padding: 1.35rem 1.5rem .35rem; background: transparent; border: 0; }
    .album-form .card-body { padding: 1.25rem 1.5rem 1.5rem; }
    .album-form__title { display: flex; align-items: center; gap: .85rem; }
    .album-form__icon { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 12px; color: var(--album-primary); background: rgba(67, 94, 190, .1); }
    .album-form__title h5 { margin: 0 0 .15rem; font-weight: 700; }
    .album-form__title p { margin: 0; color: var(--album-muted); font-size: .84rem; }
    .album-form .form-label { margin-bottom: .5rem; color: #344054; font-weight: 600; }
    .album-form .form-control { min-height: 46px; border-color: #d8deea; border-radius: 10px; }
    .album-form textarea.form-control { min-height: 132px; resize: vertical; }
    .cover-preview { overflow: hidden; aspect-ratio: 16 / 10; margin-bottom: 1rem; border: 1px solid #e4e8f0; border-radius: 14px; background: #f5f7fb; }
    .cover-preview img { width: 100%; height: 100%; object-fit: cover; }
    .upload-zone { position: relative; display: flex; min-height: 180px; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; border: 2px dashed #c9d1e5; border-radius: 16px; background: #f8f9fd; transition: .2s ease; }
    .upload-zone:hover, .upload-zone.is-dragging { border-color: var(--album-primary); background: #f1f3fc; transform: translateY(-1px); }
    .upload-zone input { position: absolute; inset: 0; width: 100%; height: 100%; cursor: pointer; opacity: 0; }
    .upload-zone__icon { display: inline-flex; align-items: center; justify-content: center; width: 54px; height: 54px; margin-bottom: .75rem; border-radius: 50%; color: var(--album-primary); background: #fff; box-shadow: 0 6px 18px rgba(67, 94, 190, .14); font-size: 1.25rem; }
    .upload-zone h6 { margin-bottom: .3rem; font-weight: 700; }
    .upload-zone p { margin: 0; color: var(--album-muted); font-size: .86rem; }
    .upload-zone strong { color: var(--album-primary); }
    .photo-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: 1.25rem 0 .85rem; }
    .photo-counter { padding: .42rem .75rem; border-radius: 999px; color: var(--album-primary); background: rgba(67, 94, 190, .1); font-size: .78rem; font-weight: 700; }
    .photo-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; }
    .photo-item { position: relative; overflow: hidden; aspect-ratio: 1 / 1; border-radius: 13px; background: #eef1f7; transition: .2s ease; }
    .photo-item img { width: 100%; height: 100%; object-fit: cover; transition: .2s ease; }
    .photo-item__number { position: absolute; left: .55rem; bottom: .55rem; z-index: 2; padding: .25rem .5rem; border-radius: 7px; color: #fff; background: rgba(16, 24, 40, .72); font-size: .72rem; font-weight: 700; }
    .photo-item__remove { position: absolute; top: .5rem; right: .5rem; z-index: 2; display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border: 0; border-radius: 50%; color: #fff; background: rgba(190, 38, 51, .9); box-shadow: 0 3px 10px rgba(0, 0, 0, .15); }
    .photo-item__name { position: absolute; right: 0; bottom: 0; left: 0; overflow: hidden; padding: 1.5rem .55rem .45rem 4rem; color: #fff; background: linear-gradient(transparent, rgba(16, 24, 40, .82)); font-size: .7rem; text-overflow: ellipsis; white-space: nowrap; }
    .photo-item__removed { position: absolute; inset: 0; z-index: 1; display: none; align-items: center; justify-content: center; flex-direction: column; gap: .35rem; color: #b42318; background: rgba(255, 245, 245, .9); font-size: .78rem; font-weight: 700; }
    .photo-item.is-removed { outline: 2px solid #dc3545; }
    .photo-item.is-removed img { filter: grayscale(1); opacity: .18; }
    .photo-item.is-removed .photo-item__removed { display: flex; }
    .photo-item.is-removed .photo-item__remove { color: #344054; background: #fff; }
    .photo-section-label { display: flex; align-items: center; gap: .5rem; margin: 1.4rem 0 .8rem; color: #344054; font-weight: 700; }
    .photo-section-label::after { content: ''; flex: 1; height: 1px; background: #eaecf0; }
    .album-actions { position: sticky; bottom: 1rem; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1rem; border: 1px solid rgba(220, 225, 235, .9); border-radius: 14px; background: rgba(255, 255, 255, .94); box-shadow: 0 10px 30px rgba(35, 46, 80, .12); backdrop-filter: blur(8px); }
    .album-actions__hint { color: var(--album-muted); font-size: .82rem; }
    .album-actions .btn { border-radius: 10px; }
    @media (max-width: 767.98px) { .photo-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .album-actions__hint { display: none; } }
</style>
@endsection

@section('content')
@php
    $existingPhotos = collect(range(1, 20))->map(function ($slot) use ($album) {
        return $album->{'file_'.$slot} ? ['slot' => $slot, 'path' => $album->{'file_'.$slot}] : null;
    })->filter()->values();
    $removedPhotos = collect(old('remove_photos', []))->map(fn ($slot) => (int) $slot);
@endphp

<section class="section album-form">
    <form id="albumForm" action="{{ route($prefix.'publish.album-update', $album->slug) }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PATCH')
        <div class="row g-4">
            <div class="col-xl-4 col-lg-5">
                <div class="card h-100">
                    <div class="card-header"><div class="album-form__title"><span class="album-form__icon"><i class="fa-regular fa-rectangle-list"></i></span><div><h5>Informasi Album</h5><p>Perbarui identitas dan sampul album.</p></div></div></div>
                    <div class="card-body">
                        <div class="cover-preview"><img id="coverPreviewImage" src="{{ asset('storage/'.$album->cover) }}" alt="Sampul {{ $album->name }}"></div>
                        <div class="mb-3">
                            <label class="form-label" for="cover">Ganti Sampul</label>
                            <input type="file" class="form-control @error('cover') is-invalid @enderror" name="cover" id="cover" accept="image/jpeg,image/png,image/gif,image/svg+xml">
                            <div class="form-text">Biarkan kosong untuk mempertahankan sampul saat ini.</div>
                            @error('cover')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="name">Nama Album <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $album->name) }}" maxlength="255" placeholder="Contoh: Wisuda Angkatan 2026">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <div class="d-flex justify-content-between"><label class="form-label" for="desc">Deskripsi <span class="text-danger">*</span></label><small class="text-muted"><span id="descriptionCount">{{ strlen(old('desc', $album->desc)) }}</span> karakter</small></div>
                            <textarea class="form-control @error('desc') is-invalid @enderror" name="desc" id="desc" placeholder="Ceritakan isi atau momen dalam album ini...">{{ old('desc', $album->desc) }}</textarea>
                            @error('desc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 col-lg-7">
                <div class="card h-100">
                    <div class="card-header"><div class="album-form__title"><span class="album-form__icon"><i class="fa-regular fa-images"></i></span><div><h5>Kelola Foto Album</h5><p>Hapus foto lama atau tambahkan beberapa foto baru sekaligus.</p></div></div></div>
                    <div class="card-body">
                        <div class="photo-toolbar mt-0">
                            <div><strong>Koleksi saat ini</strong><div class="text-muted small">Foto yang ditandai baru dihapus setelah perubahan disimpan.</div></div>
                            <span class="photo-counter"><span id="totalPhotoCount">{{ $existingPhotos->count() - $removedPhotos->count() }}</span>/20 foto</span>
                        </div>
                        <div class="photo-grid" id="existingPhotoGrid">
                            @foreach ($existingPhotos as $index => $photo)
                                <div class="photo-item existing-photo {{ $removedPhotos->contains($photo['slot']) ? 'is-removed' : '' }}" data-slot="{{ $photo['slot'] }}">
                                    <img src="{{ asset('storage/'.$photo['path']) }}" alt="Foto album {{ $index + 1 }}">
                                    <span class="photo-item__number">Foto {{ $index + 1 }}</span>
                                    <span class="photo-item__removed"><i class="fa-regular fa-trash-can fs-4"></i>Akan dihapus</span>
                                    <button type="button" class="photo-item__remove toggle-existing-photo" aria-label="{{ $removedPhotos->contains($photo['slot']) ? 'Batalkan hapus' : 'Hapus' }} foto {{ $index + 1 }}"><i class="fa-solid {{ $removedPhotos->contains($photo['slot']) ? 'fa-rotate-left' : 'fa-trash' }}"></i></button>
                                    <input type="checkbox" name="remove_photos[]" value="{{ $photo['slot'] }}" {{ $removedPhotos->contains($photo['slot']) ? 'checked' : '' }} hidden>
                                </div>
                            @endforeach
                        </div>

                        <div class="photo-section-label">Tambahkan foto baru</div>
                        <div class="upload-zone" id="photoDropZone">
                            <input type="file" name="photos[]" id="photos" accept="image/jpeg,image/png,image/gif,image/svg+xml" multiple aria-describedby="photoHelp">
                            <div><span class="upload-zone__icon"><i class="fa-solid fa-cloud-arrow-up"></i></span><h6>Tarik dan lepaskan foto di sini</h6><p>atau <strong>klik untuk memilih banyak foto</strong></p><p id="photoHelp" class="mt-2">Maksimal 20 foto dalam album · 2 MB per foto</p></div>
                        </div>
                        @error('photos')<div class="text-danger small mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>@enderror
                        @error('photos.*')<div class="text-danger small mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>@enderror
                        @error('remove_photos.*')<div class="text-danger small mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>@enderror
                        <div class="text-danger small mt-2" id="photoClientError" role="alert" hidden></div>

                        <div id="newPhotoSection" hidden>
                            <div class="photo-section-label">Foto baru (<span id="newPhotoCount">0</span>)</div>
                            <div class="photo-grid" id="newPhotoPreview" aria-live="polite"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="album-actions mt-4">
            <span class="album-actions__hint"><i class="fa-solid fa-circle-info me-1"></i>Perubahan foto diterapkan setelah tombol simpan ditekan.</span>
            <div class="d-flex gap-2 ms-auto"><a href="{{ route($prefix.'publish.album-show', $album->slug) }}" class="btn btn-light px-3">Batal</a><button type="submit" class="btn btn-primary px-4" id="submitAlbum"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan</button></div>
        </div>
    </form>
</section>
@endsection

@section('custom-js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const maximumPhotos = 20;
    const maximumFileSize = 2 * 1024 * 1024;
    const supportedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
    const existingPhotos = Array.from(document.querySelectorAll('.existing-photo'));
    const input = document.getElementById('photos');
    const dropZone = document.getElementById('photoDropZone');
    const preview = document.getElementById('newPhotoPreview');
    const newSection = document.getElementById('newPhotoSection');
    const totalCount = document.getElementById('totalPhotoCount');
    const newCount = document.getElementById('newPhotoCount');
    const error = document.getElementById('photoClientError');
    let selectedFiles = [];
    let previewUrls = [];

    function activeExistingCount() { return existingPhotos.filter(item => !item.classList.contains('is-removed')).length; }
    function showError(message) { error.textContent = message; error.hidden = !message; }
    function refreshCount() {
        const count = activeExistingCount() + selectedFiles.length;
        totalCount.textContent = count;
        newCount.textContent = selectedFiles.length;
        newSection.hidden = selectedFiles.length === 0;
        return count;
    }
    function synchronizeInput() {
        const transfer = new DataTransfer();
        selectedFiles.forEach(file => transfer.items.add(file));
        input.files = transfer.files;
    }
    function renderNewPhotos() {
        previewUrls.forEach(url => URL.revokeObjectURL(url));
        previewUrls = [];
        preview.innerHTML = '';
        selectedFiles.forEach(function (file, index) {
            const url = URL.createObjectURL(file);
            previewUrls.push(url);
            const item = document.createElement('div');
            item.className = 'photo-item';
            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Preview foto baru ' + (index + 1);
            const number = document.createElement('span');
            number.className = 'photo-item__number';
            number.textContent = 'Baru ' + (index + 1);
            const name = document.createElement('span');
            name.className = 'photo-item__name';
            name.textContent = file.name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'photo-item__remove';
            remove.setAttribute('aria-label', 'Batalkan ' + file.name);
            remove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            remove.addEventListener('click', function () {
                selectedFiles.splice(index, 1);
                synchronizeInput();
                renderNewPhotos();
            });
            item.append(image, name, number, remove);
            preview.appendChild(item);
        });
        refreshCount();
    }
    function addFiles(fileList) {
        showError('');
        const incoming = Array.from(fileList);
        const invalidType = incoming.find(file => !supportedTypes.includes(file.type));
        const oversized = incoming.find(file => file.size > maximumFileSize);
        if (invalidType) { showError('File "' + invalidType.name + '" bukan format gambar yang didukung.'); return; }
        if (oversized) { showError('File "' + oversized.name + '" melebihi ukuran maksimal 2 MB.'); return; }
        const newFiles = incoming.filter(file => !selectedFiles.some(existing => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified));
        if (activeExistingCount() + selectedFiles.length + newFiles.length > maximumPhotos) {
            showError('Album hanya dapat memuat 20 foto. Hapus foto lama atau kurangi foto baru.');
            synchronizeInput();
            return;
        }
        selectedFiles.push(...newFiles);
        synchronizeInput();
        renderNewPhotos();
    }

    document.querySelectorAll('.toggle-existing-photo').forEach(function (button) {
        button.addEventListener('click', function () {
            const item = button.closest('.existing-photo');
            const checkbox = item.querySelector('input[type="checkbox"]');
            const removing = !item.classList.contains('is-removed');
            if (!removing && activeExistingCount() + selectedFiles.length >= maximumPhotos) {
                showError('Album hanya dapat memuat 20 foto. Batalkan salah satu foto baru sebelum memulihkan foto ini.');
                return;
            }
            item.classList.toggle('is-removed', removing);
            checkbox.checked = removing;
            button.innerHTML = removing ? '<i class="fa-solid fa-rotate-left"></i>' : '<i class="fa-solid fa-trash"></i>';
            button.setAttribute('aria-label', removing ? 'Batalkan hapus foto' : 'Hapus foto');
            showError('');
            refreshCount();
        });
    });
    input.addEventListener('change', function () { addFiles(input.files); });
    ['dragenter', 'dragover'].forEach(eventName => dropZone.addEventListener(eventName, function (event) { event.preventDefault(); dropZone.classList.add('is-dragging'); }));
    ['dragleave', 'drop'].forEach(eventName => dropZone.addEventListener(eventName, function (event) { event.preventDefault(); dropZone.classList.remove('is-dragging'); }));
    dropZone.addEventListener('drop', function (event) { addFiles(event.dataTransfer.files); });
    document.getElementById('cover').addEventListener('change', function (event) {
        if (event.target.files[0]) document.getElementById('coverPreviewImage').src = URL.createObjectURL(event.target.files[0]);
    });
    document.getElementById('desc').addEventListener('input', function (event) { document.getElementById('descriptionCount').textContent = event.target.value.length; });
    document.getElementById('albumForm').addEventListener('submit', function (event) {
        const photoCount = refreshCount();
        if (photoCount === 0) {
            event.preventDefault();
            showError('Album harus memiliki minimal satu foto. Tambahkan foto baru atau pulihkan foto lama.');
            dropZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        if (photoCount > maximumPhotos) {
            event.preventDefault();
            showError('Album hanya dapat memuat maksimal 20 foto.');
            dropZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        const submit = document.getElementById('submitAlbum');
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    });
    refreshCount();
});
</script>
@endsection
