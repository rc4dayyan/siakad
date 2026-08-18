@extends('base.base-dash-index')
@section('title')
Data Pengguna Mahasiswa - Siakad By Internal Developer
@endsection
@section('menu')
Data Pengguna Mahasiswa
@endsection
@section('submenu')
Lihat Data
@endsection
@section('urlmenu')
#
@endsection
@section('subdesc')
Halaman untuk melihat data pengguna Mahasiswa
@endsection
@section('custom-css')
<style>
    .student-data-page .dataTable-top {
        margin: 0 0 16px;
        padding: 14px 16px;
        border: 1px solid #dce9e4;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 14px rgba(32, 75, 61, 0.05);
    }

    .student-data-page .dataTable-dropdown label {
        gap: 10px;
        color: #60736d;
        font-weight: 600;
    }

    .student-data-page .dataTable-selector {
        min-width: 76px;
        border-color: #cbded7;
        border-radius: 8px;
        color: #263d36;
        font-weight: 600;
    }

    .authenticated-app .student-data-page .dataTable-search .dataTable-input {
        width: min(320px, 42vw);
        min-height: 44px;
        padding: 9px 14px !important;
        border-color: #cbded7;
        border-radius: 9px;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23789088' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 14px center;
        background-size: 17px;
        color: #263d36;
        font-size: 13px;
        text-indent: 28px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .authenticated-app .student-data-page .dataTable-search .dataTable-input::placeholder {
        color: #8a9b95;
        opacity: 1;
    }

    .student-data-page .dataTable-input:focus,
    .student-data-page .dataTable-selector:focus {
        border-color: #3f8f76;
        box-shadow: 0 0 0 3px rgba(63, 143, 118, 0.13);
        outline: none;
    }

    .student-data-page .student-action-cell {
        width: 1%;
        white-space: nowrap;
    }

    .student-data-page .student-action-dropdown .dropdown-toggle {
        min-width: 88px;
        min-height: 36px;
        margin: 0;
        padding: 7px 12px;
        border-radius: 8px;
        font-weight: 600;
    }

    .student-data-page .student-action-dropdown .dropdown-menu {
        z-index: 1080;
        min-width: 190px;
        padding: 7px;
        border: 1px solid #dce9e4;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(32, 75, 61, 0.16);
    }

    .student-data-page .student-action-dropdown .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-height: 38px;
        padding: 8px 10px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: #354b44;
        font-size: 13px;
        text-align: left;
    }

    .student-data-page .student-action-dropdown .dropdown-item:hover,
    .student-data-page .student-action-dropdown .dropdown-item:focus {
        background: #edf7f3;
        color: #176b55;
    }

    .student-data-page .student-action-dropdown .dropdown-item.text-danger:hover,
    .student-data-page .student-action-dropdown .dropdown-item.text-danger:focus {
        background: #fff0f0;
        color: #c92a2a !important;
    }

    .student-data-page .student-action-dropdown .dropdown-item i {
        width: 17px;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .student-data-page .dataTable-top {
            gap: 12px;
            padding: 13px;
        }

        .student-data-page .dataTable-dropdown,
        .student-data-page .dataTable-dropdown label,
        .student-data-page .dataTable-search,
        .student-data-page .dataTable-input {
            width: 100% !important;
        }

        .student-data-page .dataTable-dropdown label {
            justify-content: space-between;
        }
    }
