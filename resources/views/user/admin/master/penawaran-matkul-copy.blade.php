@extends('base.base-dash-index')
@section('title', 'Pratinjau Salin Penawaran')
@section('menu', 'Pratinjau Salin Penawaran')
@section('submenu', 'Pratinjau')
@section('urlmenu', '#')
@section('subdesc', 'Sesuaikan kelas dan dosen sebelum menyalin')
@section('content')
<section class="section"><div class="card"><div class="card-header"><h5>{{ $source->name }} → {{ $target->name }}</h5></div><div class="card-body">
<form method="POST" action="{{ route($prefix.'master.penawaran-copy-execute') }}">@csrf<input type="hidden" name="source_period_id" value="{{ $source->id }}"><input type="hidden" name="target_period_id" value="{{ $target->id }}">
<div class="table-responsive"><table class="table"><thead><tr><th>Salin</th><th>Mata kuliah</th><th>Kelas tujuan</th><th>Dosen utama tujuan</th></tr></thead><tbody>
@foreach($offerings as $item)<tr><td><input type="checkbox" name="selected[]" value="{{ $item->id }}" checked></td><td>{{ $item->masterMataKuliah->name }}<br><small>{{ $item->kelas?->name }}</small></td><td><select name="class_map[{{ $item->id }}]" class="form-select" required>@foreach($targetClasses->where('pstudi_id', $item->pstudi_id) as $class)<option value="{{ $class->id }}" @selected($class->name === $item->kelas?->name)>{{ $class->name }}</option>@endforeach</select></td><td><select name="lecturer_map[{{ $item->id }}]" class="form-select">@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($lecturer->id === $item->dosen_utama_id)>{{ $lecturer->dsn_name }}</option>@endforeach</select></td></tr>@endforeach
</tbody></table></div><button class="btn btn-primary">Jalankan salin</button></form></div></div></section>
@endsection
