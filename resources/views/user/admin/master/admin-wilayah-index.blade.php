@extends('base.base-dash-index')

@section('title', 'Master Data Wilayah - Siakad By Internal Developer')
@section('menu', 'Master Data Wilayah')
@section('submenu', 'Daftar Wilayah')
@section('urlmenu', '#')
@section('subdesc', 'Kelola referensi kecamatan, kabupaten/kota, dan provinsi untuk integrasi OpenFeeder')

@section('custom-css')
<style>
    .region-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .region-summary__item { display: flex; align-items: center; gap: 14px; min-height: 92px; padding: 18px; border: 1px solid #e8ecef; border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgba(37, 50, 55, .05); }
    .region-summary__icon { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; flex: 0 0 46px; border-radius: 12px; font-size: 18px; }
    .region-summary__icon--district { background: #eef2ff; color: #435ebe; }
    .region-summary__icon--regency { background: #fff7e6; color: #d88700; }
    .region-summary__icon--province { background: #eaf8f2; color: #198754; }
    .region-summary__label { display: block; margin-bottom: 2px; color: #7b8794; font-size: 12px; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; }
    .region-summary__value { margin: 0; color: #263238; font-size: 24px; font-weight: 700; line-height: 1.2; }
    .region-filter { margin-bottom: 20px; padding: 20px; border: 1px solid #dfe7e4; border-radius: 14px; background: linear-gradient(135deg, #f5faf8 0%, #fff 72%); }
    .region-filter__header { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
    .region-filter__title { margin: 0 0 3px; color: #263d36; font-size: 15px; font-weight: 700; }
    .region-filter__description { margin: 0; color: #71837d; font-size: 12px; }
    .region-filter__result { display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; border-radius: 999px; background: #e8f5f0; color: #176b55; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .region-filter .form-label { margin-bottom: 6px; color: #46534f; font-size: 12px; font-weight: 700; }
    .region-filter .form-control, .region-filter .form-select { min-height: 42px; border-color: #dce4e1; border-radius: 9px; }
    .region-filter .input-group-text { border-color: #dce4e1; border-radius: 9px 0 0 9px; background: #fff; color: #87938f; }
    .region-filter__buttons { display: flex; gap: 8px; }
    .region-filter__buttons .btn { min-height: 42px; border-radius: 9px; white-space: nowrap; }
    .region-card { overflow: hidden; border: 0; border-radius: 14px; box-shadow: 0 7px 24px rgba(37, 50, 55, .07); }
    .region-card .card-header { padding: 20px 22px; border-bottom: 1px solid #edf0f2; background: #fff; }
    .region-card .card-body { padding: 8px 22px 22px; }
    .region-table th { padding-top: 15px; padding-bottom: 15px; color: #66727d; font-size: 11px; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
    .region-table td { padding-top: 14px; padding-bottom: 14px; vertical-align: middle; }
    .region-name { display: block; color: #263238; font-weight: 700; }
    .region-code { display: inline-flex; padding: 6px 9px; border-radius: 8px; background: #eef2ff; color: #435ebe; font-family: monospace; font-weight: 700; }
    .region-action-dropdown .dropdown-toggle { min-width: 88px; border-radius: 9px; font-weight: 600; }
    .region-action-dropdown .dropdown-menu { min-width: 170px; padding: 7px; border: 1px solid #e6ece9; border-radius: 10px; box-shadow: 0 10px 28px rgba(38, 61, 54, .14); }
    .region-action-dropdown .dropdown-item { display: flex; align-items: center; gap: 9px; padding: 9px 11px; border: 0; border-radius: 7px; background: transparent; font-size: 13px; }
    .region-action-dropdown .dropdown-item:hover { background: #f4f7f6; }
    .region-action-dropdown .dropdown-item i { width: 16px; text-align: center; }
    @media (max-width: 767.98px) {
        .region-summary { grid-template-columns: 1fr; gap: 10px; }
        .region-summary__item { min-height: 76px; padding: 14px; }
        .region-filter__header { align-items: flex-start; flex-direction: column; }
        .region-filter__buttons, .region-filter__buttons .btn { width: 100%; }
        .region-card .card-header { align-items: flex-start !important; flex-direction: column; }
        .region-card .card-header > div:last-child { display: flex; width: 100%; gap: 8px; }
        .region-card .card-header > div:last-child .btn { flex: 1; }
        .region-card .card-body { padding-right: 14px; padding-left: 14px; }
    }
</style>
@endsection

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

    <div class="region-summary" aria-label="Ringkasan data wilayah">
        <div class="region-summary__item"><span class="region-summary__icon region-summary__icon--district"><i class="fas fa-map-marker-alt"></i></span><div><span class="region-summary__label">Total Kecamatan</span><p class="region-summary__value">{{ number_format($regionSummary['total'], 0, ',', '.') }}</p></div></div>
        <div class="region-summary__item"><span class="region-summary__icon region-summary__icon--regency"><i class="fas fa-city"></i></span><div><span class="region-summary__label">Kabupaten/Kota</span><p class="region-summary__value">{{ number_format($regionSummary['regencies'], 0, ',', '.') }}</p></div></div>
        <div class="region-summary__item"><span class="region-summary__icon region-summary__icon--province"><i class="fas fa-map"></i></span><div><span class="region-summary__label">Provinsi</span><p class="region-summary__value">{{ number_format($regionSummary['provinces'], 0, ',', '.') }}</p></div></div>
    </div>

    <div class="region-filter">
        <div class="region-filter__header">
            <div><h6 class="region-filter__title"><i class="fas fa-sliders-h text-primary me-2"></i>Filter Data Wilayah</h6><p class="region-filter__description">Cari kode atau nama wilayah, lalu persempit berdasarkan tingkat administratif.</p></div>
            <span class="region-filter__result"><i class="fas fa-list-ul"></i>{{ number_format($wilayahs->total(), 0, ',', '.') }} data ditemukan</span>
        </div>
        <form method="GET" action="{{ route($prefix.'master.wilayah-index') }}" class="row g-3 align-items-end">
            <div class="col-xl-4 col-md-6">
                <label for="wilayah-search" class="form-label">Cari wilayah</label>
                <div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="search" class="form-control" id="wilayah-search" name="q" value="{{ $search }}" placeholder="Kode, kecamatan, kabupaten, atau provinsi"></div>
            </div>
            <div class="col-xl-2 col-md-3 col-6">
                <label for="wilayah-province" class="form-label">Provinsi</label>
                <select class="form-select" id="wilayah-province" name="provinsi"><option value="">Semua provinsi</option>@foreach ($provinces as $province)<option value="{{ $province }}" @selected($selectedProvince === $province)>{{ $province }}</option>@endforeach</select>
            </div>
            <div class="col-xl-3 col-md-3 col-6">
                <label for="wilayah-regency" class="form-label">Kabupaten/Kota</label>
                <select class="form-select" id="wilayah-regency" name="kabupaten"><option value="">Semua kabupaten/kota</option>@foreach ($regencies as $regency)<option value="{{ $regency }}" @selected($selectedRegency === $regency)>{{ $regency }}</option>@endforeach</select>
            </div>
            <div class="col-xl-3 col-12"><div class="region-filter__buttons">@if ($hasRegionFilters)<a href="{{ route($prefix.'master.wilayah-index') }}" class="btn btn-outline-secondary" title="Reset filter"><i class="fas fa-rotate-left"></i></a>@endif<button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Terapkan</button></div></div>
        </form>
    </div>

    <div class="card region-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">@yield('submenu')</h5>
                <small class="text-muted">Referensi alamat untuk data akademik dan integrasi OpenFeeder.</small>
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
            <div class="table-responsive">
                <table class="table table-hover region-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Kode</th>
                            <th>Kecamatan</th>
                            <th>Kabupaten/Kota</th>
                            <th>Provinsi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($wilayahs as $item)
                            <tr>
                                <td class="text-center">{{ $wilayahs->firstItem() + $loop->index }}</td>
                                <td><span class="region-code">{{ $item->code }}</span></td>
                                <td><span class="region-name">{{ $item->kecamatan }}</span></td>
                                <td>{{ $item->kabupaten ?: '-' }}</td>
                                <td>{{ $item->provinsi ?: '-' }}</td>
                                <td class="text-end">
                                    <div class="dropdown region-action-dropdown d-inline-block">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" id="region-action-{{ $item->id }}" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-cog me-1"></i> Aksi</button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="region-action-{{ $item->id }}">
                                            <li><button type="button" class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#editWilayah{{ $item->id }}"><i class="fas fa-pen"></i><span>Edit Data</span></button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><form action="{{ route($prefix.'master.wilayah-destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus data wilayah ini?')">@csrf @method('DELETE')<button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i><span>Hapus Data</span></button></form></li>
                                        </ul>
                                    </div>
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
