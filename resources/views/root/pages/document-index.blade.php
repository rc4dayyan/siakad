@extends('base.base-root-index')

@section('body-class', 'public-inner-page')

@section('content')
    <main class="public-page">
        <section class="public-hero public-hero--compact">
            <div class="container">
                <nav class="public-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('root.home-index') }}">Beranda</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Dokumen</span>
                </nav>
                <div class="public-hero__content">
                    <span class="home-kicker">Pusat informasi</span>
                    <h1>Dokumen publik kampus</h1>
                    <p>Temukan dan baca dokumen resmi, panduan, serta informasi akademik dalam satu tempat.</p>
                </div>
            </div>
        </section>

        <section class="public-section">
            <div class="container">
                @if ($docs->count())
                    <div class="public-document-layout">
                        <aside class="public-document-list" aria-label="Daftar dokumen">
                            <div class="public-document-list__header">
                                <span>{{ $docs->count() }} dokumen tersedia</span>
                                <h2>Pilih dokumen</h2>
                            </div>
                            <div class="public-document-list__items">
                                @foreach ($docs as $item)
                                    <button type="button" class="document-trigger" data-document-url="{{ asset('storage/' . $item->path) }}" data-document-name="{{ $item->name }}">
                                        <span class="document-trigger__cover">
                                            <img src="{{ asset('storage/' . $item->cover) }}" alt="">
                                        </span>
                                        <span class="document-trigger__text">
                                            <small>Dokumen publik</small>
                                            <strong>{{ $item->name }}</strong>
                                        </span>
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                @endforeach
                            </div>
                        </aside>

                        <div class="public-document-preview">
                            <div class="public-document-preview__header">
                                <div>
                                    <span>Pratinjau dokumen</span>
                                    <h2 id="document-title">Pilih dokumen untuk dibaca</h2>
                                </div>
                                <a id="document-open" href="#" target="_blank" rel="noopener" class="home-btn home-btn--ghost" hidden>
                                    Buka penuh <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                            <iframe id="pdf-iframe" title="Pratinjau dokumen" src="about:blank"></iframe>
                        </div>
                    </div>
                @else
                    <div class="public-empty-state">
                        <span class="home-icon home-icon--green"><i class="fa-regular fa-folder-open"></i></span>
                        <h2>Belum ada dokumen</h2>
                        <p>Dokumen publik akan ditampilkan di halaman ini setelah tersedia.</p>
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection

@section('custom-js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const iframe = document.getElementById('pdf-iframe');
            const title = document.getElementById('document-title');
            const openLink = document.getElementById('document-open');
            const triggers = document.querySelectorAll('.document-trigger');

            if (!iframe || !triggers.length) return;

            const selectDocument = (trigger) => {
                triggers.forEach((item) => item.classList.remove('is-active'));
                trigger.classList.add('is-active');
                iframe.src = trigger.dataset.documentUrl;
                title.textContent = trigger.dataset.documentName;
                openLink.href = trigger.dataset.documentUrl;
                openLink.hidden = false;
            };

            triggers.forEach((trigger) => trigger.addEventListener('click', () => selectDocument(trigger)));
            selectDocument(triggers[0]);
        });
    </script>
@endsection
