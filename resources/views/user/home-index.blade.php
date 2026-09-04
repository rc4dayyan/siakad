@extends('base.base-dash-index')
@section('title')
    {{ (int) Auth::user()->raw_type === 3 ? 'Dashboard Akademik' : ((int) Auth::user()->raw_type === 1 ? 'Dashboard Finance' : 'Dashboard Admin') }} - Internal Developer
@endsection
@section('menu')
    Dashboard
@endsection
@section('submenu')
    {{ (int) Auth::user()->raw_type === 3 ? 'Dashboard Akademik' : ((int) Auth::user()->raw_type === 1 ? 'Dashboard Finance' : 'Dashboard Admin') }}
@endsection
@section('urlmenu')
    #
@endsection
@section('subdesc')
    {{ (int) Auth::user()->raw_type === 3 ? 'Ringkasan operasional dan layanan akademik' : ((int) Auth::user()->raw_type === 1 ? 'Ringkasan tagihan, pembayaran, dan posisi keuangan' : 'Halaman dashboard admin') }}
@endsection
@section('custom-css')
    @include('base.components.professional-dashboard-styles')
    <style>
        @media (max-width: 768px) {
            .card-body {
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }

            .icon {
                margin: 10px 0;
            }

            .text-putih {
                margin-left: 0px !important;
                /* Mengatur margin-left menjadi 0 */
                margin-top: 10px;
                margin-bottom: 10px;
            }
        }

        .academic-dashboard .academic-hero {
            position: relative;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 32px;
            overflow: hidden;
            padding: 32px;
            border-radius: 18px;
            background:
                radial-gradient(circle at 85% 15%, rgba(17, 122, 101, .35), transparent 30%),
                linear-gradient(125deg, var(--dash-navy) 0%, var(--dash-navy-soft) 100%);
            box-shadow: 0 18px 40px rgba(11, 33, 53, .18);
            color: #fff;
        }

        .academic-dashboard .academic-hero::after {
            position: absolute;
            top: -80px;
            right: -45px;
            width: 260px;
            height: 260px;
            border: 45px solid rgba(255, 255, 255, .07);
            border-radius: 50%;
            content: '';
        }

        .academic-hero__content { position: relative; z-index: 1; max-width: 650px; }
        .academic-hero__eyebrow { display: block; margin-bottom: 8px; color: #dce5ff; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .academic-hero h2 { margin-bottom: 8px; color: #fff; font-size: clamp(24px, 3vw, 34px); }
        .academic-hero p { max-width: 590px; margin: 0; color: rgba(255, 255, 255, .82); }
        .academic-hero .btn { border-radius: 9px; font-weight: 600; }
        .academic-hero__period { position: relative; z-index: 1; display: flex; min-width: 240px; flex-direction: column; align-self: center; padding: 20px; border: 1px solid rgba(255, 255, 255, .18); border-radius: 14px; background: rgba(255, 255, 255, .1); backdrop-filter: blur(8px); }
        .academic-hero__period-label { margin-bottom: 7px; color: #dce5ff; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .academic-hero__period strong { color: #fff; font-size: 17px; }
        .academic-hero__period span, .academic-hero__period small { margin-top: 3px; color: rgba(255, 255, 255, .78); }

        .academic-stat-card { display: flex; min-height: 116px; height: 100%; align-items: center; gap: 14px; padding: 20px; border: 1px solid var(--dash-line); border-radius: 14px; background: var(--dash-surface); box-shadow: 0 7px 20px rgba(12, 44, 55, .05); transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .academic-stat-card:hover { border-color: rgba(17, 122, 101, .28); transform: translateY(-2px); box-shadow: 0 12px 28px rgba(11, 66, 68, .1); }
        .academic-stat-card__icon { display: flex; width: 48px; height: 48px; flex: 0 0 48px; align-items: center; justify-content: center; border-radius: 12px; font-size: 20px; }
        .academic-stat-card__icon.is-blue,
        .academic-stat-card__icon.is-purple,
        .academic-stat-card__icon.is-green { background: var(--dash-green-soft); color: var(--dash-green); }
        .academic-stat-card__icon.is-orange { background: #fff3d7; color: #9a6b12; }
        .academic-stat-card span:last-child { min-width: 0; }
        .academic-stat-card small, .academic-stat-card em { display: block; overflow: hidden; color: #7c8a96; font-size: 11px; font-style: normal; text-overflow: ellipsis; white-space: nowrap; }
        .academic-stat-card strong { display: block; margin: 2px 0; color: var(--dash-navy); font-size: 25px; line-height: 1.15; }

        .academic-panel { border: 1px solid var(--dash-line); border-radius: 15px; box-shadow: 0 7px 20px rgba(12, 44, 55, .05); }
        .academic-panel .card-header { padding: 21px 22px 12px; background: transparent; }
        .academic-panel .card-body { padding: 18px 22px 22px; }
        .krs-completion strong { color: var(--dash-navy); font-size: 28px; }
        .krs-completion .progress { height: 8px; border-radius: 20px; background: #e8efed; }
        .krs-completion .progress-bar { border-radius: 20px; background: var(--dash-green); }
        .krs-status-item { position: relative; min-height: 86px; padding: 15px; border: 1px solid var(--dash-line); border-radius: 11px; background: #f7faf9; }
        .krs-status-item__dot { display: block; width: 8px; height: 8px; margin-bottom: 9px; border-radius: 50%; }
        .krs-status-item small { display: block; color: #7c8a96; }
        .krs-status-item strong { display: block; margin-top: 2px; color: var(--dash-navy); font-size: 20px; }

        .attention-item { display: grid; grid-template-columns: 42px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 14px 4px; border-bottom: 1px solid var(--dash-line); }
        .attention-item:last-child { border-bottom: 0; }
        .attention-item__icon { display: flex; width: 42px; height: 42px; align-items: center; justify-content: center; border-radius: 10px; }
        .attention-item__icon.is-warning { background: #fff3d7; color: #9a6b12; }
        .attention-item__icon.is-danger { background: #faecee; color: #bd4d58; }
        .attention-item__icon.is-info { background: var(--dash-green-soft); color: var(--dash-green); }
        .attention-item strong, .attention-item small { display: block; }
        .attention-item strong { color: var(--dash-ink); font-size: 13px; }
        .attention-item small { margin-top: 2px; color: #84909c; font-size: 11px; }
        .attention-item > i { color: #a0a8b1; font-size: 11px; }

        .quick-action-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; }
        .quick-action-grid a { display: flex; min-height: 88px; flex-direction: column; align-items: center; justify-content: center; gap: 9px; padding: 14px; border: 1px solid var(--dash-line); border-radius: 12px; color: var(--dash-muted); text-align: center; transition: border-color .18s ease, background .18s ease, color .18s ease; }
        .quick-action-grid a:hover { border-color: rgba(17, 122, 101, .28); background: var(--dash-green-soft); color: var(--dash-green-dark); }
        .quick-action-grid i { color: var(--dash-green); font-size: 20px; }
        .quick-action-grid span { font-size: 12px; font-weight: 600; }

        @media (max-width: 991.98px) {
            .academic-dashboard .academic-hero { flex-direction: column; }
            .academic-hero__period { width: 100%; align-self: stretch; }
            .quick-action-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 575.98px) {
            .academic-dashboard .academic-hero { padding: 24px 20px; }
            .quick-action-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .academic-panel .card-header, .academic-panel .card-body { padding-right: 16px; padding-left: 16px; }
        }
    </style>
@endsection
@section('content')
    <section class="section">
        @if ((int) Auth::user()->raw_type === 3)
            @include('user.academic.home-dashboard')
        @elseif ((int) Auth::user()->raw_type === 1)
            @include('user.finance.home-dashboard')
        @elseif ((int) Auth::user()->raw_type === 0)
            @include('user.admin.home-dashboard')
        @else
        <div class="row">

            <div class="col-lg-9 col-12">
                @if (Auth::user()->raw_type == 0)
                    <div class="row">

                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.workers.student-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-user-graduate" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\Mahasiswa::all()->count() }} <br> Mahasiswa</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.workers.lecture-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-user-tie" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\Dosen::all()->count() }} <br> Dosen</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.workers.staff-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-user-tag" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\User::where('type', ['1', '2', '3', '4'])->count() }}<br> Karyawan</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.master.jadwal-mingguan-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-book-open-reader" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\JadwalKuliah::all()->count() }}<br>Jadwal Kuliah</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.master.fakultas-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-building-columns" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\Fakultas::all()->count() }} <br> Fakultas</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.master.pstudi-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-graduation-cap" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\ProgramStudi::all()->count() }}<br>Prodi</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.master.kelas-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-building-user" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\Kelas::all()->count() }}<br> Kelas</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.master.matkul-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-book-open" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\MataKuliah::all()->count() }} <br> Mata Kuliah</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.master.taka-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-calendar" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\Kurikulum::all()->count() }}<br>Kurikulum</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.master.kurikulum-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-book" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\Kurikulum::all()->count() }}<br>Kurikulum</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.master.proku-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-list-ol" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\ProgramKuliah::all()->count() }}<br> Program Kuliah</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route('web-admin.inventory.ruang-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-house-flag" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\Ruang::all()->count() }}<br>Ruangan</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route($prefix . 'finance.keuangan-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-wallet" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ number_format($balSekarang, 0, ',', '.') }}<br> Sisa Saldo ( IDR )</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route($prefix . 'finance.keuangan-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-file-invoice-dollar" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ number_format($balPending, 0, ',', '.') }}<br> Pending ( IDR )</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route($prefix . 'finance.keuangan-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-dollar" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ number_format($balIncome, 0, ',', '.') }}<br> Income ( IDR )</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route($prefix . 'finance.keuangan-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-dollar" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ number_format($balExpense, 0, ',', '.') }}<br> Expenses ( IDR )</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route($prefix . 'finance.tagihan-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-file-invoice" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\TagihanKuliah::all()->count() }}<br> Tagihan</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <a href="{{ route($prefix . 'finance.pembayaran-index') }}">
                                <div class="card btn btn-outline-success">
                                    <div class="card-body d-flex justify-content-around align-items-center p-1">
                                        <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-file-invoice-dollar" style="font-size: 32px"></i></span>
                                        <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\HistoryTagihan::where('stat', 1)->count() }}<br> Pembayaran</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif

            </div>
            <div class="col-lg-3 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title text-center">Presentasi Gender</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div id="genderMhsChart"></div>
                        </div>
                        <div class="text-center">
                            <small>Grafik Presentasi Gender Mahasiswa</small>
                        </div>
                        <hr>
                        <div class="form-group">
                            <div id="genderDsnChart"></div>
                        </div>
                        <div class="text-center">
                            <small>Grafik Presentasi Gender Dosen</small>
                        </div>
                        <hr>
                        <div class="form-group">
                            <div id="genderUsrChart"></div>
                        </div>
                        <div class="text-center">
                            <small>Grafik Presentasi Gender Pegawai</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </section>
