<section class="auth-hero" aria-labelledby="auth-title">
    <div class="auth-hero__glow auth-hero__glow--one"></div>
    <div class="auth-hero__glow auth-hero__glow--two"></div>

    <div class="container auth-hero__container">
        <div class="auth-layout">
            <div class="auth-intro">
                <div class="auth-eyebrow">
                    <span class="auth-eyebrow__dot"></span>
                    Sistem Informasi Akademik Terpadu
                </div>
                <h1>Akses akademik dalam satu portal yang <span>aman dan terhubung.</span></h1>
                <p>
                    Kelola aktivitas akademik Anda dengan mudah melalui portal resmi
                    {{ strip_tags($web->school_name) }}.
                </p>

                <div class="auth-period" aria-label="Periode akademik portal">
                    <span class="auth-period__icon" aria-hidden="true">
                        <i class="far fa-calendar-alt"></i>
                    </span>
                    <div class="auth-period__content">
                        <div class="auth-period__eyebrow">
                            <span>Periode Akademik</span>
                            @if ($academicPeriod)
                                <span class="auth-period__status">{{ $periodStatusLabel }}</span>
                            @endif
                        </div>
                        @if ($academicPeriod)
                            <strong>{{ $academicPeriod->name }}</strong>
                            <small>
                                Tahun akademik {{ $academicPeriod->year_start }}/{{ $academicPeriod->year_end }}
                                · {{ $academicPeriod->term_label }}
                            </small>
                        @else
                            <strong>Belum tersedia</strong>
                            <small>{{ $periodEmptyMessage }}</small>
                        @endif
                    </div>
                </div>

                <div class="auth-benefits" aria-label="Keunggulan portal akademik">
                    <div class="auth-benefit">
                        <span><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
                        <div>
                            <strong>Akses terlindungi</strong>
                            <small>Data dan akun akademik tetap aman.</small>
                        </div>
                    </div>
                    <div class="auth-benefit">
                        <span><i class="fas fa-layer-group" aria-hidden="true"></i></span>
                        <div>
                            <strong>Layanan terintegrasi</strong>
                            <small>Informasi akademik tersedia dalam satu tempat.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-card-wrap">
                <div class="auth-card">
                    <div class="auth-card__icon" aria-hidden="true">
                        <i class="{{ $icon }}"></i>
                    </div>
                    <div class="auth-card__heading">
                        <span>{{ $portalLabel }}</span>
                        <h2 id="auth-title">Selamat datang kembali</h2>
                        <p>Masukkan identitas akun dan kata sandi untuk melanjutkan.</p>
                    </div>

                    @include('sweetalert::alert')

                    <form action="{{ $formAction }}" method="POST" class="auth-form">
                        @csrf

                        <div class="auth-field">
                            <label for="login">{{ $loginLabel }}</label>
                            <div class="auth-input-wrap">
                                <i class="far fa-user" aria-hidden="true"></i>
                                <input
                                    id="login"
                                    type="text"
                                    name="login"
                                    value="{{ old('login') }}"
                                    placeholder="{{ $loginPlaceholder }}"
                                    autocomplete="username"
                                    autofocus
                                    class="@error('login') is-invalid @enderror"
                                >
                            </div>
                            @error('login')
                                <small class="auth-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <div class="auth-label-row">
                                <label for="password">Kata sandi</label>
                                <a href="{{ $forgotRoute }}">Lupa kata sandi?</a>
                            </div>
                            <div class="auth-input-wrap">
                                <i class="fas fa-lock" aria-hidden="true"></i>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Masukkan kata sandi"
                                    autocomplete="current-password"
                                    class="@error('password') is-invalid @enderror"
                                >
                            </div>
                            @error('password')
                                <small class="auth-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <label class="auth-remember" for="remember_me">
                            <input type="checkbox" id="remember_me" name="remember_me" value="1">
                            <span>Ingat saya di perangkat ini</span>
                        </label>

                        <div class="auth-turnstile">
                            <x-turnstile-widget theme="auto" language="id"/>
                            @error('cf-turnstile-response')
                                <small class="auth-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <button type="submit" class="auth-submit">
                            Masuk ke portal
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="auth-card__footer">
                        Kembali ke situs utama?
                        <a href="{{ route('root.home-index') }}">Kunjungi beranda</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
