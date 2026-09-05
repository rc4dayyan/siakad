<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Transkrip Nilai Sementara {{ $student->mhs_nim }}</title>
    <style>
        @page { size: A4 landscape; margin: 9mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: "Times New Roman", Times, serif; font-size: 10px; }
        .title { margin: 0 0 7px; text-align: center; font-size: 15px; font-weight: 700; }
        .identity { width: 100%; margin-bottom: 8px; border-collapse: collapse; }
        .identity td { padding: 1px 3px; vertical-align: top; }
        .identity .label { width: 70px; font-weight: 700; }
        .identity .separator { width: 7px; }
        .semester-layout { width: 100%; border-collapse: separate; border-spacing: 0 8px; table-layout: fixed; }
        .semester-layout > tbody > tr > td { width: 50%; vertical-align: top; }
        .semester-layout > tbody > tr > td:first-child { padding-right: 5px; }
        .semester-layout > tbody > tr > td:last-child { padding-left: 5px; }
        .semester { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
        .semester th, .semester td { border: 1px solid #222; padding: 2px 4px; line-height: 1.15; }
        .semester .semester-title { background: #d9d9d9; text-align: center; font-size: 11px; }
        .semester .number { width: 30px; text-align: center; }
        .semester .course { width: auto; }
        .semester .credit { width: 34px; text-align: center; }
        .semester .grade { width: 38px; text-align: center; }
        .semester .ak { width: 38px; text-align: center; }
        .semester .summary-label { font-weight: 700; }
        .semester .summary-value { font-weight: 700; }
        .empty-column { min-height: 30px; }
        .footer-layout { width: 100%; margin-top: 12px; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
        .footer-layout td { width: 50%; vertical-align: top; }
        .recap { width: 100%; border-collapse: collapse; }
        .recap td { padding: 2px 3px; }
        .recap .label { width: 55px; font-weight: 700; }
        .note { font-style: italic; }
        .signature { padding-left: 45px; text-align: left; }
        .signature-space { height: 47px; }
        .signature-name { font-weight: 700; text-decoration: underline; }
        .muted { color: #444; }
    </style>
</head>
<body>
    <h1 class="title">TRANSKRIP NILAI SEMENTARA PROGRAM STRATA SATU (S-1)</h1>

    <table class="identity">
        <tr>
            <td class="label">Nama</td><td class="separator">:</td><td><strong>{{ strtoupper($student->mhs_name) }}</strong></td>
            <td class="label">Program Studi</td><td class="separator">:</td><td><strong>{{ $program?->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">No. Pokok</td><td class="separator">:</td><td><strong>{{ $student->mhs_nim }}</strong></td>
            <td class="label">Jenjang</td><td class="separator">:</td><td><strong>{{ $program?->level ?? 'S-1' }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIRM</td><td class="separator">:</td><td><strong>{{ $student->mhs_code ?: '-' }}</strong></td>
            <td></td><td></td><td></td>
        </tr>
    </table>

    <table class="semester-layout">
        <tbody>
            @forelse ($semesters->chunk(2) as $pair)
                <tr>
                    @foreach ([0, 1] as $column)
                        <td>
                            @if ($semester = $pair->get($column))
                                <table class="semester">
                                    <thead>
                                        <tr><th colspan="5" class="semester-title">SEMESTER {{ $semester['number'] }}</th></tr>
                                        <tr>
                                            <th class="number">No</th>
                                            <th class="course">Nama Mata Kuliah</th>
                                            <th class="credit">SKS</th>
                                            <th class="grade">Nilai</th>
                                            <th class="ak">AK</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($semester['courses'] as $course)
                                            <tr>
                                                <td class="number">{{ $loop->iteration }}</td>
                                                <td>{{ $course['mata_kuliah'] }}</td>
                                                <td class="credit">{{ $course['sks'] ?? '-' }}</td>
                                                <td class="grade">{{ $course['nilai'] ?: '-' }}</td>
                                                <td class="ak">{{ $course['angka_kredit'] === null ? '-' : number_format($course['angka_kredit'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="2" class="summary-label">IP :</td>
                                            <td class="credit">{{ $semester['credits'] }}</td>
                                            <td colspan="2" class="summary-value">{{ $semester['ips'] === null ? '-' : number_format($semester['ips'], 2, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            @else
                                <div class="empty-column"></div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="2" class="muted">Belum ada mata kuliah yang tercatat pada transkrip.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer-layout">
        <tr>
            <td>
                <table class="recap">
                    <tr><td class="label">Total SKS</td><td>:</td><td><strong>{{ $totalCredits }}</strong></td></tr>
                    <tr><td class="label">IPK</td><td>:</td><td><strong>{{ $ipk === null ? '-' : number_format($ipk, 2, ',', '.') }}</strong></td></tr>
                    @if ($hasIncompleteGrade)
                        <tr><td></td><td></td><td class="note">Nilai belum keluar/mata kuliah belum dinilai.</td></tr>
                    @endif
                </table>
            </td>
            <td class="signature">
                Majalengka, {{ $printedAt->locale('id')->translatedFormat('d F Y') }}<br>
                Ketua Prodi {{ $program?->name ?? '' }}
                <div class="signature-space"></div>
                <span class="signature-name">{{ $program?->head?->dsn_name ?? '........................................' }}</span><br>
                NIDN. {{ $program?->head?->dsn_nidn ?? '-' }}
            </td>
        </tr>
    </table>
</body>
</html>
