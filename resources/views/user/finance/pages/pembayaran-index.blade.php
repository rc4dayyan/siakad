@extends('base.base-dash-index')

@section('title', 'Verifikasi Pembayaran Manual')
@section('menu', 'Verifikasi Pembayaran Manual')
@section('submenu', 'Pembayaran')
@section('urlmenu', '#')
@section('subdesc', 'Periksa bukti pembayaran mahasiswa dan tentukan hasil verifikasi')

@section('content')
<section class="section">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="alert alert-info">
        Periode: <strong>{{ $period->name }}</strong> · Pendapatan terverifikasi: <strong>Rp {{ number_format($income, 0, ',', '.') }}</strong>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Menunggu Verifikasi ({{ $pendingPayments->count() }})</h5></div>
        <div class="card-body">
            @forelse ($pendingPayments as $payment)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                        <div>
                            <strong>{{ $payment->users?->mhs_name ?? 'Mahasiswa tidak ditemukan' }}</strong>
                            <div class="text-muted">{{ $payment->users?->mhs_nim }} · {{ $payment->code }}</div>
                        </div>
                        <span class="badge bg-warning text-dark align-self-start">MENUNGGU</span>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><small>Tagihan</small><div>{{ $payment->tagihanKuliah?->name ?? $payment->tagihan_code }}</div></div>
                        <div class="col-md-3"><small>Nominal</small><div>Rp {{ number_format($payment->nominal, 0, ',', '.') }}</div></div>
                        <div class="col-md-3"><small>Tanggal transfer</small><div>{{ $payment->tanggal_transfer?->format('d-m-Y') ?? '-' }}</div></div>
                        <div class="col-md-3"><small>Nama pengirim</small><div>{{ $payment->nama_pengirim ?? '-' }}</div></div>
                    </div>
                    @if ($payment->desc)<p><small>Catatan mahasiswa:</small><br>{{ $payment->desc }}</p>@endif
                    <div class="mb-3"><a href="{{ route($prefix.'finance.pembayaran-proof', $payment) }}" class="btn btn-sm btn-outline-primary">Buka Bukti Pembayaran</a></div>
                    <form method="POST" action="{{ route($prefix.'finance.pembayaran-decision', $payment) }}">
                        @csrf @method('PATCH')
                        <textarea name="catatan_verifikasi" class="form-control mb-2" maxlength="2000" placeholder="Catatan verifikasi; wajib jika ditolak"></textarea>
                        <button name="status" value="{{ \App\Models\HistoryTagihan::STATUS_PAID }}" class="btn btn-success" onclick="return confirm('Konfirmasi pembayaran ini sebagai lunas?')">Konfirmasi Lunas</button>
                        <button name="status" value="{{ \App\Models\HistoryTagihan::STATUS_REJECTED }}" class="btn btn-danger" onclick="return confirm('Tolak konfirmasi pembayaran ini?')">Tolak</button>
                    </form>
                </div>
            @empty
                <div class="text-center text-muted py-3">Tidak ada konfirmasi pembayaran yang menunggu.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Riwayat Verifikasi</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Mahasiswa</th><th>Tagihan</th><th>Nominal</th><th>Status</th><th>Petugas</th><th>Ditinjau</th><th>Bukti</th></tr></thead>
                <tbody>
                    @forelse ($reviewedPayments as $payment)
                        <tr>
                            <td>{{ $payment->users?->mhs_name }}<br><small>{{ $payment->users?->mhs_nim }}</small></td>
                            <td>{{ $payment->tagihanKuliah?->name ?? $payment->tagihan_code }}</td>
                            <td>Rp {{ number_format($payment->nominal, 0, ',', '.') }}</td>
                            <td><span class="badge bg-{{ $payment->status === \App\Models\HistoryTagihan::STATUS_PAID ? 'success' : 'danger' }}">{{ strtoupper($payment->status) }}</span></td>
                            <td>{{ $payment->ditinjauOleh?->name ?? '-' }}</td>
                            <td>{{ $payment->ditinjau_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td>@if($payment->bukti_path)<a href="{{ route($prefix.'finance.pembayaran-proof', $payment) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>@else - @endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada riwayat verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
