<footer class="public-footer" aria-labelledby="public-footer-title">
    <div class="public-footer__accent" aria-hidden="true"></div>
    <div class="container">
        <div class="public-footer__main">
            <div class="public-footer__identity">
                <a class="public-footer__brand" href="{{ route('root.home-index') }}">
                    <span class="public-footer__logo">
                        <img src="{{ asset('storage/images/' . $web->school_logo) }}"
                            alt="Logo {{ strip_tags($web->school_name) }}">
                    </span>
                    <span>
                        <strong id="public-footer-title">{{ strip_tags($web->school_name) }}</strong>
                        <small>{{ $web->school_apps }}</small>
                    </span>
                </a>
                <p>Portal informasi akademik yang menghubungkan mahasiswa, dosen, dan sivitas kampus dalam satu layanan terpadu.</p>

                @if (($web->social_fb && $web->social_fb !== '#') || ($web->social_ig && $web->social_ig !== '#') || ($web->social_in && $web->social_in !== '#') || ($web->social_tw && $web->social_tw !== '#'))
                    <div class="public-footer__social" aria-label="Media sosial kampus">
                        @if ($web->social_fb && $web->social_fb !== '#')
                            <a href="{{ $web->social_fb }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook kampus">
                                <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($web->social_ig && $web->social_ig !== '#')
                            <a href="{{ $web->social_ig }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram kampus">
                                <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($web->social_in && $web->social_in !== '#')
                            <a href="{{ $web->social_in }}" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn kampus">
                                <i class="fa-brands fa-linkedin-in" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($web->social_tw && $web->social_tw !== '#')
                            <a href="{{ $web->social_tw }}" target="_blank" rel="noopener noreferrer" aria-label="X atau Twitter kampus">
                                <i class="fa-brands fa-x-twitter" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="public-footer__column">
                <h2>Jelajahi</h2>
                <nav aria-label="Navigasi footer">
                    <a href="{{ route('root.home-index') }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>Beranda</a>
                    <a href="{{ route('root.home-index') }}#program-studi"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>Program Studi</a>
                    <a href="{{ route('root.gallery-index') }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>Galeri Kampus</a>
                    <a href="{{ route('root.home-download') }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>Dokumen</a>
                    <a href="{{ route('root.home-advice') }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>Kontak</a>
                </nav>
            </div>

            <div class="public-footer__column public-footer__column--portal">
                <h2>Portal Akademik</h2>
                <div class="public-footer__portals">
                    <a href="{{ route('mahasiswa.auth-signin-page') }}">
                        <span><i class="fa-solid fa-user-graduate" aria-hidden="true"></i></span>
                        <span><strong>Mahasiswa</strong><small>Akses layanan akademik</small></span>
                    </a>
                    <a href="{{ route('dosen.auth-signin-page') }}">
                        <span><i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i></span>
                        <span><strong>Dosen</strong><small>Kelola aktivitas perkuliahan</small></span>
                    </a>
                </div>
            </div>

            <div class="public-footer__column public-footer__column--contact">
                <h2>Hubungi Kami</h2>
                <address>
                    @if ($web->address)
                        <div>
                            <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
                            <p>{{ $web->address }}</p>
                        </div>
                    @endif
                    @if ($web->school_email)
                        <div>
                            <span><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
                            <p><a href="mailto:{{ $web->school_email }}">{{ $web->school_email }}</a></p>
                        </div>
                    @endif
                    @if ($web->school_phone)
                        <div>
                            <span><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
                            <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $web->school_phone) }}">{{ $web->school_phone }}</a></p>
                        </div>
                    @endif
                </address>
            </div>
        </div>

        <div class="public-footer__bottom">
            <p>&copy; {{ now()->year }} <strong>{{ strip_tags($web->school_name) }}</strong>. Seluruh hak cipta dilindungi.</p>
            <p><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Sistem Informasi Akademik Terpadu</p>
            <a class="public-footer__back-top" href="#app" aria-label="Kembali ke bagian atas halaman">
                <span>Kembali ke atas</span>
                <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</footer>
