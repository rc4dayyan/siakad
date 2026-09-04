<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Mingguan {{ $student->mhs_nim }} — {{ $period?->name }}</title>
    <style>
        @page { size: A4 landscape; margin: 9mm; }
        :root { --navy: #0b2135; --green: #117a65; --green-soft: #e7f4f0; --ink: #19313f; --muted: #647681; --line: #cfdad7; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: #e9efed; font-family: Arial, Helvetica, sans-serif; font-size: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .toolbar { position: sticky; z-index: 20; top: 0; display: flex; justify-content: flex-end; gap: 8px; padding: 12px max(18px, calc((100vw - 1060px) / 2)); border-bottom: 1px solid #d6dfdc; background: rgba(255, 255, 255, .96); box-shadow: 0 4px 16px rgba(11, 33, 53, .08); }
        .toolbar button { padding: 9px 14px; border: 1px solid #c9d5d1; border-radius: 8px; color: var(--ink); background: #fff; cursor: pointer; font-weight: 700; }
        .toolbar .primary { border-color: var(--green); color: #fff; background: var(--green); }
        .sheet { width: min(1060px, calc(100% - 32px)); min-height: 750px; margin: 22px auto; padding: 28px; background: #fff; box-shadow: 0 16px 45px rgba(11, 33, 53, .14); }
        .letterhead { display: grid; grid-template-columns: 76px 1fr 190px; align-items: center; gap: 16px; padding-bottom: 14px; border-bottom: 3px solid var(--navy); }
        .logo { display: grid; width: 70px; height: 70px; place-items: center; }
        .logo img { max-width: 68px; max-height: 68px; object-fit: contain; }
        .letterhead h1 { margin: 0 0 5px; color: var(--navy); font-size: 19px; line-height: 1.15; text-transform: uppercase; }
        .letterhead p { margin: 2px 0; color: var(--muted); font-size: 8px; line-height: 1.35; }
        .document-label { padding: 11px 13px; border-radius: 9px; color: #fff; background: var(--navy); text-align: right; }
        .document-label small, .document-label strong { display: block; }
        .document-label small { color: rgba(255, 255, 255, .68); font-size: 7px; letter-spacing: .08em; text-transform: uppercase; }
        .document-label strong { margin-top: 4px; font-size: 11px; }
        .title { padding: 17px 0 13px; text-align: center; }
        .title span { color: var(--green); font-size: 8px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .title h2 { margin: 5px 0 3px; color: var(--navy); font-size: 18px; text-transform: uppercase; }
        .title p { margin: 0; color: var(--muted); }
        .identity { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 7px; margin-bottom: 13px; }
        .identity__item { min-height: 48px; padding: 9px 11px; border: 1px solid var(--line); border-radius: 8px; background: #fbfdfc; }
        .identity__item small, .identity__item strong { display: block; }
        .identity__item small { margin-bottom: 4px; color: var(--muted); font-size: 7px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .identity__item strong { overflow: hidden; color: var(--navy); font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; border: 1px solid #9fb1ac; border-radius: 7px; overflow: hidden; }
        th, td { border-right: 1px solid #afbfba; border-bottom: 1px solid #afbfba; padding: 8px 7px; vertical-align: middle; }
        tr > :last-child { border-right: 0; }
        tbody tr:last-child > td { border-bottom: 0; }
        thead { display: table-header-group; }
        thead th { color: #fff; background: var(--navy); font-size: 7px; letter-spacing: .05em; text-align: left; text-transform: uppercase; }
        tbody tr { break-inside: avoid; page-break-inside: avoid; }
        tbody tr:nth-child(even) td { background: #f7faf9; }
        .number { width: 36px; text-align: center; }
        .time { width: 145px; }
        .course { width: 245px; }
        .lecturer { width: 180px; }
        .credits { width: 55px; text-align: center; }
        td strong { display: block; color: var(--navy); font-size: 9px; line-height: 1.3; }
        td small { display: block; margin-top: 3px; color: var(--muted); font-size: 7.5px; line-height: 1.3; }
        .day { display: inline-block; min-width: 58px; margin-bottom: 4px; padding: 4px 7px; border-radius: 5px; color: #0b5f51; background: var(--green-soft); font-size: 7px; font-weight: 800; text-align: center; text-transform: uppercase; }
        .day-group { margin-top: 13px; }
        .day-group:first-of-type { margin-top: 0; }
        .day-group__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding: 8px 11px; border-left: 4px solid var(--green); border-radius: 6px; color: var(--navy); background: var(--green-soft); break-after: avoid; page-break-after: avoid; }
        .day-group__header h3 { margin: 0; font-size: 11px; text-transform: uppercase; }
        .day-group__header span { color: #49665f; font-size: 7px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .empty { padding: 32px; color: var(--muted); text-align: center; }
        .note { margin-top: 12px; padding: 9px 11px; border-radius: 7px; color: var(--muted); background: #f4f8f7; font-size: 8px; line-height: 1.4; }
        .footer { display: flex; justify-content: space-between; margin-top: 13px; padding-top: 7px; border-top: 1px solid var(--line); color: #84928d; font-size: 7px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="history.back()">Kembali</button><button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button></div>
    <main class="sheet">
        <header class="letterhead">
            <div class="logo">@if ($web?->school_logo)<img src="{{ asset('storage/images/'.$web->school_logo) }}" alt="Logo {{ strip_tags($web->school_name) }}">@endif</div>
            <div><h1>{{ strip_tags($web?->school_name ?? config('app.name')) }}</h1>@if ($web?->address)<p>{{ $web->address }}</p>@endif<p>{{ collect([$web?->school_phone ? 'Telp. '.$web->school_phone : null, $web?->school_email, $web?->school_link])->filter()->join(' · ') }}</p></div>
            <div class="document-label"><small>Dokumen Mahasiswa</small><strong>Jadwal Mingguan</strong></div>
        </header>

        <section class="title"><span>{{ $period?->name ?? 'Periode belum tersedia' }}</span><h2>Jadwal Kuliah Mingguan</h2><p>Pola perkuliahan rutin berdasarkan KRS yang telah disetujui</p></section>

        <section class="identity">
            <div class="identity__item"><small>Nama Mahasiswa</small><strong>{{ $student->mhs_name }}</strong></div>
            <div class="identity__item"><small>NIM</small><strong>{{ $student->mhs_nim }}</strong></div>
            <div class="identity__item"><small>Program Studi</small><strong>{{ $academicClass?->pstudi?->name ?? 'Belum tersedia' }}</strong></div>
            <div class="identity__item"><small>Kelas · Jumlah Jadwal</small><strong>{{ $academicClass?->name ?? '—' }} · {{ number_format($schedules->count()) }} jadwal</strong></div>
        </section>

        @php $scheduleGroups = $schedules->groupBy('hari'); @endphp
        @forelse ($scheduleGroups as $day => $daySchedules)
            <section class="day-group">
                <header class="day-group__header">
                    <h3>{{ $daySchedules->first()->hari_label }}</h3>
                    <span>{{ $daySchedules->count() }} mata kuliah</span>
                </header>
                <table>
                    <thead><tr><th class="number">No.</th><th class="time">Waktu</th><th class="course">Mata Kuliah</th><th class="lecturer">Nama Dosen</th><th>Kelas &amp; Lokasi</th><th class="credits">SKS</th></tr></thead>
                    <tbody>
                        @foreach ($daySchedules as $schedule)
                            <tr>
                                <td class="number">{{ $loop->iteration }}</td>
                                <td class="time"><strong>{{ substr($schedule->mulai, 0, 5) }}–{{ substr($schedule->selesai, 0, 5) }} WIB</strong></td>
                                <td class="course"><strong>{{ $schedule->penawaranMataKuliah?->masterMataKuliah?->name ?? 'Mata kuliah tidak tersedia' }}</strong><small>{{ $schedule->penawaranMataKuliah?->masterMataKuliah?->code ?? $schedule->penawaranMataKuliah?->code ?? $schedule->code }}</small></td>
                                <td class="lecturer"><strong>{{ $schedule->dosen?->dsn_name ?? 'Belum ditentukan' }}</strong></td>
                                <td><strong>{{ $schedule->kelas?->name ?? 'Tanpa kelas' }}</strong><small>{{ $schedule->ruang?->name ?? 'Tanpa ruangan' }}{{ $schedule->ruang?->gedung?->name ? ' · '.$schedule->ruang->gedung->name : '' }}</small></td>
                                <td class="credits"><strong>{{ $schedule->penawaranMataKuliah?->sks ?? 0 }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @empty
            <div class="empty">Belum ada jadwal mingguan dari KRS yang telah disetujui.</div>
        @endforelse

        <div class="note"><strong>Keterangan:</strong> Jadwal mingguan ini merupakan pola rutin. Tanggal pertemuan, perubahan jadwal, dan hari libur tetap mengikuti informasi terbaru pada SIAKAD.</div>
        <footer class="footer"><span>{{ strip_tags($web?->school_name ?? config('app.name')) }} · Sistem Informasi Akademik</span><span>Dicetak {{ $printedAt->locale('id')->translatedFormat('d F Y, H:i') }} WIB</span></footer>
    </main>
</body>
</html>
