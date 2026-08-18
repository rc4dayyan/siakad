@extends('base.base-dash-index')
@section('title')
    Data Master Program Kuliah - Siakad By Internal Developer
@endsection
@section('menu')
    Data Master Program Kuliah
@endsection
@section('submenu')
    Daftar Data Program Kuliah
@endsection
@section('submenu0')
    Tambah Data Program Kuliah
@endsection
@section('urlmenu')
    #
@endsection
@section('subdesc')
    Halaman untuk mengelola Data Program Kuliah
@endsection
@section('custom-css')
<style>
    .program-filter-panel {
        margin-bottom: 22px;
        padding: 18px;
        border: 1px solid #dce9e4;
        border-radius: 13px;
        background: linear-gradient(135deg, #f6fbf9 0%, #ffffff 100%);
    }

    .program-filter-panel__heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
    }

    .program-filter-panel__title {
        margin: 0 0 3px;
        color: #263d36;
        font-size: 15px;
        font-weight: 700;
    }

    .program-filter-panel__description {
        margin: 0;
        color: #71837d;
        font-size: 12px;
    }

    .program-period-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #e8f5f0;
        color: #176b55;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .program-action-dropdown .dropdown-toggle {
        min-width: 96px;
        border-radius: 8px;
        font-weight: 600;
    }

    .program-action-dropdown .dropdown-menu {
        min-width: 170px;
        padding: 7px;
        border: 1px solid #e6ece9;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(38, 61, 54, 0.14);
    }

    .program-action-dropdown .dropdown-item {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 11px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        font-size: 13px;
    }

    .program-action-dropdown .dropdown-item i {
        width: 16px;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .program-filter-panel__heading {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endsection
@section('content')
<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">@yield('submenu')</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProkuModal"><i class="fas fa-plus me-1"></i> Tambah Program Kuliah</button>
            </div>
            <div class="card-body">
                <div class="program-filter-panel">
                    <div class="program-filter-panel__heading">
                        <div>
                            <h6 class="program-filter-panel__title">
                                <i class="fas fa-sliders-h text-primary me-2"></i>Filter Program Kuliah
                            </h6>
                            <p class="program-filter-panel__description">Saring berdasarkan tahun akademik, program studi, atau gelombang pendaftaran.</p>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $proku->count() }} data ditemukan</span>
                    </div>

                    <form method="GET" action="{{ route($prefix.'master.proku-index') }}" class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <label for="filter_taka_id" class="form-label">Tahun Akademik</label>
                            <select name="taka_id" id="filter_taka_id" class="form-select">
                                <option value="">Semua tahun akademik</option>
                                @foreach ($taka as $period)
                                    <option value="{{ $period->id }}" @selected(($filters['taka_id'] ?? null) == $period->id)>
                                        {{ $period->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="filter_pstudi_id" class="form-label">Program Studi</label>
                            <select name="pstudi_id" id="filter_pstudi_id" class="form-select">
                                <option value="">Semua program studi</option>
                                @foreach ($pstudi as $studyProgram)
                                    <option value="{{ $studyProgram->id }}" @selected(($filters['pstudi_id'] ?? null) == $studyProgram->id)>
                                        {{ $studyProgram->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="filter_wave" class="form-label">Gelombang</label>
                            <select name="wave" id="filter_wave" class="form-select">
                                <option value="">Semua gelombang</option>
                                @foreach ($waves as $wave)
                                    <option value="{{ $wave }}" @selected(($filters['wave'] ?? null) === $wave)>{{ $wave }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-filter me-1"></i> Terapkan Filter
                                </button>
                                @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                                    <a href="{{ route($prefix.'master.proku-index') }}" class="btn btn-outline-secondary"
                                        title="Reset filter" aria-label="Reset filter">
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
                            <th class="text-center">Program Kuliah</th>
                            <th class="text-center">Tahun Akademik</th>
                            <th class="text-center">Program Studi</th>
                            <th class="text-center">Periode Pendaftaran</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proku as $key => $item)
                            <tr>
                                <td data-label="Number">{{ ++$key }}</td>
                                <td data-label="Program Kuliah">{{ $item->name }}</td>
                                <td data-label="Tahun Akademik">
                                    <span class="program-period-badge">
                                        <i class="fas fa-calendar-alt"></i> {{ $item->taka?->name ?? '-' }}
                                    </span>
                                </td>
                                <td data-label="Program Studi">{{ $item->pstudi?->name ?? '-' }}</td>
                                <td data-label="Periode Pendaftaran">{{ \Carbon\Carbon::parse($item->wave_start)->locale('id')->isoFormat('LL') . ' - ' . \Carbon\Carbon::parse($item->wave_ended)->locale('id')->isoFormat('LL') }}</td>
                                <td class="text-center" data-label="Aksi">
                                    <div class="dropdown program-action-dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                            id="program-action-{{ $item->code }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fas fa-cog me-1"></i> Aksi
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="program-action-{{ $item->code }}">
                                            <li>
                                                <button type="button" class="dropdown-item text-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#updateProku{{ $item->code }}">
                                                    <i class="fas fa-edit"></i>
                                                    <span>Edit Data</span>
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form id="delete-form-{{ $item->code }}"
                                                    action="{{ route($prefix.'master.proku-destroy', $item->code) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="dropdown-item text-danger"
                                                        data-url="{{ route($prefix.'master.proku-destroy', $item->code) }}"
                                                        data-name="{{ $item->name }}"
                                                        onclick="deleteData('{{ $item->code }}')">
                                                        <i class="fas fa-trash-alt"></i>
                                                        <span>Hapus Data</span>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-5 text-center text-muted">
                                    <i class="fas fa-calendar-times fa-2x mb-3"></i>
                                    <p class="mb-1 fw-semibold">Program kuliah tidak ditemukan</p>
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
<form action="{{ route($prefix.'master.proku-store') }}" method="POST">
    @csrf
    <input type="hidden" name="_form" value="create-proku">
    <div class="modal fade" id="createProkuModal" tabindex="-1" aria-labelledby="createProkuModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="createProkuModalLabel">Tambah Data Program Kuliah</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">
            <div class="form-group"><label for="create-proku-period">Tahun Akademik</label><select name="taka_id" id="create-proku-period" class="form-select"><option value="">Pilih Tahun Akademik</option>@foreach ($taka as $item)<option value="{{ $item->id }}" @selected(old('taka_id') == $item->id)>{{ $item->name }}</option>@endforeach</select>@error('taka_id')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="form-group"><label for="create-proku-study-program">Program Studi</label><select name="pstudi_id" id="create-proku-study-program" class="form-select"><option value="">Pilih Program Studi</option>@foreach ($pstudi as $item)<option value="{{ $item->id }}" @selected(old('pstudi_id') == $item->id)>{{ $item->name }}</option>@endforeach</select>@error('pstudi_id')<small class="text-danger">{{ $message }}</small>@enderror</div>
            @foreach (['name' => 'Nama Program Kuliah', 'code' => 'Kode Program Kuliah', 'wave' => 'Gelombang Program Kuliah'] as $field => $label)<div class="form-group"><label for="create-proku-{{ $field }}">{{ $label }}</label><input type="text" class="form-control" name="{{ $field }}" id="create-proku-{{ $field }}" value="{{ old($field) }}" maxlength="20">@error($field)<small class="text-danger">{{ $message }}</small>@enderror</div>@endforeach
            <div class="form-group"><label for="create-proku-start">Periode Mulai Pendaftaran</label><input type="date" class="form-control" name="wave_start" id="create-proku-start" value="{{ old('wave_start') }}">@error('wave_start')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="form-group"><label for="create-proku-end">Periode Akhir Pendaftaran</label><input type="date" class="form-control" name="wave_ended" id="create-proku-end" value="{{ old('wave_ended') }}">@error('wave_ended')<small class="text-danger">{{ $message }}</small>@enderror</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
    </div></div></div>
</form>
<div class="me-1 mb-1 d-inline-block">

    <!--Extra Large Modal -->
    @foreach ($proku as $item)
    <form action="{{ route($prefix.'master.proku-update', $item->code) }}" method="POST" enctype="multipart/form-data">
        @method('patch')
        @csrf
        <div class="modal fade text-left w-100" id="updateProku{{$item->code}}" tabindex="-1" role="dialog"
            aria-labelledby="myModalLabel16" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-l"
                role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel16">Edit Program Kuliah - {{ $item->name }}</h4>
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
                            <div class="form-group">
                                <label for="taka_id">Tahun Akademik</label>
                                <select name="taka_id" id="taka_id" class="form-select">
                                    <option value="" selected>Pilih Tahun Akademik</option>
                                    @foreach ($taka as $tk)
                                        <option value="{{ $tk->id }}" {{ $item->taka_id == $tk->id ? 'selected' : '' }}>{{ $tk->name }}</option>
                                    @endforeach
                                </select>
                                @error('taka_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
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
                            <div class="form-group">
                                <label for="name">Nama Program Kuliah</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Inputkan nama Program Kuliah..." value="{{ $item->name }}">
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="code">Kode Program Kuliah</label>
                                <input type="text" class="form-control" name="code" id="code" placeholder="Inputkan kode Program Kuliah..." maxlength="20" uppercase value="{{ $item->code }}" >
                                @error('code')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="wave">Gelombang Program Kuliah</label>
                                <input type="text" class="form-control" name="wave" id="wave" placeholder="Inputkan Gelombang Program Kuliah..." maxlength="20" uppercase value="{{ $item->wave }}" >
                                @error('wave')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="wave_start">Periode Mulai Pendaftaran</label>
                                <input type="date" class="form-control" name="wave_start" id="wave_start" placeholder="Pilih tanggal Gelombang Mulai Program Kuliah..." value="{{ $item->wave_start }}">
                                @error('wave_start')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="wave_ended">Periode Akhir Pendaftaran</label>
                                <input type="date" class="form-control" name="wave_ended" id="wave_ended" placeholder="Pilih tanggal Gelombang Akhir Program Kuliah..." value="{{ $item->wave_ended }}">
                                @error('wave_ended')
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
    @if ($errors->any() && old('_form') === 'create-proku')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('createProkuModal')).show());</script>
    @endif
@endsection
