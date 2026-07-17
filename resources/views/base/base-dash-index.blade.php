<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $web = \App\Models\Settings\WebSettings::where('id', 1)->first();
        @endphp
        <title>@yield('menu') - {{ $web->school_name }}</title>

        @include('base.panel.base-panel-header-script')

    </head>

    <body class="authenticated-app">
        <div id="app">
            <div id="sidebar">
                <div class="sidebar-wrapper active">
                    <div class="sidebar-header position-relative">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="dashboard-brand">
                                <a href="{{ route('root.home-index') }}" aria-label="Kembali ke halaman utama">
                                    <span class="dashboard-brand__logo"><img src="{{ asset('storage/images/' . $web->school_logo) }}" alt="Logo {{ strip_tags($web->school_name) }}"></span>
                                    <span class="dashboard-brand__text">
                                        <strong>{{ $web->school_apps }}</strong>
                                        <small>{{ Str::limit(strip_tags($web->school_name), 27) }}</small>
                                    </span>
                                </a>
                            </div>
                            <div class="sidebar-toggler  x">
                                <a href="#" class="sidebar-hide d-xl-none d-block" aria-label="Tutup navigasi"><i class="bi bi-x-lg"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="sidebar-menu">
                        @include('base.panel.base-panel-sidebar')

                    </div>
                </div>
            </div>
            <div id="main" class='layout-navbar navbar-fixed'>
                <div>
                    @include('base.panel.base-panel-header')
</div>
                <div id="main-content">

                    <div class="page-heading">
                        <div class="page-title">
                            <div class="row">
                                <div class="col-12 col-md-6 order-md-1 order-last">
                                    <span class="dashboard-page-kicker">@yield('menu')</span>
                                    <h3>@yield('submenu')</h3>
                                    <p class="text-subtitle text-muted">@yield('subdesc')</p>
                                </div>
                                <div class="col-12 col-md-6 order-md-2 order-first">
                                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="@yield('urlmenu')">@yield('menu')</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">@yield('submenu')</li>
                                        </ol>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        @include('sweetalert::alert')
                        @yield('content')

                    </div>

                </div>
                <footer>
                    @include('base.panel.base-panel-footer')
                </footer>
            </div>
        </div>

        @include('base.panel.base-panel-footer-script')

    </body>

</html>
