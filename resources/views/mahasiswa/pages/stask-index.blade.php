@extends('base.base-dash-index')

@section('title', 'Tugas Kuliah - SIAKAD')
@section('menu', 'Akademik')
@section('submenu', 'Tugas Kuliah')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Pantau tenggat dan kumpulkan tugas perkuliahan tepat waktu')

@section('custom-css')
    @include('base.components.student-records-styles')
@endsection

@section('content')
<section class="section student-records">
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon"><i class="fas fa-list-check"></i></span><div><small>Total tugas</small><strong>{{ number_format($taskSummary['total']) }}</strong><span>Periode {{ $period?->name ?? '-' }}</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-blue"><i class="far fa-clock"></i></span><div><small>Masih aktif</small><strong>{{ number_format($taskSummary['aktif']) }}</strong><span>Perlu diselesaikan</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-red"><i class="fas fa-triangle-exclamation"></i></span><div><small>Terlambat</small><strong>{{ number_format($taskSummary['terlambat']) }}</strong><span>Melewati tenggat</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon"><i class="fas fa-circle-check"></i></span><div><small>Dikumpulkan</small><strong>{{ number_format($taskSummary['dikumpulkan']) }}</strong><span>Sudah diserahkan</span></div></div></div>
    </div>

    <div class="card record-panel">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="mb-1">Daftar Tugas</h5><small class="text-muted">Urut berdasarkan tenggat terdekat</small></div><span class="badge bg-light-primary text-primary">{{ $stask->count() }} tugas ditemukan</span></div>
        <div class="card-body">
            <div class="record-filter">
                <h6 class="record-filter__title"><i class="fas fa-filter me-2"></i>Filter tugas</h6>
                <p class="record-filter__description">Cari berdasarkan judul, mata kuliah, atau dosen dan persempit berdasarkan status.</p>
                <form method="GET" action="{{ route('mahasiswa.akademik.tugas-index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-7"><label for="task-search" class="form-label">Pencarian</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="search" name="q" id="task-search" class="form-control" value="{{ $filters['q'] }}" placeholder="Judul, mata kuliah, atau dosen" maxlength="100"></div></div>
                    <div class="col-lg-3"><label for="task-status" class="form-label">Status</label><select name="status" id="task-status" class="form-select"><option value="">Semua status</option><option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option><option value="segera" @selected($filters['status'] === 'segera')>Segera berakhir</option><option value="terlambat" @selected($filters['status'] === 'terlambat')>Terlambat</option><option value="dikumpulkan" @selected($filters['status'] === 'dikumpulkan')>Dikumpulkan</option></select></div>
                    <div class="col-lg-2"><div class="d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-filter me-1"></i>Terapkan</button>@if (filled($filters['q']) || filled($filters['status']))<a href="{{ route('mahasiswa.akademik.tugas-index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>@endif</div></div>
                </form>
            </div>

            <div class="table-responsive"><table class="table record-table"><thead><tr><th>Tugas</th><th>Dosen</th><th>Tenggat</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @forelse ($stask as $item)
                    @php
                        $statusMeta = match ($item->student_status) {
                            'dikumpulkan' => ['is-success', 'fa-circle-check', 'Dikumpulkan'],
                            'terlambat' => ['is-danger', 'fa-circle-exclamation', 'Terlambat'],
                            'segera' => ['is-warning', 'fa-clock', 'Segera berakhir'],
                            default => ['is-info', 'fa-hourglass-half', 'Aktif'],
                        };
                    @endphp
                    <tr>
                        <td class="record-primary" data-label="Tugas"><span class="record-title">{{ $item->title }}</span><span class="record-subtitle"><i class="fas fa-book-open me-1"></i>{{ $item->jadkul?->matkul?->name ?? 'Mata kuliah tidak tersedia' }}{{ $item->jadkul?->pert_id ? ' · Pertemuan '.$item->jadkul->pert_id : '' }}</span></td>
                        <td data-label="Dosen">{{ $item->dosen?->dsn_name ?? 'Belum ditentukan' }}</td>
                        <td data-label="Tenggat"><div><span class="record-title">{{ $item->deadline_at->translatedFormat('d M Y') }}</span><span class="record-subtitle">{{ $item->deadline_at->format('H:i') }} WIB</span></div></td>
                        <td data-label="Status"><span class="record-badge {{ $statusMeta[0] }}"><i class="fas {{ $statusMeta[1] }}"></i>{{ $statusMeta[2] }}</span></td>
                        <td data-label="Aksi" class="text-end record-actions">@if ($item->student_status === 'dikumpulkan')<span class="text-muted small"><i class="fas fa-check me-1"></i>Selesai</span>@else<a href="{{ route('mahasiswa.akademik.tugas-view', $item->code) }}" class="btn btn-primary"><i class="fas fa-arrow-right me-1"></i>Lihat tugas</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="record-empty-cell p-0"><div class="record-empty"><i class="far fa-clipboard"></i><strong>Tugas tidak ditemukan</strong><span>Coba ubah filter atau belum ada tugas pada periode ini.</span></div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</section>
@endsection
