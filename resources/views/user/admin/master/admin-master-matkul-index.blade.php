@extends('base.base-dash-index')

@section('title', 'Master Mata Kuliah - Siakad By Internal Developer')
@section('menu', 'Master Mata Kuliah')
@section('submenu', 'Daftar Master Mata Kuliah')
@section('urlmenu', '#')
@section('subdesc', 'Halaman untuk mengelola katalog master mata kuliah')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">@yield('submenu')</h5>
            <div>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createMasterMatkul" title="Tambah">
                    <i class="fas fa-plus"></i>
                </button>
                <a href="{{ route($prefix.'master.master-matkul-export') }}" class="btn btn-outline-success" title="Export">
                    <i class="fa-solid fa-file-export"></i>
                </a>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#importMasterMatkul" title="Import">
                    <i class="fa-solid fa-file-import"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="table1">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Program Studi</th>
                        <th class="text-center">Semester</th>
                        <th>Nama Mata Kuliah</th>
                        <th class="text-center">SKS</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($masterMatkul as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $item->program_studi }}</td>
                            <td class="text-center">{{ $item->semester }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-center">{{ $item->sks }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMasterMatkul{{ $item->id }}" title="Ubah">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

<form action="{{ route($prefix.'master.master-matkul-store') }}" method="POST">
    @csrf
    <div class="modal fade" id="createMasterMatkul" tabindex="-1" aria-labelledby="createMasterMatkulLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createMasterMatkulLabel">Tambah Master Mata Kuliah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @include('user.admin.master.partials.master-matkul-form', ['formId' => 'create'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-outline-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</form>

@foreach ($masterMatkul as $item)
    <form action="{{ route($prefix.'master.master-matkul-update', $item) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="modal fade" id="editMasterMatkul{{ $item->id }}" tabindex="-1" aria-labelledby="editMasterMatkulLabel{{ $item->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMasterMatkulLabel{{ $item->id }}">Ubah Master Mata Kuliah</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @include('user.admin.master.partials.master-matkul-form', ['formId' => 'edit-'.$item->id, 'item' => $item])
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

<form action="{{ route($prefix.'master.master-matkul-import') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade" id="importMasterMatkul" tabindex="-1" aria-labelledby="importMasterMatkulLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importMasterMatkulLabel">Import Master Mata Kuliah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Gunakan hasil export sebagai template. Data yang sudah ada akan diperbarui berdasarkan program studi, semester, dan nama mata kuliah.</p>
                    <label for="import_master_matkul" class="form-label">File (xlsx atau csv)</label>
                    <input type="file" name="import" id="import_master_matkul" class="form-control" accept=".xlsx,.csv" required>
                    @error('import')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-outline-primary">Import</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
