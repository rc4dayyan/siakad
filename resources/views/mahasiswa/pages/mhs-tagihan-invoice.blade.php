<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $history->code }}</title>
    <style>
        @page { margin: 28px; }
        body { color: #263238; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        .header td { padding: 8px 0; vertical-align: top; }
        .brand { color: #04617b; font-size: 24px; font-weight: bold; }
        .right { text-align: right; }
        .paid { color: #198754; font-size: 18px; font-weight: bold; }
        .summary { background: #04617b; color: #fff; margin: 20px 0; padding: 12px; }
        .items th, .items td { border: 1px solid #cfd8dc; padding: 9px; }
        .items th { background: #e5f2f5; text-align: left; }
        .total td { background: #e5f2f5; font-size: 14px; font-weight: bold; }
        .details { margin-top: 24px; }
        .details td { padding: 5px 0; vertical-align: top; }
        .label { color: #607d8b; width: 145px; }
        .footer { border-top: 1px solid #cfd8dc; color: #607d8b; margin-top: 36px; padding-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">SIAKAD</div>
                <div>Sistem Informasi Akademik</div>
            </td>
            <td class="right">
                <strong>INVOICE #{{ strtoupper($history->code) }}</strong><br>
                Dikonfirmasi: {{ ($history->dibayar_at ?? $history->updated_at)->format('d-m-Y H:i') }}<br>
                <span class="paid">LUNAS</span>
            </td>
        </tr>
    </table>

    <div class="summary">
        Bukti pembayaran terverifikasi untuk tagihan {{ $history->tagihanKuliah?->name ?? $history->tagihan?->name ?? $history->tagihan_code }}.
    </div>

    <table class="items">
        <thead><tr><th>Item</th><th style="width: 60px;">Jumlah</th><th style="width: 170px;">Nominal</th></tr></thead>
        <tbody>
            <tr>
                <td>{{ $history->tagihanKuliah?->name ?? $history->tagihan?->name ?? $history->tagihan_code }}</td>
                <td>1</td>
                <td>Rp {{ number_format($history->nominal ?? $history->tagihanKuliah?->nominal ?? $history->tagihan?->price, 0, ',', '.') }}</td>
            </tr>
            <tr class="total"><td colspan="2">Total</td><td>Rp {{ number_format($history->nominal ?? $history->tagihanKuliah?->nominal ?? $history->tagihan?->price, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <table class="details">
        <tr><td class="label">Nama mahasiswa</td><td>{{ $history->users?->mhs_name ?? '-' }}</td></tr>
        <tr><td class="label">NIM</td><td>{{ $history->users?->mhs_nim ?? '-' }}</td></tr>
        <tr><td class="label">Kode tagihan</td><td>{{ $history->tagihan_code }}</td></tr>
        <tr><td class="label">Tanggal transfer</td><td>{{ $history->tanggal_transfer?->format('d-m-Y') ?? '-' }}</td></tr>
        <tr><td class="label">Nama pengirim</td><td>{{ $history->nama_pengirim ?? '-' }}</td></tr>
        <tr><td class="label">Petugas verifikasi</td><td>{{ $history->ditinjauOleh?->name ?? '-' }}</td></tr>
    </table>

    <div class="footer">Dokumen ini diterbitkan secara elektronik oleh SIAKAD.</div>
</body>
</html>
