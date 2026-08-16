@extends('base.base-dash-index')

@section('title', 'Data Master Tahun Akademik - Siakad')
@section('menu', 'Data Master Tahun Akademik')
@section('submenu', 'Daftar Data Tahun Akademik')
@section('submenu0', 'Tambah Data Tahun Akademik')
@section('urlmenu', '#')
@section('subdesc', 'Halaman untuk mengelola Data Tahun Akademik')

@section('content')
    <div class="mb-3 d-flex gap-2"><a class="btn btn-primary" href="{{ route($prefix.'period-opening.wizard') }}">Wizard Periode Baru</a><a class="btn btn-outline-primary" href="{{ route($prefix.'period-opening.index') }}">Dashboard Pembukaan Periode</a></div>
<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">@yield('submenu')</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTakaModal"><i class="fas fa-plus me-1"></i> Tambah Periode</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table1">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Rentang</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($taka as $key => $item)
                                @php
                                    $badge = match ($item->status) {
                                        'active' => 'bg-success',
                                        'closed' => 'bg-secondary',
                                        'archived' => 'bg-dark',
                                        default => 'bg-warning text-dark',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <strong>{{ $item->name }}</strong><br>
                                        <small class="text-muted">{{ $item->code }}</small>
                                    </td>
                                    <td>{{ $item->term_label }}</td>
                                    <td>
                                        {{ $item->year_start }}–{{ $item->year_end ?? '?' }}<br>
                                        <small class="text-muted">
                                            {{ $item->starts_at?->format('d-m-Y') ?? '-' }} s.d.
                                            {{ $item->ends_at?->format('d-m-Y') ?? '-' }}
                                        </small>
                                    </td>
                                    <td><span class="badge {{ $badge }}">{{ $item->status_label }}</span></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            @if ($item->status === 'draft')
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#updateTaka{{ $item->id }}"
                                                    title="Ubah periode">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route($prefix.'master.taka-activate', $item->code) }}"
                                                    method="POST" onsubmit="return confirm('Aktifkan periode ini? Periode aktif sebelumnya akan ditutup.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Aktifkan periode">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route($prefix.'master.taka-destroy', $item->code) }}"
                                                    method="POST" onsubmit="return confirm('Hapus periode draft ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus periode">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Terkunci</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Belum ada periode akademik.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<form action="{{ route($prefix.'master.taka-store') }}" method="POST">
    @csrf
    <input type="hidden" name="_form" value="create-taka">
    <div class="modal fade" id="createTakaModal" tabindex="-1" aria-labelledby="createTakaModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="createTakaModalLabel">Tambah Data Tahun Akademik</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">
            <div class="form-group"><label for="create-taka-name">Nama Periode Akademik</label><input type="text" class="form-control" name="name" id="create-taka-name" value="{{ old('name') }}" placeholder="Contoh: Tahun Akademik 2026/2027 Ganjil">@error('name')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="form-group"><label for="create-taka-code">Kode Periode</label><input type="text" class="form-control" name="code" id="create-taka-code" value="{{ old('code') }}" placeholder="Contoh: 2026-GANJIL" maxlength="32">@error('code')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="form-group"><label for="create-taka-term">Jenis Periode</label><select class="form-select" name="term" id="create-taka-term"><option value="">Pilih jenis periode</option>@foreach ($terms as $term)<option value="{{ $term }}" @selected(old('term') === $term)>{{ ucfirst($term) }}</option>@endforeach</select>@error('term')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="row"><div class="form-group col-md-6"><label for="create-taka-year-start">Tahun Mulai</label><input type="number" class="form-control" name="year_start" id="create-taka-year-start" min="2000" max="2100" value="{{ old('year_start', now()->year) }}">@error('year_start')<small class="text-danger">{{ $message }}</small>@enderror</div><div class="form-group col-md-6"><label for="create-taka-year-end">Tahun Selesai</label><input type="number" class="form-control" name="year_end" id="create-taka-year-end" min="2000" max="2101" value="{{ old('year_end', now()->year + 1) }}">@error('year_end')<small class="text-danger">{{ $message }}</small>@enderror</div></div>
            <div class="form-group"><label for="create-taka-start">Tanggal Mulai</label><input type="date" class="form-control" name="starts_at" id="create-taka-start" value="{{ old('starts_at') }}">@error('starts_at')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="form-group"><label for="create-taka-end">Tanggal Selesai</label><input type="date" class="form-control" name="ends_at" id="create-taka-end" value="{{ old('ends_at') }}">@error('ends_at')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <small class="text-muted">Periode baru disimpan sebagai draft dan harus diaktifkan secara terpisah.</small>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan sebagai Draft</button></div>
    </div></div></div>
</form>

@foreach ($taka->where('status', 'draft') as $item)
    <form action="{{ route($prefix.'master.taka-update', $item->code) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="modal fade" id="updateTaka{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit {{ $item->name }}</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name-{{ $item->id }}">Nama Periode Akademik</label>
                            <input type="text" class="form-control" name="name" id="name-{{ $item->id }}" value="{{ $item->name }}">
                        </div>
                        <div class="form-group">
                            <label for="code-{{ $item->id }}">Kode Periode</label>
                            <input type="text" class="form-control" name="code" id="code-{{ $item->id }}" value="{{ $item->code }}" maxlength="32">
                        </div>
                        <div class="form-group">
                            <label for="term-{{ $item->id }}">Jenis Periode</label>
                            <select class="form-select" name="term" id="term-{{ $item->id }}">
                                @foreach ($terms as $term)
                                    <option value="{{ $term }}" @selected($item->term === $term)>{{ ucfirst($term) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="year-start-{{ $item->id }}">Tahun Mulai</label>
                                <input type="number" class="form-control" name="year_start" id="year-start-{{ $item->id }}"
                                    min="2000" max="2100" value="{{ $item->year_start }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="year-end-{{ $item->id }}">Tahun Selesai</label>
                                <input type="number" class="form-control" name="year_end" id="year-end-{{ $item->id }}"
                                    min="2000" max="2101" value="{{ $item->year_end }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="starts-at-{{ $item->id }}">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="starts_at" id="starts-at-{{ $item->id }}"
                                value="{{ $item->starts_at?->format('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label for="ends-at-{{ $item->id }}">Tanggal Selesai</label>
                            <input type="date" class="form-control" name="ends_at" id="ends-at-{{ $item->id }}"
                                value="{{ $item->ends_at?->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-outline-primary">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endforeach
@endsection
@section('custom-js')
    @if ($errors->any() && old('_form') === 'create-taka')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('createTakaModal')).show());</script>
    @endif
@endsection
