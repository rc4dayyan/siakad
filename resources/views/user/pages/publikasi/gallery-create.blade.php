@extends('base.base-dash-index')

@section('menu', 'Album Foto')
@section('submenu', 'Buat Album Baru')
@section('subdesc', 'Susun informasi album dan unggah seluruh foto dalam satu langkah.')
@section('urlmenu')
    {{ route($prefix.'publish.album-index') }}
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
    .cover-preview { position: relative; overflow: hidden; aspect-ratio: 16 / 10; margin-bottom: 1rem; border: 1px solid #e4e8f0; border-radius: 14px; background: #f5f7fb; }
    .cover-preview img { width: 100%; height: 100%; object-fit: cover; }
    .cover-preview__placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .45rem; color: #98a2b3; }
    .cover-preview__placeholder i { font-size: 2rem; }
    .upload-zone { position: relative; display: flex; min-height: 200px; align-items: center; justify-content: center; padding: 2rem; text-align: center; border: 2px dashed #c9d1e5; border-radius: 16px; background: #f8f9fd; transition: .2s ease; }
    .upload-zone:hover, .upload-zone.is-dragging { border-color: var(--album-primary); background: #f1f3fc; transform: translateY(-1px); }
    .upload-zone input { position: absolute; inset: 0; width: 100%; height: 100%; cursor: pointer; opacity: 0; }
    .upload-zone__icon { display: inline-flex; align-items: center; justify-content: center; width: 58px; height: 58px; margin-bottom: .85rem; border-radius: 50%; color: var(--album-primary); background: #fff; box-shadow: 0 6px 18px rgba(67, 94, 190, .14); font-size: 1.35rem; }
    .upload-zone h6 { margin-bottom: .3rem; font-weight: 700; }
    .upload-zone p { margin: 0; color: var(--album-muted); font-size: .88rem; }
    .upload-zone strong { color: var(--album-primary); }
    .photo-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: 1.25rem 0 .85rem; }
    .photo-counter { padding: .42rem .75rem; border-radius: 999px; color: var(--album-primary); background: rgba(67, 94, 190, .1); font-size: .78rem; font-weight: 700; }
    .photo-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; }
    .photo-item { position: relative; overflow: hidden; aspect-ratio: 1 / 1; border-radius: 13px; background: #eef1f7; }
    .photo-item img { width: 100%; height: 100%; object-fit: cover; }
    .photo-item__number { position: absolute; left: .55rem; bottom: .55rem; z-index: 1; padding: .25rem .5rem; border-radius: 7px; color: #fff; background: rgba(16, 24, 40, .72); font-size: .72rem; font-weight: 700; }
    .photo-item__remove { position: absolute; top: .5rem; right: .5rem; z-index: 1; display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border: 0; border-radius: 50%; color: #fff; background: rgba(190, 38, 51, .9); box-shadow: 0 3px 10px rgba(0, 0, 0, .15); }
    .photo-item__name { position: absolute; right: 0; bottom: 0; left: 0; overflow: hidden; padding: 1.5rem .55rem .45rem 4rem; color: #fff; background: linear-gradient(transparent, rgba(16, 24, 40, .82)); font-size: .7rem; text-overflow: ellipsis; white-space: nowrap; }
    .album-actions { position: sticky; bottom: 1rem; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1rem; border: 1px solid rgba(220, 225, 235, .9); border-radius: 14px; background: rgba(255, 255, 255, .94); box-shadow: 0 10px 30px rgba(35, 46, 80, .12); backdrop-filter: blur(8px); }
    .album-actions__hint { color: var(--album-muted); font-size: .82rem; }
    .album-actions .btn { border-radius: 10px; }
    @media (max-width: 767.98px) { .photo-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .album-actions__hint { display: none; } }
</style>
@endsection

@section('content')
<section class="section album-form">
    <form id="albumForm" action="{{ route($prefix.'publish.album-store') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-xl-4 col-lg-5">
                <div class="card h-100">
                    <div class="card-header"><div class="album-form__title"><span class="album-form__icon"><i class="fa-regular fa-rectangle-list"></i></span><div><h5>Informasi Album</h5><p>Lengkapi identitas dan sampul album.</p></div></div></div>
                    <div class="card-body">
                        <div class="cover-preview" id="coverPreview">
                            <img id="coverPreviewImage" src="" alt="Preview sampul album" hidden>
                            <div class="cover-preview__placeholder" id="coverPlaceholder"><i class="fa-regular fa-image"></i><span>Preview sampul</span></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="cover">Sampul Album <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('cover') is-invalid @enderror" name="cover" id="cover" accept="image/jpeg,image/png,image/gif,image/svg+xml">
                            <div class="form-text">JPG, PNG, GIF, atau SVG. Maksimal 2 MB.</div>
                            @error('cover')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="name">Nama Album <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" maxlength="255" placeholder="Contoh: Wisuda Angkatan 2026">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <div class="d-flex justify-content-between"><label class="form-label" for="desc">Deskripsi <span class="text-danger">*</span></label><small class="text-muted"><span id="descriptionCount">{{ strlen(old('desc', '')) }}</span> karakter</small></div>
                            <textarea class="form-control @error('desc') is-invalid @enderror" name="desc" id="desc" placeholder="Ceritakan isi atau momen dalam album ini...">{{ old('desc') }}</textarea>
                            @error('desc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 col-lg-7">
                <div class="card h-100">
                    <div class="card-header"><div class="album-form__title"><span class="album-form__icon"><i class="fa-regular fa-images"></i></span><div><h5>Foto Album</h5><p>Pilih beberapa foto sekaligus agar proses lebih cepat.</p></div></div></div>
                    <div class="card-body">
                        <div class="upload-zone" id="photoDropZone">
                            <input type="file" name="photos[]" id="photos" accept="image/jpeg,image/png,image/gif,image/svg+xml" multiple aria-describedby="photoHelp">
                            <div><span class="upload-zone__icon"><i class="fa-solid fa-cloud-arrow-up"></i></span><h6>Tarik dan lepaskan foto di sini</h6><p>atau <strong>klik untuk memilih banyak foto</strong></p><p id="photoHelp" class="mt-2">Maksimal 20 foto · 2 MB per foto</p></div>
                        </div>
                        @error('photos')<div class="text-danger small mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>@enderror
                        @error('photos.*')<div class="text-danger small mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>@enderror
                        <div class="text-danger small mt-2" id="photoClientError" role="alert" hidden></div>
                        <div class="photo-toolbar"><div><strong>Foto terpilih</strong><div class="text-muted small">Klik ikon hapus untuk membatalkan foto.</div></div><span class="photo-counter"><span id="photoCount">0</span>/20 foto</span></div>
                        <div class="photo-grid" id="photoPreview" aria-live="polite"></div>
                        <div class="text-center text-muted py-4" id="photoEmpty"><i class="fa-regular fa-images d-block mb-2 fs-3"></i>Belum ada foto dipilih.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="album-actions mt-4">
            <span class="album-actions__hint"><i class="fa-solid fa-circle-info me-1"></i>Pastikan sampul dan minimal satu foto sudah dipilih.</span>
            <div class="d-flex gap-2 ms-auto"><a href="{{ route($prefix.'publish.album-index') }}" class="btn btn-light px-3">Batal</a><button type="submit" class="btn btn-primary px-4" id="submitAlbum"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Album</button></div>
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
    const input = document.getElementById('photos');
    const dropZone = document.getElementById('photoDropZone');
    const preview = document.getElementById('photoPreview');
    const emptyState = document.getElementById('photoEmpty');
    const count = document.getElementById('photoCount');
    const error = document.getElementById('photoClientError');
    const coverInput = document.getElementById('cover');
    const coverImage = document.getElementById('coverPreviewImage');
    const coverPlaceholder = document.getElementById('coverPlaceholder');
    let selectedFiles = [];
    let previewUrls = [];

    function showError(message) { error.textContent = message; error.hidden = !message; }
    function synchronizeInput() {
        const transfer = new DataTransfer();
        selectedFiles.forEach(file => transfer.items.add(file));
        input.files = transfer.files;
    }
    function renderPhotos() {
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
            image.alt = 'Preview foto ' + (index + 1);
            const number = document.createElement('span');
            number.className = 'photo-item__number';
            number.textContent = 'Foto ' + (index + 1);
            const name = document.createElement('span');
            name.className = 'photo-item__name';
            name.textContent = file.name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'photo-item__remove';
            remove.setAttribute('aria-label', 'Hapus ' + file.name);
            remove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            remove.addEventListener('click', function () {
                selectedFiles.splice(index, 1);
                synchronizeInput();
                renderPhotos();
            });
            item.append(image, name, number, remove);
            preview.appendChild(item);
        });
        count.textContent = selectedFiles.length;
        emptyState.hidden = selectedFiles.length > 0;
    }
    function addFiles(fileList) {
        showError('');
        const incoming = Array.from(fileList);
        const invalidType = incoming.find(file => !supportedTypes.includes(file.type));
        const oversized = incoming.find(file => file.size > maximumFileSize);
        if (invalidType) { showError('File "' + invalidType.name + '" bukan format gambar yang didukung.'); return; }
        if (oversized) { showError('File "' + oversized.name + '" melebihi ukuran maksimal 2 MB.'); return; }
        const newFiles = incoming.filter(file => !selectedFiles.some(existing => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified));
        if (selectedFiles.length + newFiles.length > maximumPhotos) { showError('Maksimal 20 foto dalam satu album. Kurangi ' + (selectedFiles.length + newFiles.length - maximumPhotos) + ' foto.'); return; }
        selectedFiles.push(...newFiles);
        synchronizeInput();
        renderPhotos();
    }
    input.addEventListener('change', function () { addFiles(input.files); });
    ['dragenter', 'dragover'].forEach(eventName => dropZone.addEventListener(eventName, function (event) { event.preventDefault(); dropZone.classList.add('is-dragging'); }));
    ['dragleave', 'drop'].forEach(eventName => dropZone.addEventListener(eventName, function (event) { event.preventDefault(); dropZone.classList.remove('is-dragging'); }));
    dropZone.addEventListener('drop', function (event) { addFiles(event.dataTransfer.files); });
    coverInput.addEventListener('change', function () {
        if (!coverInput.files[0]) return;
        coverImage.src = URL.createObjectURL(coverInput.files[0]);
        coverImage.hidden = false;
        coverPlaceholder.hidden = true;
    });
    document.getElementById('desc').addEventListener('input', function (event) { document.getElementById('descriptionCount').textContent = event.target.value.length; });
    document.getElementById('albumForm').addEventListener('submit', function (event) {
        if (!selectedFiles.length) {
            event.preventDefault();
            showError('Pilih minimal satu foto untuk isi album.');
            dropZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        const submit = document.getElementById('submitAlbum');
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    });
});
</script>
@endsection
