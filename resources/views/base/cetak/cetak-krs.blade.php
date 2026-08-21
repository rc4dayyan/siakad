<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KRS {{ $krs->registrasiMahasiswa->mahasiswa->mhs_nim }} — {{ $krs->registrasiMahasiswa->taka->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        :root { --navy: #0b2135; --green: #117a65; --green-soft: #e7f4f0; --ink: #19313f; --muted: #647681; --line: #cfdad7; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: #e9efed; font-family: Arial, Helvetica, sans-serif; font-size: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .toolbar { position: sticky; z-index: 20; top: 0; display: flex; justify-content: flex-end; gap: 8px; padding: 12px max(18px, calc((100vw - 794px) / 2)); border-bottom: 1px solid #d6dfdc; background: rgba(255, 255, 255, .96); box-shadow: 0 4px 16px rgba(11, 33, 53, .08); }
        .toolbar button { padding: 9px 14px; border: 1px solid #c9d5d1; border-radius: 8px; color: var(--ink); background: #fff; cursor: pointer; font-weight: 700; }
        .toolbar .primary { border-color: var(--green); color: #fff; background: var(--green); }
        .sheet { width: min(794px, calc(100% - 32px)); min-height: 1123px; margin: 22px auto; padding: 32px; background: #fff; box-shadow: 0 16px 45px rgba(11, 33, 53, .14); }
        .letterhead { display: grid; grid-template-columns: 76px 1fr 150px; align-items: center; gap: 16px; padding-bottom: 14px; border-bottom: 3px solid var(--navy); }
        .logo { display: grid; width: 70px; height: 70px; place-items: center; }
        .logo img { max-width: 68px; max-height: 68px; object-fit: contain; }
        .letterhead h1 { margin: 0 0 5px; color: var(--navy); font-size: 18px; line-height: 1.15; text-transform: uppercase; }
        .letterhead p { margin: 2px 0; color: var(--muted); font-size: 8px; line-height: 1.35; }
        .document-label { padding: 11px 13px; border-radius: 9px; color: #fff; background: var(--navy); text-align: right; }
        .document-label small, .document-label strong { display: block; }
        .document-label small { color: rgba(255, 255, 255, .68); font-size: 7px; letter-spacing: .08em; text-transform: uppercase; }
        .document-label strong { margin-top: 4px; font-size: 11px; }
        .title { padding: 18px 0 14px; text-align: center; }
        .title span { color: var(--green); font-size: 8px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .title h2 { margin: 5px 0 3px; color: var(--navy); font-size: 18px; text-transform: uppercase; }
        .title p { margin: 0; color: var(--muted); }
        .identity { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 7px; margin-bottom: 13px; }
        .identity__item { display: grid; grid-template-columns: 96px 1fr; min-height: 38px; align-items: center; padding: 8px 10px; border: 1px solid var(--line); border-radius: 7px; background: #fbfdfc; }
        .identity__item small { color: var(--muted); font-size: 7px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .identity__item strong { overflow-wrap: anywhere; color: var(--navy); font-size: 9px; line-height: 1.3; }
        .status { display: inline-block; padding: 4px 7px; border-radius: 5px; color: #0b5f51; background: var(--green-soft); font-size: 7px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; border: 1px solid #9fb1ac; border-radius: 7px; overflow: hidden; }
        th, td { border-right: 1px solid #afbfba; border-bottom: 1px solid #afbfba; padding: 8px 7px; vertical-align: middle; }
        tr > :last-child { border-right: 0; }
        thead { display: table-header-group; }
        thead th { color: #fff; background: var(--navy); font-size: 7px; letter-spacing: .05em; text-align: left; text-transform: uppercase; }
        tbody tr { break-inside: avoid; page-break-inside: avoid; }
        tbody tr:nth-child(even) td { background: #f7faf9; }
        .number { width: 34px; text-align: center; }
        .code { width: 83px; }
        .class { width: 72px; }
        .credits { width: 48px; text-align: center; }
        td strong { display: block; color: var(--navy); font-size: 9px; line-height: 1.3; }
        td small { display: block; margin-top: 3px; color: var(--muted); font-size: 7.5px; line-height: 1.3; }
        .empty { padding: 30px; color: var(--muted); text-align: center; }
        tfoot th { border-bottom: 0; color: var(--navy); background: #edf5f2; font-size: 8px; }
        tfoot .total-label { text-align: right; text-transform: uppercase; }
        tfoot .total-value { color: #0b5f51; font-size: 11px; text-align: center; }
        .approval { display: grid; grid-template-columns: 1fr 1fr; gap: 70px; margin-top: 25px; padding: 0 22px; break-inside: avoid; page-break-inside: avoid; }
        .signature { min-height: 112px; text-align: center; }
        .signature small, .signature strong { display: block; }
        .signature small { color: var(--muted); font-size: 8px; line-height: 1.45; }
        .signature__space { height: 52px; }
        .signature strong { color: var(--navy); font-size: 9px; text-decoration: underline; text-underline-offset: 3px; }
        .signature em { display: block; margin-top: 4px; color: var(--muted); font-size: 7px; font-style: normal; }
        .note { margin-top: 15px; padding: 9px 11px; border-radius: 7px; color: var(--muted); background: #f4f8f7; font-size: 8px; line-height: 1.45; }
        .footer { display: flex; justify-content: space-between; gap: 20px; margin-top: 13px; padding-top: 7px; border-top: 1px solid var(--line); color: #84928d; font-size: 7px; }
        @media (max-width: 640px) {
            .sheet { width: calc(100% - 16px); margin: 8px auto; padding: 18px; }
            .letterhead { grid-template-columns: 58px 1fr; }
            .logo { width: 52px; height: 52px; }
            .logo img { max-width: 50px; max-height: 50px; }
            .document-label { display: none; }
            .identity { grid-template-columns: 1fr; }
            .approval { gap: 24px; padding: 0; }
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    @php
        $registration = $krs->registrasiMahasiswa;
        $student = $registration->mahasiswa;
        $statusLabels = [
            'draft' => 'Draft',
            'submitted' => 'Diajukan',
            'approved' => 'Disetujui',
            'rejected' => 'Perlu Perbaikan',
            'locked' => 'Dikunci',
        ];
        $statusLabel = $statusLabels[$krs->status] ?? ucfirst($krs->status);
    @endphp

    <div class="toolbar">
        <button type="button" onclick="history.back()">Kembali</button>
        <button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <main class="sheet">
        <header class="letterhead">
            <div class="logo">
                @if ($web?->school_logo)
                    <img src="{{ asset('storage/images/'.$web->school_logo) }}" alt="Logo {{ strip_tags($web->school_name) }}">
                @endif
            </div>
            <div>
                <h1>{{ strip_tags($web?->school_name ?? config('app.name')) }}</h1>
                @if ($web?->address)<p>{{ $web->address }}</p>@endif
                <p>{{ collect([$web?->school_phone ? 'Telp. '.$web->school_phone : null, $web?->school_email, $web?->school_link])->filter()->join(' · ') }}</p>
            </div>
            <div class="document-label"><small>Dokumen Akademik</small><strong>KRS Mahasiswa</strong></div>
        </header>

        <section class="title">
            <span>{{ $registration->taka->name }}</span>
            <h2>Kartu Rencana Studi</h2>
            <p>Rencana pengambilan mata kuliah pada periode akademik berjalan</p>
        </section>

        <section class="identity">
            <div class="identity__item"><small>Nama Mahasiswa</small><strong>{{ $student->mhs_name }}</strong></div>
            <div class="identity__item"><small>NIM</small><strong>{{ $student->mhs_nim }}</strong></div>
            <div class="identity__item"><small>Program Studi</small><strong>{{ $registration->kelas?->pstudi?->name ?? 'Belum tersedia' }}</strong></div>
            <div class="identity__item"><small>Kelas</small><strong>{{ $registration->kelas?->name ?? 'Belum tersedia' }}</strong></div>
            <div class="identity__item"><small>Semester</small><strong>Semester {{ $registration->semester_mahasiswa }}</strong></div>
            <div class="identity__item"><small>Status KRS</small><strong><span class="status">{{ $statusLabel }}</span></strong></div>
        </section>

        <table>
            <thead>
                <tr><th class="number">No.</th><th class="code">Kode</th><th>Mata Kuliah</th><th class="class">Kelas</th><th>Dosen Pengampu</th><th class="credits">SKS</th></tr>
            </thead>
            <tbody>
                @forelse ($krs->items as $item)
                    <tr>
                        <td class="number">{{ $loop->iteration }}</td>
                        <td class="code"><strong>{{ $item->penawaranMataKuliah->code }}</strong></td>
                        <td><strong>{{ $item->penawaranMataKuliah->masterMataKuliah->name }}</strong></td>
                        <td class="class"><strong>{{ $item->penawaranMataKuliah->kelas?->name ?? '—' }}</strong></td>
                        <td><strong>{{ $item->penawaranMataKuliah->dosenUtama?->dsn_name ?? 'Belum ditentukan' }}</strong></td>
                        <td class="credits"><strong>{{ $item->sks }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Belum ada mata kuliah dalam KRS ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><th colspan="5" class="total-label">Total Beban Studi</th><th class="total-value">{{ $krs->total_sks }}</th></tr></tfoot>
        </table>

        <section class="approval">
            <div class="signature">
                <small>Mahasiswa,</small>
                <div class="signature__space"></div>
                <strong>{{ $student->mhs_name }}</strong>
                <em>NIM {{ $student->mhs_nim }}</em>
            </div>
            <div class="signature">
                <small>Dosen Wali,</small>
                <div class="signature__space"></div>
                <strong>{{ $registration->dosenWali?->dsn_name ?? 'Belum ditentukan' }}</strong>
                @if ($krs->diputuskan_at)<em>Disetujui {{ $krs->diputuskan_at->locale('id')->translatedFormat('d F Y') }}</em>@endif
            </div>
        </section>

        <div class="note"><strong>Keterangan:</strong> KRS ini berstatus <strong>{{ $statusLabel }}</strong>. Keabsahan pengambilan mata kuliah mengikuti status dan pembaruan terbaru yang tercatat pada SIAKAD.</div>
        <footer class="footer"><span>{{ strip_tags($web?->school_name ?? config('app.name')) }} · Sistem Informasi Akademik</span><span>Dicetak {{ $printedAt->locale('id')->translatedFormat('d F Y, H:i') }} WIB</span></footer>
    </main>
</body>
</html>
