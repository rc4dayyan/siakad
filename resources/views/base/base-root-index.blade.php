<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $menu . $title }}</title>



    {{--
    <link rel="shortcut icon" href="{{ asset('storage/images/'. $web->school_logo) }}" type="image/x-icon"> --}}
    <link rel="shortcut icon" href="{{ asset('dist') }}/assets/compiled/svg/favicon.svg" type="image/x-icon">
    <link rel="shortcut icon"
        href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACEAAAAiCAYAAADRcLDBAAAEs2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgZXhpZjpQaXhlbFhEaW1lbnNpb249IjMzIgogICBleGlmOlBpeGVsWURpbWVuc2lvbj0iMzQiCiAgIGV4aWY6Q29sb3JTcGFjZT0iMSIKICAgdGlmZjpJbWFnZVdpZHRoPSIzMyIKICAgdGlmZjpJbWFnZUxlbmd0aD0iMzQiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249Ijk2LjAiCiAgIHRpZmY6WVJlc29sdXRpb249Ijk2LjAiCiAgIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiCiAgIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgeG1wOk1vZGlmeURhdGU9IjIwMjItMDMtMzFUMTA6NTA6MjMrMDI6MDAiCiAgIHhtcDpNZXRhZGF0YURhdGU9IjIwMjItMDMtMzFUMTA6NTA6MjMrMDI6MDAiPgogICA8eG1wTU06SGlzdG9yeT4KICAgIDxyZGY6U2VxPgogICAgIDxyZGY6bGkKICAgICAgc3RFdnQ6YWN0aW9uPSJwcm9kdWNlZCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWZmaW5pdHkgRGVzaWduZXIgMS4xMC4xIgogICAgICBzdEV2dDp3aGVuPSIyMDIyLTAzLTMxVDEwOjUwOjIzKzAyOjAwIi8+CiAgICA8L3JkZjpTZXE+CiAgIDwveG1wTU06SGlzdG9yeT4KICA8L3JkZjpEZXNjcmlwdGlvbj4KIDwvcmRmOlJERj4KPC94OnhtcG1ldGE+Cjw/eHBhY2tldCBlbmQ9InIiPz5V57uAAAABgmlDQ1BzUkdCIElFQzYxOTY2LTIuMQAAKJF1kc8rRFEUxz9maORHo1hYKC9hISNGTWwsRn4VFmOUX5uZZ36oeTOv954kW2WrKLHxa8FfwFZZK0WkZClrYoOe87ypmWTO7dzzud97z+nec8ETzaiaWd4NWtYyIiNhZWZ2TvE946WZSjqoj6mmPjE1HKWkfdxR5sSbgFOr9Ll/rXoxYapQVik8oOqGJTwqPL5i6Q5vCzeo6dii8KlwpyEXFL519LjLLw6nXP5y2IhGBsFTJ6ykijhexGra0ITl5bRqmWU1fx/nJTWJ7PSUxBbxJkwijBBGYYwhBgnRQ7/MIQIE6ZIVJfK7f/MnyUmuKrPOKgZLpEhj0SnqslRPSEyKnpCRYdXp/9++msneoFu9JgwVT7b91ga+LfjetO3PQ9v+PgLvI1xkC/m5A+h7F32zoLXug38dzi4LWnwHzjeg8UGPGbFfySvuSSbh9QRqZ6H+Gqrm3Z7l9zm+h+iafNUV7O5Bu5z3L/wAdthn7QIme0YAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAJTSURBVFiF7Zi9axRBGIefEw2IdxFBRQsLWUTBaywSK4ubdSGVIY1Y6HZql8ZKCGIqwX/AYLmCgVQKfiDn7jZeEQMWfsSAHAiKqPiB5mIgELWYOW5vzc3O7niHhT/YZvY37/swM/vOzJbIqVq9uQ04CYwCI8AhYAlYAB4Dc7HnrOSJWcoJcBS4ARzQ2F4BZ2LPmTeNuykHwEWgkQGAet9QfiMZjUSt3hwD7psGTWgs9pwH1hC1enMYeA7sKwDxBqjGnvNdZzKZjqmCAKh+U1kmEwi3IEBbIsugnY5avTkEtIAtFhBrQCX2nLVehqyRqFoCAAwBh3WGLAhbgCRIYYinwLolwLqKUwwi9pxV4KUlxKKKUwxC6ZElRCPLYAJxGfhSEOCz6m8HEXvOB2CyIMSk6m8HoXQTmMkJcA2YNTHm3congOvATo3tE3A29pxbpnFzQSiQPcB55IFmFNgFfEQeahaAGZMpsIJIAZWAHcDX2HN+2cT6r39GxmvC9aPNwH5gO1BOPFuBVWAZue0vA9+A12EgjPadnhCuH1WAE8ivYAQ4ohKaagV4gvxi5oG7YSA2vApsCOH60WngKrA3R9IsvQUuhIGY00K4flQG7gHH/mLytB4C42EgfrQb0mV7us8AAMeBS8mGNMR4nwHamtBB7B4QRNdaS0M8GxDEog7iyoAguvJ0QYSBuAOcAt71Kfl7wA8DcTvZ2KtOlJEr+ByyQtqqhTyHTIeB+ONeqi3brh+VgIN0fohUgWGggizZFTplu12yW8iy/YLOGWMpDMTPXnl+Az9vj2HERYqPAAAAAElFTkSuQmCC"
        type="image/png">



    <link rel="stylesheet" href="{{ asset('dist') }}/assets/compiled/css/app.css">
    <link rel="stylesheet" href="{{ asset('dist') }}/assets/compiled/css/app-dark.css">
    <link rel="stylesheet" href="{{ asset('dist') }}/assets/compiled/css/iconly.css">
    <link rel="stylesheet" href="{{ asset('dist') }}/assets/custom/css/news-section.css">
    <link rel="stylesheet" href="{{ asset('dist') }}/custom/banner.css">
    <link rel="stylesheet" href="{{ asset('dist/custom/home.css') }}?v={{ filemtime(public_path('dist/custom/home.css')) }}">
    {{-- PLUGIN FONT AWESOME --}}
    <link rel="stylesheet" href="{{ asset('vendor') }}/fontawesome/css/all.min.css" rel="stylesheet">
    @yield('custom-css')

