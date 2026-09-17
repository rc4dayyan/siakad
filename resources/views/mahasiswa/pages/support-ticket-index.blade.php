@extends('base.base-dash-index')

@section('title', 'Pusat Bantuan - SIAKAD')
@section('menu', 'Layanan')
@section('submenu', 'Pusat Bantuan')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Pantau tiket dan komunikasi Anda dengan unit layanan kampus')

@section('custom-css')
    @include('base.components.student-records-styles')
@endsection

@section('content')
@php
    $statusLabels = [0 => 'Terbuka', 1 => 'Diproses', 2 => 'Ditutup', 3 => 'Dijawab', 4 => 'Balasan mahasiswa', 5 => 'Ditahan', 6 => 'Menunggu mahasiswa'];
    $priorityLabels = [0 => 'Rendah', 1 => 'Sedang', 2 => 'Tinggi', 3 => 'Mendesak'];
    $departmentLabels = [0 => 'Web Administrator', 1 => 'Keuangan', 2 => 'Penerimaan/Officer', 3 => 'Akademik', 4 => 'Administrasi', 5 => 'Dukungan Teknis'];
@endphp
<section class="section student-records">
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-blue"><i class="fas fa-ticket"></i></span><div><small>Total tiket</small><strong>{{ number_format($ticketSummary['total']) }}</strong><span>Seluruh permintaan Anda</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-gold"><i class="fas fa-spinner"></i></span><div><small>Masih aktif</small><strong>{{ number_format($ticketSummary['open']) }}</strong><span>Dalam tindak lanjut</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon"><i class="fas fa-reply"></i></span><div><small>Sudah dijawab</small><strong>{{ number_format($ticketSummary['answered']) }}</strong><span>Perlu Anda periksa</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon"><i class="fas fa-circle-check"></i></span><div><small>Ditutup</small><strong>{{ number_format($ticketSummary['closed']) }}</strong><span>Permintaan selesai</span></div></div></div>
    </div>

    <div class="card record-panel">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="mb-1">Tiket Bantuan Saya</h5><small class="text-muted">Kelola komunikasi dengan unit layanan kampus</small></div><a href="{{ route('mahasiswa.support.ticket-open') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Buat tiket baru</a></div>
        <div class="card-body">
            <div class="record-filter">
                <h6 class="record-filter__title"><i class="fas fa-filter me-2"></i>Filter tiket</h6>
                <p class="record-filter__description">Cari nomor atau subjek tiket dan persempit hasil berdasarkan layanan, prioritas, atau status.</p>
                <form method="GET" action="{{ route('mahasiswa.support.ticket-index') }}" class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6"><label for="ticket-search" class="form-label">Pencarian</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="search" name="q" id="ticket-search" class="form-control" value="{{ $filters['q'] }}" placeholder="Nomor atau subjek tiket" maxlength="100"></div></div>
                    <div class="col-xl-2 col-md-6"><label for="ticket-department" class="form-label">Unit layanan</label><select name="department" id="ticket-department" class="form-select"><option value="">Semua unit</option>@foreach ($departmentLabels as $value => $label)<option value="{{ $value }}" @selected($filters['department'] === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-xl-2 col-md-6"><label for="ticket-priority" class="form-label">Prioritas</label><select name="priority" id="ticket-priority" class="form-select"><option value="">Semua prioritas</option>@foreach ($priorityLabels as $value => $label)<option value="{{ $value }}" @selected($filters['priority'] === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-xl-2 col-md-6"><label for="ticket-status" class="form-label">Status</label><select name="status" id="ticket-status" class="form-select"><option value="">Semua status</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-xl-2"><div class="d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-filter me-1"></i>Terapkan</button>@if (filled($filters['q']) || $filters['department'] !== null || $filters['priority'] !== null || $filters['status'] !== null)<a href="{{ route('mahasiswa.support.ticket-index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>@endif</div></div>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2"><small class="text-muted">Menampilkan {{ $ticket->firstItem() ?? 0 }}–{{ $ticket->lastItem() ?? 0 }} dari {{ number_format($ticket->total()) }} tiket</small></div>
            <div class="table-responsive"><table class="table record-table"><thead><tr><th>Tiket</th><th>Unit layanan</th><th>Prioritas</th><th>Status</th><th>Pembaruan</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @forelse ($ticket as $item)
                    @php
                        $statusClass = match ($item->raw_stat_id) { 2 => 'is-muted', 3 => 'is-success', 1, 5, 6 => 'is-warning', 4 => 'is-info', default => 'is-success' };
                        $priorityClass = match ($item->raw_prio_id) { 3 => 'is-danger', 2 => 'is-warning', 1 => 'is-info', default => 'is-muted' };
                    @endphp
                    <tr>
                        <td class="record-primary" data-label="Tiket"><span class="record-title">{{ $item->subject }}</span><span class="record-subtitle">#{{ $item->code }}</span></td>
                        <td data-label="Unit layanan">{{ $departmentLabels[$item->raw_dept_id] ?? $item->dept_id }}</td>
                        <td data-label="Prioritas"><span class="record-badge {{ $priorityClass }}">{{ $priorityLabels[$item->raw_prio_id] ?? $item->prio_id }}</span></td>
                        <td data-label="Status"><span class="record-badge {{ $statusClass }}">{{ $statusLabels[$item->raw_stat_id] ?? $item->stat_id }}</span></td>
                        <td data-label="Pembaruan"><span class="record-title">{{ $item->updated_at->diffForHumans() }}</span><span class="record-subtitle">{{ $item->updated_at->translatedFormat('d M Y · H.i') }}</span></td>
                        <td data-label="Aksi" class="text-end record-actions"><a href="{{ route('mahasiswa.support.ticket-view', $item->code) }}" class="btn btn-outline-primary"><i class="fas fa-message me-1"></i>Lihat percakapan</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="record-empty-cell p-0"><div class="record-empty"><i class="far fa-comments"></i><strong>Tiket tidak ditemukan</strong><span>Coba ubah filter atau buat tiket baru jika Anda membutuhkan bantuan.</span></div></td></tr>
                @endforelse
            </tbody></table></div>

            @if ($ticket->hasPages())<div class="d-flex justify-content-end mt-4">{{ $ticket->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
        </div>
    </div>
</section>
@endsection
