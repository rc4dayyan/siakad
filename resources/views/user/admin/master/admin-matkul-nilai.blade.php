@extends('base.base-dash-index')
@section('title')
Data Nilai Mata Kuliah - Siakad By Internal Developer
@endsection
@section('menu')
Data Nilai Mata Kuliah
@endsection
@section('submenu')
Data Nilai Mata Kuliah
@endsection
@section('submenu0')

@endsection
@section('urlmenu')
#
@endsection
@section('subdesc')
Halaman untuk mengelola Data Nilai Mata Kuliah
@endsection
@section('content')
<section class="section">
    <form method="POST" action="{{ route($prefix.'master.matkul-storenilai') }}">
        @csrf
        <div class="card">
            <div class="card-header">
                <h5 class="card-title d-flex justify-content-between align-items-center">
                    @yield('submenu')
                    <div class="">
                        <a href="{{ route($prefix.'master.matkul-index') }}" class="btn btn-outline-warning"><i class="fa-solid fa-backward"></i></a>
                        <button type="submit" class="btn btn-outline-primary" @disabled(! $canManageNilai)><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </h5>
            </div>
            <div class="card-body">

                <input type="hidden" name="mata_kuliah_id" value="{{ $mataKuliah->id }}">

                <div class="mb-4">
                    <h5>Mata Kuliah: <strong>{{ $mataKuliah->name }}</strong></h5>
                    <h5>Kelas: <strong>{{ $mataKuliah->kelas->name ?? '' }}</strong></h5>
                    <h5>Periode: <strong>{{ $period->name }}</strong></h5>
                </div>

                @if (! $canManageNilai)
                    <div class="alert alert-warning">Nilai tidak dapat diubah karena periode hanya-baca atau mata kuliah belum terhubung dengan kelas periode ini.</div>
                @endif
                @error('kelas_id') <div class="alert alert-danger">{{ $message }}</div> @enderror

                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 12%">NIM</th>
                                        <th class="text-start">Nama Mahasiswa</th>
                                        <th style="width: 20%">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mahasiswas as $index => $mhs)
                                    @php
                                    $nilaiLama = $existingNilais[$mhs->id]->nilai ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ $mhs->mhs_nim }}
                                        </td>
                                        <td class="text-start">
                                            {{ strtoupper($mhs->mhs_name) }}
                                            <input type="hidden" name="nilai[{{ $mhs->id }}][mahasiswa_id]" value="{{ $mhs->id }}">
                                        </td>
                                        <td>
                                            <select name="nilai[{{ $mhs->id }}][nilai]" class="form-select">
                                                <option value="">-- Pilih Nilai --</option>
                                                @foreach(['A', 'B', 'C', 'D', 'E'] as $huruf)
                                                <option value="{{ $huruf }}" {{ $nilaiLama == $huruf ? 'selected' : '' }}>
                                                    {{ $huruf }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>

</section>
@endsection
