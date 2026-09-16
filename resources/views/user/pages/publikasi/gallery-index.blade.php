@extends('base.base-dash-index')

@section('title', 'Manajemen Album Foto')
@section('menu', 'Publikasi')
@section('submenu', 'Album Foto')
@section('subdesc', 'Kelola dokumentasi kegiatan kampus yang ditampilkan pada halaman publik.')
@section('urlmenu', '#')

@section('custom-css')
<style>
    .album-page { --album-primary: #435ebe; --album-ink: #25324b; --album-muted: #697386; }
    .album-overview { position: relative; overflow: hidden; padding: 1.6rem; border-radius: 20px; color: #fff; background: linear-gradient(125deg, #3048a4 0%, #526fd8 58%, #7189e6 100%); box-shadow: 0 14px 34px rgba(48, 69, 153, .2); }
    .album-overview::before, .album-overview::after { position: absolute; border: 1px solid rgba(255, 255, 255, .12); border-radius: 50%; content: ''; }
    .album-overview::before { top: -95px; right: 110px; width: 220px; height: 220px; }
    .album-overview::after { right: -60px; bottom: -130px; width: 290px; height: 290px; border-width: 46px; }
    .album-overview__content { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
    .album-overview__eyebrow { display: inline-flex; align-items: center; gap: .45rem; margin-bottom: .45rem; color: rgba(255, 255, 255, .72); font-size: .73rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
    .album-overview h4 { margin-bottom: .35rem; color: #fff; font-weight: 700; }
    .album-overview p { max-width: 640px; margin: 0; color: rgba(255, 255, 255, .78); }
    .album-overview .btn { flex: 0 0 auto; padding: .72rem 1rem; border: 0; border-radius: 11px; color: #344cae; font-weight: 700; background: #fff; box-shadow: 0 7px 18px rgba(22, 34, 87, .18); }
    .album-panel { overflow: hidden; border: 0; border-radius: 18px; box-shadow: 0 8px 28px rgba(35, 46, 80, .07); }
    .album-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.35rem; border-bottom: 1px solid #edf0f5; }
    .album-toolbar h5 { margin: 0 0 .15rem; color: var(--album-ink); font-weight: 700; }
    .album-toolbar p { margin: 0; color: var(--album-muted); font-size: .82rem; }
    .album-search { display: flex; align-items: center; gap: .55rem; }
    .album-search__field { position: relative; min-width: 300px; }
    .album-search__field > i { position: absolute; top: 50%; left: .9rem; z-index: 1; color: #98a2b3; transform: translateY(-50%); }
    .album-search__field .form-control { min-height: 43px; padding-right: 2.6rem; padding-left: 2.55rem; border-color: #dce1eb; border-radius: 10px; }
    .album-search__clear { position: absolute; top: 50%; right: .55rem; display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 7px; color: #7a8496; transform: translateY(-50%); }
    .album-search__clear:hover { color: var(--album-primary); background: #eef1fb; }
    .album-search .btn { min-height: 43px; border-radius: 10px; }
    .album-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.15rem; padding: 1.35rem; }
    .album-card { display: flex; overflow: hidden; min-width: 0; flex-direction: column; border: 1px solid #e6e9f0; border-radius: 16px; background: var(--bs-card-bg, #fff); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .album-card:hover { border-color: #cdd5ef; box-shadow: 0 13px 27px rgba(35, 46, 80, .11); transform: translateY(-3px); }
    .album-card__cover { position: relative; display: block; overflow: hidden; aspect-ratio: 16 / 10; background: #eef1f7; }
    .album-card__cover img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
    .album-card:hover .album-card__cover img { transform: scale(1.035); }
    .album-card__cover::after { position: absolute; inset: 45% 0 0; content: ''; background: linear-gradient(to bottom, transparent, rgba(12, 20, 40, .55)); }
    .album-card__status { position: absolute; z-index: 1; top: .75rem; left: .75rem; display: inline-flex; align-items: center; gap: .38rem; padding: .35rem .62rem; border-radius: 999px; color: #fff; background: rgba(21, 31, 57, .72); backdrop-filter: blur(7px); font-size: .7rem; font-weight: 700; }
    .album-card__status i { color: #6ce0a2; font-size: .5rem; }
    .album-card__status--draft i { color: #f8c76a; }
    .album-card__photos { position: absolute; z-index: 1; right: .75rem; bottom: .7rem; display: inline-flex; align-items: center; gap: .4rem; color: #fff; font-size: .75rem; font-weight: 700; }
    .album-card__body { display: flex; min-height: 174px; flex: 1; flex-direction: column; padding: 1rem; }
    .album-card__body h6 { overflow: hidden; margin-bottom: .45rem; color: var(--album-ink); font-size: 1rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .album-card__description { display: -webkit-box; overflow: hidden; margin-bottom: 1rem; color: var(--album-muted); font-size: .8rem; line-height: 1.55; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .album-card__meta { display: flex; min-width: 0; align-items: center; gap: .6rem; margin-top: auto; }
    .album-card__avatar { display: inline-flex; width: 32px; height: 32px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 10px; color: var(--album-primary); background: rgba(67, 94, 190, .1); font-size: .75rem; font-weight: 700; }
    .album-card__meta-text { min-width: 0; color: var(--album-muted); font-size: .71rem; }
    .album-card__meta-text strong { display: block; overflow: hidden; color: #475467; font-size: .76rem; text-overflow: ellipsis; white-space: nowrap; }
    .album-card__actions { display: flex; align-items: center; gap: .55rem; padding: .8rem 1rem; border-top: 1px solid #edf0f5; }
    .album-card__actions .btn { display: inline-flex; min-height: 36px; align-items: center; justify-content: center; border-radius: 9px; font-size: .78rem; font-weight: 600; }
    .album-card__actions .btn:first-child { flex: 1; }
    .album-empty { padding: 4.5rem 1.5rem; text-align: center; }
    .album-empty__icon { display: inline-flex; width: 72px; height: 72px; margin-bottom: 1rem; align-items: center; justify-content: center; border-radius: 20px; color: var(--album-primary); background: rgba(67, 94, 190, .09); font-size: 1.7rem; }
    .album-empty h6 { margin-bottom: .4rem; color: var(--album-ink); font-weight: 700; }
    .album-empty p { max-width: 440px; margin: 0 auto 1.15rem; color: var(--album-muted); }
    .album-pagination { padding: 0 1.35rem 1.35rem; }
    @media (max-width: 1199.98px) { .album-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 767.98px) { .album-overview__content, .album-toolbar { align-items: stretch; flex-direction: column; } .album-overview .btn { align-self: flex-start; } .album-search { align-items: stretch; } .album-search__field { min-width: 0; flex: 1; } .album-grid { grid-template-columns: 1fr; padding: 1rem; } .album-pagination { padding: 0 1rem 1rem; } }
    @media (max-width: 420px) { .album-search { flex-direction: column; } .album-search .btn { width: 100%; } }
</style>
@endsection

@section('content')
<section class="section album-page">
    <div class="album-overview mb-4">
        <div class="album-overview__content">
            <div>
                <span class="album-overview__eyebrow"><i class="fa-regular fa-images"></i> Galeri Kampus</span>
                <h4>Dokumentasikan setiap momen penting</h4>
                <p>Susun foto kegiatan dalam album yang rapi agar pengunjung mudah mengenal aktivitas dan suasana kampus.</p>
            </div>
            <a href="{{ route($prefix.'publish.album-create') }}" class="btn"><i class="fa-solid fa-plus me-2"></i>Tambah Album</a>
        </div>
    </div>

    <div class="card album-panel">
        <div class="album-toolbar">
            <div>
                <h5>Daftar Album</h5>
                <p>{{ $album->total() }} album ditemukan{{ request('search') ? ' untuk pencarian “'.request('search').'”' : '' }}</p>
            </div>
            <form action="{{ route($prefix.'publish.album-search') }}" method="GET" class="album-search" role="search">
                <div class="album-search__field">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama album..." aria-label="Cari nama album">
                    @if (request('search'))
                        <a href="{{ route($prefix.'publish.album-index') }}" class="album-search__clear" aria-label="Hapus pencarian" title="Hapus pencarian"><i class="fa-solid fa-xmark"></i></a>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary"><span class="d-none d-sm-inline">Cari</span><i class="fa-solid fa-arrow-right ms-sm-2"></i></button>
            </form>
        </div>

        @if ($album->isNotEmpty())
            <div class="album-grid">
                @foreach ($album as $item)
                    @php
                        $photoCount = collect(range(1, 20))->filter(fn ($slot) => filled($item->{'file_'.$slot}))->count();
                        $authorName = $item->author?->name ?? 'Pengguna tidak tersedia';
                        $authorInitial = mb_strtoupper(mb_substr($authorName, 0, 1));
                    @endphp
                    <article class="album-card">
                        <a href="{{ route($prefix.'publish.album-show', $item->slug) }}" class="album-card__cover" aria-label="Lihat album {{ $item->name }}">
                            <img src="{{ asset('storage/'.$item->cover) }}" alt="Sampul {{ $item->name }}" loading="lazy">
                            <span class="album-card__status {{ $item->isPublish ? '' : 'album-card__status--draft' }}"><i class="fa-solid fa-circle"></i>{{ $item->isPublish ? 'Dipublikasikan' : 'Draf' }}</span>
                            <span class="album-card__photos"><i class="fa-regular fa-images"></i>{{ $photoCount }} foto</span>
                        </a>
                        <div class="album-card__body">
                            <h6 title="{{ $item->name }}">{{ $item->name }}</h6>
                            <p class="album-card__description">{{ Str::limit($item->desc, 120) }}</p>
                            <div class="album-card__meta">
                                <span class="album-card__avatar">{{ $authorInitial }}</span>
                                <span class="album-card__meta-text"><strong>{{ $authorName }}</strong>Dibuat {{ $item->created_at?->translatedFormat('d M Y') ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="album-card__actions">
                            <a href="{{ route($prefix.'publish.album-show', $item->slug) }}" class="btn btn-light-primary"><i class="fa-regular fa-eye me-2"></i>Lihat Album</a>
                            <a href="{{ route($prefix.'publish.album-edit', $item->slug) }}" class="btn btn-outline-primary" aria-label="Edit {{ $item->name }}" title="Edit album"><i class="fa-regular fa-pen-to-square"></i></a>
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($album->hasPages())
                <div class="album-pagination">{{ $album->onEachSide(1)->links('root.vendor.paginator') }}</div>
            @endif
        @else
            <div class="album-empty">
                <span class="album-empty__icon"><i class="fa-regular fa-images"></i></span>
                @if (request('search'))
                    <h6>Album tidak ditemukan</h6>
                    <p>Tidak ada album yang cocok dengan “{{ request('search') }}”. Coba gunakan kata kunci lain.</p>
                    <a href="{{ route($prefix.'publish.album-index') }}" class="btn btn-light-primary"><i class="fa-solid fa-arrow-left me-2"></i>Lihat Semua Album</a>
                @else
                    <h6>Belum ada album foto</h6>
                    <p>Tambahkan album pertama untuk mulai menampilkan dokumentasi kegiatan kampus.</p>
                    <a href="{{ route($prefix.'publish.album-create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Tambah Album</a>
                @endif
            </div>
        @endif
    </div>
</section>
@endsection
