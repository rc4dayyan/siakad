@extends('base.base-dash-index')

@section('title', 'Master Data Wilayah - Siakad By Internal Developer')
@section('menu', 'Master Data Wilayah')
@section('submenu', 'Daftar Wilayah')
@section('urlmenu', '#')
@section('subdesc', 'Kelola referensi kecamatan, kabupaten/kota, dan provinsi untuk integrasi OpenFeeder')

@section('content')
<section class="section">
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Data belum dapat diproses.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">@yield('submenu')</h5>
                <small class="text-muted">{{ number_format($wilayahs->total(), 0, ',', '.') }} data ditemukan</small>
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createWilayah" title="Tambah wilayah">
                    <i class="fas fa-plus"></i> Tambah
                </button>
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importWilayah" title="Import wilayah">
                    <i class="fa-solid fa-file-import"></i> Import
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route($prefix.'master.wilayah-index') }}" class="row g-2 mb-4">
                <div class="col-lg-5">
                    <label for="wilayah-search" class="visually-hidden">Cari wilayah</label>
                    <input type="search" class="form-control" id="wilayah-search" name="q" value="{{ $search }}" placeholder="Cari kode, kecamatan, kabupaten, atau provinsi">
                </div>
                <div class="col-lg-4">
                    <label for="wilayah-province" class="visually-hidden">Filter provinsi</label>
                    <select class="form-select" id="wilayah-province" name="provinsi">
                        <option value="">Semua provinsi</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province }}" @selected($selectedProvince === $province)>{{ $province }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                    <a href="{{ route($prefix.'master.wilayah-index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Kode</th>
                            <th>Kecamatan</th>
                            <th>Kabupaten/Kota</th>
                            <th>Provinsi</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($wilayahs as $item)
                            <tr>
                                <td class="text-center">{{ $wilayahs->firstItem() + $loop->index }}</td>
                                <td><span class="badge bg-light-primary text-primary">{{ $item->code }}</span></td>
                                <td>{{ $item->kecamatan }}</td>
                                <td>{{ $item->kabupaten ?: '-' }}</td>
                                <td>{{ $item->provinsi ?: '-' }}</td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editWilayah{{ $item->id }}" title="Ubah">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form class="d-inline" action="{{ route($prefix.'master.wilayah-destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus data wilayah ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada data wilayah yang sesuai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $wilayahs->links() }}
            </div>
        </div>
    </div>
</section>

<form action="{{ route($prefix.'master.wilayah-store') }}" method="POST">
    @csrf
    <input type="hidden" name="_form" value="create-wilayah">
    <div class="modal fade" id="createWilayah" tabindex="-1" aria-labelledby="createWilayahLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createWilayahLabel">Tambah Data Wilayah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @include('user.admin.master.partials.wilayah-form', ['formId' => 'create'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</form>

@foreach ($wilayahs as $item)
    <form action="{{ route($prefix.'master.wilayah-update', $item) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="modal fade" id="editWilayah{{ $item->id }}" tabindex="-1" aria-labelledby="editWilayahLabel{{ $item->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editWilayahLabel{{ $item->id }}">Ubah Wilayah {{ $item->code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @include('user.admin.master.partials.wilayah-form', ['formId' => 'edit-'.$item->id, 'item' => $item])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endforeach

<form action="{{ route($prefix.'master.wilayah-import') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade" id="importWilayah" tabindex="-1" aria-labelledby="importWilayahLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importWilayahLabel">Import Data Wilayah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Gunakan file OpenFeeder dengan kolom <code>id_wil</code>, <code>kecamatan</code>, <code>kabupaten</code>, dan <code>provinsi</code>. Data dengan kode yang sama akan diperbarui.</p>
                    <label for="import_wilayah" class="form-label">File wilayah (xlsx atau csv, maksimal 5 MB)</label>
                    <input type="file" name="import" id="import_wilayah" class="form-control" accept=".xlsx,.csv" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
@section('custom-js')
    @if ($errors->any() && old('_form') === 'create-wilayah')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('createWilayah')).show());</script>
    @endif
@endsection
