@extends('base.base-dash-index')
@section('title', 'Data Master Mata Kuliah - Siakad By Internal Developer')
@section('menu', 'Data Master Mata Kuliah')
@section('submenu', 'Data Master Mata Kuliah')
@section('urlmenu', '#')
@section('subdesc', 'Halaman untuk mengelola Mata Kuliah')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">@yield('submenu')</h5>
                    <small class="text-muted">
                        Periode: {{ $selectedPeriod?->name ?? 'Belum dipilih' }}
                        @if ($selectedPeriod)
                            — {{ $selectedPeriod->status_label }}
                        @endif
                    </small>
                </div>
                <div>
                    @if (Route::has($prefix.'master.penawaran-index'))
                        <a href="{{ route($prefix.'master.penawaran-index') }}" class="btn btn-outline-dark" title="Kelola penawaran dan KRS">Penawaran &amp; KRS</a>
                    @endif
                    @if ($canManageMataKuliah)
                        <a href="{{ route($prefix.'master.matkul-create') }}" class="btn btn-outline-primary" title="Tambah"><i class="fa-solid fa-plus"></i></a>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#importMataKuliah" title="Import"><i class="fa-solid fa-file-import"></i></button>
                    @elseif ($selectedPeriod)
                        <span class="badge bg-secondary me-1">Mode hanya baca</span>
                    @endif
                    @if ($selectedPeriod)
                        <a href="{{ route($prefix.'services.convert.export-matkul') }}" class="btn btn-outline-success" title="Export"><i class="fa-solid fa-file-export"></i></a>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            @if (! $selectedPeriod)
                <div class="alert alert-warning">Pilih periode akademik pada bagian atas halaman untuk melihat data mata kuliah.</div>
            @endif

            <table class="table table-striped" id="table1">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Program Studi</th>
                        <th>Nama Mata Kuliah</th>
                        <th>Kode</th>
                        <th>Semester</th>
                        <th>Kelas</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matkul as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $item->pstudi?->name ?? '-' }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->masterMataKuliah?->semester ?? $item->taka?->semester ?? '-' }}</td>
                            <td>{{ $item->kelas?->name ?? '-' }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    @if (Route::has($prefix.'master.matkul-nilai'))
                                        <a href="{{ route($prefix.'master.matkul-nilai', $item->id) }}" class="btn btn-outline-info">Nilai</a>
                                    @endif
                                    @if ($canManageMataKuliah)
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#updateMatkul{{ $item->id }}" class="btn btn-outline-primary"><i class="fas fa-edit"></i></button>
                                        <form id="delete-form-{{ $item->code }}" action="{{ route($prefix.'master.matkul-destroy', $item->code) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-outline-danger" data-url="{{ route($prefix.'master.matkul-destroy', $item->code) }}" data-name="{{ $item->name }}" onclick="deleteData('{{ $item->code }}')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada mata kuliah pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@if ($canManageMataKuliah)
<form action="{{ route($prefix.'services.convert.import-matkul') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade" id="importMataKuliah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title">Import Mata Kuliah</h4>
                        <small><a href="{{ route('root.download-matakuliah-example') }}">Download contoh file</a></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tahun Akademik</label>
                        <input class="form-control" value="{{ $selectedPeriod?->name }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="import-pstudi" class="form-label">Program Studi</label>
                        <select name="pstudi_id" id="import-pstudi" class="form-select" required>
                            <option value="">Pilih Program Studi</option>
                            @foreach ($pstudi as $item)
                                <option value="{{ $item->id }}" @selected(old('pstudi_id') == $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('pstudi_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="import-kuri" class="form-label">Kurikulum</label>
                        <select name="kuri_id" id="import-kuri" class="form-select" required>
                            <option value="">Pilih Kurikulum</option>
                            @foreach ($kuri as $item)
                                <option value="{{ $item->id }}" @selected(old('kuri_id') == $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('kuri_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="import-dosen" class="form-label">Dosen Pengampu</label>
                        <select name="dosen_1" id="import-dosen" class="form-select" required>
                            <option value="">Pilih Dosen Pengampu</option>
                            @foreach ($dosen as $item)
                                <option value="{{ $item->id }}" @selected(old('dosen_1') == $item->id)>{{ $item->dsn_name }}</option>
                            @endforeach
                        </select>
                        @error('dosen_1') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="import-file" class="form-label">File (xlsx atau csv)</label>
                        <input type="file" name="import" id="import-file" class="form-control" accept=".xlsx,.csv" required>
                        @error('import') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-outline-primary">Import</button>
                </div>
            </div>
        </div>
    </div>
</form>

@foreach ($matkul as $item)
<form action="{{ route($prefix.'master.matkul-update', $item->code) }}" method="POST">
    @csrf
    @method('PATCH')
    <div class="modal fade" id="updateMatkul{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Mata Kuliah — {{ $item->name }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body"><div class="row">
                    <div class="form-group col-lg-6">
                        <label for="mid-{{ $item->id }}">Nama Mata Kuliah</label>
                        <select name="mid" id="mid-{{ $item->id }}" class="form-select" required>
                            @foreach ($masterMatkul as $master)
                                <option value="{{ $master->id }}" @selected($item->mid == $master->id)>{{ $master->name }} — {{ $master->program_studi }}, Semester {{ $master->semester }} ({{ $master->sks }} SKS)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-6"><label>Kode Mata Kuliah</label><input type="text" name="code" class="form-control" value="{{ $item->code }}" required></div>
                    <div class="form-group col-lg-6">
                        <label>Persyaratan Mata Kuliah</label>
                        <select name="requ_id" class="form-select"><option value="">Tanpa prasyarat</option>@foreach ($matkul as $prasyarat) @if ($prasyarat->id !== $item->id)<option value="{{ $prasyarat->id }}" @selected($item->requ_id == $prasyarat->id)>{{ $prasyarat->name }}</option>@endif @endforeach</select>
                    </div>
                    <div class="form-group col-lg-6"><label>Beban SKS</label><input type="number" min="1" max="40" name="bsks" class="form-control" value="{{ $item->bsks }}" required></div>
                    <div class="form-group col-lg-4"><label>Kurikulum</label><select name="kuri_id" class="form-select" required>@foreach ($kuri as $value)<option value="{{ $value->id }}" @selected($item->kuri_id == $value->id)>{{ $value->name }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-4"><label>Tahun Akademik</label><input class="form-control" value="{{ $selectedPeriod?->name }}" readonly></div>
                    <div class="form-group col-lg-4"><label>Program Studi</label><select name="pstudi_id" class="form-select" required>@foreach ($pstudi as $value)<option value="{{ $value->id }}" @selected($item->pstudi_id == $value->id)>{{ $value->name }}</option>@endforeach</select></div>
                    @foreach ([1 => 'Dosen Pengampu', 2 => 'Dosen Cadangan 1', 3 => 'Dosen Cadangan 2'] as $number => $label)
                        <div class="form-group col-lg-4"><label>{{ $label }}</label><select name="dosen_{{ $number }}" class="form-select" @required($number === 1)><option value="">Pilih dosen</option>@foreach ($dosen as $value)<option value="{{ $value->id }}" @selected($item->{'dosen_'.$number} == $value->id)>{{ $value->dsn_name }}</option>@endforeach</select></div>
                    @endforeach
                    <div class="form-group col-12"><label>Deskripsi Mata Kuliah</label><textarea name="desc" class="form-control" rows="5" required>{{ $item->desc }}</textarea></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-outline-primary">Simpan</button></div>
            </div>
        </div>
    </div>
</form>
@endforeach
@endif
@endsection
