@extends('base.base-root-index')

@section('body-class', 'public-inner-page')

@section('content')
    <main class="public-page">
        <section class="public-hero public-hero--compact">
            <div class="container">
                <nav class="public-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('root.home-index') }}">Beranda</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Kontak kami atau Saran dan Masukan</span>
                </nav>
                <div class="public-hero__content">
                    <span class="home-kicker">Kami siap mendengarkan</span>
                    <h1>Sampaikan saran dan masukan Anda</h1>
                    <p>Setiap masukan membantu kami menghadirkan layanan akademik yang semakin baik, terbuka, dan relevan.</p>
                </div>
            </div>
        </section>

        <section class="public-section">
            <div class="container">
                <div class="row g-4 g-lg-5">
                    <div class="col-lg-4">
                        <aside class="public-contact-card">
                            <span class="home-icon home-icon--green"><i class="fa-regular fa-comments"></i></span>
                            <h2>Mari bertumbuh bersama</h2>
                            <p>Kritik, ide, dan pengalaman Anda menjadi bagian penting dalam pengembangan layanan kampus.</p>
                            <div class="public-contact-list">
                                <a href="mailto:{{ $web->school_email }}">
                                    <i class="fa-regular fa-envelope"></i>
                                    <span><small>Email</small><strong>{{ $web->school_email }}</strong></span>
                                </a>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $web->school_phone) }}">
                                    <i class="fa-solid fa-phone"></i>
                                    <span><small>Telepon</small><strong>{{ $web->school_phone }}</strong></span>
                                </a>
                                @if ($web->address)
                                    <div>
                                        <i class="fa-solid fa-location-dot"></i>
                                        <span><small>Alamat</small><strong>{{ $web->address }}</strong></span>
                                    </div>
                                @endif
                            </div>
                        </aside>
                    </div>

                    <div class="col-lg-8">
                        <form class="public-form-card" action="{{ route('root.home-advice-store') }}" method="post">
                            @csrf
                            <div class="public-form-card__heading">
                                <span>Formulir masukan</span>
                                <h2>Ceritakan kepada kami</h2>
                                <p>Isi data berikut dengan lengkap. Kami akan menjaga informasi Anda dan menggunakannya untuk menindaklanjuti masukan.</p>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="public-label" for="name">Nama lengkap</label>
                                    <input type="text" name="name" id="name" class="public-input @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Masukkan nama lengkap" autocomplete="name">
                                    @error('name')<small class="public-error">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="public-label" for="email">Alamat email</label>
                                    <input type="email" name="email" id="email" class="public-input @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email">
                                    @error('email')<small class="public-error">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="public-label" for="subject">Subjek</label>
                                    <input type="text" name="subject" id="subject" class="public-input @error('subject') is-invalid @enderror" value="{{ old('subject') }}" placeholder="Topik saran atau masukan">
                                    @error('subject')<small class="public-error">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="public-label" for="desc">Pesan Anda</label>
                                    <textarea name="desc" id="desc" rows="7" class="public-input public-textarea @error('desc') is-invalid @enderror" placeholder="Jelaskan saran atau masukan Anda secara rinci">{{ old('desc') }}</textarea>
                                    @error('desc')<small class="public-error">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-12">
                                    <x-turnstile-widget theme="auto" language="id" />
                                    @error('cf-turnstile-response')
                                        <small class="public-error">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-12 public-form-card__footer">
                                    <p><i class="fa-solid fa-shield-halved"></i> Data Anda dikirim dengan aman.</p>
                                    <button type="submit" class="home-btn home-btn--primary">
                                        Kirim Masukan <i class="fa-regular fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
