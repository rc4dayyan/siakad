@extends('base.base-dash-index')

@section('title', 'Jadwal Kuliah')
@section('menu', 'Akademik')
@section('submenu', 'Jadwal Kuliah')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Jadwal kuliah mingguan pada periode akademik berjalan')

@section('custom-css')
    <style>
        .student-weekly-card { border: 1px solid var(--dash-line); border-radius: 15px; box-shadow: 0 8px 24px rgba(12, 44, 55, .05); }
        .student-weekly-card .card-header { padding: 21px 22px 14px; background: transparent; }
        .student-weekly-card .card-body { padding: 16px 22px 22px; }
        .student-weekly-list { overflow: hidden; border: 1px solid var(--dash-line); border-radius: 13px; }
        .student-weekly-group + .student-weekly-group { border-top: 8px solid #f3f6f5; }
        .student-weekly-group__header { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 18px; border-bottom: 1px solid #cfe2db; color: var(--dash-navy); background: linear-gradient(135deg, var(--dash-green-soft), #f7fbfa); }
        .student-weekly-group__header h6 { margin: 0; font-size: 14px; font-weight: 800; }
        .student-weekly-group__header span { color: var(--dash-green-dark); font-size: 11px; font-weight: 700; }
        .student-weekly-item { display: grid; grid-template-columns: 72px minmax(0, 1fr) auto; align-items: center; gap: 18px; padding: 18px; border-bottom: 1px solid var(--dash-line); background: #fff; transition: background .18s ease; }
        .student-weekly-item:last-child { border-bottom: 0; }
        .student-weekly-item:hover { background: #f8fbfa; }
        .student-weekly-day { display: flex; min-height: 68px; flex-direction: column; align-items: center; justify-content: center; border-radius: 12px; color: var(--dash-green-dark); background: var(--dash-green-soft); text-align: center; }
        .student-weekly-day span { font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .student-weekly-day strong { margin: 2px 0; color: var(--dash-navy); font-size: 22px; line-height: 1; }
        .student-weekly-day small { color: var(--dash-muted); font-size: 10px; }
        .student-weekly-title { margin: 0 0 7px; color: var(--dash-navy); font-size: 15px; font-weight: 750; }
        .student-weekly-code { color: var(--dash-muted); font-size: 11px; font-weight: 600; }
        .student-weekly-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 7px 16px; margin-top: 7px; color: var(--dash-muted); font-size: 11px; }
        .student-weekly-meta span { display: inline-flex; align-items: center; gap: 6px; }
        .student-weekly-meta i { width: 13px; color: var(--dash-green); text-align: center; }
        .student-weekly-actions .btn { border-radius: 8px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .student-weekly-empty { padding: 50px 18px; color: var(--dash-muted); text-align: center; }
        .student-weekly-empty i { display: block; margin-bottom: 11px; color: #aab8b4; font-size: 30px; }
        @media (max-width: 767.98px) {
            .student-weekly-card .card-header, .student-weekly-card .card-body { padding-right: 16px; padding-left: 16px; }
            .student-weekly-item { grid-template-columns: 58px minmax(0, 1fr); gap: 13px; padding: 15px; }
            .student-weekly-day { min-height: 58px; }
            .student-weekly-day strong { font-size: 18px; }
            .student-weekly-actions { grid-column: 1 / -1; }
            .student-weekly-actions .btn { width: 100%; }
        }
    </style>
@endsection

@section('content')
    <section class="section">
        <div class="card student-weekly-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><h5 class="mb-1">Jadwal Kuliah Mingguan</h5><small class="text-muted">Periode {{ $selectedPeriod?->name ?? 'belum tersedia' }} · berdasarkan KRS yang disetujui</small></div>
                @if ($selectedPeriod)
                    <a href="{{ route('mahasiswa.home-jadkul-weekly-print', isset($filters['days_id']) ? ['hari' => $filters['days_id']] : []) }}" target="_blank" class="btn btn-outline-success"><i class="fas fa-print me-1"></i> Cetak Jadwal Mingguan</a>
                @endif
            </div>
            <div class="card-body">
                @if (! $selectedPeriod)
                    <div class="alert alert-warning"><i class="fas fa-triangle-exclamation me-1"></i> Belum ada periode akademik yang dipublikasikan.</div>
                @else
                    @include('base.partials.schedule-filter', [
                        'filterRoute' => 'mahasiswa.home-jadkul-index',
                        'filterContext' => 'kuliah Anda',
                        'showMethodFilter' => false,
                        'showDateFilters' => false,
                    ])
                @endif

                @if ($jadkul->isNotEmpty())
                    <div class="student-weekly-list">
                        @foreach ($jadkul->getCollection()->groupBy('hari') as $daySchedules)
                            <section class="student-weekly-group">
                                <header class="student-weekly-group__header">
                                    <h6><i class="far fa-calendar me-2"></i>{{ $daySchedules->first()->hari_label }}</h6>
                                    <span>{{ $daySchedules->count() }} jadwal</span>
                                </header>
                                @foreach ($daySchedules as $item)
                                    <article class="student-weekly-item">
                                        <div class="student-weekly-day" aria-label="Mulai pukul {{ substr($item->mulai, 0, 5) }}"><span>Mulai</span><strong>{{ substr($item->mulai, 0, 5) }}</strong><small>WIB</small></div>
                                        <div>
                                            <h6 class="student-weekly-title">{{ $item->penawaranMataKuliah?->masterMataKuliah?->name ?? 'Mata kuliah tidak tersedia' }}</h6>
                                            <span class="student-weekly-code">{{ $item->penawaranMataKuliah?->masterMataKuliah?->code ?? $item->penawaranMataKuliah?->code ?? $item->code }}</span>
                                            <div class="student-weekly-meta">
                                                <span><i class="far fa-clock"></i>{{ substr($item->mulai, 0, 5) }}–{{ substr($item->selesai, 0, 5) }} WIB</span>
                                                <span><i class="fas fa-user-tie"></i>{{ $item->dosen?->dsn_name ?? 'Dosen belum ditentukan' }}</span>
                                                <span><i class="fas fa-users"></i>{{ $item->kelas?->name ?? $item->kelas?->code ?? 'Tanpa kelas' }}</span>
                                                <span><i class="fas fa-location-dot"></i>{{ $item->ruang?->name ?? 'Tanpa ruangan' }}{{ $item->ruang?->gedung?->name ? ' · '.$item->ruang->gedung->name : '' }}</span>
                                                <span><i class="fas fa-book-open"></i>{{ $item->sks ?? $item->penawaranMataKuliah?->sks ?? 0 }} SKS</span>
                                            </div>
                                        </div>
                                        <div class="student-weekly-actions"><a href="{{ route('mahasiswa.home-jadkul-meetings', $item->code) }}" class="btn btn-primary"><i class="fas fa-calendar-check me-1"></i> {{ $item->pertemuans_count > 0 ? $item->pertemuans_count.' Pertemuan' : 'Lihat Pertemuan' }}</a></div>
                                    </article>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                @else
                    <div class="student-weekly-list">
                        <div class="student-weekly-empty"><i class="far fa-calendar-xmark"></i><strong class="d-block mb-1">Jadwal tidak ditemukan</strong><span>Belum ada jadwal mingguan yang sesuai dengan KRS atau filter Anda.</span></div>
                    </div>
                @endif

                @if ($jadkul->hasPages())
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4"><small class="text-muted">Menampilkan {{ $jadkul->firstItem() }}–{{ $jadkul->lastItem() }} dari {{ number_format($jadkul->total()) }} jadwal</small>{{ $jadkul->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
