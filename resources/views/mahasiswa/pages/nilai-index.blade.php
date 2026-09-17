@extends('base.base-dash-index')

@section('title', 'Nilai Kuliah - SIAKAD')
@section('menu', 'Akademik')
@section('submenu', 'Nilai Kuliah')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Tinjau hasil studi dan status penilaian mata kuliah Anda')

@section('custom-css')
    @include('base.components.student-records-styles')
@endsection

@section('content')
<section class="section student-records">
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-blue"><i class="fas fa-book-open"></i></span><div><small>Mata kuliah</small><strong>{{ number_format($gradeSummary['courses']) }}</strong><span>Periode {{ $period?->name ?? '-' }}</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon"><i class="fas fa-layer-group"></i></span><div><small>Total SKS</small><strong>{{ number_format($gradeSummary['credits']) }}</strong><span>Beban studi tercatat</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon"><i class="fas fa-circle-check"></i></span><div><small>Sudah dinilai</small><strong>{{ number_format($gradeSummary['graded']) }}</strong><span>Nilai telah diterbitkan</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-gold"><i class="fas fa-hourglass-half"></i></span><div><small>Belum dinilai</small><strong>{{ number_format($gradeSummary['ungraded']) }}</strong><span>Menunggu dosen</span></div></div></div>
    </div>

    <div class="card record-panel mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><h5 class="mb-1">Hasil Studi Periode Aktif</h5><small class="text-muted">{{ $period?->name ?? 'Tidak ada periode akademik aktif' }}</small></div>
            <div class="d-flex flex-wrap gap-2"><a href="{{ route('mahasiswa.akademik.nilai-index', ['transkrip' => 1]) }}" class="btn btn-outline-primary"><i class="fas fa-table-list me-1"></i>Lihat transkrip</a><a href="{{ route('mahasiswa.akademik.nilai-transkrip-print') }}" class="btn btn-danger" target="_blank" rel="noopener"><i class="fas fa-file-pdf me-1"></i>Cetak transkrip</a></div>
        </div>
        <div class="card-body">
            <div class="record-filter">
                <h6 class="record-filter__title"><i class="fas fa-filter me-2"></i>Filter nilai</h6>
                <p class="record-filter__description">Cari mata kuliah atau dosen, lalu persempit berdasarkan publikasi dan nilai huruf.</p>
                <form method="GET" action="{{ route('mahasiswa.akademik.nilai-index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-5"><label for="grade-search" class="form-label">Pencarian</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="search" name="q" id="grade-search" class="form-control" value="{{ $filters['q'] }}" placeholder="Kode, mata kuliah, dosen, atau kelas" maxlength="100"></div></div>
                    <div class="col-lg-3"><label for="grade-status" class="form-label">Status penilaian</label><select name="status" id="grade-status" class="form-select"><option value="">Semua status</option><option value="sudah_dinilai" @selected($filters['status'] === 'sudah_dinilai')>Sudah dinilai</option><option value="belum_dinilai" @selected($filters['status'] === 'belum_dinilai')>Belum dinilai</option></select></div>
                    <div class="col-lg-2"><label for="grade-letter" class="form-label">Nilai huruf</label><select name="nilai" id="grade-letter" class="form-select"><option value="">Semua nilai</option>@foreach (['A', 'B', 'C', 'D', 'E'] as $grade)<option value="{{ $grade }}" @selected($filters['nilai'] === $grade)>{{ $grade }}</option>@endforeach</select></div>
                    <div class="col-lg-2"><div class="d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-filter me-1"></i>Terapkan</button>@if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())<a href="{{ route('mahasiswa.akademik.nilai-index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>@endif</div></div>
                </form>
            </div>

            <div class="table-responsive"><table class="table record-table"><thead><tr><th>Mata kuliah</th><th>Kelas</th><th>SKS</th><th>Dosen</th><th>Status KRS</th><th class="text-center">Nilai</th></tr></thead><tbody>
                @forelse ($nilai as $item)
                    <tr>
                        <td class="record-primary" data-label="Mata kuliah"><span class="record-title">{{ $item['mata_kuliah'] }}</span><span class="record-subtitle">{{ $item['kode_mata_kuliah'] ?: 'Kode belum tersedia' }}</span></td>
                        <td data-label="Kelas">{{ $item['kelas'] }}</td>
                        <td data-label="SKS">{{ $item['sks'] ?? '-' }}</td>
                        <td data-label="Dosen">{{ $item['dosen'] }}</td>
                        <td data-label="Status KRS"><span class="record-badge {{ ($item['status'] ?? null) === 'KRS Disetujui' ? 'is-success' : 'is-muted' }}">{{ $item['status'] ?? '-' }}</span></td>
                        <td data-label="Nilai" class="text-center">@if (filled($item['nilai']))<span class="record-badge is-success px-3">{{ strtoupper($item['nilai']) }}</span>@else<span class="record-badge is-warning">Belum dinilai</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="record-empty-cell p-0"><div class="record-empty"><i class="fas fa-chart-column"></i><strong>Nilai tidak ditemukan</strong><span>Coba ubah filter atau hasil studi belum tersedia.</span></div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="card record-panel">
        <div class="card-header"><h5 class="mb-1">Ringkasan Hasil Studi</h5><small class="text-muted">Indeks prestasi dan progres penilaian per semester</small></div>
        <div class="card-body pt-2"><div class="table-responsive"><table class="table record-table"><thead><tr><th>Periode</th><th>Semester</th><th>IPS</th><th>IPK</th><th>Progres tugas</th></tr></thead><tbody>
            @forelse ($hasilStudi as $hasil)
                <tr><td class="record-primary" data-label="Periode"><span class="record-title">{{ $hasil->taka?->name ?? '-' }}</span></td><td data-label="Semester">{{ $hasil->smt_id }}</td><td data-label="IPS"><span class="record-badge is-info">{{ $hasil->nilai_ips ?? '-' }}</span></td><td data-label="IPK"><span class="record-badge is-success">{{ $hasil->nilai_ipk ?? '-' }}</span></td><td data-label="Progres tugas">{{ $hasil->score_tugas }} / {{ $hasil->max_tugas }} tugas</td></tr>
            @empty
                <tr><td colspan="5" class="record-empty-cell p-0"><div class="record-empty"><i class="fas fa-graduation-cap"></i><strong>Belum ada ringkasan hasil studi</strong><span>Ringkasan akan muncul setelah hasil studi diproses.</span></div></td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</section>
@endsection
