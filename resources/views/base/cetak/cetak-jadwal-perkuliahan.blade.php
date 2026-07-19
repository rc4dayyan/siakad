<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jadwal Perkuliahan {{ $program?->name }} — {{ $period->name }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: "Times New Roman", serif; font-size: 11px; }
        .actions { margin-bottom: 10px; }
        button { border: 1px solid #333; border-radius: 3px; background: #fff; padding: 7px 12px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #222; padding: 6px 5px; text-align: center; vertical-align: middle; }
        .title { font-family: Arial, sans-serif; font-size: 18px; font-weight: 700; line-height: 1.18; padding: 2px 5px; }
        .heading th { background: #d9d9d9; font-size: 13px; }
        .number { width: 4%; }
        .day { width: 7%; font-weight: 700; }
        .time { width: 10%; font-size: 13px; white-space: nowrap; }
        .course { width: auto; font-size: 13px; }
        .lecturer-code { width: 5%; font-size: 12px; }
        .break { font-family: Arial, sans-serif; font-weight: 700; letter-spacing: .7em; }
        .class-name { display: block; margin-top: 2px; font-family: Arial, sans-serif; font-size: 8px; font-weight: normal; }
        .empty { padding: 22px; font-family: Arial, sans-serif; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
    <table>
        <thead>
            <tr><th colspan="{{ 3 + ($semesters->count() * 2) }}" class="title">JADWAL PERKULIAHAN SEMESTER {{ strtoupper($period->term_label) }}</th></tr>
            <tr><th colspan="{{ 3 + ($semesters->count() * 2) }}" class="title">PROGRAM STUDI {{ strtoupper(trim(($program?->level ? $program->level.' ' : '').($program?->name ?? 'BELUM DIPILIH'))) }}</th></tr>
            <tr><th colspan="{{ 3 + ($semesters->count() * 2) }}" class="title">{{ strtoupper(strip_tags($web?->school_name ?? config('app.name'))) }}</th></tr>
            <tr><th colspan="{{ 3 + ($semesters->count() * 2) }}" class="title">TAHUN AKADEMIK {{ $period->year_start && $period->year_end ? $period->year_start.'/'.$period->year_end : strtoupper($period->name) }}</th></tr>
            <tr class="heading">
                <th class="number">NO</th>
                <th class="day">HARI</th>
                <th class="time">WAKTU</th>
                @foreach($semesters as $semester)
                    <th>SEMESTER {{ [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV'][$semester] ?? $semester }}</th>
                    <th class="lecturer-code">KD</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($days as $dayIndex => $day)
                @foreach($day['rows'] as $rowIndex => $row)
                    <tr>
                        @if($rowIndex === 0)
                            <td rowspan="{{ count($day['rows']) }}" class="number">{{ $dayIndex + 1 }}</td>
                            <td rowspan="{{ count($day['rows']) }}" class="day">{{ $day['label'] }}</td>
                        @endif
                        <td class="time">{{ str_replace(':', '.', $row['start']) }}-{{ str_replace(':', '.', $row['end']) }}</td>
                        @if($row['type'] === 'break')
                            <td colspan="{{ max(1, $semesters->count() * 2) }}" class="break">ISTIRAHAT</td>
                        @else
                            @foreach($semesters as $semester)
                                @php($cellSchedules = $row['cells']->get($semester, collect()))
                                <td class="course">
                                    @foreach($cellSchedules as $schedule)
                                        @if(!$loop->first)<br>@endif
                                        {{ $schedule->penawaranMataKuliah?->masterMataKuliah?->name ?? 'Legacy' }}
                                        @if($cellSchedules->count() > 1)<span class="class-name">{{ $schedule->kelas?->name }}</span>@endif
                                    @endforeach
                                </td>
                                <td class="lecturer-code">{{ $cellSchedules->pluck('dosen.dsn_code')->filter()->join(' / ') }}</td>
                            @endforeach
                        @endif
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="{{ 3 + ($semesters->count() * 2) }}" class="empty">Belum ada jadwal mingguan untuk program studi dan periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
