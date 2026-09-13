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
                            @if ($semester = $pair->values()->get($column))
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
