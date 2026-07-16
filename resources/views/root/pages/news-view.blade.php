@extends('base.base-root-index')

@section('body-class', 'public-inner-page')

@section('content')
    <main class="public-page">
        <section class="public-hero public-hero--article">
            <div class="container">
                <nav class="public-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('root.home-index') }}">Beranda</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Berita</span>
                </nav>
                <div class="public-article-heading">
                    <span class="public-category">{{ $post->category?->name ?? 'Berita Kampus' }}</span>
                    <h1>{{ $post->name }}</h1>
                    <div class="public-article-meta">
                        <span><i class="fa-regular fa-calendar"></i> {{ $post->created_at->translatedFormat('d F Y · H.i') }} WIB</span>
                        <span><i class="fa-regular fa-user"></i> {{ $post->author?->name ?? 'Administrator' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-section public-section--article">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-8">
                        <article class="public-article">
                            <figure class="public-article__cover">
                                <img src="{{ asset('storage/images/' . $post->image) }}" alt="{{ $post->name }}">
                            </figure>
                            <div class="public-article__content">{!! $post->content !!}</div>
                            <footer class="public-article__footer">
                                <div>
                                    <small>Dipublikasikan oleh</small>
                                    <strong>{{ $post->author?->name ?? 'Administrator' }}</strong>
                                </div>
                                <a href="{{ route('root.home-index') }}#berita" class="home-text-link"><i class="fa-solid fa-arrow-left"></i> Berita lainnya</a>
                            </footer>
                        </article>
                    </div>

                    <div class="col-lg-4">
                        <aside class="public-article-sidebar">
                            <section class="public-sidebar-card public-sidebar-card--leader">
                                <div class="public-sidebar-card__title">
                                    <span class="home-kicker">Pimpinan kampus</span>
                                    <h2>Sambutan Rektor</h2>
                                </div>
                                <img src="{{ asset('storage/images/default/default-profile.jpg') }}" alt="{{ $web->school_head }}">
                                <h3>{{ $web->school_head }}</h3>
                                <span>Rektor · {!! $web->school_name !!}</span>
                                <p>{{ Str::limit(strip_tags($web->school_desc), 190) }}</p>
                            </section>

                            <section class="public-sidebar-card">
                                <div class="public-sidebar-card__title">
                                    <span class="home-kicker">Informasi</span>
                                    <h2>Pengumuman terbaru</h2>
                                </div>
                                <div class="public-sidebar-announcements">
                                    @forelse ($notify->take(4) as $item)
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#news-announcement{{ $item->code }}">
                                            <time>{{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') }}</time>
                                            <strong>{{ $item->name }}</strong>
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                    @empty
                                        <p>Belum ada pengumuman terbaru.</p>
                                    @endforelse
                                </div>
                            </section>
                        </aside>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @foreach ($notify as $item)
        <div class="modal fade home-modal" id="news-announcement{{ $item->code }}" tabindex="-1" aria-labelledby="newsAnnouncementTitle{{ $item->code }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div><small>Pengumuman kampus</small><h4 class="modal-title" id="newsAnnouncementTitle{{ $item->code }}">{{ $item->name }}</h4></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">{!! $item->desc !!}</div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
