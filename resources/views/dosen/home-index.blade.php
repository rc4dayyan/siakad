@extends('base.base-dash-index')

@section('title', 'Dashboard Dosen')
@section('menu', 'Dashboard')
@section('submenu', 'Dashboard Dosen')
@section('urlmenu', route('dosen.home-index'))
@section('subdesc', 'Ringkasan aktivitas mengajar dan bimbingan akademik Anda')

@section('custom-css')
    @include('base.components.professional-dashboard-styles')
    <style>
        .lecturer-chart { min-height: 310px; }
        .lecturer-chart__canvas { min-height: 265px; }
        .lecturer-announcement {
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
        }
        .lecturer-announcement:hover strong { color: var(--dash-green-dark); }
        .lecturer-announcement:focus-visible {
            border-radius: 10px;
            outline: 3px solid rgba(17, 122, 101, .18);
        }
        .lecturer-chart__legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px 16px;
            color: var(--dash-muted);
            font-size: 11px;
        }
        .lecturer-chart__legend span { display: inline-flex; align-items: center; gap: 6px; }
        .lecturer-chart__legend i { width: 8px; height: 8px; border-radius: 50%; }
    </style>
@endsection

@section('content')
    <section class="section professional-dashboard">
        <div class="dashboard-hero mb-4">
            <div class="dashboard-hero__content">
                <span class="dashboard-hero__eyebrow">Portal Dosen</span>
                <h2>Selamat datang, {{ $lecturer->dsn_name }}</h2>
                <p>Kelola kegiatan mengajar, penilaian, tugas, dan bimbingan akademik dari satu ruang kerja.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('dosen.akademik.jadwal-index') }}" class="btn btn-light">
                        <i class="fas fa-calendar-week me-1"></i> Lihat Jadwal
                    </a>
                    <a href="{{ route('dosen.akademik.krs-index') }}" class="btn btn-outline-light">
                        <i class="fas fa-file-signature me-1"></i> Persetujuan KRS
                    </a>
                </div>
            </div>
            <div class="dashboard-hero__aside">
                <small>Periode akademik</small>
                <strong>{{ $period?->name ?? 'Belum tersedia' }}</strong>
                <span>{{ $period ? $period->term_label.' · '.$period->status_label : 'Belum ada periode yang dipublikasikan' }}</span>
                <span class="mt-1">NIDN {{ $lecturer->dsn_nidn ?: 'belum dilengkapi' }}</span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <a href="{{ route('dosen.akademik.jadwal-index') }}" class="dashboard-metric">
                    <span class="dashboard-metric__icon"><i class="fas fa-calendar-check"></i></span>
                    <span class="dashboard-metric__content"><small>Jadwal Mengajar</small><strong>{{ number_format($scheduleCount) }}</strong><em>Jadwal pada periode berjalan</em></span>
                </a>
            </div>
            <div class="col-xl-3 col-sm-6">
                <a href="{{ route('dosen.akademik.stask-index') }}" class="dashboard-metric">
                    <span class="dashboard-metric__icon"><i class="fas fa-list-check"></i></span>
                    <span class="dashboard-metric__content"><small>Tugas Aktif</small><strong>{{ number_format($taskCount) }}</strong><em>Tugas yang telah diterbitkan</em></span>
                </a>
            </div>
            <div class="col-xl-3 col-sm-6">
                <a href="{{ route('dosen.akademik.jadwal-index') }}" class="dashboard-metric">
                    <span class="dashboard-metric__icon is-gold"><i class="fas fa-star"></i></span>
                    <span class="dashboard-metric__content"><small>Respons Evaluasi</small><strong>{{ number_format($feedbackCount) }}</strong><em>Umpan balik mahasiswa</em></span>
                </a>
            </div>
            <div class="col-xl-3 col-sm-6">
                <a href="{{ route('dosen.akademik.krs-index') }}" class="dashboard-metric">
                    <span class="dashboard-metric__icon {{ $pendingKrsCount > 0 ? 'is-red' : '' }}"><i class="fas fa-file-circle-check"></i></span>
                    <span class="dashboard-metric__content"><small>KRS Menunggu</small><strong>{{ number_format($pendingKrsCount) }}</strong><em>Perlu ditinjau dan diputuskan</em></span>
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-7">
                <div class="card dashboard-panel lecturer-chart">
                    <div class="card-header d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h5 class="mb-1">Kepuasan Mengajar</h5>
                            <small class="text-muted">Distribusi evaluasi mahasiswa pada periode berjalan</small>
                        </div>
                        <span class="badge bg-light-primary text-primary">{{ number_format($feedbackCount) }} respons</span>
                    </div>
                    <div class="card-body pt-0">
                        @if ($feedbackCount > 0)
                            <div id="lecturerSatisfactionChart" class="lecturer-chart__canvas" aria-label="Grafik kepuasan mengajar"></div>
                            <div class="lecturer-chart__legend" aria-hidden="true">
                                <span><i style="background: #dc6575"></i>Tidak Puas</span>
                                <span><i style="background: #e9b949"></i>Cukup Puas</span>
                                <span><i style="background: #117a65"></i>Sangat Puas</span>
                            </div>
                        @else
                            <div class="dashboard-empty py-5">
                                <i class="far fa-chart-bar"></i>
                                Belum ada respons evaluasi pada periode ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card dashboard-panel">
                    <div class="card-header">
                        <h5 class="mb-1">Pengumuman Terbaru</h5>
                        <small class="text-muted">Informasi penting untuk dosen</small>
                    </div>
                    <div class="card-body pt-2">
                        @forelse ($notify as $item)
                            <button type="button" class="dashboard-list-item lecturer-announcement" data-bs-toggle="modal" data-bs-target="#lecturerNotification{{ $loop->iteration }}">
                                <span class="dashboard-list-item__icon is-gold"><i class="fas fa-bullhorn"></i></span>
                                <span>
                                    <strong>{{ $item->name }}</strong>
                                    <small>{{ $item->created_at?->translatedFormat('d M Y · H.i') }}</small>
                                </span>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        @empty
                            <div class="dashboard-empty py-5">
                                <i class="far fa-bell-slash"></i>
                                Belum ada pengumuman terbaru.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="card dashboard-panel">
            <div class="card-header">
                <h5 class="mb-1">Akses Cepat</h5>
                <small class="text-muted">Menu yang paling sering digunakan dalam kegiatan akademik</small>
            </div>
            <div class="card-body pt-2">
                <div class="dashboard-action-grid">
                    <a href="{{ route('dosen.akademik.jadwal-index') }}"><i class="fas fa-calendar-week"></i><span>Jadwal Mengajar</span></a>
                    <a href="{{ route('dosen.akademik.matkul-index') }}"><i class="fas fa-graduation-cap"></i><span>Mata Kuliah &amp; Nilai</span></a>
                    <a href="{{ route('dosen.akademik.stask-index') }}"><i class="fas fa-list-check"></i><span>Tugas &amp; Penilaian</span></a>
                    <a href="{{ route('dosen.akademik.krs-index') }}"><i class="fas fa-file-signature"></i><span>Persetujuan KRS</span></a>
                </div>
            </div>
        </div>
    </section>

    @foreach ($notify as $item)
        <div class="modal fade" id="lecturerNotification{{ $loop->iteration }}" tabindex="-1" aria-labelledby="lecturerNotificationLabel{{ $loop->iteration }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <small class="text-muted d-block mb-1">Pengumuman dosen</small>
                            <h5 class="modal-title" id="lecturerNotificationLabel{{ $loop->iteration }}">{{ $item->name }}</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">{!! $item->desc !!}</div>
                    <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Tutup</button></div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@section('custom-js')
    @if ($feedbackCount > 0)
        <script src="{{ asset('dist/assets/extensions/apexcharts/apexcharts.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const chartElement = document.querySelector('#lecturerSatisfactionChart');

                if (!chartElement || typeof ApexCharts === 'undefined') {
                    return;
                }

                new ApexCharts(chartElement, {
                    chart: { type: 'donut', height: 275, toolbar: { show: false } },
                    series: @json($feedbackChart),
                    labels: ['Tidak Puas', 'Cukup Puas', 'Sangat Puas'],
                    colors: ['#dc6575', '#e9b949', '#117a65'],
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    stroke: { colors: ['#ffffff'], width: 4 },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '72%',
                                labels: {
                                    show: true,
                                    name: { show: true, offsetY: 18 },
                                    value: { show: true, fontSize: '24px', fontWeight: 700, offsetY: -18 },
                                    total: { show: true, label: 'Total Respons', formatter: function () { return '{{ number_format($feedbackCount) }}'; } }
                                }
                            }
                        }
                    },
                    responsive: [{ breakpoint: 576, options: { chart: { height: 245 } } }]
                }).render();
            });
        </script>
    @endif
@endsection
