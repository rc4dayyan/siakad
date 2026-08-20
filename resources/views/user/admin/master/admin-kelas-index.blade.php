@extends('base.base-dash-index')
@section('title')
    Data Master Kelas - Siakad By Internal Developer
@endsection
@section('menu')
    Data Master Kelas
@endsection
@section('submenu')
    Daftar Data Kelas
@endsection
@section('submenu0')
    Tambah Data Kelas
@endsection
@section('urlmenu')
    #
@endsection
@section('subdesc')
    Halaman untuk mengelola Data Kelas
@endsection
@section('custom-css')
<style>
    .class-filter-panel {
        margin-bottom: 22px;
        padding: 18px;
        border: 1px solid #dce9e4;
        border-radius: 13px;
        background: linear-gradient(135deg, #f6fbf9 0%, #ffffff 100%);
    }

    .class-filter-panel__heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
    }

    .class-filter-panel__title {
        margin: 0 0 3px;
        color: #263d36;
        font-size: 15px;
        font-weight: 700;
    }

    .class-filter-panel__description {
        margin: 0;
        color: #71837d;
        font-size: 12px;
    }

    .class-filter-panel .form-label {
        margin-bottom: 6px;
        color: #415a52;
        font-size: 12px;
        font-weight: 700;
    }

    .class-filter-panel .form-control,
    .class-filter-panel .form-select {
        min-height: 40px;
        border-color: #d7e4df;
        border-radius: 9px;
    }

    .class-action-dropdown .dropdown-toggle {
        min-width: 96px;
        border-radius: 8px;
        font-weight: 600;
    }

    .class-action-dropdown .dropdown-menu {
        min-width: 190px;
        padding: 7px;
        border: 1px solid #e6ece9;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(38, 61, 54, 0.14);
    }

    .class-action-dropdown .dropdown-item {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 11px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        font-size: 13px;
    }

    .class-action-dropdown .dropdown-item i {
        width: 16px;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .class-filter-panel__heading {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endsection
@section('content')
<section class="section row">

    <div class="col-lg-12 col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-1">@yield('submenu')</h5>
                    <small class="text-muted">
                        Periode: {{ $selectedPeriod?->name ?? 'Belum dipilih' }}
                    </small>
                </div>
                <div>
                    @if ($canManageClasses)
                        <a href="#" data-bs-toggle="modal" data-bs-target="#tambahKelas" class="btn btn-outline-primary"><i class="fas fa-plus"></i></a>
                        <a href="{{ route($prefix.'services.convert.export-kelas') }}" class="btn btn-outline-success"><i class="fa-solid fa-file-export"></i></a>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#importKelas" class="btn btn-outline-danger"><i class="fa-solid fa-file-import"></i></a>
                    @elseif ($selectedPeriod)
                        <a href="{{ route($prefix.'services.convert.export-kelas') }}" class="btn btn-outline-success"><i class="fa-solid fa-file-export"></i></a>
                        <span class="badge bg-secondary">Periode hanya-baca</span>
                    @else
                        <button type="button" class="btn btn-outline-secondary" disabled>Periode belum tersedia</button>
                    @endif
                </div>

            </div>
            <div class="card-body">
                @error('academic_period')
                    <div class="alert alert-warning">{{ $message }}</div>
                @enderror

                <div class="class-filter-panel">
                    <div class="class-filter-panel__heading">
                        <div>
                            <h6 class="class-filter-panel__title">
                                <i class="fas fa-sliders-h text-primary me-2"></i>Filter Data Kelas
                            </h6>
                            <p class="class-filter-panel__description">
                                Cari dan saring kelas pada periode {{ $selectedPeriod?->name ?? 'yang aktif' }}.
                            </p>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $kelas->count() }} data ditemukan</span>
                    </div>

                    <form method="GET" action="{{ route($prefix.'master.kelas-index') }}" class="row g-3 align-items-end">
                        <div class="col-xl-3 col-md-6">
                            <label for="filter_class_keyword" class="form-label">Cari Kelas</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="search" name="q" id="filter_class_keyword"
                                    class="form-control border-start-0 ps-0" value="{{ $filters['q'] ?? '' }}"
                                    placeholder="Nama atau kode kelas">
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-6">
                            <label for="filter_class_study_program" class="form-label">Program Studi</label>
                            <select name="pstudi_id" id="filter_class_study_program" class="form-select">
                                <option value="">Semua program studi</option>
                                @foreach ($pstudi as $studyProgram)
                                    <option value="{{ $studyProgram->id }}" @selected(($filters['pstudi_id'] ?? null) == $studyProgram->id)>
                                        {{ $studyProgram->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-6">
                            <label for="filter_class_program" class="form-label">Program Kuliah</label>
                            <select name="proku_id" id="filter_class_program" class="form-select">
                                <option value="">Semua program kuliah</option>
                                @foreach ($proku as $program)
                                    <option value="{{ $program->id }}" @selected(($filters['proku_id'] ?? null) == $program->id)>
                                        {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-6">
                            <label for="filter_class_lecturer" class="form-label">Wali Dosen</label>
                            <select name="dosen_id" id="filter_class_lecturer" class="form-select">
                                <option value="">Semua wali dosen</option>
                                @foreach ($dosen as $lecturer)
                                    <option value="{{ $lecturer->id }}" @selected(($filters['dosen_id'] ?? null) == $lecturer->id)>
                                        {{ $lecturer->dsn_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-filter me-1"></i> Terapkan Filter
                                </button>
                                @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                                    <a href="{{ route($prefix.'master.kelas-index') }}"
                                        class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                <table class="table table-striped" id="table1">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Nama Kelas</th>
                            <th class="text-center">Program Studi</th>
                            <th class="text-center">Kapasitas</th>
                            <th class="text-center">Wali Dosen</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kelas as $key => $item)
                            <tr class="text-center">
                                <td data-label="Number">{{ ++$key }}</td>
                                <td data-label="Kelas">{{ ($item->proku?->name ? $item->proku->name.' - ' : '').$item->name }}</td>
                                <td data-label="Program Studi">{{ $item->pstudi?->name ?? '-' }}</td>
                                <td data-label="Kapasitas">{{ $item->mahasiswas_count.' / '.$item->capacity.' Mahasiswa' }}</td>
                                <td data-label="Wali Dosen">{{ $item->dosen?->dsn_name ?? '-' }}</td>
                                <td class="text-center" data-label="Aksi">
                                    <div class="dropdown class-action-dropdown d-inline-block">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                                            id="class-action-{{ $item->code }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fas fa-cog me-1"></i> Aksi
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="class-action-{{ $item->code }}">
                                            <li>
                                                <a href="{{ route($prefix.'master.kelas-mahasiswa-view', $item->code) }}"
                                                    class="dropdown-item text-info">
                                                    <i class="fas fa-users"></i>
                                                    <span>Lihat Mahasiswa</span>
                                                </a>
                                            </li>
                                            @if ($canManageClasses)
                                                <li>
                                                    <button type="button" class="dropdown-item text-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#updateKelas{{ $item->code }}">
                                                        <i class="fas fa-edit"></i>
                                                        <span>Edit Data</span>
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form id="delete-form-{{ $item->code }}"
                                                        action="{{ route($prefix.'master.kelas-destroy', $item->code) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="dropdown-item text-danger"
                                                            data-url="{{ route($prefix.'master.kelas-destroy', $item->code) }}"
                                                            data-name="{{ $item->name }}"
                                                            onclick="deleteData('{{ $item->code }}')">
                                                            <i class="fas fa-trash-alt"></i>
                                                            <span>Hapus Data</span>
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-5 text-center text-muted">
                                    <i class="fas fa-school fa-2x mb-3"></i>
                                    <p class="mb-1 fw-semibold">Data kelas tidak ditemukan</p>
                                    <small>Coba ubah atau reset filter yang digunakan.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</section>
<div class="me-1 mb-1 d-inline-block">
    <form action="{{ route($prefix.'services.convert.import-kelas') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal fade text-left w-100" id="importKelas" tabindex="-1" role="dialog"
            aria-labelledby="importKelasLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="importKelasLabel">Import Data Kelas</h4>
                        <div>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Gunakan hasil export sebagai template. Kolom legacy "Kode Tahun Akademik" berisi kode Periode Akademik dan setiap baris harus
                            <strong>{{ $selectedPeriod?->code }}</strong>. Kode kelas yang sudah ada akan dilewati.
                        </p>
                        <div class="form-group">
                            <label for="import_kelas">Import Files (xlsx, csv)</label>
                            <input type="file" name="import" id="import_kelas" class="form-control"
                                accept=".xlsx,.csv" required>
                            @error('import')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="me-1 mb-1 d-inline-block">

    <!--Extra Large Modal -->
    <form action="{{ route($prefix.'master.kelas-store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_form" value="create-kelas">
        <div class="modal fade text-left w-100" id="tambahKelas" tabindex="-1" role="dialog"
            aria-labelledby="myModalLabel16" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl"
                role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel16">Tambah Kelas</h4>
                        <div class="">
    
                            <button type="submit" class="btn btn-outline-primary" >
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-lg-4 col-12">
                                <label for="create-class-name">Nama Kelas</label>
                                <input type="text" class="form-control" name="name" id="create-class-name" value="{{ old('name') }}" placeholder="Inputkan nama Kelas...">
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-4 col-12">
                                <label for="create-class-code">Kode Kelas</label>
                                <input type="text" class="form-control" name="code" id="create-class-code" value="{{ old('code') }}" placeholder="Inputkan kode Kelas..." maxlength="32">
                                @error('code')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-4 col-12">
                                <label for="create-class-capacity">Kapasitas Kelas</label>
                                <input type="number" class="form-control" name="capacity" id="create-class-capacity" value="{{ old('capacity') }}" placeholder="Inputkan kapasitas Kelas..." min="1" max="100" required>
                                @error('capacity')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-6 col-12">
                                <label>Periode Akademik</label>
                                <input type="text" class="form-control" value="{{ $selectedPeriod?->name }}" readonly>
                            </div>
                            <div class="form-group col-lg-6 col-12">
                                <label for="create-class-study-program">Program Studi</label>
                                <select name="pstudi_id" id="create-class-study-program" class="form-select">
                                    <option value="">Pilih Program Studi</option>
                                    @foreach ($pstudi as $studi)
                                        <option value="{{ $studi->id }}" @selected(old('pstudi_id') == $studi->id)>{{ $studi->name }}</option>
                                    @endforeach
                                </select>
                                @error('pstudi_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-6 col-12">
                                <label for="create-class-program">Program Kuliah</label>
                                <select name="proku_id" id="create-class-program" class="form-select">
                                    <option value="">Pilih Program Kuliah</option>
                                    @foreach ($proku as $item)
                                        <option value="{{ $item->id }}" @selected(old('proku_id') == $item->id)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('proku_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-6 col-12">
                                <label for="create-class-advisor">Wali Dosen</label>
                                <select name="dosen_id" id="create-class-advisor" class="form-select">
                                    <option value="">Pilih Wali Dosen</option>
                                    @foreach ($dosen as $item)
                                        <option value="{{ $item->id }}" @selected(old('dosen_id') == $item->id)>{{ $item->dsn_name }}</option>
                                    @endforeach
                                </select>
                                @error('dosen_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="me-1 mb-1 d-inline-block">

    <!--Extra Large Modal -->
    @foreach ($kelas as $item)
    <form action="{{ route($prefix.'master.kelas-update', $item->code) }}" method="POST" enctype="multipart/form-data">
        @method('patch')
        @csrf
        <div class="modal fade text-left w-100" id="updateKelas{{$item->code}}" tabindex="-1" role="dialog"
            aria-labelledby="myModalLabel16" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl"
                role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel16">Edit Kelas - {{ $item->name }}</h4>
                        <div class="">
    
                            <button type="submit" class="btn btn-outline-primary" >
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-lg-4 col-12">
                                <label for="name">Nama Kelas</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Inputkan nama Kelas..." value="{{ $item->name }}">
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-4 col-12">
                                <label for="code">Kode Kelas</label>
                                <input type="text" class="form-control" name="code" id="code" placeholder="Inputkan kode Kelas..." maxlength="32" uppercase value="{{ $item->code }}" >
                                @error('code')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-4 col-12">
                                <label for="capacity-{{ $item->id }}">Kapasitas Kelas</label>
                                <input type="number" class="form-control" name="capacity" id="capacity-{{ $item->id }}" placeholder="Inputkan kapasitas Kelas..." min="1" max="100" value="{{ $item->capacity }}" required>
                                @error('capacity')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-6 col-12">
                                <label>Periode Akademik</label>
                                <input type="text" class="form-control" value="{{ $selectedPeriod?->name }}" readonly>
                            </div>
                            <div class="form-group col-lg-6 col-12">
                                <label for="pstudi_id">Program Studi</label>
                                <select name="pstudi_id" id="pstudi_id" class="form-select">
                                    <option value="" selected>Pilih Program Studi</option>
                                    @foreach ($pstudi as $studi)
                                        <option value="{{ $studi->id }}" {{ $item->pstudi_id == $studi->id ? 'selected' : '' }}>{{ $studi->name }}</option>
                                    @endforeach
                                </select>
                                @error('pstudi_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-6 col-12">
                                <label for="proku_id">Program Kuliah</label>
                                <select name="proku_id" id="proku_id" class="form-select">
                                    <option value="" selected>Pilih Program Kuliah</option>
                                    @foreach ($proku as $prokuh)
                                        <option value="{{ $prokuh->id }}" {{ $item->proku_id == $prokuh->id ? 'selected' : '' }}>{{ $prokuh->name }}</option>
                                    @endforeach
                                </select>
                                @error('proku_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-6 col-12">
                                <label for="dosen_id">Wali Dosen</label>
                                <select name="dosen_id" id="dosen_id" class="form-select">
                                    <option value="" selected>Pilih Wali Dosen</option>
                                    @foreach ($dosen as $dosens)
                                        <option value="{{ $dosens->id }}" {{ $item->dosen_id == $dosens->id ? 'selected' : '' }}>{{ $dosens->dsn_name }}</option>
                                    @endforeach
                                </select>
                                @error('dosen_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endforeach
    
</div>
@endsection
@section('custom-js')
    @if ($errors->any() && old('_form') === 'create-kelas')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('tambahKelas')).show());</script>
    @endif
@endsection
