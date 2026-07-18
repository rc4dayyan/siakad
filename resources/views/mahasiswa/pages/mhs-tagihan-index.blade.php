@extends('base.base-dash-index')

@section('title', 'Data Tagihan Perkuliahan')
@section('menu', 'Data Tagihan Perkuliahan')
@section('submenu', 'Daftar Tagihan')
@section('urlmenu', '#')
@section('subdesc', 'Lihat tagihan dan status konfirmasi pembayaran')

@section('content')
<section class="section">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-1">Daftar Tagihan</h5>
            <small class="text-muted">Periode: {{ $period?->name ?? 'belum aktif' }}</small>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped" id="table1">
                <thead><tr><th>#</th><th>Kode</th><th>Nama Tagihan</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($tagihan as $item)
                        @php
                            $payment = $payments->firstWhere('tagihan_kuliah_id', $item->id)
                                ?? $payments->firstWhere('tagihan_code', $item->code);
                            $paid = $payment && ($payment->status === \App\Models\HistoryTagihan::STATUS_PAID || (int) $payment->stat === 1);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->name }}</td>
                            <td>Rp {{ number_format($item->nominal ?? $item->price, 0, ',', '.') }}</td>
                            <td>
                                @if ($paid)
                                    <span class="badge bg-success">LUNAS</span>
                                @elseif ($payment?->status === \App\Models\HistoryTagihan::STATUS_PENDING)
                                    <span class="badge bg-warning text-dark">MENUNGGU VERIFIKASI</span>
                                @elseif ($payment?->status === \App\Models\HistoryTagihan::STATUS_REJECTED)
                                    <span class="badge bg-danger">DITOLAK</span>
                                @else
                                    <span class="badge bg-secondary">BELUM DIBAYAR</span>
                                @endif
                            </td>
                            <td>
                                @if ($paid)
                                    <a href="{{ route('mahasiswa.home-tagihan-invoice', $payment->code) }}" class="btn btn-sm btn-outline-success">Invoice</a>
                                @else
                                    <a href="{{ route('mahasiswa.home-tagihan-view', $item->code) }}" class="btn btn-sm btn-outline-primary">
                                        {{ $payment?->status === \App\Models\HistoryTagihan::STATUS_PENDING ? 'Lihat Konfirmasi' : 'Konfirmasi Pembayaran' }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Belum ada tagihan pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title">Riwayat Pembayaran Lunas</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead><tr><th>#</th><th>Kode Pembayaran</th><th>Kode Tagihan</th><th>Nominal</th><th>Invoice</th></tr></thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->tagihan_code }}</td>
                            <td>Rp {{ number_format($item->nominal ?? $item->tagihan?->nominal ?? $item->tagihan?->price, 0, ',', '.') }}</td>
                            <td><a href="{{ route('mahasiswa.home-tagihan-invoice', $item->code) }}" class="btn btn-sm btn-outline-success">Unduh</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada pembayaran yang dikonfirmasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
