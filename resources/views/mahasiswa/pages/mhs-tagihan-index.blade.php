@extends('base.base-dash-index')

@section('title', 'Tagihan Perkuliahan - SIAKAD')
@section('menu', 'Keuangan')
@section('submenu', 'Tagihan Perkuliahan')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Pantau kewajiban pembayaran dan riwayat transaksi Anda')

@section('custom-css')
    @include('base.components.student-records-styles')
@endsection

@section('content')
<section class="section student-records">
    @if (session('success'))<div class="alert alert-success"><i class="fas fa-circle-check me-2"></i>{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-blue"><i class="fas fa-file-invoice-dollar"></i></span><div><small>Total tagihan</small><strong>Rp {{ number_format($billingSummary['total'], 0, ',', '.') }}</strong><span>Periode {{ $period?->name ?? '-' }}</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-gold"><i class="fas fa-wallet"></i></span><div><small>Sisa kewajiban</small><strong>Rp {{ number_format($billingSummary['outstanding'], 0, ',', '.') }}</strong><span>Belum terverifikasi lunas</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon"><i class="fas fa-circle-check"></i></span><div><small>Tagihan lunas</small><strong>{{ number_format($billingSummary['paid']) }}</strong><span>Pembayaran terverifikasi</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-gold"><i class="fas fa-hourglass-half"></i></span><div><small>Dalam verifikasi</small><strong>{{ number_format($billingSummary['pending']) }}</strong><span>Menunggu petugas</span></div></div></div>
    </div>

    <div class="card record-panel mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="mb-1">Daftar Tagihan</h5><small class="text-muted">Periode {{ $period?->name ?? 'belum tersedia' }}</small></div><span class="badge bg-light-primary text-primary">{{ $tagihan->count() }} tagihan ditemukan</span></div>
        <div class="card-body">
            <div class="record-filter">
                <h6 class="record-filter__title"><i class="fas fa-filter me-2"></i>Filter tagihan</h6>
                <p class="record-filter__description">Temukan tagihan berdasarkan kode atau nama dan status pembayarannya.</p>
                <form method="GET" action="{{ route('mahasiswa.home-tagihan-index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-7"><label for="billing-search" class="form-label">Pencarian</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="search" name="q" id="billing-search" class="form-control" value="{{ $filters['q'] }}" placeholder="Kode atau nama tagihan" maxlength="100"></div></div>
                    <div class="col-lg-3"><label for="billing-status" class="form-label">Status pembayaran</label><select name="status" id="billing-status" class="form-select"><option value="">Semua status</option><option value="lunas" @selected($filters['status'] === 'lunas')>Lunas</option><option value="pending" @selected($filters['status'] === 'pending')>Menunggu verifikasi</option><option value="ditolak" @selected($filters['status'] === 'ditolak')>Ditolak</option><option value="belum_dibayar" @selected($filters['status'] === 'belum_dibayar')>Belum dibayar</option></select></div>
                    <div class="col-lg-2"><div class="d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-filter me-1"></i>Terapkan</button>@if (filled($filters['q']) || filled($filters['status']))<a href="{{ route('mahasiswa.home-tagihan-index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>@endif</div></div>
                </form>
            </div>

            <div class="table-responsive"><table class="table record-table"><thead><tr><th>Tagihan</th><th>Nominal</th><th>Jatuh tempo</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @forelse ($tagihan as $item)
                    @php
                        $payment = $item->currentPayment;
                        $statusMeta = match ($item->student_status) {
                            'lunas' => ['is-success', 'fa-circle-check', 'Lunas'],
                            'pending' => ['is-warning', 'fa-hourglass-half', 'Menunggu verifikasi'],
                            'ditolak' => ['is-danger', 'fa-circle-xmark', 'Ditolak'],
                            default => ['is-muted', 'fa-wallet', 'Belum dibayar'],
                        };
                    @endphp
                    <tr>
                        <td class="record-primary" data-label="Tagihan"><span class="record-title">{{ $item->name }}</span><span class="record-subtitle">{{ strtoupper($item->code) }}</span></td>
                        <td data-label="Nominal"><span class="record-title">Rp {{ number_format($item->nominal ?? $item->price, 0, ',', '.') }}</span></td>
                        <td data-label="Jatuh tempo">@if ($item->jatuh_tempo)<span class="record-title">{{ $item->jatuh_tempo->translatedFormat('d M Y') }}</span><span class="record-subtitle">{{ $item->jatuh_tempo->isPast() && $item->student_status !== 'lunas' ? 'Telah melewati tenggat' : $item->jatuh_tempo->diffForHumans() }}</span>@else<span class="text-muted">Tidak ditentukan</span>@endif</td>
                        <td data-label="Status"><span class="record-badge {{ $statusMeta[0] }}"><i class="fas {{ $statusMeta[1] }}"></i>{{ $statusMeta[2] }}</span></td>
                        <td data-label="Aksi" class="text-end record-actions">@if ($item->student_status === 'lunas')<a href="{{ route('mahasiswa.home-tagihan-invoice', $payment->code) }}" class="btn btn-outline-success"><i class="fas fa-download me-1"></i>Invoice</a>@else<a href="{{ route('mahasiswa.home-tagihan-view', $item->code) }}" class="btn btn-primary"><i class="fas fa-arrow-right me-1"></i>{{ $item->student_status === 'pending' ? 'Lihat konfirmasi' : 'Lihat tagihan' }}</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="record-empty-cell p-0"><div class="record-empty"><i class="far fa-file-lines"></i><strong>Tagihan tidak ditemukan</strong><span>Coba ubah filter atau belum ada tagihan pada periode ini.</span></div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="card record-panel">
        <div class="card-header"><h5 class="mb-1">Riwayat Pembayaran</h5><small class="text-muted">Pembayaran yang telah diverifikasi lunas</small></div>
        <div class="card-body pt-2"><div class="table-responsive"><table class="table record-table"><thead><tr><th>Pembayaran</th><th>Tagihan</th><th>Nominal</th><th>Tanggal</th><th class="text-end">Dokumen</th></tr></thead><tbody>
            @forelse ($history as $item)
                <tr><td class="record-primary" data-label="Pembayaran"><span class="record-title">{{ $item->code }}</span><span class="record-subtitle">Kode pembayaran</span></td><td data-label="Tagihan">{{ $item->tagihan?->name ?? $item->tagihan_code }}</td><td data-label="Nominal">Rp {{ number_format($item->nominal ?? $item->tagihan?->nominal ?? $item->tagihan?->price, 0, ',', '.') }}</td><td data-label="Tanggal">{{ ($item->dibayar_at ?? $item->updated_at)?->translatedFormat('d M Y') ?? '-' }}</td><td data-label="Dokumen" class="text-end record-actions"><a href="{{ route('mahasiswa.home-tagihan-invoice', $item->code) }}" class="btn btn-outline-success"><i class="fas fa-download me-1"></i>Invoice</a></td></tr>
            @empty
                <tr><td colspan="5" class="record-empty-cell p-0"><div class="record-empty"><i class="far fa-credit-card"></i><strong>Belum ada riwayat pembayaran</strong><span>Pembayaran lunas akan ditampilkan di bagian ini.</span></div></td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</section>
@endsection
