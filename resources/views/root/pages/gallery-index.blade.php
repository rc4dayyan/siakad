@extends('base.base-root-index')

@section('body-class', 'public-inner-page')

@section('content')
    <main class="public-page">
        <section class="public-hero public-hero--compact">
            <div class="container">
                <nav class="public-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('root.home-index') }}">Beranda</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Galeri</span>
                </nav>
                <div class="public-hero__split">
                    <div class="public-hero__content">
                        <span class="home-kicker">Momen dan cerita</span>
                        <h1>Galeri kegiatan kampus</h1>
                        <p>Rekam jejak aktivitas akademik, kolaborasi, dan kebersamaan sivitas kampus.</p>
                    </div>
                    <form class="public-search" action="{{ route('root.gallery-search') }}" method="GET" role="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <label class="visually-hidden" for="gallery-search">Cari album</label>
                        <input type="search" name="search" id="gallery-search" value="{{ request('search') }}" placeholder="Cari album...">
                        <button type="submit">Cari</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="public-section">
            <div class="container">
                <div class="public-section-heading">
                    <div>
                        <span class="home-kicker">Koleksi terbaru</span>
                        <h2>{{ request('search') ? 'Hasil pencarian “' . request('search') . '”' : 'Jelajahi album foto' }}</h2>
                    </div>
                    <span class="public-result-count">{{ $albums->total() }} album</span>
                </div>

                @if ($albums->count())
                    <div class="public-album-grid">
                        @foreach ($albums as $item)
                            <article class="public-album-card">
                                <a href="{{ route('root.gallery-show', $item->slug) }}" class="public-album-card__image">
                                    <img src="{{ asset('storage/' . $item->cover) }}" alt="{{ $item->name }}" loading="lazy">
                                    <span><i class="fa-regular fa-images"></i> Lihat album</span>
                                </a>
                                <div class="public-album-card__body">
                                    <small>{{ $item->created_at?->translatedFormat('d F Y') ?? 'Galeri kampus' }}</small>
                                    <h3><a href="{{ route('root.gallery-show', $item->slug) }}">{{ $item->name }}</a></h3>
                                    <p>{{ Str::limit(strip_tags($item->desc), 90) }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="public-pagination">{{ $albums->withQueryString()->links('root.vendor.paginator') }}</div>
                @else
                    <div class="public-empty-state">
                        <span class="home-icon home-icon--green"><i class="fa-regular fa-images"></i></span>
                        <h2>Album tidak ditemukan</h2>
                        <p>Coba gunakan kata kunci lain atau kembali melihat seluruh koleksi galeri.</p>
                        <a href="{{ route('root.gallery-index') }}" class="home-btn home-btn--primary">Lihat semua album</a>
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection
