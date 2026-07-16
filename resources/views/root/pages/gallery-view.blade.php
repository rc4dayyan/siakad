@extends('base.base-root-index')

@section('body-class', 'public-inner-page')

@section('content')
    @php
        $photos = collect(range(1, 20))
            ->map(fn ($index) => $album->{'file_' . $index})
            ->filter()
            ->values();
    @endphp

    <main class="public-page">
        <section class="public-hero public-hero--album" style="--album-cover: url('{{ asset('storage/' . $album->cover) }}')">
            <div class="container">
                <nav class="public-breadcrumb public-breadcrumb--light" aria-label="Breadcrumb">
                    <a href="{{ route('root.home-index') }}">Beranda</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="{{ route('root.gallery-index') }}">Galeri</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>{{ $album->name }}</span>
                </nav>
                <div class="public-hero__content public-hero__content--light">
                    <span class="home-kicker">Album foto</span>
                    <h1>{{ $album->name }}</h1>
                    <div class="public-album-meta">
                        <span><i class="fa-regular fa-images"></i> {{ $photos->count() }} foto</span>
                        @if ($album->created_at)
                            <span><i class="fa-regular fa-calendar"></i> {{ $album->created_at->translatedFormat('d F Y') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="public-section">
            <div class="container">
                <div class="public-album-intro">
                    <div>
                        <span class="home-kicker">Tentang album</span>
                        <h2>Cerita di balik momen</h2>
                    </div>
                    <p>{{ $album->desc ?: 'Dokumentasi kegiatan dan kebersamaan sivitas kampus.' }}</p>
                </div>

                @if ($photos->count())
                    <div class="public-photo-grid">
                        @foreach ($photos as $index => $photo)
                            <button type="button" class="public-photo-card public-photo-card--{{ ($index % 5) + 1 }}" data-bs-toggle="modal" data-bs-target="#gallery-lightbox" data-photo="{{ asset('storage/' . $photo) }}" data-caption="{{ $album->name }} — Foto {{ $index + 1 }}">
                                <img src="{{ asset('storage/' . $photo) }}" alt="{{ $album->name }} — Foto {{ $index + 1 }}" loading="lazy">
                                <span><i class="fa-solid fa-expand"></i></span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="public-empty-state">
                        <span class="home-icon home-icon--green"><i class="fa-regular fa-image"></i></span>
                        <h2>Foto sedang dipersiapkan</h2>
                        <p>Koleksi foto untuk album ini belum tersedia.</p>
                    </div>
                @endif

                <div class="public-back-link">
                    <a href="{{ route('root.gallery-index') }}" class="home-text-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke semua album</a>
                </div>
            </div>
        </section>
    </main>

    <div class="modal fade public-lightbox" id="gallery-lightbox" tabindex="-1" aria-labelledby="gallery-lightbox-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="gallery-lightbox-title">{{ $album->name }}</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body"><img id="gallery-lightbox-image" src="" alt=""></div>
            </div>
        </div>
    </div>
@endsection

@section('custom-js')
    <script>
        document.getElementById('gallery-lightbox')?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const image = document.getElementById('gallery-lightbox-image');
            const title = document.getElementById('gallery-lightbox-title');
            image.src = trigger.dataset.photo;
            image.alt = trigger.dataset.caption;
            title.textContent = trigger.dataset.caption;
        });
    </script>
@endsection
