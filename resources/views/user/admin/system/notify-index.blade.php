@extends('base.base-dash-index')

@section('menu', 'Pengumuman')
@section('submenu', 'Daftar Pengumuman')
@section('urlmenu', '#')
@section('subdesc', 'Kelola informasi yang dikirimkan kepada seluruh pengguna sistem.')

@section('custom-css')
<style>
    .notify-page { --notify-primary: #435ebe; --notify-ink: #25324b; --notify-muted: #697386; }
    .notify-overview { position: relative; overflow: hidden; padding: 1.55rem; border-radius: 20px; color: #fff; background: linear-gradient(125deg, #3349a5 0%, #5271df 62%, #6d87e7 100%); box-shadow: 0 14px 34px rgba(48, 69, 153, .2); }
    .notify-overview::after { position: absolute; top: -80px; right: -45px; width: 245px; height: 245px; border: 44px solid rgba(255, 255, 255, .08); border-radius: 50%; content: ''; }
    .notify-overview__content { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
    .notify-overview__eyebrow { display: block; margin-bottom: .35rem; color: rgba(255, 255, 255, .72); font-size: .73rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
    .notify-overview h4 { margin-bottom: .4rem; color: #fff; font-weight: 700; }
    .notify-overview p { max-width: 650px; margin: 0; color: rgba(255, 255, 255, .78); }
    .notify-overview .btn { flex: 0 0 auto; padding: .72rem 1rem; border: 0; border-radius: 11px; color: #344cae; font-weight: 700; background: #fff; box-shadow: 0 7px 18px rgba(22, 34, 87, .18); }
    .notify-stat { display: flex; height: 100%; align-items: center; gap: .9rem; padding: 1rem 1.1rem; border: 1px solid #e7eaf2; border-radius: 15px; background: var(--bs-card-bg, #fff); }
    .notify-stat__icon { display: inline-flex; width: 42px; height: 42px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 12px; color: var(--notify-primary); background: rgba(67, 94, 190, .1); }
    .notify-stat__icon--warning { color: #b87910; background: rgba(245, 166, 35, .13); }
    .notify-stat__icon--success { color: #16855b; background: rgba(34, 170, 112, .12); }
    .notify-stat strong { display: block; color: var(--notify-ink); font-size: 1.25rem; line-height: 1.1; }
    .notify-stat small { color: var(--notify-muted); }
    .notify-filter { border: 0; border-radius: 18px; box-shadow: 0 8px 28px rgba(35, 46, 80, .07); }
    .notify-filter .card-body { padding: 1.25rem 1.35rem; }
    .notify-filter__heading { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
    .notify-filter__heading span { display: inline-flex; width: 36px; height: 36px; align-items: center; justify-content: center; border-radius: 10px; color: var(--notify-primary); background: rgba(67, 94, 190, .1); }
    .notify-filter__heading h5 { margin: 0 0 .1rem; font-size: 1rem; font-weight: 700; }
    .notify-filter__heading p { margin: 0; color: var(--notify-muted); font-size: .76rem; }
    .notify-filter .form-label { margin-bottom: .4rem; color: #475467; font-size: .77rem; font-weight: 600; }
    .notify-filter .form-control, .notify-filter .form-select { min-height: 43px; border-color: #dce1eb; border-radius: 10px; }
    .notify-filter__search { position: relative; }
    .notify-filter__search i { position: absolute; top: 50%; left: .9rem; color: #98a2b3; transform: translateY(-50%); }
    .notify-filter__search .form-control { padding-left: 2.5rem; }
    .notify-filter__actions { display: flex; align-items: end; gap: .55rem; height: 100%; padding-top: 1.55rem; }
    .notify-filter__actions .btn { min-height: 43px; border-radius: 10px; }
    .notify-panel { overflow: hidden; border: 0; border-radius: 18px; box-shadow: 0 8px 28px rgba(35, 46, 80, .07); }
    .notify-panel__header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.2rem 1.35rem; border-bottom: 1px solid #edf0f5; }
    .notify-panel__header h5 { margin: 0 0 .15rem; color: var(--notify-ink); font-weight: 700; }
    .notify-panel__header p { margin: 0; color: var(--notify-muted); font-size: .8rem; }
    .notify-active-filter { padding: .38rem .68rem; border-radius: 999px; color: var(--notify-primary); background: rgba(67, 94, 190, .09); font-size: .7rem; font-weight: 700; }
    .notify-list { display: flex; flex-direction: column; }
    .notify-item { display: grid; grid-template-columns: auto minmax(0, 1fr) minmax(180px, .35fr) auto; gap: 1rem; align-items: center; padding: 1.1rem 1.35rem; border-bottom: 1px solid #edf0f5; transition: background .18s ease; }
    .notify-item:last-child { border-bottom: 0; }
    .notify-item:hover { background: #fafbfe; }
    .notify-item__icon { display: inline-flex; width: 44px; height: 44px; align-items: center; justify-content: center; border-radius: 13px; color: var(--notify-primary); background: rgba(67, 94, 190, .1); }
    .notify-item__content { min-width: 0; }
    .notify-item__title { display: flex; min-width: 0; align-items: center; gap: .5rem; margin-bottom: .3rem; }
    .notify-item__title h6 { overflow: hidden; margin: 0; color: var(--notify-ink); font-size: .92rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .notify-item__description { overflow: hidden; margin: 0 0 .55rem; color: var(--notify-muted); font-size: .78rem; text-overflow: ellipsis; white-space: nowrap; }
    .notify-item__badges { display: flex; flex-wrap: wrap; gap: .4rem; }
    .notify-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .27rem .52rem; border-radius: 7px; color: #596579; background: #f0f2f6; font-size: .67rem; font-weight: 600; }
    .notify-badge--target { color: var(--notify-primary); background: rgba(67, 94, 190, .09); }
    .notify-badge--read { color: #16855b; background: rgba(34, 170, 112, .1); }
    .notify-badge--unread { color: #a76b09; background: rgba(245, 166, 35, .13); }
    .notify-item__meta { min-width: 0; color: var(--notify-muted); font-size: .7rem; }
    .notify-item__meta strong { display: block; overflow: hidden; margin-bottom: .2rem; color: #475467; font-size: .76rem; text-overflow: ellipsis; white-space: nowrap; }
    .notify-item__actions { display: flex; gap: .45rem; }
    .notify-item__actions .btn { display: inline-flex; width: 36px; height: 36px; padding: 0; align-items: center; justify-content: center; border-radius: 9px; }
    .notify-pagination { padding: 1rem 1.35rem; border-top: 1px solid #edf0f5; }
    .notify-empty { padding: 4.5rem 1.5rem; text-align: center; }
    .notify-empty__icon { display: inline-flex; width: 70px; height: 70px; margin-bottom: 1rem; align-items: center; justify-content: center; border-radius: 19px; color: var(--notify-primary); background: rgba(67, 94, 190, .09); font-size: 1.6rem; }
    .notify-empty h6 { margin-bottom: .35rem; color: var(--notify-ink); font-weight: 700; }
    .notify-empty p { max-width: 450px; margin: 0 auto 1rem; color: var(--notify-muted); }
    .notify-modal .modal-content { overflow: hidden; border: 0; border-radius: 18px; box-shadow: 0 20px 60px rgba(30, 42, 80, .22); }
    .notify-modal .modal-header, .notify-modal .modal-footer { padding: 1.1rem 1.35rem; border-color: #edf0f5; }
    .notify-modal .modal-body { padding: 1.35rem; }
    .notify-modal .modal-title { font-weight: 700; }
    .notify-modal .form-label { margin-bottom: .45rem; color: #344054; font-weight: 600; }
    .notify-modal .form-control, .notify-modal .form-select { min-height: 44px; border-color: #d8deea; border-radius: 10px; }
    @media (max-width: 991.98px) { .notify-item { grid-template-columns: auto minmax(0, 1fr) auto; } .notify-item__meta { grid-column: 2; } .notify-item__actions { grid-column: 3; grid-row: 1 / span 2; } }
    @media (max-width: 767.98px) { .notify-overview__content, .notify-panel__header { align-items: stretch; flex-direction: column; } .notify-overview .btn { align-self: flex-start; } .notify-filter__actions { padding-top: 0; } .notify-item { grid-template-columns: auto minmax(0, 1fr); padding: 1rem; } .notify-item__meta { grid-column: 2; } .notify-item__actions { grid-column: 1 / -1; grid-row: auto; padding-top: .25rem; } .notify-item__actions .btn { width: 42px; } }
</style>
@endsection

@section('content')
@php
    $targetLabels = [
        0 => 'Semua pengguna',
        1 => 'Staff / Pegawai',
        2 => 'Dosen',
        3 => 'Mahasiswa',
    ];
    $activeFilterCount = collect(['search', 'target', 'type', 'status'])
        ->filter(fn ($filter) => request()->filled($filter))
        ->count();
@endphp

<section class="section notify-page">
    <div class="notify-overview mb-4">
        <div class="notify-overview__content">
            <div>
                <span class="notify-overview__eyebrow">Pusat Informasi</span>
                <h4>Sampaikan pengumuman secara tepat sasaran</h4>
                <p>Kelola informasi penting untuk seluruh pengguna, staff, dosen, atau mahasiswa dari satu halaman.</p>
            </div>
            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#createNotifyModal"><i class="fa-solid fa-plus me-2"></i>Tambah Pengumuman</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4"><div class="notify-stat"><span class="notify-stat__icon"><i class="fa-solid fa-bullhorn"></i></span><div><strong>{{ $totalNotifications }}</strong><small>Total pengumuman</small></div></div></div>
        <div class="col-6 col-lg-4"><div class="notify-stat"><span class="notify-stat__icon notify-stat__icon--warning"><i class="fa-regular fa-envelope"></i></span><div><strong>{{ $unreadNotifications }}</strong><small>Belum dibaca</small></div></div></div>
        <div class="col-12 col-lg-4"><div class="notify-stat"><span class="notify-stat__icon notify-stat__icon--success"><i class="fa-solid fa-filter"></i></span><div><strong>{{ $notify->total() }}</strong><small>Hasil ditampilkan</small></div></div></div>
    </div>

    <form action="{{ route($prefix.'system.notify-index') }}" method="GET" class="card notify-filter mb-4">
        <div class="card-body">
            <div class="notify-filter__heading">
                <span><i class="fa-solid fa-sliders"></i></span>
                <div><h5>Filter Pengumuman</h5><p>Temukan pengumuman berdasarkan kata kunci, sasaran, kategori, atau status.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <label for="notify-search" class="form-label">Pencarian</label>
                    <div class="notify-filter__search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="search" id="notify-search" class="form-control" value="{{ request('search') }}" placeholder="Cari judul, isi, atau kategori..."></div>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="notify-target" class="form-label">Sasaran</label>
                    <select name="target" id="notify-target" class="form-select"><option value="">Semua sasaran</option>@foreach ($targetLabels as $value => $label)<option value="{{ $value }}" @selected((string) request('target') === (string) $value)>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="notify-type" class="form-label">Kategori</label>
                    <select name="type" id="notify-type" class="form-select"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category }}" @selected(request('type') === $category)>{{ $category }}</option>@endforeach</select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="notify-status" class="form-label">Status</label>
                    <select name="status" id="notify-status" class="form-select"><option value="">Semua status</option><option value="unread" @selected(request('status') === 'unread')>Belum dibaca</option><option value="read" @selected(request('status') === 'read')>Sudah dibaca</option></select>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="notify-filter__actions"><button type="submit" class="btn btn-primary flex-grow-1"><i class="fa-solid fa-filter me-1"></i>Terapkan</button>@if ($activeFilterCount)<a href="{{ route($prefix.'system.notify-index') }}" class="btn btn-light" aria-label="Reset filter" title="Reset filter"><i class="fa-solid fa-rotate-left"></i></a>@endif</div>
                </div>
            </div>
        </div>
    </form>

    <div class="card notify-panel">
        <div class="notify-panel__header">
            <div><h5>Daftar Pengumuman</h5><p>Menampilkan {{ $notify->firstItem() ?? 0 }}–{{ $notify->lastItem() ?? 0 }} dari {{ $notify->total() }} pengumuman</p></div>
            @if ($activeFilterCount)<span class="notify-active-filter"><i class="fa-solid fa-filter me-1"></i>{{ $activeFilterCount }} filter aktif</span>@endif
        </div>

        @if ($notify->isNotEmpty())
            <div class="notify-list">
                @foreach ($notify as $item)
                    <article class="notify-item">
                        <span class="notify-item__icon"><i class="fa-solid fa-bullhorn"></i></span>
                        <div class="notify-item__content">
                            <div class="notify-item__title"><h6 title="{{ $item->name }}">{{ $item->name }}</h6></div>
                            <p class="notify-item__description">{{ Str::limit(strip_tags($item->desc), 140) }}</p>
                            <div class="notify-item__badges">
                                <span class="notify-badge notify-badge--target"><i class="fa-solid fa-users"></i>{{ $targetLabels[$item->send_to] ?? 'Sasaran khusus' }}</span>
                                <span class="notify-badge">{{ $item->type }}</span>
                                <span class="notify-badge {{ $item->read ? 'notify-badge--read' : 'notify-badge--unread' }}"><i class="fa-solid fa-circle"></i>{{ $item->read ? 'Sudah dibaca' : 'Belum dibaca' }}</span>
                            </div>
                        </div>
                        <div class="notify-item__meta"><strong>{{ $item->author?->name ?? 'Pengguna tidak tersedia' }}</strong><span><i class="fa-regular fa-calendar me-1"></i>{{ $item->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span></div>
                        <div class="notify-item__actions">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateNotify{{ $item->code }}" aria-label="Edit {{ $item->name }}" title="Edit pengumuman"><i class="fa-regular fa-pen-to-square"></i></button>
                            <form id="delete-form-{{ $item->code }}" action="{{ route($prefix.'system.notify-destroy', $item->code) }}" method="POST">@csrf @method('DELETE')<button type="button" class="btn btn-outline-danger" data-name="{{ $item->name }}" onclick="deleteData('{{ $item->code }}')" aria-label="Hapus {{ $item->name }}" title="Hapus pengumuman"><i class="fa-regular fa-trash-can"></i></button></form>
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($notify->hasPages())<div class="notify-pagination">{{ $notify->onEachSide(1)->links('root.vendor.paginator') }}</div>@endif
        @else
            <div class="notify-empty">
                <span class="notify-empty__icon"><i class="fa-regular fa-bell-slash"></i></span>
                <h6>{{ $activeFilterCount ? 'Pengumuman tidak ditemukan' : 'Belum ada pengumuman' }}</h6>
                <p>{{ $activeFilterCount ? 'Coba ubah atau reset filter untuk melihat hasil lainnya.' : 'Tambahkan pengumuman pertama untuk mulai menyampaikan informasi kepada pengguna.' }}</p>
                @if ($activeFilterCount)<a href="{{ route($prefix.'system.notify-index') }}" class="btn btn-light-primary">Reset Filter</a>@else<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNotifyModal">Tambah Pengumuman</button>@endif
            </div>
        @endif
    </div>
</section>

<datalist id="notificationCategories">@foreach ($categories as $category)<option value="{{ $category }}">@endforeach</datalist>

<form action="{{ route($prefix.'system.notify-store') }}" method="POST">
    @csrf
    <input type="hidden" name="_form" value="create-notify">
    <div class="modal fade notify-modal" id="createNotifyModal" tabindex="-1" aria-labelledby="createNotifyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content">
            <div class="modal-header"><div><h5 class="modal-title" id="createNotifyModalLabel">Tambah Pengumuman</h5><small class="text-muted">Lengkapi informasi yang akan dikirimkan.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label for="create-notify-target" class="form-label">Sasaran <span class="text-danger">*</span></label><select name="send_to" id="create-notify-target" class="form-select @error('send_to') is-invalid @enderror"><option value="">Pilih sasaran</option>@foreach ($targetLabels as $value => $label)<option value="{{ $value }}" @selected(old('send_to') !== null && (int) old('send_to') === $value)>{{ $label }}</option>@endforeach</select>@error('send_to')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label for="create-notify-type" class="form-label">Kategori <span class="text-danger">*</span></label><input type="text" name="type" id="create-notify-type" list="notificationCategories" class="form-control @error('type') is-invalid @enderror" value="{{ old('type') }}" maxlength="255" placeholder="Contoh: Akademik">@error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label for="create-notify-name" class="form-label">Judul Pengumuman <span class="text-danger">*</span></label><input type="text" name="name" id="create-notify-name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="255" placeholder="Masukkan judul yang ringkas dan jelas">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label for="create-notify-desc" class="form-label">Isi Pengumuman <span class="text-danger">*</span></label><textarea name="desc" id="create-notify-desc" class="form-control notify-editor @error('desc') is-invalid @enderror" rows="8">{{ old('desc') }}</textarea>@error('desc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-2"></i>Simpan Pengumuman</button></div>
        </div></div>
    </div>
</form>

@foreach ($notify as $item)
    @php($isEditing = old('_form') === 'update-notify-'.$item->code)
    <form action="{{ route($prefix.'system.notify-update', $item->code) }}" method="POST">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_form" value="update-notify-{{ $item->code }}">
        <div class="modal fade notify-modal" id="updateNotify{{ $item->code }}" tabindex="-1" aria-labelledby="updateNotifyLabel{{ $item->code }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content">
                <div class="modal-header"><div><h5 class="modal-title" id="updateNotifyLabel{{ $item->code }}">Edit Pengumuman</h5><small class="text-muted">Perbarui informasi “{{ $item->name }}”.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label for="update-target-{{ $item->code }}" class="form-label">Sasaran <span class="text-danger">*</span></label><select name="send_to" id="update-target-{{ $item->code }}" class="form-select @if ($isEditing) @error('send_to') is-invalid @enderror @endif">@foreach ($targetLabels as $value => $label)<option value="{{ $value }}" @selected((int) ($isEditing ? old('send_to', $item->send_to) : $item->send_to) === $value)>{{ $label }}</option>@endforeach</select>@if ($isEditing) @error('send_to')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-md-6"><label for="update-type-{{ $item->code }}" class="form-label">Kategori <span class="text-danger">*</span></label><input type="text" name="type" id="update-type-{{ $item->code }}" list="notificationCategories" class="form-control @if ($isEditing) @error('type') is-invalid @enderror @endif" value="{{ $isEditing ? old('type', $item->type) : $item->type }}" maxlength="255">@if ($isEditing) @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-12"><label for="update-name-{{ $item->code }}" class="form-label">Judul Pengumuman <span class="text-danger">*</span></label><input type="text" name="name" id="update-name-{{ $item->code }}" class="form-control @if ($isEditing) @error('name') is-invalid @enderror @endif" value="{{ $isEditing ? old('name', $item->name) : $item->name }}" maxlength="255">@if ($isEditing) @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                        <div class="col-12"><label for="update-desc-{{ $item->code }}" class="form-label">Isi Pengumuman <span class="text-danger">*</span></label><textarea name="desc" id="update-desc-{{ $item->code }}" class="form-control notify-editor @if ($isEditing) @error('desc') is-invalid @enderror @endif" rows="8">{{ $isEditing ? old('desc', $item->desc) : $item->desc }}</textarea>@if ($isEditing) @error('desc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan</button></div>
            </div></div>
        </div>
    </form>
@endforeach
@endsection

@section('custom-js')
<script src="{{ asset('dist') }}/assets/extensions/tinymce/tinymce.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        tinymce.init({
            selector: 'textarea.notify-editor',
            height: 260,
            menubar: false,
            plugins: 'lists link',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat',
            branding: false
        });

        @if ($errors->any() && old('_form') === 'create-notify')
            bootstrap.Modal.getOrCreateInstance(document.getElementById('createNotifyModal')).show();
        @elseif ($errors->any() && Str::startsWith(old('_form', ''), 'update-notify-'))
            const updateModalId = 'updateNotify' + @json(Str::after(old('_form'), 'update-notify-'));
            bootstrap.Modal.getOrCreateInstance(document.getElementById(updateModalId)).show();
        @endif
    });
</script>
@endsection
