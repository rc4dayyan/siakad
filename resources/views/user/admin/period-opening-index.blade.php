@extends('base.base-dash-index')

@section('menu', 'Pembukaan Periode')
@section('submenu', 'Kesiapan dan Publikasi')
@section('urlmenu', '#')
@section('subdesc', 'Pemeriksaan terpadu sebelum periode akademik dipublikasikan')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><small>Periode terpilih</small><h4 class="mb-1">{{ $period->name }}</h4><span class="badge bg-{{ $period->is_published ? 'success' : 'secondary' }}">{{ $period->is_published ? 'Dipublikasikan' : 'Belum dipublikasikan' }}</span></div>
        <div style="min-width:280px"><div class="d-flex justify-content-between"><span>Progres kesiapan</span><strong>{{ $result['progress'] }}%</strong></div><div class="progress"><div class="progress-bar" style="width:{{ $result['progress'] }}%"></div></div><small>{{ $result['counts']['siap'] }} siap · {{ $result['counts']['peringatan'] }} peringatan · {{ $result['counts']['gagal'] }} gagal</small></div>
        <div class="d-flex gap-2"><form method="POST" action="{{ route($prefix.'period-opening.inspect') }}">@csrf<button class="btn btn-outline-primary">Simpan pemeriksaan</button></form>@if ($canPublish)<form method="POST" action="{{ route($prefix.'period-opening.publish') }}" onsubmit="return confirm('Publikasikan periode ini ke portal?')">@csrf<button class="btn btn-success" @disabled($period->is_published)>Publikasikan</button></form>@endif</div>
    </div></div>

    @php
        $fixRoutes = [
            'identitas' => 'master.taka-index', 'kurikulum_prodi' => 'master.matkul-index',
            'registrasi_kelas' => 'workers.student-index', 'penawaran_dosen' => 'master.penawaran-index',
            'jadwal' => 'master.jadwal-mingguan-index', 'tagihan' => 'billing-period.index',
        ];
    @endphp
    <div class="row">
        @foreach ($checks as $check)
            @php $color = ['siap' => 'success', 'peringatan' => 'warning', 'gagal' => 'danger'][$check['status']]; $routeName = $prefix.($fixRoutes[$check['key']] ?? ''); @endphp
            <div class="col-lg-6"><div class="card border-{{ $color }}"><div class="card-body"><div class="d-flex justify-content-between"><h6>{{ $check['label'] }}</h6><span class="badge bg-{{ $color }}">{{ ucfirst($check['status']) }}</span></div><p class="mb-2">{{ $check['message'] }}</p>@if (Route::has($routeName))<a href="{{ route($routeName) }}" class="btn btn-sm btn-outline-{{ $color }}">Perbaiki data</a>@endif</div></div></div>
        @endforeach
    </div>

    @if ($canCopy)
        <div class="card"><div class="card-header"><h5>Salin Konfigurasi Periode</h5></div><div class="card-body">
            <form method="POST" action="{{ route($prefix.'period-opening.copy-preview') }}" class="row g-3">@csrf
                <div class="col-md-4"><label class="form-label">Periode sumber</label><select name="source_period_id" class="form-select" required>@foreach ($periods as $item)<option value="{{ $item->id }}" @selected(old('source_period_id') == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Periode tujuan (draft)</label><select name="target_period_id" class="form-select" required>@foreach ($periods->where('status', 'draft') as $item)<option value="{{ $item->id }}" @selected(old('target_period_id', $period->id) == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Data konfigurasi</label>@foreach (['kelas'=>'Kelas','dosen'=>'Penugasan dosen','penawaran'=>'Penawaran','jadwal'=>'Jadwal','template_tagihan'=>'Template tagihan'] as $value=>$label)<div class="form-check"><input class="form-check-input" type="checkbox" name="selections[]" value="{{ $value }}" id="copy-{{ $value }}" @checked(in_array($value, old('selections', [])))><label class="form-check-label" for="copy-{{ $value }}">{{ $label }}</label></div>@endforeach</div>
                <div class="col-12"><button class="btn btn-outline-primary">Pratinjau salin</button></div>
            </form>
            @if ($preview)<hr><h6>Pratinjau</h6><p>{{ $preview['total'] }} referensi dipilih. Data transaksi yang selalu dikecualikan: {{ implode(', ', $preview['excluded']) }}.</p>@if ($preview['conflicts'])<div class="alert alert-warning"><ul class="mb-0">@foreach ($preview['conflicts'] as $conflict)<li>{{ $conflict }}</li>@endforeach</ul></div>@endif<form method="POST" action="{{ route($prefix.'period-opening.copy-execute') }}">@csrf<input type="hidden" name="source_period_id" value="{{ old('source_period_id') }}"><input type="hidden" name="target_period_id" value="{{ old('target_period_id') }}">@foreach (old('selections', []) as $selection)<input type="hidden" name="selections[]" value="{{ $selection }}">@endforeach<button class="btn btn-primary" onclick="return confirm('Jalankan penyalinan konfigurasi?')">Jalankan penyalinan</button></form>@endif
        </div></div>
    @endif

    <div class="row"><div class="col-lg-6"><div class="card"><div class="card-header"><h6>Snapshot Kesiapan</h6></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Waktu</th><th>Tujuan</th><th>Status</th></tr></thead><tbody>@forelse ($snapshots as $snapshot)<tr><td>{{ $snapshot->checked_at }}</td><td>{{ $snapshot->purpose }}</td><td>{{ $snapshot->status }} ({{ $snapshot->failed_count }} gagal)</td></tr>@empty<tr><td colspan="3">Belum ada snapshot.</td></tr>@endforelse</tbody></table></div></div></div><div class="col-lg-6"><div class="card"><div class="card-header"><h6>Riwayat Salin</h6></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Waktu</th><th>Status</th><th>Hasil</th></tr></thead><tbody>@forelse ($copyRuns as $run)<tr><td>{{ $run->created_at }}</td><td>{{ $run->status }}</td><td>{{ $run->created_count }} dibuat / {{ $run->conflict_count }} konflik</td></tr>@empty<tr><td colspan="3">Belum ada proses.</td></tr>@endforelse</tbody></table></div></div></div></div>
    @if ($integrity)<div class="card"><div class="card-header"><h6>Audit Integritas Referensi</h6></div><div class="card-body"><div class="alert alert-{{ $integrity['orphan_count'] ? 'danger' : 'success' }}">{{ $integrity['orphan_count'] }} referensi yatim ditemukan.</div><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Relasi</th><th>Yatim</th><th>Kebijakan</th></tr></thead><tbody>@foreach ($integrity['relations'] as $relation)<tr><td>{{ $relation['relation'] }}</td><td>{{ $relation['orphans'] }}</td><td>{{ $relation['policy'] }}</td></tr>@endforeach</tbody></table></div></div></div>@endif
@endsection
