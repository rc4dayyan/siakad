<!--
=========================================================
* Argon Dashboard 2 - v2.0.4
=========================================================

* Product Page: https://www.creative-tim.com/product/argon-dashboard
* Copyright 2022 Creative Tim (https://www.creative-tim.com)
* Licensed under MIT (https://www.creative-tim.com/license)
* Coded by Creative Tim

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
-->
<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('auth') }}/assets/img/apple-icon.png">
        <link rel="icon" type="image/png" href="{{ asset('auth') }}/assets/img/favicon.png">
        <title>
           {{ $title }}
        </title>
        <!--     Fonts and icons     -->
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet" />
        <!-- Nucleo Icons -->
        <link href="{{ asset('auth') }}/assets/css/nucleo-icons.css" rel="stylesheet" />
        <link href="{{ asset('auth') }}/assets/css/nucleo-svg.css" rel="stylesheet" />
        <!-- Font Awesome Icons -->
        <link href="{{ asset('vendor') }}/fontawesome/css/all.min.css" rel="stylesheet" />
        <!-- CSS Files -->
        <link id="pagestyle" href="{{ asset('auth') }}/assets/css/argon-dashboard.css?v=2.0.4" rel="stylesheet" />
        <link href="{{ asset('dist') }}/custom/auth.css" rel="stylesheet" />
    </head>

    <body class="auth-site">
        <header class="auth-header">
            <nav class="navbar navbar-expand-lg">
                <div class="container">
                            <a class="auth-brand" href="{{ route('root.home-index') }}">
                                <img src="{{ asset('storage/images/' . $web->school_logo) }}" alt="Logo {{ strip_tags($web->school_name) }}">
                                <span>{{ $web->school_name }}</span>
                            </a>
                            <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false" aria-label="Buka navigasi">
                                <span class="navbar-toggler-icon">
                                    <span class="navbar-toggler-bar bar1"></span>
                                    <span class="navbar-toggler-bar bar2"></span>
                                    <span class="navbar-toggler-bar bar3"></span>
                                </span>
                            </button>
                            <div class="collapse navbar-collapse" id="navigation">
                                <ul class="navbar-nav ms-auto align-items-lg-center auth-nav">
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('mahasiswa.auth-signin-page') ? 'active' : '' }}" href="{{ route('mahasiswa.auth-signin-page') }}">
                                            Mahasiswa
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('dosen.auth-signin-page') ? 'active' : '' }}" href="{{ route('dosen.auth-signin-page') }}">
                                            Dosen
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('admin.auth-signin-page') ? 'active' : '' }}" href="{{ route('admin.auth-signin-page') }}">
                                            Admin / Pegawai
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('root.home-index') }}" class="auth-home-link">
                                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                            Beranda
                                        </a>
                                    </li>
                                </ul>
                            </div>
                </div>
            </nav>
        </header>
        <main class="auth-main">
            @yield('content')
        </main>
        <!--   Core JS Files   -->
        <script src="{{ asset('auth') }}/assets/js/core/popper.min.js"></script>
        <script src="{{ asset('auth') }}/assets/js/core/bootstrap.min.js"></script>
        <script src="{{ asset('auth') }}/assets/js/plugins/perfect-scrollbar.min.js"></script>
        <script src="{{ asset('auth') }}/assets/js/plugins/smooth-scrollbar.min.js"></script>
        <script>
            var win = navigator.platform.indexOf('Win') > -1;
            if (win && document.querySelector('#sidenav-scrollbar')) {
                var options = {
                    damping: '0.5'
                }
                Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
            }
        </script>
        <script src="{{ asset('auth') }}/assets/js/argon-dashboard.min.js?v=2.0.4"></script>
    </body>

</html>
