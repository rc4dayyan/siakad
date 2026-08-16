@extends('base.base-dash-index')
@section('title', 'Penawaran Mata Kuliah')
@section('menu', 'Penawaran Mata Kuliah')
@section('submenu', 'Penawaran Periode')
@section('urlmenu', '#')
@section('subdesc', 'Kelola mata kuliah yang ditawarkan pada periode terpilih')
@section('content')
    <div class="mb-3"><a class="btn btn-outline-primary" href="{{ route($prefix.'period-opening.index') }}">Dashboard Pembukaan Periode</a></div>
<section class="section">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title">Salin Penawaran</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route($prefix.'master.penawaran-copy-preview') }}" class="row g-2">@csrf
                <div class="col-md-5"><select name="source_period_id" class="form-select" required><option value="">Periode sumber</option>@foreach($periods as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
                <input type="hidden" name="target_period_id" value="{{ $period?->id }}">
                <div class="col-md-3"><button class="btn btn-outline-primary" @disabled(!$canManage)>Pratinjau salin</button></div>
            </form>
        </div>
    </div>
    @if($canManage)
    <div class="card mb-3"><div class="card-header"><h5 class="card-title">Jadwal Pengisian KRS</h5></div><div class="card-body"><form method="POST" action="{{ route($prefix.'master.penawaran-krs-window') }}" class="row g-2">@csrf @method('PATCH')<div class="col-md-4"><label class="form-label">Mulai</label><input type="datetime-local" name="mulai_at" value="{{ $krsWindow?->mulai_at?->format('Y-m-d\\TH:i') }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Selesai</label><input type="datetime-local" name="selesai_at" value="{{ $krsWindow?->selesai_at?->format('Y-m-d\\TH:i') }}" class="form-control" required></div><div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="dipublikasikan" value="1" @checked($krsWindow?->dipublikasikan)><label class="form-check-label">Publikasikan</label></div></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary">Simpan jadwal</button></div></form></div></div>
    <div class="modal fade" id="createOfferingModal" tabindex="-1" aria-labelledby="createOfferingModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="createOfferingModalLabel">Tambah Penawaran — {{ $period->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">@if($errors->any() && old('_form') === 'create-offering')<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<form id="create-offering-form" method="POST" action="{{ route($prefix.'master.penawaran-store') }}" class="row g-3">@csrf<input type="hidden" name="_form" value="create-offering">
            <div class="col-md-4"><label class="form-label">Master mata kuliah</label><select name="master_mata_kuliah_id" class="form-select" required>@foreach($masters as $item)<option value="{{ $item->id }}" @selected(old('master_mata_kuliah_id') == $item->id)>{{ $item->name }} — {{ $masterProgramNames[$item->program_studi] ?? $item->program_studi }} — Semester {{ $item->semester }} ({{ $item->sks }} SKS)</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Program studi</label><select name="pstudi_id" class="form-select" required>@foreach($programs as $item)<option value="{{ $item->id }}" @selected(old('pstudi_id', $filters['pstudi_id'] ?? null) == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Kurikulum</label><select name="kuri_id" class="form-select" required>@foreach($curricula as $item)<option value="{{ $item->id }}" @selected(old('kuri_id', $filters['kuri_id'] ?? null) == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Kelas (dapat lebih dari satu)</label><select name="kelas_ids[]" class="form-select" multiple required>@foreach($classes as $item)<option value="{{ $item->id }}" @selected(in_array($item->id, old('kelas_ids', [])))>{{ $item->name }} — {{ $item->pstudi?->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Dosen utama</label><select name="dosen_utama_id" class="form-select" required>@foreach($lecturers as $item)<option value="{{ $item->id }}" @selected(old('dosen_utama_id') == $item->id)>{{ $item->dsn_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Dosen pendamping</label><select name="dosen_pendamping_1_id" class="form-select"><option value="">-</option>@foreach($lecturers as $item)<option value="{{ $item->id }}" @selected(old('dosen_pendamping_1_id') == $item->id)>{{ $item->dsn_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Pendamping kedua</label><select name="dosen_pendamping_2_id" class="form-select"><option value="">-</option>@foreach($lecturers as $item)<option value="{{ $item->id }}" @selected(old('dosen_pendamping_2_id') == $item->id)>{{ $item->dsn_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Prasyarat</label><select name="prasyarat_master_id" class="form-select"><option value="">Tanpa prasyarat</option>@foreach($masters as $item)<option value="{{ $item->id }}" @selected(old('prasyarat_master_id') == $item->id)>{{ $item->name }} — {{ $masterProgramNames[$item->program_studi] ?? $item->program_studi }} — Semester {{ $item->semester }} ({{ $item->sks }} SKS)</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" value="{{ old('kapasitas', 40) }}" min="1" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control">{{ old('deskripsi') }}</textarea></div>
        </form></div>
        <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" form="create-offering-form" class="btn btn-primary">Simpan Penawaran</button></div>
    </div></div></div>
    <div class="modal fade" id="importOfferingModal" tabindex="-1" aria-labelledby="importOfferingModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <form method="POST" action="{{ route($prefix.'master.penawaran-import') }}" enctype="multipart/form-data">@csrf<input type="hidden" name="_form" value="import-offerings">
            <div class="modal-header"><h5 class="modal-title" id="importOfferingModalLabel">Import Penawaran Mata Kuliah — {{ $period->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                @if($errors->any() && old('_form') === 'import-offerings')<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <p class="text-muted">Gunakan hasil export sebagai template. Kode periode harus <strong>{{ $period->code }}</strong>. Semester Mata Kuliah dan Semester Prasyarat dapat ditulis dengan angka atau Romawi (contoh: 2 atau II). Penawaran dengan kombinasi mata kuliah, program studi, kurikulum, dan kelas yang sudah ada akan dilewati.</p>
                <label for="import-offering-file" class="form-label">File XLSX atau CSV</label>
                <input type="file" name="import" id="import-offering-file" class="form-control" accept=".xlsx,.csv" required>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Import Penawaran</button></div>
        </form>
    </div></div></div>
    @endif
    <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title">Daftar Penawaran — {{ $period?->name ?? 'Belum ada periode' }}</h5><div class="d-flex gap-2">@if($period)<a class="btn btn-outline-success" href="{{ route($prefix.'master.penawaran-export', array_filter($filters)) }}"><i class="fa-solid fa-file-export me-1"></i> Export</a>@endif @if($canManage)<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#importOfferingModal"><i class="fa-solid fa-file-import me-1"></i> Import</button><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOfferingModal"><i class="fas fa-plus me-1"></i> Tambah Penawaran</button>@endif</div></div><div class="card-body table-responsive">
        <form method="GET" class="row g-2 mb-3"><div class="col-md-4"><select name="pstudi_id" class="form-select"><option value="">Semua program studi</option>@foreach($programs as $item)<option value="{{ $item->id }}" @selected(($filters['pstudi_id'] ?? null) == $item->id)>{{ $item->name }}</option>@endforeach</select></div><div class="col-md-4"><select name="kuri_id" class="form-select"><option value="">Semua kurikulum</option>@foreach($curricula as $item)<option value="{{ $item->id }}" @selected(($filters['kuri_id'] ?? null) == $item->id)>{{ $item->name }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div></form>
        <table class="table table-striped"><thead><tr><th>Kode</th><th>Mata Kuliah</th><th>Kelas</th><th>Dosen</th><th>SKS</th><th>Kapasitas</th><th>Aksi</th></tr></thead><tbody>
        @forelse($offerings as $item)<tr><td>{{ $item->code }}</td><td>{{ $item->masterMataKuliah->name }}</td><td>{{ $item->kelas->name }}</td><td>{{ $item->dosenUtama->dsn_name }}</td><td>{{ $item->sks }}</td><td>{{ $item->krsItems()->count() }} / {{ $item->kapasitas }}</td><td class="d-flex gap-1"><a class="btn btn-sm btn-outline-info" href="{{ route($prefix.'master.penawaran-participants', $item) }}">Peserta</a>@if($canManage)<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editOffering{{ $item->id }}">Edit</button><form method="POST" action="{{ route($prefix.'master.penawaran-destroy', $item) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus penawaran?')">Hapus</button></form>@endif</td></tr>
        @empty<tr><td colspan="7" class="text-center text-muted">Belum ada penawaran.</td></tr>@endforelse
        </tbody></table>
        @if($canManage) @foreach($offerings as $item)
        <div class="modal fade" id="editOffering{{ $item->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST" action="{{ route($prefix.'master.penawaran-update', $item) }}">@csrf @method('PATCH')
            <div class="modal-header"><h5 class="modal-title">Edit {{ $item->masterMataKuliah->name }} — {{ $item->kelas->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3">
                <input type="hidden" name="master_mata_kuliah_id" value="{{ $item->master_mata_kuliah_id }}"><input type="hidden" name="pstudi_id" value="{{ $item->pstudi_id }}"><input type="hidden" name="kuri_id" value="{{ $item->kuri_id }}"><input type="hidden" name="kelas_id" value="{{ $item->kelas_id }}"><input type="hidden" name="prasyarat_master_id" value="{{ $item->prasyarat_master_id }}">
                @if($item->hasParticipants())<div class="col-12"><div class="alert alert-warning mb-0">Penawaran telah dipakai KRS. Identitas mata kuliah, kelas, kurikulum, SKS, dan prasyarat dikunci.</div></div>@endif
                <div class="col-md-6"><label class="form-label">Dosen utama</label><select name="dosen_utama_id" class="form-select" required>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($lecturer->id === $item->dosen_utama_id)>{{ $lecturer->dsn_name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Pendamping pertama</label><select name="dosen_pendamping_1_id" class="form-select"><option value="">-</option>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($lecturer->id === $item->dosen_pendamping_1_id)>{{ $lecturer->dsn_name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Pendamping kedua</label><select name="dosen_pendamping_2_id" class="form-select"><option value="">-</option>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($lecturer->id === $item->dosen_pendamping_2_id)>{{ $lecturer->dsn_name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" value="{{ $item->kapasitas }}" min="1" class="form-control" required></div><div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control">{{ $item->deskripsi }}</textarea></div>
            </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan perubahan</button></div>
        </form></div></div></div>
        @endforeach @endif
    </div></div>
</section>
@if($errors->any() && old('_form') === 'create-offering')<script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('createOfferingModal')).show());</script>@endif
@if($errors->any() && old('_form') === 'import-offerings')<script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('importOfferingModal')).show());</script>@endif
@endsection
