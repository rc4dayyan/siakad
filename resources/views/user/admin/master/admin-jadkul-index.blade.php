@extends('base.base-dash-index')
@section('title')
Data Master Jadwal Kuliah - Siakad By Internal Developer
@endsection
@section('menu')
Data Master Jadwal Kuliah
@endsection
@section('submenu')
Data Master Jadwal Kuliah
@endsection
@section('urlmenu')
#
@endsection
@section('subdesc')
Halaman untuk mengelola Jadwal Kuliah
@endsection
@section('custom-css')
<style>
    .schedule-filter-panel {
        margin-bottom: 22px;
        padding: 18px;
        border: 1px solid #dce9e4;
        border-radius: 13px;
        background: linear-gradient(135deg, #f6fbf9 0%, #ffffff 100%);
    }

    .schedule-filter-panel__heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
    }

    .schedule-filter-panel__title {
        margin: 0 0 3px;
        color: #263d36;
        font-size: 15px;
        font-weight: 700;
    }

    .schedule-filter-panel__description {
        margin: 0;
        color: #71837d;
        font-size: 12px;
    }

    .schedule-filter-panel .form-label {
        margin-bottom: 6px;
        color: #415a52;
        font-size: 12px;
        font-weight: 700;
    }

    .schedule-filter-panel .form-control,
    .schedule-filter-panel .form-select,
    .schedule-filter-panel .input-group-text {
        min-height: 40px;
        border-color: #d7e4df;
    }

    .schedule-filter-panel .form-control,
    .schedule-filter-panel .form-select {
        border-radius: 9px;
    }

    .schedule-filter-panel .input-group-text {
        border-radius: 9px 0 0 9px;
    }

    .schedule-filter-panel .input-group .form-control {
        border-radius: 0 9px 9px 0;
    }

    .schedule-action-dropdown .dropdown-toggle {
        min-width: 96px;
        border-radius: 8px;
        font-weight: 600;
    }

    .schedule-action-dropdown .dropdown-menu {
        min-width: 205px;
        padding: 7px;
        border: 1px solid #e6ece9;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(38, 61, 54, 0.14);
    }

    .schedule-action-dropdown .dropdown-item {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 11px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        font-size: 13px;
    }

    .schedule-action-dropdown .dropdown-item i {
        width: 16px;
        text-align: center;
    }

    .schedule-table thead th {
        padding-top: 12px;
        padding-bottom: 12px;
        color: #607080;
        font-size: 11px;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .schedule-table td {
        padding-top: 14px;
        padding-bottom: 14px;
        vertical-align: middle;
    }

    .schedule-table__primary {
        display: block;
        color: #25396f;
        font-weight: 600;
    }

    .schedule-table__meta {
        display: block;
        margin-top: 3px;
        color: #7c8a96;
        font-size: 12px;
    }

    .schedule-table__course {
        min-width: 210px;
    }

    .schedule-table__slot {
        min-width: 230px;
    }

    @media (max-width: 767.98px) {
        .schedule-filter-panel__heading,
        .schedule-page-header {
            align-items: flex-start !important;
            flex-direction: column;
        }
    }
</style>

@endsection
@section('content')
<section class="section">
    <div class="card">
        <div class="card-header schedule-page-header d-flex justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1">@yield('submenu')</h5>
                <small class="text-muted">Periode: {{ $selectedPeriod?->name ?? 'Belum dipilih' }}</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                    @if (Route::has($prefix.'master.jadwal-mingguan-index'))
                        <a href="{{ route($prefix.'master.jadwal-mingguan-index') }}" class="btn btn-outline-dark">
                            <i class="fas fa-calendar-week me-1"></i> Jadwal Mingguan
                        </a>
                    @endif
                    @if ($canManageJadwal)
                        <a href="{{ route($prefix.'master.jadkul-create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus me-1"></i> Tambah Jadwal
                        </a>
                        <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importJadwalKuliah">
                            <i class="fa-solid fa-file-import me-1"></i> Import
                        </a>
                    @elseif ($selectedPeriod)
                        <span class="badge bg-secondary">Mode hanya baca</span>
                    @endif
                    @if ($selectedPeriod)
                        <a href="{{ route($prefix.'services.convert.export-jadkul') }}" class="btn btn-outline-success">
                            <i class="fa-solid fa-file-export me-1"></i> Export
                        </a>
                    @endif
            </div>
        </div>
        <div class="card-body">
            <div class="schedule-filter-panel">
                <div class="schedule-filter-panel__heading">
                    <div>
                        <h6 class="schedule-filter-panel__title">
                            <i class="fas fa-sliders-h text-primary me-2"></i>Filter Jadwal Kuliah
                        </h6>
                        <p class="schedule-filter-panel__description">
                            Cari jadwal berdasarkan mata kuliah, kelas, dosen, metode, hari, atau rentang tanggal.
                        </p>
                    </div>
                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ $jadkul->count() }} data ditemukan</span>
                </div>

                <form method="GET" action="{{ route($prefix.'master.jadkul-index') }}" class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label for="schedule_filter_keyword" class="form-label">Pencarian</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="search" name="q" id="schedule_filter_keyword"
                                class="form-control border-start-0 ps-0" value="{{ $filters['q'] ?? '' }}"
                                placeholder="Mata kuliah, kelas, dosen, atau kode jadwal">
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label for="schedule_filter_study_program" class="form-label">Program Studi</label>
                        <select name="pstudi_id" id="schedule_filter_study_program" class="form-select">
                            <option value="">Semua program studi</option>
                            @foreach ($pstudi as $studyProgram)
                                <option value="{{ $studyProgram->id }}" @selected(($filters['pstudi_id'] ?? null) == $studyProgram->id)>
                                    {{ $studyProgram->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label for="schedule_filter_class" class="form-label">Kelas</label>
                        <select name="kelas_id" id="schedule_filter_class" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach ($kelas as $class)
                                <option value="{{ $class->id }}" @selected(($filters['kelas_id'] ?? null) == $class->id)>
                                    {{ $class->name }} ({{ $class->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label for="schedule_filter_lecturer" class="form-label">Dosen Pengajar</label>
                        <select name="dosen_id" id="schedule_filter_lecturer" class="form-select">
                            <option value="">Semua dosen</option>
                            @foreach ($dosen as $lecturer)
                                <option value="{{ $lecturer->id }}" @selected(($filters['dosen_id'] ?? null) == $lecturer->id)>
                                    {{ $lecturer->dsn_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="schedule_filter_method" class="form-label">Metode</label>
                        <select name="meth_id" id="schedule_filter_method" class="form-select">
                            <option value="">Semua metode</option>
                            <option value="0" @selected(isset($filters['meth_id']) && (int) $filters['meth_id'] === 0)>Tatap Muka</option>
                            <option value="1" @selected(isset($filters['meth_id']) && (int) $filters['meth_id'] === 1)>Teleconference</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="schedule_filter_day" class="form-label">Hari</label>
                        <select name="days_id" id="schedule_filter_day" class="form-select">
                            <option value="">Semua hari</option>
                            @foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'] as $dayId => $dayName)
                                <option value="{{ $dayId }}" @selected(isset($filters['days_id']) && (int) $filters['days_id'] === $dayId)>{{ $dayName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="schedule_filter_room" class="form-label">Ruangan</label>
                        <select name="ruang_id" id="schedule_filter_room" class="form-select">
                            <option value="">Semua ruangan</option>
                            @foreach ($ruang as $room)
                                <option value="{{ $room->id }}" @selected(($filters['ruang_id'] ?? null) == $room->id)>
                                    {{ $room->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label for="schedule_filter_date_from" class="form-label">Tanggal Mulai</label>
                        <input type="date" name="date_from" id="schedule_filter_date_from" class="form-control"
                            value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label for="schedule_filter_date_to" class="form-label">Tanggal Akhir</label>
                        <input type="date" name="date_to" id="schedule_filter_date_to" class="form-control"
                            value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-1"></i> Terapkan
                            </button>
                            @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                                <a href="{{ route($prefix.'master.jadkul-index') }}" class="btn btn-outline-secondary"
                                    title="Reset filter" aria-label="Reset filter">
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover schedule-table" id="table1">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Kelas &amp; Prodi</th>
                            <th>Mata Kuliah</th>
                            <th>Dosen</th>
                            <th>Jadwal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jadkul as $key => $item)
                            <tr>
                                <td class="text-center" data-label="Nomor">{{ ++$key }}</td>
                                <td data-label="Kelas & Prodi">
                                    <span class="schedule-table__primary">{{ $item->kelas->name ?? $item->kelas->code ?? '—' }}</span>
                                    <small class="schedule-table__meta">
                                        {{ $item->kelas->code ?? '—' }} · {{ $item->kelas->pstudi->name ?? 'Program studi tidak tersedia' }}
                                    </small>
                                </td>
                                <td class="schedule-table__course" data-label="Mata Kuliah">
                                    <span class="schedule-table__primary">{{ $item->matkul->name ?? '—' }}</span>
                                    <small class="schedule-table__meta">{{ $item->pert_id }} · {{ $item->bsks }} SKS</small>
                                </td>
                                <td data-label="Dosen">
                                    <span class="schedule-table__primary">{{ $item->dosen->dsn_name ?? 'Belum ditentukan' }}</span>
                                </td>
                                <td class="schedule-table__slot" data-label="Jadwal">
                                    <span class="schedule-table__primary">
                                        {{ $item->days_id }}, {{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}
                                    </span>
                                    <small class="schedule-table__meta">
                                        <i class="far fa-clock me-1"></i>{{ $item->start }}–{{ $item->ended }}
                                        <span class="mx-1">·</span>
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $item->ruang->name ?? 'Tanpa ruangan' }}
                                    </small>
                                    <span class="badge bg-light-primary text-primary mt-2">{{ $item->meth_id }}</span>
                                </td>
                                <td class="text-center" data-label="Aksi">
                                    <div class="dropdown schedule-action-dropdown d-inline-block">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                                            id="schedule-action-{{ $item->code }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog me-1"></i> Aksi
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="schedule-action-{{ $item->code }}">
                                            <li>
                                                <a href="{{ route($prefix.'master.jadkul-absen-view', $item->code) }}" class="dropdown-item text-info">
                                                    <i class="fa-solid fa-user-check"></i> Lihat Absensi
                                                </a>
                                            </li>
                                            @if ($canManageJadwal)
                                                <li>
                                                    <button type="button" class="dropdown-item text-primary" data-bs-toggle="modal"
                                                        data-bs-target="#updateJadkul{{ $item->code }}">
                                                        <i class="fas fa-edit"></i> Edit Jadwal
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <form id="delete-form-{{ $item->code }}"
                                                        action="{{ route($prefix.'master.jadkul-destroy', $item->code) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="dropdown-item text-danger"
                                                            data-url="{{ route($prefix.'master.jadkul-destroy', $item->code) }}"
                                                            data-name="{{ $item->matkul->name ?? 'jadwal ini' }}"
                                                            onclick="deleteData('{{ $item->code }}')">
                                                            <i class="fas fa-trash"></i> Hapus Jadwal
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada jadwal pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</section>
@if ($canManageJadwal)
<div class="me-1 mb-1 d-inline-block">
    <form action="{{ route($prefix.'services.convert.import-jadkul') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal fade text-left w-100" id="importJadwalKuliah" tabindex="-1" role="dialog"
            aria-labelledby="importJadwalKuliahLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="importJadwalKuliahLabel">Import Jadwal Kuliah</h4>
                        <div>
                            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-paper-plane"></i></button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Gunakan hasil export sebagai template. Metode: 0 Tatap Muka, 1 Teleconference. Hari: 0 Minggu sampai 6 Sabtu. Kode jadwal yang sudah ada akan dilewati.
                        </p>
                        <div class="form-group">
                            <label for="import_jadkul">Import Files (xlsx, csv)</label>
                            <input type="file" name="import" id="import_jadkul" class="form-control" accept=".xlsx,.csv" required>
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
    @foreach ($jadkul as $item)
    <form action="{{ route($prefix.'master.jadkul-update', $item->code) }}" method="POST" enctype="multipart/form-data">
        @method('patch')
        @csrf
        <div class="modal fade text-left w-100" id="updateJadkul{{$item->code}}" tabindex="-1" role="dialog"
            aria-labelledby="myModalLabel16" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl"
                role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel16">Edit Jadwal Perkuliahan - {{ ($item->matkul->name ?? '') . ' ' .$item->pert_id }}</h4>
                        <div class="">

                            <button type="submit" class="mt-1 btn btn-outline-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button type="button" class="mt-1 btn btn-outline-danger" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="row">

                            <div class="form-group col-lg-3 col-12">
                                <label for="makul_id">Mata Kuliah</label>
                                <select name="makul_id" id="makul_id" class="form-select" readonly>
                                    <option value="" selected>Pilih Mata Kuliah</option>
                                    @foreach ($matkul as $item_m)
                                    @php
                                    $dosen1_name = isset($item_m->dosen1) ? $item_m->dosen1->dsn_name : null;
                                    $dosen2_name = isset($item_m->dosen2) ? $item_m->dosen2->dsn_name : null;
                                    $dosen3_name = isset($item_m->dosen3) ? $item_m->dosen3->dsn_name : null;
                                    @endphp
                                    <option value="{{ $item_m->id }}" {{ $item->makul_id == $item_m->id ? 'selected' : '' }} data-dosen1="{{ $item_m->dosen_1 }}" data-dosen2="{{ $item_m->dosen_2 }}" data-dosen3="{{ $item_m->dosen_3 }}" data-dosen1-name="{{ $dosen1_name }}" data-dosen2-name="{{ $dosen2_name }}" data-dosen3-name="{{ $dosen3_name }}">{{ $item_m->name }}</option>
                                    @endforeach
                                </select>
                                @error('makul_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="pert_id">Pertemuan</label>
                                <select name="pert_id" id="pert_id" class="form-select" readonly>
                                    <option value="" selected>Pilih Pertemuan</option>
                                    <option value="1" {{ $item->raw_pert_id == 1 ? 'selected' : '' }}>Pertemuan 1</option>
                                    <option value="2" {{ $item->raw_pert_id == 2 ? 'selected' : '' }}>Pertemuan 2</option>
                                    <option value="3" {{ $item->raw_pert_id == 3 ? 'selected' : '' }}>Pertemuan 3</option>
                                    <option value="4" {{ $item->raw_pert_id == 4 ? 'selected' : '' }}>Pertemuan 4</option>
                                    <option value="5" {{ $item->raw_pert_id == 5 ? 'selected' : '' }}>Pertemuan 5</option>
                                    <option value="6" {{ $item->raw_pert_id == 6 ? 'selected' : '' }}>Pertemuan 6</option>
                                    <option value="7" {{ $item->raw_pert_id == 7 ? 'selected' : '' }}>Pertemuan 7</option>
                                    <option value="8" {{ $item->raw_pert_id == 8 ? 'selected' : '' }}>Pertemuan 8</option>
                                    <option value="9" {{ $item->raw_pert_id == 9 ? 'selected' : '' }}>Pertemuan 9</option>
                                    <option value="10" {{ $item->raw_pert_id == 10 ? 'selected' : '' }}>Pertemuan 10</option>
                                    <option value="11" {{ $item->raw_pert_id == 11 ? 'selected' : '' }}>Pertemuan 11</option>
                                    <option value="12" {{ $item->raw_pert_id == 12 ? 'selected' : '' }}>Pertemuan 12</option>
                                    <option value="13" {{ $item->raw_pert_id == 13 ? 'selected' : '' }}>Pertemuan 13</option>
                                    <option value="14" {{ $item->raw_pert_id == 14 ? 'selected' : '' }}>Pertemuan 14</option>
                                    <option value="15" {{ $item->raw_pert_id == 15 ? 'selected' : '' }}>Pertemuan 15</option>
                                    <option value="16" {{ $item->raw_pert_id == 16 ? 'selected' : '' }}>Pertemuan 16</option>
                                </select>
                                @error('pert_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="meth_id">Metode Perkuliahan</label>
                                <select name="meth_id" id="meth_id" class="form-select">
                                    <option value="" selected>Pilih Metode Perkuliahan</option>
                                    <option value="0" {{ $item->raw_meth_id == 0 ? 'selected' : '' }}>Tatap Muka</option>
                                    <option value="1" {{ $item->raw_meth_id == 1 ? 'selected' : '' }}>Teleconference</option>
                                </select>
                                @error('meth_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="bsks">Bebas SKS Hari Ini</label>
                                <input type="number" min="1" max="8" name="bsks" id="bsks" class="form-control" value="{{ $item->bsks }}">
                                @error('bsks')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="days_id">Hari</label>
                                <select name="days_id" id="days_id" class="form-select">
                                    <option value="" selected>Pilih Hari</option>
                                    <option value="0" {{ $item->raw_days_id == 0 ? 'selected' : '' }}>Hari Minggu</option>
                                    <option value="1" {{ $item->raw_days_id == 1 ? 'selected' : '' }}>Hari Senin</option>
                                    <option value="2" {{ $item->raw_days_id == 2 ? 'selected' : '' }}>Hari Selasa</option>
                                    <option value="3" {{ $item->raw_days_id == 3 ? 'selected' : '' }}>Hari Rabu</option>
                                    <option value="4" {{ $item->raw_days_id == 4 ? 'selected' : '' }}>Hari Kamis</option>
                                    <option value="5" {{ $item->raw_days_id == 5 ? 'selected' : '' }}>Hari Jum'at</option>
                                    <option value="6" {{ $item->raw_days_id == 6 ? 'selected' : '' }}>Hari Sabtu</option>
                                </select>
                                @error('days_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="date">Tanggal Perkuliahan</label>
                                <input type="date" name="date" id="date" class="form-control" value="{{ $item->date }}">
                                @error('date')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="start">Waktu Mulai Perkuliahan</label>
                                <input type="time" name="start" id="start" class="form-control" value="{{ \Carbon\Carbon::parse($item->start)->format('H:i') }}">
                                @error('start')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="ended">Waktu Selesai Perkuliahan</label>
                                <input type="time" name="ended" id="ended" class="form-control" value="{{ \Carbon\Carbon::parse($item->ended)->format('H:i') }}">
                                @error('ended')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-4 col-12">
                                <label for="ruang_id">Ruangan</label>
                                <select name="ruang_id" id="ruang_id" class="form-select">
                                    <option value="" selected>Pilih Ruangan</option>
                                    @foreach ($ruang as $item_r)
                                    <option value="{{ $item_r->id }}" {{ $item->ruang_id == $item_r->id ? 'selected' : '' }}>{{ $item_r->name }}</option>
                                    @endforeach
                                </select>
                                @error('ruang_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-4 col-12">
                                <label for="kelas_id">Kelas</label>
                                <select name="kelas_id" id="kelas_id" class="form-select">
                                    <option value="" selected>Pilih Kelas</option>
                                    @foreach ($kelas as $item_k)
                                    <option value="{{ $item_k->id }}" {{ $item->kelas_id == $item_k->id ? 'selected' : '' }}>{{ $item_k->name }}</option>
                                    @endforeach
                                </select>
                                @error('kelas_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group col-lg-4 col-12">
                                <label for="dosen_id">Dosen</label>
                                <select name="dosen_id" id="dosen_id" class="form-select">
                                    <option value="" selected>Pilih Dosen</option>
                                    <option value="{{ !isset($item->matkul->dosen_1) ? '' : $item->matkul->dosen_1 }}" {{ isset($item->matkul->dosen_1) && $item->matkul->dosen_1 == $item->dosen_id ? 'selected' : ''  }} {{ !isset($item->matkul->dosen_1) ? 'disabled' : '' }}>{{ !isset($item->matkul->dosen_1) ? 'Tidak Tersedia' : $item->matkul->dosen1->dsn_name }}</option>
                                    <option value="{{ !isset($item->matkul->dosen_2) ? '' : $item->matkul->dosen_2 }}" {{ isset($item->matkul->dosen_2) && $item->matkul->dosen_2 == $item->dosen_id ? 'selected' : ''  }} {{ !isset($item->matkul->dosen_2) ? 'disabled' : '' }}>{{ !isset($item->matkul->dosen_2) ? 'Tidak Tersedia' : $item->matkul->dosen2->dsn_name }}</option>
                                    <option value="{{ !isset($item->matkul->dosen_3) ? '' : $item->matkul->dosen_3 }}" {{ isset($item->matkul->dosen_3) && $item->matkul->dosen_3 == $item->dosen_id ? 'selected' : ''  }} {{ !isset($item->matkul->dosen_3) ? 'disabled' : '' }}>{{ !isset($item->matkul->dosen_3) ? 'Tidak Tersedia' : $item->matkul->dosen3->dsn_name }}</option>
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
@endif
@endsection
@section('custom-js')


@endsection
