@php
    $dashboard = $financeDashboard;
    $period = $dashboard['period'];
    $summary = $dashboard['summary'];
@endphp

<div class="professional-dashboard">
    <div class="dashboard-hero mb-4">
        <div class="dashboard-hero__content">
            <span class="dashboard-hero__eyebrow">Ruang Kerja Keuangan</span>
            <h2>Selamat datang, {{ Auth::user()->name }}</h2>
            <p>Pantau penerbitan tagihan, pembayaran mahasiswa, dan posisi keuangan dari satu halaman.</p>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route($prefix.'billing-period.index') }}" class="btn btn-light"><i class="fas fa-file-circle-plus me-1"></i> Kelola Tagihan</a>
                <a href="{{ route($prefix.'finance.pembayaran-index') }}" class="btn btn-outline-light"><i class="fas fa-clipboard-check me-1"></i> Verifikasi Pembayaran</a>
            </div>
        </div>
        <div class="dashboard-hero__aside">
            <small>Periode laporan</small>
            <strong>{{ $period?->name ?? 'Belum tersedia' }}</strong>
            <span>{{ $period ? $period->code.' · '.$period->status_label : 'Pilih atau aktifkan periode akademik' }}</span>
            <span class="mt-2"><i class="fas fa-circle me-1" style="font-size: 7px"></i> Data dibatasi sesuai periode</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><a href="{{ route($prefix.'finance.keuangan-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-wallet"></i></span><span class="dashboard-metric__content"><small>Saldo Berjalan</small><strong>Rp {{ number_format($balSekarang, 0, ',', '.') }}</strong><em>Pemasukan dikurangi pengeluaran</em></span></a></div>
        <div class="col-xl-3 col-sm-6"><a href="{{ route($prefix.'billing-period.index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-file-invoice"></i></span><span class="dashboard-metric__content"><small>Total Tagihan</small><strong>Rp {{ number_format($summary['total_tagihan'], 0, ',', '.') }}</strong><em>{{ number_format($summary['jumlah_tagihan']) }} tagihan diterbitkan</em></span></a></div>
        <div class="col-xl-3 col-sm-6"><a href="{{ route($prefix.'finance.pembayaran-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-money-bill-transfer"></i></span><span class="dashboard-metric__content"><small>Pembayaran Diterima</small><strong>Rp {{ number_format($summary['total_pembayaran'], 0, ',', '.') }}</strong><em>{{ number_format($summary['jumlah_pembayaran']) }} pembayaran terverifikasi</em></span></a></div>
        <div class="col-xl-3 col-sm-6"><a href="{{ route($prefix.'billing-period.index') }}" class="dashboard-metric"><span class="dashboard-metric__icon {{ $summary['total_tunggakan'] > 0 ? 'is-gold' : '' }}"><i class="fas fa-hourglass-half"></i></span><span class="dashboard-metric__content"><small>Total Tunggakan</small><strong>Rp {{ number_format($summary['total_tunggakan'], 0, ',', '.') }}</strong><em>Belum tercatat lunas</em></span></a></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="card dashboard-panel">
                <div class="card-header"><h5 class="mb-1">Perlu Ditindaklanjuti</h5><small class="text-muted">Prioritas pekerjaan keuangan saat ini</small></div>
                <div class="card-body pt-2">
                    <a href="{{ route($prefix.'finance.pembayaran-index') }}" class="dashboard-list-item"><span class="dashboard-list-item__icon {{ $dashboard['pendingPayments'] > 0 ? 'is-gold' : '' }}"><i class="fas fa-receipt"></i></span><span><strong>{{ number_format($dashboard['pendingPayments']) }} pembayaran menunggu verifikasi</strong><small>Tinjau bukti pembayaran mahasiswa</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route($prefix.'billing-period.index') }}" class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-layer-group"></i></span><span><strong>{{ number_format($dashboard['templates']) }} template tagihan</strong><small>Kelola dan terbitkan tagihan periode</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route($prefix.'finance.keuangan-index') }}" class="dashboard-list-item"><span class="dashboard-list-item__icon {{ $balPending > 0 ? 'is-gold' : '' }}"><i class="fas fa-clock-rotate-left"></i></span><span><strong>Rp {{ number_format($balPending, 0, ',', '.') }} transaksi pending</strong><small>Periksa pencatatan arus keuangan</small></span><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card dashboard-panel">
                <div class="card-header d-flex justify-content-between align-items-start gap-3"><div><h5 class="mb-1">Tagihan Terbaru</h5><small class="text-muted">Penerbitan terakhir pada periode terpilih</small></div><a href="{{ route($prefix.'billing-period.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a></div>
                <div class="card-body pt-2">
                    @forelse ($dashboard['recentBills'] as $bill)
                        <div class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-file-invoice-dollar"></i></span><span><strong>{{ $bill->name }}</strong><small>{{ $bill->targetMahasiswa?->mhs_name ?? 'Target mahasiswa tidak tersedia' }} · {{ $bill->created_at?->translatedFormat('d M Y') }}</small></span><strong class="text-nowrap">Rp {{ number_format($bill->nominal ?: $bill->price, 0, ',', '.') }}</strong></div>
                    @empty
                        <div class="dashboard-empty"><i class="far fa-file-lines"></i>Belum ada tagihan pada periode ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card dashboard-panel">
                <div class="card-header"><h5 class="mb-1">Akses Cepat</h5><small class="text-muted">Menu utama pengelolaan keuangan</small></div>
                <div class="card-body pt-2"><div class="dashboard-action-grid">
                    <a href="{{ route($prefix.'billing-period.index') }}"><i class="fas fa-file-circle-plus"></i><span>Tagihan Periode</span></a>
                    <a href="{{ route($prefix.'finance.tagihan-index') }}"><i class="fas fa-list"></i><span>Daftar Tagihan</span></a>
                    <a href="{{ route($prefix.'finance.pembayaran-index') }}"><i class="fas fa-money-check-dollar"></i><span>Pembayaran</span></a>
                    <a href="{{ route($prefix.'finance.keuangan-index') }}"><i class="fas fa-chart-line"></i><span>Arus Keuangan</span></a>
                </div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card dashboard-panel">
                <div class="card-header"><h5 class="mb-1">Posisi Kas</h5><small class="text-muted">Ringkasan seluruh pencatatan kas</small></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3"><span class="text-muted">Pemasukan</span><strong class="text-success">Rp {{ number_format($balIncome, 0, ',', '.') }}</strong></div>
                    <div class="d-flex justify-content-between mb-3"><span class="text-muted">Pengeluaran</span><strong class="text-danger">Rp {{ number_format($balExpense, 0, ',', '.') }}</strong></div>
                    <div class="pt-3 border-top d-flex justify-content-between"><span class="fw-bold">Saldo</span><strong>Rp {{ number_format($balSekarang, 0, ',', '.') }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
