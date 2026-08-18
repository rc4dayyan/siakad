@extends('base.base-dash-index')
@section('title')
Mata Kuliah dan Nilai - SIAKAD
@endsection
@section('menu')
Mata Kuliah & Nilai
@endsection
@section('submenu')
Mata Kuliah Saya
@endsection
@section('urlmenu')
@endsection
@section('subdesc')
Daftar mata kuliah per kelas dan nilai berdasarkan KRS yang telah disetujui
@endsection
@section('content')
<section class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title d-flex justify-content-between align-items-center">
                <span>@yield('menu') <small class="text-muted">— {{ $isTranscript ? 'Transkrip seluruh periode' : ($period?->name ?? 'Tidak ada periode aktif') }}</small></span>
                <div class="">
                    @if ($isTranscript)
                        <a href="{{ route('mahasiswa.akademik.nilai-index') }}" class="btn btn-outline-primary">Periode Aktif</a>
                    @else
                        <a href="{{ route('mahasiswa.akademik.nilai-index', ['transkrip' => 1]) }}" class="btn btn-outline-secondary">Lihat Transkrip</a>
                    @endif
                </div>
            </h5>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="table1">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Periode</th>
                        <th class="text-center">Kelas</th>
                        <th class="text-center">Mata Kuliah</th>
                        <th class="text-center">SKS</th>
                        <th class="text-center">Nama Dosen</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nilai as $key => $item)
                    <tr>
                        <td data-label="Number">{{ ++$key }}</td>
                        <td data-label="Periode">{{ $item['periode'] }}</td>
                        <td data-label="Kelas">{{ $item['kelas'] }}</td>
                        <td data-label="Mata Kuliah">
                            <div class="fw-semibold">{{ $item['mata_kuliah'] }}</div>
                            @if (filled($item['kode_mata_kuliah']))
                                <small class="text-muted">{{ $item['kode_mata_kuliah'] }}</small>
                            @endif
                        </td>
                        <td data-label="SKS">{{ $item['sks'] ?? '-' }}</td>
                        <td data-label="Nama Dosen">{{ $item['dosen'] }}</td>
                        <td data-label="Status">
                            @if (($item['status'] ?? null) === 'KRS Disetujui')
                                <span class="badge bg-light-success text-success">KRS Disetujui</span>
                            @else
                                <span class="badge bg-light-secondary text-secondary">{{ $item['status'] ?? '-' }}</span>
                            @endif
                        </td>
                        <td data-label="Nilai">
                            @if (filled($item['nilai']))
                                <span class="badge bg-success px-3 py-2">{{ $item['nilai'] }}</span>
                            @else
                                <span class="badge bg-light-secondary text-secondary px-3 py-2">Belum dinilai</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">Belum ada mata kuliah yang diikuti pada cakupan ini.</td></tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h5 class="card-title">Ringkasan Hasil Studi</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Periode</th><th>Semester</th><th>IPS</th><th>IPK</th><th>Nilai Tugas</th></tr></thead>
                    <tbody>
                        @forelse ($hasilStudi as $hasil)
                            <tr>
                                <td>{{ $hasil->taka?->name ?? '-' }}</td>
                                <td>{{ $hasil->smt_id }}</td>
                                <td>{{ $hasil->nilai_ips }}</td>
                                <td>{{ $hasil->nilai_ipk }}</td>
                                <td>{{ $hasil->score_tugas }} / {{ $hasil->max_tugas }} tugas</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada ringkasan hasil studi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
