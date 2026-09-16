@extends('base.base-dash-index')

@section('title', 'Manajemen Dokumen - Siakad By Internal Developer')
@section('menu', 'Publikasi')
@section('submenu', 'Dokumen Publik')
@section('urlmenu', '#')
@section('subdesc', 'Kelola dokumen yang ditampilkan pada halaman publik kampus.')

@section('custom-css')
<style>
    .document-page { --document-primary: #435ebe; --document-ink: #25324b; --document-muted: #697386; }
    .document-overview { position: relative; overflow: hidden; padding: 1.5rem; border: 0; border-radius: 20px; color: #fff; background: linear-gradient(125deg, #3349a5 0%, #5271df 62%, #6d87e7 100%); box-shadow: 0 14px 34px rgba(48, 69, 153, .2); }
    .document-overview::after { position: absolute; top: -70px; right: -45px; width: 230px; height: 230px; border: 42px solid rgba(255,255,255,.08); border-radius: 50%; content: ''; }
    .document-overview__content { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
    .document-overview__eyebrow { display: block; margin-bottom: .35rem; color: rgba(255,255,255,.72); font-size: .75rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
    .document-overview h4 { margin-bottom: .4rem; color: #fff; font-weight: 700; }
    .document-overview p { max-width: 620px; margin: 0; color: rgba(255,255,255,.78); }
    .document-overview .btn { flex: 0 0 auto; padding: .72rem 1rem; border: 0; border-radius: 11px; color: #344cae; font-weight: 700; background: #fff; box-shadow: 0 7px 18px rgba(22, 34, 87, .18); }
    .document-stat { height: 100%; padding: 1rem 1.1rem; border: 1px solid #e7eaf2; border-radius: 15px; background: var(--bs-card-bg, #fff); }
    .document-stat__icon { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; margin-bottom: .8rem; border-radius: 11px; color: var(--document-primary); background: rgba(67, 94, 190, .1); }
    .document-stat strong { display: block; color: var(--document-ink); font-size: 1.35rem; line-height: 1; }
    .document-stat small { color: var(--document-muted); }
    .document-panel { border: 0; border-radius: 18px; box-shadow: 0 8px 28px rgba(35, 46, 80, .07); }
    .document-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.2rem 1.35rem; border-bottom: 1px solid #edf0f5; }
    .document-toolbar h5 { margin: 0 0 .15rem; font-weight: 700; }
    .document-toolbar p { margin: 0; color: var(--document-muted); font-size: .82rem; }
    .document-filters { display: flex; align-items: center; gap: .65rem; }
    .document-search { position: relative; min-width: 270px; }
    .document-search i { position: absolute; top: 50%; left: .9rem; color: #98a2b3; transform: translateY(-50%); }
    .document-search .form-control { min-height: 42px; padding-left: 2.55rem; border-color: #dce1eb; border-radius: 10px; }
    .document-filters .form-select { min-width: 155px; min-height: 42px; border-color: #dce1eb; border-radius: 10px; }
    .document-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.15rem; padding: 1.35rem; }
    .document-card { display: flex; overflow: hidden; min-width: 0; flex-direction: column; border: 1px solid #e6e9f0; border-radius: 15px; background: var(--bs-card-bg, #fff); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .document-card:hover { border-color: #ccd5f4; box-shadow: 0 12px 25px rgba(35, 46, 80, .1); transform: translateY(-3px); }
    .document-card__cover { position: relative; overflow: hidden; aspect-ratio: 16 / 9; background: #eef1f7; }
    .document-card__cover img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s ease; }
    .document-card:hover .document-card__cover img { transform: scale(1.025); }
    .document-card__type { position: absolute; top: .7rem; left: .7rem; display: inline-flex; align-items: center; gap: .35rem; padding: .34rem .6rem; border-radius: 8px; color: #fff; background: rgba(23, 31, 56, .78); backdrop-filter: blur(6px); font-size: .7rem; font-weight: 700; }
    .document-card__body { display: flex; min-height: 176px; flex: 1; flex-direction: column; padding: 1rem; }
    .document-card__body h6 { overflow: hidden; margin-bottom: .55rem; color: var(--document-ink); font-size: .98rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .document-card__source { overflow: hidden; margin-bottom: .85rem; color: var(--document-muted); font-size: .76rem; text-overflow: ellipsis; white-space: nowrap; }
    .document-card__meta { display: flex; align-items: center; gap: .5rem; margin-top: auto; color: var(--document-muted); font-size: .75rem; }
    .document-card__avatar { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; flex: 0 0 auto; border-radius: 50%; color: var(--document-primary); background: rgba(67,94,190,.1); font-weight: 700; }
    .document-card__meta-text { min-width: 0; }
    .document-card__meta strong { display: block; overflow: hidden; color: #475467; font-size: .75rem; text-overflow: ellipsis; white-space: nowrap; }
    .document-card__actions { display: flex; gap: .5rem; padding: .8rem 1rem; border-top: 1px solid #edf0f5; }
    .document-card__actions .btn { display: inline-flex; min-height: 36px; align-items: center; justify-content: center; border-radius: 9px; font-size: .78rem; font-weight: 600; }
    .document-card__actions .btn:first-child { flex: 1; }
    .document-empty { padding: 4rem 1.5rem; text-align: center; }
    .document-empty__icon { display: inline-flex; align-items: center; justify-content: center; width: 68px; height: 68px; margin-bottom: 1rem; border-radius: 18px; color: var(--document-primary); background: rgba(67,94,190,.09); font-size: 1.6rem; }
    .document-empty h6 { margin-bottom: .35rem; font-weight: 700; }
    .document-empty p { max-width: 430px; margin: 0 auto; color: var(--document-muted); }
    .document-modal .modal-content { overflow: hidden; border: 0; border-radius: 18px; box-shadow: 0 20px 60px rgba(30, 42, 80, .22); }
    .document-modal .modal-header { padding: 1.25rem 1.4rem; border-bottom-color: #edf0f5; }
    .document-modal .modal-body { padding: 1.4rem; }
    .document-modal .modal-footer { padding: 1rem 1.4rem; border-top-color: #edf0f5; }
    .document-modal .form-label { margin-bottom: .45rem; color: #344054; font-weight: 600; }
    .document-modal .form-control { min-height: 44px; border-color: #d8deea; border-radius: 10px; }
    .document-cover-preview { position: relative; overflow: hidden; min-height: 220px; border: 2px dashed #cbd3e5; border-radius: 14px; background: #f7f8fc; }
    .document-cover-preview img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .document-cover-preview__placeholder { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: .45rem; color: #98a2b3; text-align: center; }
    .document-cover-preview__placeholder i { font-size: 1.8rem; }
    .document-source-switch { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
    .document-source-option { position: relative; margin: 0; }
    .document-source-option input { position: absolute; opacity: 0; pointer-events: none; }
    .document-source-option span { display: flex; align-items: center; gap: .55rem; padding: .7rem .8rem; border: 1px solid #d8deea; border-radius: 10px; color: #667085; cursor: pointer; font-size: .82rem; font-weight: 600; }
    .document-source-option input:checked + span { border-color: var(--document-primary); color: var(--document-primary); background: rgba(67,94,190,.07); box-shadow: 0 0 0 1px var(--document-primary); }
    @media (max-width: 1199.98px) { .document-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 767.98px) { .document-overview__content, .document-toolbar { align-items: stretch; flex-direction: column; } .document-overview .btn { align-self: flex-start; } .document-filters { align-items: stretch; flex-direction: column; } .document-search { min-width: 0; } .document-grid { grid-template-columns: 1fr; padding: 1rem; } }
</style>
@endsection

@section('content')
@php
    $uploadedDocuments = $docs->whereNotNull('path')->count();
    $linkedDocuments = $docs->whereNotNull('link')->count();
@endphp
<section class="section document-page">
    <div class="document-overview mb-4">
        <div class="document-overview__content">
            <div><span class="document-overview__eyebrow">Pusat Dokumen</span><h4>Kelola publikasi dokumen dengan lebih rapi</h4><p>Unggah PDF atau cantumkan tautan dokumen eksternal agar informasi penting mudah diakses oleh pengunjung.</p></div>
            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#createDocumentModal"><i class="fa-solid fa-plus me-2"></i>Tambah Dokumen</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4"><div class="document-stat"><span class="document-stat__icon"><i class="fa-regular fa-folder-open"></i></span><strong>{{ $docs->count() }}</strong><small>Total dokumen</small></div></div>
        <div class="col-6 col-lg-4"><div class="document-stat"><span class="document-stat__icon"><i class="fa-regular fa-file-pdf"></i></span><strong>{{ $uploadedDocuments }}</strong><small>File PDF</small></div></div>
        <div class="col-12 col-lg-4"><div class="document-stat"><span class="document-stat__icon"><i class="fa-solid fa-link"></i></span><strong>{{ $linkedDocuments }}</strong><small>Tautan eksternal</small></div></div>
    </div>

    <div class="card document-panel">
        <div class="document-toolbar">
            <div><h5>Daftar Dokumen</h5><p><span id="documentResultCount">{{ $docs->count() }}</span> dokumen ditampilkan</p></div>
            <div class="document-filters">
                <div class="document-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" class="form-control" id="documentSearch" placeholder="Cari nama atau penulis..." aria-label="Cari dokumen"></div>
                <select class="form-select" id="documentTypeFilter" aria-label="Filter jenis dokumen"><option value="all">Semua sumber</option><option value="file">File PDF</option><option value="link">Tautan eksternal</option></select>
            </div>
        </div>

        @if ($docs->isNotEmpty())
            <div class="document-grid" id="documentGrid">
                @foreach ($docs as $item)
                    @php
                        $documentUrl = $item->link ?: asset('storage/'.$item->path);
                        $documentType = $item->link ? 'link' : 'file';
                        $authorName = $item->author?->name ?? 'Pengguna tidak tersedia';
                        $authorInitial = mb_strtoupper(mb_substr($authorName, 0, 1));
                    @endphp
                    <article class="document-card" data-document-card data-name="{{ Str::lower($item->name.' '.$authorName) }}" data-type="{{ $documentType }}">
                        <div class="document-card__cover"><img src="{{ asset('storage/'.$item->cover) }}" alt="Sampul {{ $item->name }}" loading="lazy"><span class="document-card__type"><i class="fa-solid {{ $documentType === 'link' ? 'fa-link' : 'fa-file-pdf' }}"></i>{{ $documentType === 'link' ? 'Tautan' : 'PDF' }}</span></div>
                        <div class="document-card__body">
                            <h6 title="{{ $item->name }}">{{ $item->name }}</h6>
                            <div class="document-card__source" title="{{ $item->link ?: $item->path }}"><i class="fa-solid {{ $documentType === 'link' ? 'fa-arrow-up-right-from-square' : 'fa-paperclip' }} me-1"></i>{{ $item->link ?: basename($item->path) }}</div>
                            <div class="document-card__meta"><span class="document-card__avatar">{{ $authorInitial }}</span><span class="document-card__meta-text"><strong>{{ $authorName }}</strong>{{ $item->created_at?->translatedFormat('d M Y') ?? '-' }}</span></div>
                        </div>
                        <div class="document-card__actions">
                            <a href="{{ $documentUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-light-primary"><i class="fa-regular fa-eye me-2"></i>Buka Dokumen</a>
                            <form id="delete-form-{{ $item->code }}" action="{{ route($prefix.'document-destroy', $item->code) }}" method="POST">@csrf @method('DELETE')<button type="button" class="btn btn-outline-danger" title="Hapus {{ $item->name }}" aria-label="Hapus {{ $item->name }}" onclick="deleteData('{{ $item->code }}')"><i class="fa-regular fa-trash-can"></i></button></form>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="document-empty" id="documentNoResult" hidden><span class="document-empty__icon"><i class="fa-solid fa-magnifying-glass"></i></span><h6>Dokumen tidak ditemukan</h6><p>Coba ubah kata kunci atau pilih filter sumber yang berbeda.</p></div>
        @else
            <div class="document-empty"><span class="document-empty__icon"><i class="fa-regular fa-folder-open"></i></span><h6>Belum ada dokumen</h6><p>Tambahkan dokumen pertama agar dapat diakses melalui halaman publik kampus.</p><button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createDocumentModal"><i class="fa-solid fa-plus me-2"></i>Tambah Dokumen</button></div>
        @endif
    </div>
</section>

<form action="{{ route($prefix.'document-store') }}" method="POST" enctype="multipart/form-data" id="createDocumentForm">
    @csrf
    <input type="hidden" name="_form" value="create-document">
    <div class="modal fade document-modal" id="createDocumentModal" tabindex="-1" aria-labelledby="createDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><div><h5 class="modal-title" id="createDocumentModalLabel">Tambah Dokumen</h5><small class="text-muted">Lengkapi informasi yang akan ditampilkan kepada publik.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><div class="row g-4">
                <div class="col-md-5">
                    <div class="document-cover-preview mb-3"><img id="documentCoverImage" src="" alt="Preview sampul dokumen" hidden><div class="document-cover-preview__placeholder" id="documentCoverPlaceholder"><i class="fa-regular fa-image"></i><strong>Preview sampul</strong><small>Rasio 16:9 direkomendasikan</small></div></div>
                    <label class="form-label" for="document-cover">Sampul Dokumen <span class="text-danger">*</span></label>
                    <input type="file" class="form-control @error('cover') is-invalid @enderror" name="cover" id="document-cover" accept="image/jpeg,image/png,image/gif,image/svg+xml" required>
                    <div class="form-text">JPG, PNG, GIF, atau SVG. Maksimal 2 MB.</div>@error('cover')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-7">
                    <div class="mb-3"><label class="form-label" for="document-name">Nama Dokumen <span class="text-danger">*</span></label><input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="document-name" value="{{ old('name') }}" maxlength="255" placeholder="Contoh: Pedoman Akademik 2026" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label d-block">Sumber Dokumen <span class="text-danger">*</span></label><div class="document-source-switch">
                        <label class="document-source-option"><input type="radio" name="source_type" value="file" @checked(old('source_type', 'file') === 'file')><span><i class="fa-solid fa-file-arrow-up"></i>Unggah PDF</span></label>
                        <label class="document-source-option"><input type="radio" name="source_type" value="link" @checked(old('source_type') === 'link')><span><i class="fa-solid fa-link"></i>Tautan eksternal</span></label>
                    </div></div>
                    <div id="documentFileField"><label class="form-label" for="document-path">File PDF <span class="text-danger">*</span></label><input type="file" class="form-control @error('path') is-invalid @enderror" name="path" id="document-path" accept="application/pdf"><div class="form-text">Hanya PDF, maksimal 8 MB.</div>@error('path')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div id="documentLinkField" hidden><label class="form-label" for="document-link">URL Dokumen <span class="text-danger">*</span></label><input type="url" class="form-control @error('link') is-invalid @enderror" name="link" id="document-link" value="{{ old('link') }}" maxlength="255" placeholder="https://contoh.id/dokumen.pdf"><div class="form-text">Gunakan tautan lengkap yang dapat diakses publik.</div>@error('link')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" id="documentSubmit"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Dokumen</button></div>
        </div></div>
    </div>
</form>
@endsection

@section('custom-js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('documentSearch');
    const typeFilter = document.getElementById('documentTypeFilter');
    const cards = Array.from(document.querySelectorAll('[data-document-card]'));
    const noResult = document.getElementById('documentNoResult');
    const resultCount = document.getElementById('documentResultCount');
    function filterDocuments() {
        const query = (search?.value || '').trim().toLocaleLowerCase('id');
        const type = typeFilter?.value || 'all';
        let visible = 0;
        cards.forEach(function (card) { const show = card.dataset.name.includes(query) && (type === 'all' || card.dataset.type === type); card.hidden = !show; if (show) visible++; });
        if (resultCount) resultCount.textContent = visible;
        if (noResult) noResult.hidden = visible !== 0;
    }
    search?.addEventListener('input', filterDocuments);
    typeFilter?.addEventListener('change', filterDocuments);

    const coverInput = document.getElementById('document-cover');
    const coverImage = document.getElementById('documentCoverImage');
    const coverPlaceholder = document.getElementById('documentCoverPlaceholder');
    coverInput?.addEventListener('change', function (event) { const file = event.target.files[0]; if (!file) return; coverImage.src = URL.createObjectURL(file); coverImage.hidden = false; coverPlaceholder.hidden = true; });

    const sourceInputs = document.querySelectorAll('input[name="source_type"]');
    const fileField = document.getElementById('documentFileField');
    const linkField = document.getElementById('documentLinkField');
    const pathInput = document.getElementById('document-path');
    const linkInput = document.getElementById('document-link');
    function updateSourceFields() {
        const isFile = (document.querySelector('input[name="source_type"]:checked')?.value || 'file') === 'file';
        fileField.hidden = !isFile; linkField.hidden = isFile; pathInput.required = isFile; linkInput.required = !isFile;
        if (isFile) linkInput.value = ''; else pathInput.value = '';
    }
    sourceInputs.forEach(input => input.addEventListener('change', updateSourceFields));
    updateSourceFields();

    document.getElementById('createDocumentForm')?.addEventListener('submit', function () { const submit = document.getElementById('documentSubmit'); submit.disabled = true; submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Menyimpan...'; });
    @if ($errors->any() && old('_form') === 'create-document')
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createDocumentModal')).show();
    @endif
});
</script>
@endsection
