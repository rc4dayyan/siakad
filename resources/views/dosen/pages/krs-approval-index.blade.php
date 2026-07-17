@extends('base.base-dash-index')
@section('title', 'Persetujuan KRS')
@section('menu', 'Persetujuan KRS')
@section('submenu', 'Mahasiswa Bimbingan')
@section('urlmenu', '#')
@section('subdesc', 'Tinjau KRS mahasiswa yang menjadi bimbingan Anda')
@section('content')
<section class="section">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@forelse($submissions as $krs)<div class="card mb-3"><div class="card-header d-flex justify-content-between"><div><h5>{{ $krs->registrasiMahasiswa->mahasiswa->mhs_name }}</h5><small>{{ $krs->registrasiMahasiswa->mahasiswa->mhs_nim }} · {{ $krs->registrasiMahasiswa->taka->name }}</small></div><span class="badge bg-secondary">{{ strtoupper($krs->status) }}</span></div><div class="card-body"><ul>@foreach($krs->items as $item)<li>{{ $item->penawaranMataKuliah->masterMataKuliah->name }} — {{ $item->sks }} SKS</li>@endforeach</ul><strong>Total: {{ $krs->total_sks }} SKS</strong>@if($krs->status === 'submitted')<form method="POST" action="{{ route('dosen.akademik.krs-decide', $krs) }}" class="mt-3">@csrf @method('PATCH')<textarea name="catatan" class="form-control mb-2" placeholder="Catatan keputusan"></textarea><button name="status" value="approved" class="btn btn-success">Setujui dan kunci</button><button name="status" value="rejected" class="btn btn-danger">Tolak</button></form>@endif</div></div>@empty<div class="alert alert-info">Belum ada pengajuan KRS mahasiswa bimbingan.</div>@endforelse
</section>
@endsection
