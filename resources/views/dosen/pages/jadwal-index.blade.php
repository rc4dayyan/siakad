@extends('base.base-dash-index')

@section('title', 'Jadwal Mengajar')
@section('menu', 'Akademik')
@section('submenu', 'Jadwal Mengajar')
@section('urlmenu', route('dosen.home-index'))
@section('subdesc', 'Agenda perkuliahan pada periode akademik berjalan')

@section('custom-css')
    <style>
        .lecturer-schedule-card {
            border: 1px solid var(--dash-line);
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(12, 44, 55, .05);
        }
        .lecturer-schedule-card .card-header { padding: 21px 22px 14px; background: transparent; }
        .lecturer-schedule-card .card-body { padding: 16px 22px 22px; }
        .lecturer-schedule-list { overflow: hidden; border: 1px solid var(--dash-line); border-radius: 13px; }
        .lecturer-schedule-item {
            display: grid;
            grid-template-columns: 68px minmax(0, 1fr) auto;
            align-items: center;
            gap: 17px;
            padding: 17px 18px;
            border-bottom: 1px solid var(--dash-line);
            background: #fff;
            transition: background .18s ease;
        }
        .lecturer-schedule-item:last-child { border-bottom: 0; }
        .lecturer-schedule-item:hover { background: #f8fbfa; }
        .lecturer-schedule-date {
            display: flex;
            min-height: 64px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            color: var(--dash-green-dark);
            background: var(--dash-green-soft);
            text-align: center;
        }
        .lecturer-schedule-date span { font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .lecturer-schedule-date strong { margin: 1px 0; color: var(--dash-navy); font-size: 22px; line-height: 1; }
        .lecturer-schedule-date small { color: var(--dash-muted); font-size: 10px; }
        .lecturer-schedule-title { margin: 0 0 7px; color: var(--dash-navy); font-size: 14px; font-weight: 750; }
        .lecturer-schedule-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 15px; color: var(--dash-muted); font-size: 11px; }
        .lecturer-schedule-meta span { display: inline-flex; align-items: center; gap: 6px; }
        .lecturer-schedule-meta i { width: 13px; color: var(--dash-green); text-align: center; }
        .lecturer-schedule-badge {
            padding: 5px 9px;
            border-radius: 999px;
            color: var(--dash-green-dark);
            background: var(--dash-green-soft);
            font-size: 10px;
            font-weight: 700;
        }
        .lecturer-schedule-actions { display: flex; gap: 7px; }
        .lecturer-schedule-actions .btn { border-radius: 8px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .lecturer-schedule-empty { padding: 48px 18px; color: var(--dash-muted); text-align: center; }
        .lecturer-schedule-empty i { display: block; margin-bottom: 10px; color: #aab8b4; font-size: 29px; }

        @media (max-width: 767.98px) {
            .lecturer-schedule-card .card-header, .lecturer-schedule-card .card-body { padding-right: 16px; padding-left: 16px; }
            .lecturer-schedule-item { grid-template-columns: 58px minmax(0, 1fr); gap: 13px; padding: 15px; }
            .lecturer-schedule-date { min-height: 58px; }
            .lecturer-schedule-date strong { font-size: 19px; }
            .lecturer-schedule-actions { grid-column: 1 / -1; }
            .lecturer-schedule-actions .btn { flex: 1; }
        }
    </style>
@endsection

@section('content')
    <section class="section">
        <div class="card lecturer-schedule-card">
            <div class="card-header">
                <h5 class="mb-1">Agenda Mengajar</h5>
                <small class="text-muted">Periode {{ $selectedPeriod?->name ?? 'belum tersedia' }}</small>
            </div>
            <div class="card-body">
                @include('base.partials.schedule-filter', [
                    'filterRoute' => 'dosen.akademik.jadwal-index',
                    'filterContext' => 'mengajar Anda',
                ])

                <div class="lecturer-schedule-list">
                    @forelse ($jadkul as $item)
                        @php $scheduleDate = \Carbon\Carbon::parse($item->date); @endphp
                        <article class="lecturer-schedule-item">
                            <div class="lecturer-schedule-date" aria-label="{{ $scheduleDate->translatedFormat('l, d F Y') }}">
                                <span>{{ $item->days_id }}</span>
                                <strong>{{ $scheduleDate->format('d') }}</strong>
                                <small>{{ $scheduleDate->translatedFormat('M Y') }}</small>
                            </div>

                            <div>
                                <h6 class="lecturer-schedule-title">{{ $item->matkul?->name ?? 'Mata kuliah tidak tersedia' }}</h6>
                                <div class="lecturer-schedule-meta">
                                    <span><i class="far fa-clock"></i>{{ substr($item->start, 0, 5) }}–{{ substr($item->ended, 0, 5) }}</span>
                                    <span><i class="fas fa-users"></i>Kelas {{ $item->kelas?->code ?? $item->kelas?->name ?? '—' }}</span>
                                    <span><i class="fas fa-location-dot"></i>{{ $item->ruang?->name ?? 'Tanpa ruangan' }}{{ $item->ruang?->gedung?->name ? ' · '.$item->ruang->gedung->name : '' }}</span>
                                    <span><i class="fas fa-book-open"></i>{{ $item->pert_id }} · {{ $item->bsks }} SKS</span>
                                    <span class="lecturer-schedule-badge">{{ $item->meth_id }}</span>
                                </div>
                            </div>

                            <div class="lecturer-schedule-actions">
                                <a href="{{ route('dosen.akademik.jadwal-view-absen', $item->code) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-calendar-check me-1"></i> Presensi
                                </a>
                                <a href="{{ route('dosen.akademik.jadwal-view-feedback', $item->code) }}" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-star me-1"></i> Evaluasi
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="lecturer-schedule-empty">
                            <i class="far fa-calendar-xmark"></i>
                            <strong class="d-block mb-1">Jadwal tidak ditemukan</strong>
                            <span>Tidak ada agenda mengajar yang sesuai dengan filter.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
