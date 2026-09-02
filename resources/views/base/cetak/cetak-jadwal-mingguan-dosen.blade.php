<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jadwal Mingguan Dosen — {{ $lecturer->dsn_name }}</title>
    <style>
        @page { margin: 24px 30px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #19313f; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { padding-bottom: 12px; border-bottom: 3px solid #0b2135; text-align: center; }
        .header h1 { margin: 0 0 4px; color: #0b2135; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #647681; font-size: 9px; }
        .title { margin: 14px 0 11px; text-align: center; }
        .title h2 { margin: 0 0 4px; color: #0b2135; font-size: 16px; text-transform: uppercase; }
        .title p { margin: 0; color: #647681; }
        .identity { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .identity td { width: 33.333%; padding: 7px 9px; border: 1px solid #cfdad7; background: #f7faf9; }
        .identity small, .identity strong { display: block; }
        .identity small { margin-bottom: 3px; color: #647681; font-size: 7px; text-transform: uppercase; }
        .identity strong { color: #0b2135; font-size: 9px; }
        .schedule { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .schedule th, .schedule td { padding: 8px 7px; border: 1px solid #afbfba; vertical-align: middle; }
        .schedule th { color: #fff; background: #0b2135; font-size: 8px; text-align: left; text-transform: uppercase; }
        .schedule tbody tr:nth-child(even) td { background: #f7faf9; }
        .number { width: 35px; text-align: center !important; }
        .time { width: 130px; }
        .course { width: 240px; }
        .credits { width: 45px; text-align: center !important; }
        .schedule strong { display: block; color: #0b2135; font-size: 9px; }
        .schedule small { display: block; margin-top: 3px; color: #647681; font-size: 7px; }
        .day { display: inline-block; margin-bottom: 3px; padding: 3px 6px; color: #0b5f51; background: #e7f4f0; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .empty { padding: 28px !important; color: #647681; text-align: center; }
        .note { margin-top: 10px; padding: 8px 10px; color: #647681; background: #f4f8f7; font-size: 8px; }
        .footer { margin-top: 12px; padding-top: 7px; border-top: 1px solid #cfdad7; color: #84928d; font-size: 7px; text-align: right; }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ strip_tags($web?->school_name ?? config('app.name')) }}</h1>
        @if ($web?->address)<p>{{ $web->address }}</p>@endif
        <p>{{ collect([$web?->school_phone ? 'Telp. '.$web->school_phone : null, $web?->school_email])->filter()->join(' · ') }}</p>
    </header>

    <section class="title">
        <h2>Jadwal Mengajar Mingguan</h2>
        <p>Pola perkuliahan rutin pada {{ $period?->name ?? 'periode akademik aktif' }}</p>
    </section>

    <table class="identity">
        <tr>
            <td><small>Nama Dosen</small><strong>{{ $lecturer->dsn_name }}</strong></td>
            <td><small>NIDN / Identitas</small><strong>{{ $lecturer->dsn_nidn ?? '—' }}</strong></td>
            <td><small>Jumlah Jadwal</small><strong>{{ number_format($schedules->count()) }} jadwal mingguan</strong></td>
        </tr>
    </table>

    <table class="schedule">
        <thead>
            <tr><th class="number">No.</th><th class="time">Hari &amp; Waktu</th><th class="course">Mata Kuliah</th><th>Kelas</th><th>Lokasi</th><th class="credits">SKS</th></tr>
        </thead>
        <tbody>
            @forelse ($schedules as $schedule)
                <tr>
                    <td class="number">{{ $loop->iteration }}</td>
                    <td class="time"><span class="day">{{ $schedule->hari_label }}</span><strong>{{ substr($schedule->mulai, 0, 5) }}–{{ substr($schedule->selesai, 0, 5) }} WIB</strong></td>
                    <td class="course"><strong>{{ $schedule->penawaranMataKuliah?->masterMataKuliah?->name ?? 'Mata kuliah tidak tersedia' }}</strong><small>{{ $schedule->penawaranMataKuliah?->masterMataKuliah?->code ?? $schedule->penawaranMataKuliah?->code ?? $schedule->code }}</small></td>
                    <td><strong>{{ $schedule->kelas?->name ?? 'Tanpa kelas' }}</strong><small>{{ $schedule->kelas?->code }}</small></td>
                    <td><strong>{{ $schedule->ruang?->name ?? 'Tanpa ruangan' }}</strong><small>{{ $schedule->ruang?->gedung?->name }}</small></td>
                    <td class="credits"><strong>{{ $schedule->penawaranMataKuliah?->sks ?? 0 }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Belum ada jadwal mingguan untuk dosen ini pada periode akademik aktif.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="note"><strong>Keterangan:</strong> Dokumen ini menampilkan pola jadwal rutin. Tanggal pertemuan, perubahan jadwal, dan hari libur tetap mengikuti informasi terbaru pada SIAKAD.</div>
    <footer class="footer">Diunduh {{ $printedAt->locale('id')->translatedFormat('d F Y, H:i') }} WIB</footer>
</body>
</html>
