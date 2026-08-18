@extends('base.base-dash-index')
@section('title', 'Peserta Penawaran')
@section('menu', 'Peserta Penawaran')
@section('submenu', 'KRS Disetujui')
@section('urlmenu', '#')
@section('subdesc', 'Daftar peserta berdasarkan KRS yang disetujui')
@section('content')
<section class="section"><div class="card"><div class="card-header d-flex justify-content-between align-items-start gap-3"><div><h5>{{ $penawaran->masterMataKuliah->name }} — {{ $penawaran->kelas->name }}</h5><small>{{ $penawaran->taka->name }} · {{ $penawaran->dosenUtama->dsn_name }}</small></div><a href="{{ route($prefix.'master.penawaran-grades', $penawaran) }}" class="btn btn-primary"><i class="fas fa-graduation-cap me-1"></i> Kelola Nilai</a></div><div class="card-body"><table class="table table-striped"><thead><tr><th>#</th><th>NIM</th><th>Nama</th></tr></thead><tbody>@forelse($students as $student)<tr><td>{{ $loop->iteration }}</td><td>{{ $student->mhs_nim }}</td><td>{{ $student->mhs_name }}</td></tr>@empty<tr><td colspan="3" class="text-center">Belum ada peserta dengan KRS disetujui.</td></tr>@endforelse</tbody></table></div></div></section>
@endsection