</style>
@endsection
@section('content')
<section class="section">
    <div class="card student-data-page">
        <div class="card-header">
            <h5 class="card-title d-flex justify-content-between align-items-center">
                @yield('menu')
                <div class="">
                    <a href="{{ route($prefix.'workers.student-promotion-index') }}" class="btn btn-outline-warning" title="Kenaikan semester massal"><i class="fa-solid fa-users-gear"></i></a>
                    <a href="{{ route($prefix.'workers.student-create') }}" class="btn btn-outline-primary"><i class="fa-solid fa-plus"></i></a>
                    <a href="{{ route($prefix.'services.convert.export-student', array_filter($filters ?? [])) }}" class="btn btn-outline-success"><i class="fa-solid fa-file-export"></i></a>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#importStudent" title="Import mahasiswa"><i class="fa-solid fa-file-import"></i></button>
                </div>
            </h5>
        </div>
        <div class="card-body">
            <div class="border rounded-3 bg-light p-3 p-lg-4 my-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h6 class="mb-1"><i class="fas fa-sliders-h text-primary me-2"></i>Filter Data Mahasiswa</h6>
                        <p class="text-muted small mb-0">Saring mahasiswa berdasarkan angkatan, program studi, dan kelas pada periode terpilih.</p>
                    </div>
                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ $student->count() }} mahasiswa ditemukan</span>
                </div>
                <form action="{{ route($prefix.'workers.student-index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label for="filter_angkatan" class="form-label fw-semibold">Angkatan</label>
                        <select name="angkatan" id="filter_angkatan" class="form-select">
                            <option value="">Semua angkatan</option>
                            @foreach ($angkatan as $tahun)
                                <option value="{{ $tahun }}" @selected(($filters['angkatan'] ?? null) == $tahun)>
                                    Angkatan {{ $tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="filter_prodi_id" class="form-label fw-semibold">Program Studi</label>
                        <select name="prodi_id" id="filter_prodi_id" class="form-select">
                            <option value="">Semua program studi</option>
                            @foreach ($programStudi as $item)
                                <option value="{{ $item->id }}" @selected(($filters['prodi_id'] ?? null) == $item->id)>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="filter_kelas_id" class="form-label fw-semibold">Kelas</label>
                        <select name="kelas_id" id="filter_kelas_id" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach ($filterKelas as $item)
                                <option value="{{ $item->id }}" data-prodi-id="{{ $item->pstudi_id }}" @selected(($filters['kelas_id'] ?? null) == $item->id)>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-1"></i> Terapkan
                            </button>
                            @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                                <a href="{{ route($prefix.'workers.student-index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter">
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
                        <th class="text-center">NIM</th>
                        <th class="text-center">NIK</th>
                        <th class="text-center">Nama Mahasiswa</th>
                        <th class="text-center">Program Studi</th>
                        <th class="text-center">Kelas</th>
                        <th class="text-center">Gender</th>
                        <!-- <th class="text-center">Join Date</th> -->
                        <!-- <th class="text-center">Status</th> -->
                        <th class="text-center" data-sortable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($student as $key => $item)

                    @php($currentClass = $item->registrasiAkademik->first()?->kelas ?? $item->kelas)

                    <tr>
                        <td data-label="Number">{{ ++$key }}</td>
                        <td data-label="NIM Mahasiswa">{{ $item->mhs_nim }}</td>
                        <td data-label="NIK Mahasiswa">{{ $item->mhs_nik }}</td>
                        <td data-label="Nama Mahasiswa">{{ $item->mhs_name }}</td>
                        <td data-label="Program Studi">{{ $currentClass?->pstudi?->name ?? '-' }}</td>
                        <td data-label="Kelas">{{ $currentClass?->name ?? '-' }}</td>
                        <td data-label="Gender">{{ $item->mhs_gend }}</td>
                        <!-- <td data-label="Join Date">{{ \Carbon\Carbon::parse($item->mhs_register_date)->format('l, d M Y') }}</td> -->
                        <!-- <td data-label="Status Mahasiswa">{{ $item->mhs_stat }}</td> -->
                        <td class="student-action-cell" data-label="Aksi">
                            <div class="dropdown student-action-dropdown text-center">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                    id="studentAction{{ $item->mhs_code }}" data-bs-toggle="dropdown"
                                    aria-expanded="false" aria-label="Aksi untuk {{ $item->mhs_name }}">
                                    <i class="fas fa-ellipsis-v me-1"></i> Aksi
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="studentAction{{ $item->mhs_code }}">
                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#viewContact{{ $item->mhs_code }}">
                                        <i class="fas fa-address-card text-info"></i>
                                        <span>Lihat Kontak</span>
                                    </a>
                                    <a href="{{ route($prefix.'workers.student-edit', $item->mhs_code) }}" class="dropdown-item">
                                        <i class="fas fa-edit text-primary"></i>
                                        <span>Edit Mahasiswa</span>
                                    </a>
                                    <div class="dropdown-divider my-1"></div>
                                    <form id="delete-form-{{ $item->mhs_code }}"
                                        action="{{ route($prefix.'workers.student-destroy', $item->mhs_code) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="dropdown-item text-danger"
                                            data-url="{{ route($prefix.'workers.student-destroy', $item->mhs_code) }}"
                                            data-name="{{ $item->mhs_name }}"
                                            onclick="deleteData('{{ $item->mhs_code }}')">
                                            <i class="fas fa-trash"></i>
                                            <span>Hapus Mahasiswa</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>

</section>
<div class="me-1 mb-1 d-inline-block">

    <!--Extra Large Modal -->
    <form action="{{ route('web-admin.workers.student-import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal fade text-left w-100" id="importStudent" tabindex="-1" role="dialog"
            aria-labelledby="myModalLabel16" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-l"
                role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel16">Import Mahasiswa</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Unggah file XLSX dengan kolom <strong>NIM</strong>, <strong>Nama</strong>, biodata,
                            alamat, orang tua, <strong>Kode Prodi</strong>, dan <strong>Nama Prodi</strong>. Kode Kecamatan
                            akan dicocokkan dengan master wilayah. Username dan password awal adalah NIM.
                        </p>
                        @error('academic_period')
                            <div class="alert alert-danger py-2">{{ $message }}</div>
                        @enderror
                        <div class="row">
                            <div class="form-group col-12">
                                <label for="class_id" class="form-label">Kelas tujuan</label>
                                <select name="class_id" id="class_id" class="form-select" required>
                                    <option value="">Pilih Kelas</option>
                                    @foreach ($filterKelas as $item)
                                    <option value="{{ $item->id }}" @selected(old('class_id') == $item->id)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12">
                                <label for="import" class="form-label">File XLSX atau CSV (maksimal 5 MB)</label>
                                <input type="file" name="import" id="import" class="form-control" accept=".xlsx,.csv" required>
                                @error('import')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12">
                                <div class="form-check">
                                    <input type="hidden" name="dry_run" value="0">
                                    <input class="form-check-input" type="checkbox" name="dry_run" value="1" id="student_import_dry_run" @checked(old('dry_run'))>
                                    <label class="form-check-label" for="student_import_dry_run">Dry-run (validasi tanpa menyimpan data)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" @disabled(! $academicPeriod)>
                            <i class="fas fa-file-import me-1"></i> Import
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="me-1 mb-1 d-inline-block">

    @foreach ($student as $item)

    <div class="modal fade text-left w-100" id="viewContact{{ $item->mhs_code }}" tabindex="-1" role="dialog"
        aria-labelledby="myModalLabel16" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-l"
            role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel16">Lihat Data Kontak - {{ $item->mhs_name }} </h4>
                    <div class="">

                        <button type="button" class="btn btn-outline-danger mt-1" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-lg-12 col-12">
                            <label for="kode_kelas">Nomor Telepon</label>
                            <div class="d-flex justify-content-between align-items-center">
                                <input type="text" class="form-control" value="{{ $item->mhs_phone }}">
                                <a href="https://wa.me/{{ $item->mhs_phone }}" target="_blank" class="btn btn-outline-success" style="margin-left: 10px"><i class="fa-solid fa-square-phone"></i></a>
                            </div>

                        </div>
                        <div class="form-group col-lg-12 col-12">
                            <label for="kode_kelas">Alamat Email</label>
                            <div class="d-flex justify-content-between align-items-center">
                                <input type="text" class="form-control" value="{{ $item->mhs_mail }}">
                                <a href="mailto:{{ $item->mhs_mail }}" class="btn btn-outline-danger" style="margin-left: 10px"><i class="fa-solid fa-envelope"></i></a>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endsection
@section('custom-js')
<script src="{{ asset('dist') }}/assets/extensions/tinymce/tinymce.min.js"></script>
<script src="{{ asset('dist') }}/assets/static/js/pages/tinymce.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('.student-data-page .dataTable-search input');
    const programSelect = document.getElementById('filter_prodi_id');
    const classSelect = document.getElementById('filter_kelas_id');

    if (searchInput) {
        searchInput.placeholder = 'Cari NIM atau nama mahasiswa...';
        searchInput.setAttribute('aria-label', 'Cari NIM atau nama mahasiswa');
        searchInput.setAttribute('autocomplete', 'off');
        searchInput.setAttribute('spellcheck', 'false');
    }

    document.addEventListener('click', function (event) {
        const actionTrigger = event.target.closest('.student-action-dropdown [data-bs-toggle="dropdown"]');

        if (!actionTrigger) {
            return;
        }

        const menu = actionTrigger.parentElement.querySelector('.dropdown-menu');
        if (!menu || !menu.classList.contains('show')) {
            return;
        }

        const gap = 6;
        const triggerRect = actionTrigger.getBoundingClientRect();
        const menuRect = menu.getBoundingClientRect();
        const opensBelow = window.innerHeight - triggerRect.bottom >= menuRect.height + gap;
        const top = opensBelow
            ? triggerRect.bottom + gap
            : Math.max(gap, triggerRect.top - menuRect.height - gap);
        const left = Math.min(
            window.innerWidth - menuRect.width - gap,
            Math.max(gap, triggerRect.right - menuRect.width)
        );

        menu.style.position = 'fixed';
        menu.style.inset = 'auto';
        menu.style.top = `${top}px`;
        menu.style.left = `${left}px`;
        menu.style.transform = 'none';
    });

    if (!programSelect || !classSelect) {
        return;
    }

    const filterClasses = function (resetSelection) {
        const selectedProgram = programSelect.value;

        Array.from(classSelect.options).forEach(function (option) {
            if (!option.value) {
                return;
            }

            const matches = !selectedProgram || option.dataset.prodiId === selectedProgram;
            option.hidden = !matches;
            option.disabled = !matches;
        });

        if (resetSelection && classSelect.selectedOptions[0]?.disabled) {
            classSelect.value = '';
        }
    };

    filterClasses(false);
    programSelect.addEventListener('change', function () {
        filterClasses(true);
    });
});
</script>
@if ($errors->has('import') || $errors->has('class_id') || $errors->has('academic_period'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('importStudent')).show();
});
</script>
@endif
@endsection
