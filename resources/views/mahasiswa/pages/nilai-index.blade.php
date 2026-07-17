@extends('base.base-dash-index')
@section('title')
Data Nilai Kuliah - Siakad By Internal Developer
@endsection
@section('menu')
Data Nilai Kuliah
@endsection
@section('submenu')
Data
@endsection
@section('urlmenu')
@endsection
@section('subdesc')
Halaman untuk melihat data Nilai Kuliah
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
                        <th class="text-center">Nama Dosen</th>
                        <th class="text-center">Nilai</th>
                        <!-- <th class="text-center">Button</th> -->
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nilai as $key => $item)
                    <tr>
                        <td data-label="Number">{{ ++$key }}</td>
                        <td data-label="Periode">{{ $item->taka?->name ?? $item->mataKuliah?->taka?->name ?? '-' }}</td>
                        <td data-label="Judul Tugas">{{ $item->kelas?->name ?? '-' }}</td>
                        <td data-label="Mata Kuliah">{{ $item->mataKuliah->name ?? '' }}</td>
                        <td data-label="Nama Dosen">{{ $item->dosen?->dsn_name ?? '-' }}</td>
                        <td data-label="Nilai">{{ $item->nilai }}</td>
                        <!-- <td class="d-flex justify-content-center align-items-center">
                            <a href="{{ route('mahasiswa.akademik.nilai-view', $item->id) }}" class="btn btn-primary"><i class="fa-solid fa-eye"></i></a>
                        </td> -->
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada nilai untuk cakupan yang dipilih.</td></tr>
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
