@extends('base.base-dash-index')
@section('menu', 'Import Nilai')
@section('submenu', 'Import Nilai Mahasiswa')
@section('urlmenu', route('web-admin.nilai-import.index'))
@section('subdesc', 'Import nilai mahasiswa dari file Excel OpenFeeder')
@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card">
        <div class="card-header"><h4>Import Nilai</h4></div>
        <div class="card-body">
            <p>Gunakan file XLSX dengan format template import nilai OpenFeeder seperti file PAI 25. Pilih periode sesuai kolom Semester: 20251 berarti 2025/2026 Ganjil.</p>
            <p>Kolom wajib: NIM, Nama Mahasiswa, Kode Mata Kuliah, Nama Mata Kuliah, Semester, Nama Kelas, Nilai Huruf, Nilai Indeks, Nilai Angka, dan Kode Prodi.</p>
            <div class="alert alert-info">Mahasiswa harus memiliki KRS yang disetujui untuk mata kuliah tersebut. Nilai Huruf (A–E), Nilai Indeks (0–4), dan Nilai Angka (0–100) akan disimpan. Nilai numerik yang kosong disimpan sebagai kosong. Import ulang memperbarui nilai yang sudah ada. Jika ada baris tidak valid, seluruh import dibatalkan.</div>
            @if ($errors->any())
                <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form action="{{ route('web-admin.nilai-import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="nilai-periode" class="form-label">Periode Akademik Tujuan</label>
                    <select id="nilai-periode" name="periode" class="form-select" required>
                        <option value="">Pilih periode akademik</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->code }}" @selected(old('periode') === $period->code)>{{ $period->name }} ({{ $period->status }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="nilai-file" class="form-label">File Nilai Excel</label>
                    <input id="nilai-file" type="file" name="import" class="form-control" accept=".xlsx" required>
                    <div class="form-text">Maksimal 5 MB. Pilih kembali file apabila validasi gagal.</div>
                </div>
                <button type="submit" class="btn btn-primary">Import Nilai</button>
            </form>
        </div>
    </div>
@endsection
