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
        .schedule-table { color: #000; width: 100%; border-collapse: collapse; table-layout: fixed; }
        .schedule-table th, .schedule-table td { height: 36px; border: 1px solid #222; padding: 6px 8px; text-align: center; vertical-align: middle; overflow-wrap: anywhere; }
        .schedule-table th { font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .schedule-table thead { display: table-header-group; }
        .schedule-table tbody { break-inside: avoid; page-break-inside: avoid; }
        .schedule-table tr { break-inside: avoid; page-break-inside: avoid; }
        .number { width: 5%; }
        .day { width: 6.5%; }
        .time { width: 12%; white-space: nowrap; }
        .lecturer-code { width: 4.5%; }
        .schedule-table .course { text-align: left; }
        .course-item + .course-item { margin-top: 5px; padding-top: 5px; border-top: 1px dotted #777; }
        .break-row .time, .break-row .break { font-size: 13px; font-weight: 800; font-style: italic; }
        .empty { height: 70px !important; }
        .is-dense .schedule-table th, .is-dense .schedule-table td { padding: 5px 3px; font-size: 9px; }
        .print-note { margin-top: 14px; padding: 10px 12px; border-radius: 8px; color: var(--muted); background: #f4f8f7; line-height: 1.45; }
        .print-note strong { color: var(--navy); }
        .document-footer { display: flex; justify-content: space-between; margin-top: 14px; padding-top: 7px; border-top: 1px solid var(--line); color: #84928d; font-size: 7px; }
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
            <colgroup>
                <col class="number"><col class="day"><col class="time">
                @foreach ($semesters as $semester)
                    <col><col class="lecturer-code">
                @endforeach
            </colgroup>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Hari</th>
                    <th>Waktu</th>
                    @foreach ($semesters as $semester)
                        <th>Semester {{ [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV'][$semester] ?? $semester }}</th>
                        <th>KD</th>
                    @endforeach
                </tr>
            </thead>
            @forelse ($days as $dayIndex => $day)
                <tbody>
                    @foreach ($day['rows'] as $rowIndex => $row)
                        <tr class="{{ $row['type'] === 'break' ? 'break-row' : '' }}">
                            @if ($rowIndex === 0)
                                <td rowspan="{{ count($day['rows']) }}">{{ $dayIndex + 1 }}</td>
                                <td rowspan="{{ count($day['rows']) }}">{{ $day['label'] }}</td>
                            @endif
                            <td class="time">{{ str_replace(':', '.', $row['start']) }} – {{ str_replace(':', '.', $row['end']) }}</td>
                            @foreach ($semesters as $semester)
                                @if ($row['skip'][$semester] ?? false)
                                    @continue
                                @endif
                                @if ($row['type'] === 'break')
                                    <td rowspan="{{ $row['spans'][$semester] }}" class="break">ISTIRAHAT</td>
                                    <td rowspan="{{ $row['spans'][$semester] }}">-</td>
                                @else
                                    @php($cellSchedules = $row['cells']->get($semester, collect()))
                                    <td rowspan="{{ $row['spans'][$semester] }}" class="course">
                                        @foreach ($cellSchedules as $schedule)
                                            <div class="course-item">{{ $schedule->penawaranMataKuliah?->masterMataKuliah?->name ?? 'Mata Kuliah Legacy' }}</div>
                                        @endforeach
                                    </td>
                                    <td rowspan="{{ $row['spans'][$semester] }}">
                                        @forelse ($cellSchedules as $schedule)
                                            <div class="course-item">{{ $schedule->dosen?->dsn_code ?: '-' }}</div>
                                        @empty
                                            -
                                        @endforelse
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            @empty
                <tbody><tr><td colspan="{{ 3 + ($semesters->count() * 2) }}" class="empty">Belum ada jadwal mingguan untuk program studi dan periode ini.</td></tr></tbody>
            @endforelse
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