</head>

<body class="public-site @yield('body-class')">
    <script src="{{ asset('dist') }}/assets/static/js/initTheme.js"></script>
    <div id="app">
        <div id="main" class="layout-horizontal">
            <header class="public-header">
                <div class="public-header__utility">
                    <div class="container">
                        <p>
                            <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                            <span>Sistem Informasi Akademik Terpadu</span>
                        </p>
                        <div class="public-header__utility-links" aria-label="Tautan cepat">
                            @if ($web->school_email)
                                <a href="mailto:{{ $web->school_email }}">
                                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                                    {{ $web->school_email }}
                                </a>
                            @endif
                            <a href="{{ route('root.gallery-index') }}">Galeri</a>
                            <a href="{{ route('root.home-download') }}">Dokumen</a>
                            <a href="{{ route('root.home-advice') }}">Kontak</a>
                        </div>
                    </div>
                </div>
                <div class="header-top">
                    <div class="container">
                        <div class="logo">
                            <a href="{{ route('root.home-index') }}" aria-label="Beranda {{ strip_tags($web->school_name) }}">
                                <span class="public-brand__mark">
                                    <img src="{{ asset('storage/images/' . $web->school_logo) }}"
                                        alt="Logo {{ strip_tags($web->school_name) }}">
                                </span>
                                <span class="public-brand__copy">
                                    <strong>{{ strip_tags($web->school_name) }}</strong>
                                    <small>Portal Informasi Akademik</small>
                                </span>
                            </a>
                        </div>
                        <div class="header-top-right">
                            <div class="dropdown">
                                <a href="#" id="topbarUserDropdown"
                                    class="user-dropdown d-flex align-items-center dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    @if (Auth::guard('dosen')->check())
                                    <div class="avatar avatar-md2">
                                        <img src="{{ asset('storage/images/' . Auth::guard('dosen')->user()->dsn_image) }}"
                                            alt="Avatar">
                                    </div>
                                    <div class="text">

                                        <h6 class="user-dropdown-name">{{ Auth::guard('dosen')->user()->dsn_name }}</h6>
                                        <p class="user-dropdown-status text-sm text-muted">{{
                                            Auth::guard('dosen')->user()->dsn_stat }}</p>
                                    </div>
                                    @elseif(Auth::guard('mahasiswa')->check())
                                    <div class="avatar avatar-md2">
                                        <img src="{{ asset('storage/images/' . Auth::guard('mahasiswa')->user()->mhs_image) }}"
                                            alt="Avatar">
                                    </div>
                                    <div class="text">
                                        <h6 class="user-dropdown-name">{{ Auth::guard('mahasiswa')->user()->mhs_name }}
                                        </h6>
                                        <p class="user-dropdown-status text-sm text-muted">{{
                                            Auth::guard('mahasiswa')->user()->mhs_stat }}</p>
                                    </div>
                                    @elseif(Auth::check())
                                    <div class="avatar avatar-md2">
                                        <img src="{{ asset('storage/images/' . Auth::user()->image) }}" alt="Avatar">
                                    </div>
                                    <div class="text">

                                        <h6 class="user-dropdown-name">{{ Auth::user()->name }}</h6>
                                        <p class="user-dropdown-status text-sm text-muted">{{ Auth::user()->type }}</p>
                                    </div>
                                    @else
                                    <span class="public-login__icon"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                                    <span class="public-login__copy">
                                        <small>Akses akun</small>
                                        <strong>Masuk Portal</strong>
                                    </span>
                                    @endif
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg"
                                    aria-labelledby="topbarUserDropdown">
                                    @if (Auth::guard('dosen')->check())
                                    <li><a class="dropdown-item" href="{{ route('dosen.home-profile') }}">My Account</a>
                                    </li>
                                    <li><a class="dropdown-item"
                                            href="{{ route('dosen.auth-signout-post') }}">Logout</a></li>
                                    @elseif(Auth::guard('mahasiswa')->check())
                                    <li><a class="dropdown-item" href="{{ route('mahasiswa.home-profile') }}">My
                                            Account</a></li>
                                    <li><a class="dropdown-item"
                                            href="{{ route('mahasiswa.auth-signout-post') }}">Logout</a></li>
                                    @elseif(Auth::check())
                                    <li><a class="dropdown-item" href="{{ route($prefix . 'home-profile') }}">My
                                            Account</a></li>
                                    <li><a class="dropdown-item"
                                            href="{{ route($prefix . 'auth-signout-post') }}">Logout</a></li>
                                    @else
                                    <li><a class="dropdown-item" href="{{ route('mahasiswa.auth-signin-page') }}">Login
                                            Mahasiswa</a></li>
                                    <li><a class="dropdown-item" href="{{ route('dosen.auth-signin-page') }}">Login
                                            Dosen</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.auth-signin-page') }}">Login
                                            Admin / Pegawai</a></li>
                                    @endif

                                </ul>
                            </div>

                            <button type="button" class="burger-btn d-flex d-xl-none" aria-controls="publicNavigation"
                                aria-expanded="false" aria-label="Buka menu navigasi">
                                <span></span><span></span><span></span>
                            </button>
                        </div>
                    </div>
                </div>
                <nav class="main-navbar" id="publicNavigation" aria-label="Navigasi utama">
                    <div class="container">
                        <ul>
                            <li class="menu-item {{ request()->routeIs('root.home-index') ? 'active' : '' }}">
                                <a href="{{ route('root.home-index') }}" class='menu-link'>
                                    <span><i class="fa-solid fa-house" aria-hidden="true"></i> Beranda</span>
                                </a>
                            </li>

                            <li class="menu-item has-sub {{ request()->routeIs('root.gallery-*') ? 'active' : '' }}">
                                <a href="#" class='menu-link' aria-haspopup="true" aria-expanded="false">
                                    <span><i class="fa-regular fa-compass" aria-hidden="true"></i> Tentang Kampus</span>
                                </a>
                                <div class="submenu">
                                    <div class="submenu-group-wrapper">
                                        <ul class="submenu-group">
                                            <li class="submenu-item {{ request()->routeIs('root.gallery-*') ? 'active' : '' }}">
                                                <a href="{{ route('root.gallery-index') }}" class='submenu-link'>
                                                    <span class="submenu-link__icon"><i class="fa-regular fa-images" aria-hidden="true"></i></span>
                                                    <span><strong>Galeri Kampus</strong><small>Dokumentasi kegiatan dan suasana kampus</small></span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                            <li class="menu-item has-sub {{ request()->routeIs('root.home-prodi', 'root.home-proku') ? 'active' : '' }}">
                                <a href="#" class='menu-link' aria-haspopup="true" aria-expanded="false">
                                    <span><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Akademik</span>
                                </a>
                                <div class="submenu">
                                    <div class="submenu-group-wrapper">
                                        <ul class="submenu-group">
                                            @foreach ($fakultas as $faku)
                                            <li class="submenu-item has-sub">
                                                <a href="#" class='submenu-link'>
                                                    <span class="submenu-link__icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                                                    <span><strong>{{ $faku->name }}</strong><small>Lihat program studi</small></span>
                                                </a>
                                                <ul class="subsubmenu">
                                                    @php
                                                    $pstudi = \App\Models\ProgramStudi::where('faku_id',
                                                    $faku->id)->get();
                                                    @endphp
                                                    @foreach ($pstudi as $item)
                                                    <li class="subsubmenu-item">
                                                        <a href="{{ route('root.home-prodi', $item->slug) }}" class="subsubmenu-link">{{ $item->level . ' - ' . $item->name }}</a>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </li>

                            <li class="menu-item {{ request()->routeIs('root.home-download') ? 'active' : '' }}">
                                <a href="{{ route('root.home-download') }}" class='menu-link'>
                                    <span><i class="fa-regular fa-file-lines" aria-hidden="true"></i> Dokumen</span>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->routeIs('root.home-advice') ? 'active' : '' }}">
                                <a href="{{ route('root.home-advice') }}" class='menu-link'>
                                    <span><i class="fa-regular fa-message" aria-hidden="true"></i> Kontak</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <div class="content-wrapper container">
                @include('sweetalert::alert')

                @yield('content')

            </div>


            @include('base.partials.public-footer')
        </div>
    </div>
    <script src="{{ asset('dist') }}/assets/static/js/components/dark.js"></script>
    <script src="{{ asset('dist/custom/public-header.js') }}?v={{ filemtime(public_path('dist/custom/public-header.js')) }}"></script>
    <script src="{{ asset('dist') }}/assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>

    <script src="{{ asset('dist') }}/assets/compiled/js/app.js"></script>


    @yield('custom-js')
</body>

</html>
