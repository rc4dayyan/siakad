@extends('base.base-dash-index')

@section('title', 'Konfirmasi Pembayaran')
@section('menu', 'Konfirmasi Pembayaran')
@section('submenu', $tagihan->code)
@section('urlmenu', route('mahasiswa.home-tagihan-index'))
@section('subdesc', 'Kirim bukti pembayaran untuk diperiksa petugas keuangan')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">{{ $tagihan->name }}</h5>
                <small>{{ $tagihan->code }}</small>
            </div>
            <a href="{{ route('mahasiswa.home-tagihan-index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <dl class="row">
                <dt class="col-sm-3">Nominal</dt><dd class="col-sm-9">Rp {{ number_format($tagihan->nominal ?? $tagihan->price, 0, ',', '.') }}</dd>
                <dt class="col-sm-3">Jatuh tempo</dt><dd class="col-sm-9">{{ $tagihan->jatuh_tempo?->format('d-m-Y') ?? '-' }}</dd>
            </dl>

            @if ($manualPayment?->status === \App\Models\HistoryTagihan::STATUS_PENDING)
                <div class="alert alert-info">
                    <strong>Menunggu verifikasi petugas.</strong><br>
                    Pengajuan {{ $manualPayment->code }} dikirim {{ $manualPayment->diajukan_at?->format('d-m-Y H:i') }}.
                    <a href="{{ route('mahasiswa.home-tagihan-payment-proof', $manualPayment) }}" class="alert-link">Lihat bukti pembayaran</a>.
                </div>
            @elseif ($manualPayment?->status === \App\Models\HistoryTagihan::STATUS_PAID || (int) $manualPayment?->stat === 1)
                <div class="alert alert-success">
                    Pembayaran telah dikonfirmasi lunas oleh petugas.
                    <a href="{{ route('mahasiswa.home-tagihan-invoice', $manualPayment->code) }}" class="alert-link">Unduh invoice</a>.
                </div>
            @else
                @if ($manualPayment?->status === \App\Models\HistoryTagihan::STATUS_REJECTED)
                    <div class="alert alert-warning">
                        <strong>Konfirmasi sebelumnya ditolak.</strong><br>
                        Catatan petugas: {{ $manualPayment->catatan_verifikasi ?: 'Tidak ada catatan.' }}
                    </div>
                @endif

                <form method="POST" action="{{ route('mahasiswa.home-tagihan-payment', $tagihan->code) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="tanggal-transfer">Tanggal transfer</label>
                            <input id="tanggal-transfer" type="date" name="tanggal_transfer" value="{{ old('tanggal_transfer', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="nama-pengirim">Nama pemilik rekening/pengirim</label>
                            <input id="nama-pengirim" name="nama_pengirim" value="{{ old('nama_pengirim', Auth::guard('mahasiswa')->user()->mhs_name) }}" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="bukti-pembayaran">Bukti pembayaran</label>
                            <input id="bukti-pembayaran" type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.pdf" class="form-control" required>
                            <small class="text-muted">Format JPG, PNG, atau PDF; maksimal 5 MB.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="note">Catatan (opsional)</label>
                            <textarea id="note" name="note" maxlength="255" class="form-control" rows="3">{{ old('note') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Kirim konfirmasi pembayaran untuk diperiksa petugas?')">Kirim Konfirmasi Pembayaran</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
