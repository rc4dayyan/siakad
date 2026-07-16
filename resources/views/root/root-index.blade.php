@extends('base.base-root-index')

@section('body-class', 'modern-home')

@section('custom-css')
    <link rel="stylesheet" href="{{ asset('dist') }}/custom/home.css">
@endsection

@section('content')
    @php
        $featuredAlbum = $album->first();
        $featuredPost = $posts->first();
        $heroImage = $featuredAlbum
            ? asset('storage/' . $featuredAlbum->cover)
            : asset('auth/assets/img/curved-images/curved11.jpg');
    @endphp

    <main class="home-page">
        <section class="home-hero" aria-labelledby="hero-title">
            <div class="home-hero__glow home-hero__glow--one"></div>
            <div class="home-hero__glow home-hero__glow--two"></div>
            <div class="container home-hero__container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="home-eyebrow">
                            <span class="home-eyebrow__dot"></span>
                            Sistem Informasi Akademik Terpadu
                        </div>
                        <h1 id="hero-title">Langkah cerdas menuju masa depan yang <span>lebih bermakna.</span></h1>
                        <p class="home-hero__lead">
                            Temukan pengalaman akademik yang terhubung, transparan, dan mudah diakses di
                            {{ strip_tags($web->school_name) }}.
                        </p>
                        <div class="home-hero__actions">
                            <a href="#program-studi" class="home-btn home-btn--primary">
                                Jelajahi Program
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                            <a href="{{ route('mahasiswa.auth-signin-page') }}" class="home-btn home-btn--ghost">
                                <i class="fa-regular fa-user" aria-hidden="true"></i>
                                Portal Mahasiswa
                            </a>
                        </div>
                        <div class="home-hero__metrics" aria-label="Ringkasan kampus">
                            <div>
                                <strong>{{ $fakultas->count() }}</strong>
                                <span>Fakultas</span>
                            </div>
                            <div>
                                <strong>{{ $proku->count() }}</strong>
                                <span>Program Kuliah</span>
                            </div>
                            <div>
                                <strong>{{ $posts->total() }}</strong>
                                <span>Informasi Terkini</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="home-hero__visual">
                            <div class="home-hero__image-wrap">
                                <img src="{{ $heroImage }}" alt="Kegiatan akademik {{ strip_tags($web->school_name) }}">
                                <div class="home-hero__image-shade"></div>
                                @if ($featuredAlbum)
                                    <a href="{{ route('root.gallery-show', $featuredAlbum->slug) }}" class="home-hero__caption">
                                        <span class="home-hero__caption-icon"><i class="fa-regular fa-images"></i></span>
                                        <span>
                                            <small>Galeri pilihan</small>
                                            <strong>{{ $featuredAlbum->name }}</strong>
                                        </span>
                                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                            <div class="home-hero__badge">
                                <span><i class="fa-solid fa-graduation-cap"></i></span>
                                <div>
                                    <strong>Kampus berdampak</strong>
                                    <small>Belajar, bertumbuh, mengabdi</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-quick" aria-label="Akses cepat">
            <div class="container">
                <div class="home-quick__grid">
                    <a href="{{ route('mahasiswa.auth-signin-page') }}" class="home-quick__item">
                        <span class="home-icon home-icon--green"><i class="fa-solid fa-user-graduate"></i></span>
                        <span><strong>Portal Mahasiswa</strong><small>Akses jadwal, tugas, nilai, dan tagihan</small></span>
                        <i class="fa-solid fa-chevron-right home-quick__arrow"></i>
                    </a>
                    <a href="{{ route('dosen.auth-signin-page') }}" class="home-quick__item">
                        <span class="home-icon home-icon--blue"><i class="fa-solid fa-chalkboard-user"></i></span>
                        <span><strong>Portal Dosen</strong><small>Kelola perkuliahan dan aktivitas akademik</small></span>
                        <i class="fa-solid fa-chevron-right home-quick__arrow"></i>
                    </a>
                    <a href="{{ route('root.home-download') }}" class="home-quick__item">
                        <span class="home-icon home-icon--gold"><i class="fa-regular fa-folder-open"></i></span>
                        <span><strong>Pusat Dokumen</strong><small>Unduh informasi dan dokumen kampus</small></span>
                        <i class="fa-solid fa-chevron-right home-quick__arrow"></i>
                    </a>
                </div>
            </div>
        </section>

        <section class="home-section" id="program-studi">
            <div class="container">
                <div class="home-section__heading home-section__heading--center">
                    <span class="home-kicker">Pendidikan untuk masa depan</span>
                    <h2>Temukan jalur akademik yang tepat untuk Anda</h2>
                    <p>Program pendidikan dirancang untuk membentuk lulusan yang kompeten, adaptif, dan berintegritas.</p>
                </div>

                <div class="row g-4 justify-content-center">
                    @forelse ($fakultas as $index => $faku)
                        <div class="col-md-6 col-lg-4">
                            <article class="home-program-card">
                                <div class="home-program-card__number">0{{ $index + 1 }}</div>
                                <span class="home-icon home-icon--soft"><i class="fa-solid fa-book-open-reader"></i></span>
                                <h3>{{ $faku->name }}</h3>
                                @php
                                    $programs = \App\Models\ProgramStudi::where('faku_id', $faku->id)->get();
                                @endphp
                                <p>{{ $programs->count() }} program studi tersedia untuk mendukung tujuan akademik dan karier Anda.</p>
                                <div class="home-program-card__links">
                                    @foreach ($programs->take(3) as $program)
                                        <a href="{{ route('root.home-prodi', $program->slug) }}">
                                            <span>{{ $program->level }} · {{ $program->name }}</span>
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    @endforeach
                                </div>
                            </article>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="home-empty">Informasi fakultas sedang dipersiapkan.</div>
                        </div>
                    @endforelse

                    <div class="col-md-6 col-lg-4">
                        <article class="home-program-card home-program-card--accent">
                            <div class="home-program-card__number"><i class="fa-solid fa-compass"></i></div>
                            <span class="home-icon home-icon--accent"><i class="fa-solid fa-calendar-check"></i></span>
                            <h3>Program Kuliah</h3>
                            <p>Pilih skema perkuliahan yang paling sesuai dengan ritme dan kebutuhan belajar Anda.</p>
                            <div class="home-program-card__links">
                                @forelse ($proku->take(3) as $program)
                                    <a href="{{ route('root.home-proku', $program->code) }}">
                                        <span>{{ $program->name }}</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                @empty
                                    <span class="home-program-card__muted">Informasi segera tersedia.</span>
                                @endforelse
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-section home-section--muted">
            <div class="container">
                <div class="row g-5 align-items-start">
                    <div class="col-lg-8">
                        <div class="home-section__heading home-section__heading--row">
                            <div>
                                <span class="home-kicker">Warta kampus</span>
                                <h2>Berita dan cerita terbaru</h2>
                            </div>
                        </div>

                        @if ($featuredPost)
                            <div class="home-news-grid">
                                <article class="home-news-featured">
                                    <a href="{{ route('root.post-view', $featuredPost->slug) }}" class="home-news-featured__image">
                                        <img src="{{ asset('storage/images/' . $featuredPost->image) }}" alt="{{ $featuredPost->name }}">
                                        <span>{{ $featuredPost->category?->name ?? 'Berita Kampus' }}</span>
                                    </a>
                                    <div class="home-news-featured__body">
                                        <time datetime="{{ $featuredPost->created_at->toDateString() }}">
                                            {{ $featuredPost->created_at->translatedFormat('d F Y') }}
                                        </time>
                                        <h3><a href="{{ route('root.post-view', $featuredPost->slug) }}">{{ $featuredPost->name }}</a></h3>
                                        <p>{{ Str::limit(strip_tags($featuredPost->content), 150) }}</p>
                                        <a href="{{ route('root.post-view', $featuredPost->slug) }}" class="home-text-link">
                                            Baca selengkapnya <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </article>

                                <div class="home-news-list">
                                    @foreach ($posts->skip(1)->take(3) as $item)
                                        <article class="home-news-item">
                                            <a href="{{ route('root.post-view', $item->slug) }}" class="home-news-item__image">
                                                <img src="{{ asset('storage/images/' . $item->image) }}" alt="{{ $item->name }}">
                                            </a>
                                            <div>
                                                <time datetime="{{ $item->created_at->toDateString() }}">{{ $item->created_at->translatedFormat('d M Y') }}</time>
                                                <h3><a href="{{ route('root.post-view', $item->slug) }}">{{ $item->name }}</a></h3>
                                                <span>{{ $item->category?->name ?? 'Berita Kampus' }}</span>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                            <div class="home-pagination">{{ $posts->links('root.vendor.paginator') }}</div>
                        @else
                            <div class="home-empty">Belum ada berita yang dipublikasikan.</div>
                        @endif
                    </div>

                    <div class="col-lg-4">
                        <aside class="home-announcements">
                            <div class="home-announcements__header">
                                <span class="home-icon home-icon--gold"><i class="fa-solid fa-bullhorn"></i></span>
                                <div><small>Informasi penting</small><h2>Pengumuman</h2></div>
                            </div>
                            <div class="home-announcements__list">
                                @forelse ($notify->take(5) as $item)
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#announcement{{ $item->code }}">
                                        <time>{{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') }}</time>
                                        <strong>{{ $item->name }}</strong>
                                        <span>Lihat detail <i class="fa-solid fa-arrow-right"></i></span>
                                    </button>
                                @empty
                                    <div class="home-announcements__empty">
                                        <i class="fa-regular fa-circle-check"></i>
                                        <p>Belum ada pengumuman baru hari ini.</p>
                                    </div>
                                @endforelse
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-section home-leader">
            <div class="container">
                <div class="home-leader__card">
                    <div class="home-leader__portrait">
                        <img src="{{ asset('storage/images/default/default-profile.jpg') }}" alt="{{ $web->school_head }}">
                        <div class="home-leader__portrait-mark"><i class="fa-solid fa-quote-left"></i></div>
                    </div>
                    <div class="home-leader__content">
                        <span class="home-kicker">Sambutan pimpinan</span>
                        <h2>Menumbuhkan ilmu, karakter, dan kontribusi nyata.</h2>
                        <div class="home-leader__quote">{!! Str::limit(strip_tags($web->school_desc), 420) !!}</div>
                        <div class="home-leader__identity">
                            <strong>{{ $web->school_head }}</strong>
                            <span>Rektor · {!! $web->school_name !!}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($album->count())
            <section class="home-section home-gallery">
                <div class="container">
                    <div class="home-section__heading home-section__heading--row">
                        <div>
                            <span class="home-kicker">Momen kampus</span>
                            <h2>Kehidupan di kampus</h2>
                        </div>
                        <a href="{{ route('root.gallery-index') }}" class="home-text-link">Lihat semua galeri <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div class="home-gallery__grid">
                        @foreach ($album as $index => $item)
                            <a href="{{ route('root.gallery-show', $item->slug) }}" class="home-gallery__item home-gallery__item--{{ $index + 1 }}">
                                <img src="{{ asset('storage/' . $item->cover) }}" alt="{{ $item->name }}">
                                <span><small>Galeri</small><strong>{{ $item->name }}</strong></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="home-cta">
            <div class="container">
                <div class="home-cta__inner">
                    <div>
                        <span class="home-kicker">Mulai perjalanan Anda</span>
                        <h2>Siap menjadi bagian dari {{ strip_tags($web->school_name) }}?</h2>
                        <p>Akses layanan akademik dan temukan informasi yang Anda butuhkan dalam satu tempat.</p>
                    </div>
                    <div class="home-cta__actions">
                        <a href="{{ route('mahasiswa.auth-signin-page') }}" class="home-btn home-btn--light">Masuk ke SIAKAD</a>
                        <a href="{{ route('root.home-advice') }}" class="home-btn home-btn--outline-light">Hubungi Kami</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @foreach ($notify as $item)
        <div class="modal fade home-modal" id="announcement{{ $item->code }}" tabindex="-1" aria-labelledby="announcementTitle{{ $item->code }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <small>Pengumuman kampus</small>
                            <h4 class="modal-title" id="announcementTitle{{ $item->code }}">{{ $item->name }}</h4>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">{!! $item->desc !!}</div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