@endsection
@section('custom-js')
    @if (in_array((int) Auth::user()->raw_type, [2, 4, 5], true))
    <script src="{{ asset('dist') }}/assets/extensions/apexcharts/apexcharts.min.js"></script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script> --}}
    <script>
        var ajaxRunning = false;

        $(document).ready(function() {
            // Fungsi untuk melakukan permintaan AJAX
            function fetchData() {
                // Jika sedang berjalan, hentikan fungsi
                if (ajaxRunning) {
                    return;
                }

                ajaxRunning = true;

                $.ajax({
                    url: '{{ route($prefix.'home.ajax-mhs-gender') }}',
                    method: 'GET',
                    success: function(response) {
                        var maleCount = response.male;
                        var femaleCount = response.female;
                        var dmaleCount = response.dmale;
                        var dfemaleCount = response.dfemale;
                        var umaleCount = response.umale;
                        var ufemaleCount = response.ufemale;

                        var options = {
                            chart: {
                                type: 'pie',
                            },
                            series: [maleCount, femaleCount],
                            labels: ['Laki-laki', 'Perempuan'],
                            legend: {
                                position: 'bottom'
                            }
                        };

                        var chart = new ApexCharts(document.querySelector('#genderMhsChart'), options);
                        chart.render();

                        var options = {
                            chart: {
                                type: 'pie',
                            },
                            series: [dmaleCount, dfemaleCount],
                            labels: ['Laki-laki', 'Perempuan'],
                            legend: {
                                position: 'bottom'
                            }
                        };

                        var chart = new ApexCharts(document.querySelector('#genderDsnChart'), options);
                        chart.render();
                        var options = {
                            chart: {
                                type: 'pie',
                            },
                            series: [umaleCount, ufemaleCount],
                            labels: ['Laki-laki', 'Perempuan'],
                            legend: {
                                position: 'bottom'
                            }
                        };

                        var chart = new ApexCharts(document.querySelector('#genderUsrChart'), options);
                        chart.render();
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    },
                    complete: function() {
                        ajaxRunning = false; // Setelah permintaan selesai, set status menjadi false
                    }
                });
            }

            // Panggil fungsi untuk pertama kalinya
            fetchData();
        });
    </script>
    @endif
@endsection
