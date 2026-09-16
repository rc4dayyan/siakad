@extends('base.base-root-index')

@section('body-class', 'public-inner-page')

@section('content')
    @php
        $photos = collect(range(1, 20))
            ->map(fn ($index) => $album->{'file_'.$index})
            ->filter()
            ->values();
        $lightboxPhotos = $photos->map(fn ($photo, $index) => [
            'src' => asset('storage/'.$photo),
            'caption' => $album->name.' — Foto '.($index + 1),
        ])->values();
    @endphp

    <main class="public-page public-album-detail">
        <section class="public-hero public-hero--album" style="--album-cover: url('{{ asset('storage/'.$album->cover) }}')">
            <div class="container">
                <nav class="public-breadcrumb public-breadcrumb--light" aria-label="Breadcrumb">
                    <a href="{{ route('root.home-index') }}">Beranda</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="{{ route('root.gallery-index') }}">Galeri</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>{{ $album->name }}</span>
                </nav>
                <div class="public-hero__content public-hero__content--light">
                    <span class="home-kicker">Dokumentasi kampus</span>
                    <h1>{{ $album->name }}</h1>
                    <div class="public-album-meta">
                        <span><i class="fa-regular fa-images"></i>{{ $photos->count() }} foto</span>
                        @if ($album->created_at)
                            <span><i class="fa-regular fa-calendar"></i>{{ $album->created_at->translatedFormat('d F Y') }}</span>
                        @endif
                        <span><i class="fa-solid fa-globe"></i>Album publik</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-section public-album-content">
            <div class="container">
                <div class="public-album-story">
                    <div class="public-album-story__icon"><i class="fa-solid fa-quote-left"></i></div>
                    <div class="public-album-story__copy">
                        <span class="home-kicker">Tentang album</span>
                        <h2>Cerita di balik momen</h2>
                        <p>{{ $album->desc ?: 'Dokumentasi kegiatan dan kebersamaan sivitas kampus.' }}</p>
                    </div>
                    <div class="public-album-story__hint">
                        <i class="fa-solid fa-expand"></i>
                        <span><strong>Lihat lebih dekat</strong>Pilih foto untuk membuka galeri layar penuh.</span>
                    </div>
                </div>

                @if ($photos->count())
                    <div class="public-gallery-heading">
                        <div>
                            <span class="home-kicker">Koleksi foto</span>
                            <h2>{{ $photos->count() }} momen dalam album ini</h2>
                        </div>
                        <span class="public-gallery-heading__note"><i class="fa-regular fa-keyboard"></i>Gunakan tombol panah saat galeri terbuka</span>
                    </div>

                    <div class="public-photo-grid">
                        @foreach ($photos as $index => $photo)
                            <button type="button" class="public-photo-card public-photo-card--{{ ($index % 5) + 1 }}" data-bs-toggle="modal" data-bs-target="#gallery-lightbox" data-photo-index="{{ $index }}" aria-label="Buka foto {{ $index + 1 }} dari {{ $photos->count() }}">
                                <img src="{{ asset('storage/'.$photo) }}" alt="{{ $album->name }} — Foto {{ $index + 1 }}" loading="lazy">
                                <span class="public-photo-card__number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="public-photo-card__open"><i class="fa-solid fa-expand"></i></span>
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

                <div class="public-album-footer">
                    <a href="{{ route('root.gallery-index') }}" class="home-btn home-btn--ghost"><i class="fa-solid fa-arrow-left"></i>Kembali ke galeri</a>
                    @if ($albums->isNotEmpty())
                        <a href="#album-lain" class="home-text-link">Jelajahi album lain <i class="fa-solid fa-arrow-down"></i></a>
                    @endif
                </div>
            </div>
        </section>

        @if ($albums->isNotEmpty())
            <section class="public-section public-related-albums" id="album-lain">
                <div class="container">
                    <div class="public-section-heading">
                        <div>
                            <span class="home-kicker">Koleksi lainnya</span>
                            <h2>Lanjut menjelajahi galeri</h2>
                        </div>
                        <a href="{{ route('root.gallery-index') }}" class="home-text-link">Semua album <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div class="public-album-grid">
                        @foreach ($albums as $item)
                            <article class="public-album-card">
                                <a href="{{ route('root.gallery-show', $item->slug) }}" class="public-album-card__image">
                                    <img src="{{ asset('storage/'.$item->cover) }}" alt="Sampul {{ $item->name }}" loading="lazy">
                                    <span><i class="fa-regular fa-images"></i>Lihat album</span>
                                </a>
                                <div class="public-album-card__body">
                                    <small>{{ $item->created_at?->translatedFormat('d F Y') ?? 'Galeri kampus' }}</small>
                                    <h3><a href="{{ route('root.gallery-show', $item->slug) }}">{{ $item->name }}</a></h3>
                                    <p>{{ Str::limit(strip_tags($item->desc), 90) }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    @if ($photos->count())
        <div class="modal fade public-lightbox" id="gallery-lightbox" tabindex="-1" aria-labelledby="gallery-lightbox-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span class="public-lightbox__eyebrow">{{ $album->name }}</span>
                            <h2 class="modal-title" id="gallery-lightbox-title">Foto album</h2>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup galeri"></button>
                    </div>
                    <div class="modal-body">
                        <button type="button" class="public-lightbox__nav public-lightbox__nav--previous" id="galleryPrevious" aria-label="Foto sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
                        <figure>
                            <img id="gallery-lightbox-image" src="" alt="">
                            <figcaption id="gallery-lightbox-caption"></figcaption>
                        </figure>
                        <button type="button" class="public-lightbox__nav public-lightbox__nav--next" id="galleryNext" aria-label="Foto berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="public-lightbox__footer">
                        <span id="gallery-lightbox-counter">1 / {{ $photos->count() }}</span>
                        <span><i class="fa-solid fa-arrow-left-long"></i><i class="fa-solid fa-arrow-right-long"></i> Navigasi foto</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('custom-js')
    @if ($photos->count())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const lightbox = document.getElementById('gallery-lightbox');
                const image = document.getElementById('gallery-lightbox-image');
                const title = document.getElementById('gallery-lightbox-title');
                const caption = document.getElementById('gallery-lightbox-caption');
                const counter = document.getElementById('gallery-lightbox-counter');
                const previous = document.getElementById('galleryPrevious');
                const next = document.getElementById('galleryNext');
                const photos = @json($lightboxPhotos);
                let activeIndex = 0;

                const showPhoto = (index) => {
                    activeIndex = (index + photos.length) % photos.length;
                    const photo = photos[activeIndex];
                    image.src = photo.src;
                    image.alt = photo.caption;
                    title.textContent = `Foto ${activeIndex + 1}`;
                    caption.textContent = photo.caption;
                    counter.textContent = `${activeIndex + 1} / ${photos.length}`;
                };

                lightbox.addEventListener('show.bs.modal', (event) => {
                    showPhoto(Number(event.relatedTarget?.dataset.photoIndex ?? 0));
                });
                lightbox.addEventListener('hidden.bs.modal', () => {
                    image.src = '';
                });
                previous.addEventListener('click', () => showPhoto(activeIndex - 1));
                next.addEventListener('click', () => showPhoto(activeIndex + 1));
                document.addEventListener('keydown', (event) => {
                    if (!lightbox.classList.contains('show')) return;
                    if (event.key === 'ArrowLeft') showPhoto(activeIndex - 1);
                    if (event.key === 'ArrowRight') showPhoto(activeIndex + 1);
                });
            });
        </script>
    @endif
@endsection
