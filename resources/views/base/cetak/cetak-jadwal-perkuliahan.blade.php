<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Perkuliahan {{ $program?->name }} — {{ $period->name }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        :root { --navy: #0b2135; --green: #117a65; --green-soft: #e7f4f0; --ink: #19313f; --muted: #647681; --line: #cfdad7; --surface: #fff; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: #e9efed; font-family: Arial, Helvetica, sans-serif; font-size: 9px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .print-toolbar { position: sticky; z-index: 20; top: 0; display: flex; justify-content: flex-end; gap: 8px; padding: 12px max(18px, calc((100vw - 1120px) / 2)); border-bottom: 1px solid #d6dfdc; background: rgba(255, 255, 255, .96); box-shadow: 0 4px 16px rgba(11, 33, 53, .08); }
        .print-toolbar button { padding: 9px 14px; border: 1px solid #c9d5d1; border-radius: 8px; color: var(--ink); background: #fff; cursor: pointer; font-weight: 700; }
        .print-toolbar .primary { border-color: var(--green); color: #fff; background: var(--green); }
        .sheet { width: min(1120px, calc(100% - 32px)); min-height: 760px; margin: 22px auto; padding: 28px; background: var(--surface); box-shadow: 0 16px 45px rgba(11, 33, 53, .14); }
        .letterhead { display: grid; grid-template-columns: 76px 1fr 190px; align-items: center; gap: 16px; padding-bottom: 14px; border-bottom: 3px solid var(--navy); }
        .letterhead__logo { display: grid; width: 70px; height: 70px; place-items: center; }
        .letterhead__logo img { max-width: 68px; max-height: 68px; object-fit: contain; }
        .letterhead__identity h1 { margin: 0 0 5px; color: var(--navy); font-size: 19px; line-height: 1.15; text-transform: uppercase; }
        .letterhead__identity p { margin: 2px 0; color: var(--muted); font-size: 8px; line-height: 1.35; }
        .letterhead__document { padding: 11px 13px; border-radius: 9px; color: #fff; background: var(--navy); text-align: right; }
        .letterhead__document small, .letterhead__document strong { display: block; }
        .letterhead__document small { color: rgba(255, 255, 255, .68); font-size: 7px; letter-spacing: .08em; text-transform: uppercase; }
        .letterhead__document strong { margin-top: 4px; font-size: 11px; }
        .document-title { padding: 17px 0 13px; text-align: center; }
        .document-title span { color: var(--green); font-size: 8px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .document-title h2 { margin: 5px 0 3px; color: var(--navy); font-size: 18px; text-transform: uppercase; }
        .document-title p { margin: 0; color: var(--muted); font-size: 9px; }
        .summary { display: grid; grid-template-columns: 1.5fr repeat(4, .65fr); gap: 7px; margin-bottom: 12px; }
        .summary__item { min-height: 48px; padding: 9px 11px; border: 1px solid var(--line); border-radius: 8px; background: #fbfdfc; }
        .summary__item small, .summary__item strong { display: block; }
        .summary__item small { margin-bottom: 4px; color: var(--muted); font-size: 7px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .summary__item strong { overflow: hidden; color: var(--navy); font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
        .schedule-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; border: 1px solid #9fb1ac; border-radius: 7px; overflow: hidden; }
        .schedule-table th, .schedule-table td { border-right: 1px solid #afbfba; border-bottom: 1px solid #afbfba; padding: 6px 5px; text-align: center; vertical-align: middle; }
        .schedule-table tr > :last-child { border-right: 0; }
        .schedule-table tbody tr:last-child > td { border-bottom: 0; }
        .schedule-table thead { display: table-header-group; }
        .schedule-table thead th { color: #fff; background: var(--navy); font-size: 7px; letter-spacing: .04em; text-transform: uppercase; }
        .schedule-table tbody tr { break-inside: avoid; page-break-inside: avoid; }
        .schedule-table tbody tr:nth-child(even) td { background: #f7faf9; }
        .schedule-table .number { width: 30px; font-weight: 700; }
        .schedule-table .day { width: 58px; color: var(--navy); background: var(--green-soft) !important; font-weight: 800; letter-spacing: .04em; }
        .schedule-table .time { width: 67px; font-weight: 700; white-space: nowrap; }
        .schedule-table .lecturer-name { width: 92px; font-size: 7px; font-weight: 700; line-height: 1.25; }
        .course-item + .course-item { margin-top: 5px; padding-top: 5px; border-top: 1px dashed #c3cecb; }
        .course-item strong { display: block; color: var(--navy); font-size: 8px; line-height: 1.25; }
        .course-item span { display: block; margin-top: 2px; color: var(--muted); font-size: 6.8px; line-height: 1.2; }
        .break { color: #786128; background: #fbf5e7 !important; font-weight: 800; letter-spacing: .25em; }
        .empty { padding: 30px !important; color: var(--muted); }
        .print-note { margin-top: 14px; padding: 10px 12px; border-radius: 8px; color: var(--muted); background: #f4f8f7; line-height: 1.45; }
        .print-note strong { color: var(--navy); }
        .document-footer { display: flex; justify-content: space-between; margin-top: 14px; padding-top: 7px; border-top: 1px solid var(--line); color: #84928d; font-size: 7px; }
        .is-dense .schedule-table th, .is-dense .schedule-table td { padding: 4px 3px; }
        .is-dense .course-item strong { font-size: 7px; }
        .is-dense .course-item span { font-size: 6px; }
        @media print {
            body { background: #fff; }
            .print-toolbar { display: none; }
            .sheet { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="history.back()">Kembali</button>
        <button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <main class="sheet {{ $semesters->count() > 6 ? 'is-dense' : '' }}">
        <header class="letterhead">
            <div class="letterhead__logo">
                @if ($web?->school_logo)
                    <img src="{{ asset('storage/images/'.$web->school_logo) }}" alt="Logo {{ strip_tags($web->school_name) }}">
                @endif
            </div>
            <div class="letterhead__identity">
                <h1>{{ strip_tags($web?->school_name ?? config('app.name')) }}</h1>
                @if ($web?->address)<p>{{ $web->address }}</p>@endif
                <p>{{ collect([$web?->school_phone ? 'Telp. '.$web->school_phone : null, $web?->school_email, $web?->school_link])->filter()->join(' · ') }}</p>
            </div>
            <div class="letterhead__document">
                <small>Dokumen Akademik</small>
                <strong>Jadwal Perkuliahan</strong>
            </div>
        </header>

        <section class="document-title">
            <span>Semester {{ strtoupper($period->term_label) }}</span>
            <h2>Jadwal Perkuliahan</h2>
            <p>Program Studi {{ trim(($program?->level ? $program->level.' ' : '').($program?->name ?? 'Belum dipilih')) }}</p>
        </section>

        <section class="summary">
            <div class="summary__item"><small>Periode Akademik</small><strong>{{ $period->name }}</strong></div>
            <div class="summary__item"><small>Total Jadwal</small><strong>{{ number_format($summary['schedules']) }}</strong></div>
            <div class="summary__item"><small>Kelas</small><strong>{{ number_format($summary['classes']) }}</strong></div>
            <div class="summary__item"><small>Dosen</small><strong>{{ number_format($summary['lecturers']) }}</strong></div>
            <div class="summary__item"><small>Ruang</small><strong>{{ number_format($summary['rooms']) }}</strong></div>
        </section>

        <table class="schedule-table">
            <thead>
                <tr>
                    <th class="number" rowspan="2">No.</th>
                    <th class="day" rowspan="2">Hari</th>
                    <th class="time" rowspan="2">Waktu</th>
                    @foreach ($semesters as $semester)
                        <th colspan="2">Semester {{ [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV'][$semester] ?? $semester }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($semesters as $semester)
                        <th>Mata Kuliah</th>
                        <th class="lecturer-name">Nama Dosen</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($days as $dayIndex => $day)
                    @foreach ($day['rows'] as $rowIndex => $row)
                        <tr>
                            @if ($rowIndex === 0)
                                <td rowspan="{{ count($day['rows']) }}" class="number">{{ $dayIndex + 1 }}</td>
                                <td rowspan="{{ count($day['rows']) }}" class="day">{{ $day['label'] }}</td>
                            @endif
                            <td class="time">{{ str_replace(':', '.', $row['start']) }}–{{ str_replace(':', '.', $row['end']) }}</td>
                            @if ($row['type'] === 'break')
                                <td colspan="{{ max(1, $semesters->count() * 2) }}" class="break">ISTIRAHAT</td>
                            @else
                                @foreach ($semesters as $semester)
                                    @php($cellSchedules = $row['cells']->get($semester, collect()))
                                    <td>
                                        @foreach ($cellSchedules as $schedule)
                                            <div class="course-item">
                                                <strong>{{ $schedule->penawaranMataKuliah?->masterMataKuliah?->name ?? 'Mata Kuliah Legacy' }}</strong>
                                                <span>
                                                    {{ $schedule->penawaranMataKuliah?->masterMataKuliah?->code ?? $schedule->penawaranMataKuliah?->code ?? 'Tanpa kode' }} ·
                                                    {{ $schedule->kelas?->name ?? 'Tanpa kelas' }} ·
                                                    {{ $schedule->ruang?->name ?? 'Ruang belum ditentukan' }}
                                                    @if ($schedule->ruang?->gedung?->name) ({{ $schedule->ruang->gedung->name }}) @endif ·
                                                    {{ $schedule->sks ?? $schedule->penawaranMataKuliah?->sks ?? 0 }} SKS
                                                </span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="lecturer-name">
                                        @forelse ($cellSchedules as $schedule)
                                            @if (! $loop->first)<br><br>@endif{{ $schedule->dosen?->dsn_name ?? 'Dosen belum ditentukan' }}
                                        @empty
                                            —
                                        @endforelse
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="{{ 3 + ($semesters->count() * 2) }}" class="empty">Belum ada jadwal mingguan untuk program studi dan periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        <aside class="print-note">
            <strong>Keterangan:</strong> Jadwal disusun berdasarkan periode akademik dan program studi terpilih. Perubahan jadwal setelah dokumen dicetak mengikuti pembaruan pada SIAKAD.
        </aside>

        <footer class="document-footer">
            <span>{{ strip_tags($web?->school_name ?? config('app.name')) }} · Sistem Informasi Akademik</span>
            <span>Dicetak {{ $printedAt->locale('id')->translatedFormat('d F Y, H:i') }} WIB</span>
        </footer>
    </main>
</body>
</html>
