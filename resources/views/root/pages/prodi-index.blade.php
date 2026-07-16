@extends('base.base-root-index')

@section('body-class', 'public-inner-page')

@section('content')
    @php
        $isStudyProgram = $pstudi instanceof \App\Models\ProgramStudi;
        $faculty = $isStudyProgram ? $pstudi->fakultas : $pstudi->pstudi?->fakultas;
        $studyProgram = $isStudyProgram ? $pstudi : $pstudi->pstudi;
    @endphp

    <main class="public-page">
        <section class="public-hero public-hero--program">
            <div class="container">
                <nav class="public-breadcrumb public-breadcrumb--light" aria-label="Breadcrumb">
                    <a href="{{ route('root.home-index') }}">Beranda</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>{{ $isStudyProgram ? 'Program Studi' : 'Program Kuliah' }}</span>
                </nav>
                <div class="row align-items-end g-5">
                    <div class="col-lg-8">
                        <div class="public-hero__content public-hero__content--light">
                            <span class="home-kicker">{{ $faculty?->name ?? 'Pilihan pendidikan' }}</span>
                            <h1>{{ $pstudi->name }}</h1>
                            <p>{{ $isStudyProgram ? 'Bangun kompetensi, karakter, dan pengalaman akademik untuk menghadapi tantangan masa depan.' : 'Skema perkuliahan fleksibel yang dirancang untuk mendukung perjalanan akademik Anda.' }}</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="public-program-code">
                            <small>Kode program</small>
                            <strong>{{ $pstudi->code }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-section">
            <div class="container">
                <div class="row g-4 g-lg-5">
                    <div class="col-lg-8">
                        <article class="public-program-content">
                            <span class="home-kicker">Tentang program</span>
                            <h2>Pendidikan yang relevan dan berorientasi masa depan</h2>
                            <p>{{ $pstudi->desc ?? 'Program ini memberikan pengalaman belajar yang terstruktur, kolaboratif, dan dekat dengan kebutuhan masyarakat. Mahasiswa didampingi untuk mengembangkan pengetahuan, keterampilan profesional, serta karakter yang kuat.' }}</p>

                            <div class="public-program-values">
                                <div><span class="home-icon home-icon--green"><i class="fa-solid fa-book-open"></i></span><h3>Pembelajaran relevan</h3><p>Materi akademik terarah dan selaras dengan kebutuhan masa kini.</p></div>
                                <div><span class="home-icon home-icon--blue"><i class="fa-solid fa-people-group"></i></span><h3>Komunitas suportif</h3><p>Lingkungan belajar kolaboratif bersama dosen dan mahasiswa.</p></div>
                                <div><span class="home-icon home-icon--gold"><i class="fa-solid fa-seedling"></i></span><h3>Karakter berdampak</h3><p>Menumbuhkan integritas, kepedulian, dan semangat pengabdian.</p></div>
                            </div>
                        </article>
                    </div>

                    <div class="col-lg-4">
                        <aside class="public-program-facts">
                            <div class="public-program-facts__header">
                                <span class="home-icon home-icon--accent"><i class="fa-solid fa-graduation-cap"></i></span>
                                <div><small>Ringkasan</small><h2>Informasi program</h2></div>
                            </div>
                            <dl>
                                <div><dt>Nama program</dt><dd>{{ $pstudi->name }}</dd></div>
                                <div><dt>Kode</dt><dd>{{ $pstudi->code }}</dd></div>
                                @if ($isStudyProgram)
                                    <div><dt>Jenjang</dt><dd>{{ $pstudi->level ?? 'Sarjana' }}</dd></div>
                                    <div><dt>Fakultas</dt><dd>{{ $faculty?->name ?? '—' }}</dd></div>
                                    @if ($pstudi->head)<div><dt>Ketua program</dt><dd>{{ $pstudi->head->dsn_name }}</dd></div>@endif
                                @else
                                    <div><dt>Program studi</dt><dd>{{ $studyProgram?->name ?? '—' }}</dd></div>
                                    <div><dt>Gelombang</dt><dd>{{ $pstudi->wave ?? '—' }}</dd></div>
                                    <div><dt>Tahun akademik</dt><dd>{{ $pstudi->taka?->name ?? '—' }}</dd></div>
                                @endif
                            </dl>
                            <a href="{{ route('root.home-advice') }}" class="home-btn home-btn--light">Tanyakan program <i class="fa-solid fa-arrow-right"></i></a>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-program-cta">
            <div class="container">
                <div>
                    <span class="home-kicker">Butuh informasi lebih lanjut?</span>
                    <h2>Temukan program yang sesuai dengan tujuan Anda.</h2>
                    <a href="{{ route('root.home-advice') }}" class="home-btn home-btn--primary">Hubungi kami <i class="fa-regular fa-paper-plane"></i></a>
                </div>
            </div>
        </section>
    </main>
@endsection
