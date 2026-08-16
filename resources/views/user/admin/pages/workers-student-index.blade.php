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
@section('content')
<section class="section">
    <div class="card">
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
            <form action="{{ route($prefix.'workers.student-index') }}" method="GET" class="row g-2 mb-4 align-items-end">
                <div class="col-md-4">
                    <label for="filter_angkatan" class="form-label">Angkatan</label>
                    <select name="angkatan" id="filter_angkatan" class="form-select">
                        <option value="">Semua Angkatan</option>
                        @foreach ($angkatan as $tahun)
                            <option value="{{ $tahun }}" @selected(($filters['angkatan'] ?? null) == $tahun)>
                                Angkatan {{ $tahun }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter_kelas_id" class="form-label">Kelas</label>
                    <select name="kelas_id" id="filter_kelas_id" class="form-select">
                        <option value="">Semua Kelas</option>
                        @foreach ($filterKelas as $item)
                            <option value="{{ $item->id }}" @selected(($filters['kelas_id'] ?? null) == $item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    @if (filled($filters['angkatan'] ?? null) || filled($filters['kelas_id'] ?? null))
                        <a href="{{ route($prefix.'workers.student-index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
            <table class="table table-striped" id="table1">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">NIM</th>
                        <th class="text-center">NIK</th>
                        <th class="text-center">Nama Mahasiswa</th>
                        <th class="text-center">Kelas</th>
                        <th class="text-center">Gender</th>
                        <!-- <th class="text-center">Join Date</th> -->
                        <!-- <th class="text-center">Status</th> -->
                        <th class="text-center">Button</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($student as $key => $item)

                    <tr>
                        <td data-label="Number">{{ ++$key }}</td>
                        <td data-label="NIM Mahasiswa">{{ $item->mhs_nim }}</td>
                        <td data-label="NIK Mahasiswa">{{ $item->mhs_nik }}</td>
                        <td data-label="Nama Mahasiswa">{{ $item->mhs_name }}</td>
                        <td data-label="Kelas">{{ $item->registrasiAkademik->first()?->kelas?->name ?? $item->kelas?->name ?? '' }}</td>
                        <td data-label="Gender">{{ $item->mhs_gend }}</td>
                        <!-- <td data-label="Join Date">{{ \Carbon\Carbon::parse($item->mhs_register_date)->format('l, d M Y') }}</td> -->
                        <!-- <td data-label="Status Mahasiswa">{{ $item->mhs_stat }}</td> -->
                        <td>
                            <div class="d-flex justify-content-center align-items-center w-100" style="padding: 10px;">

                                <a href="#" style="margin-right: 10px" data-bs-toggle="modal" data-bs-target="#viewContact{{ $item->mhs_code }}" class="btn btn-outline-info"><i class="fas fa-phone"></i></a>
                                <a href="{{ route($prefix.'workers.student-edit', $item->mhs_code) }}" style="margin-right: 10px" class="btn btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <form id="delete-form-{{ $item->mhs_code }}"
                                    action="{{ route($prefix.'workers.student-destroy', $item->mhs_code) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <a type="button" class="bs-tooltip btn btn-rounded btn-outline-danger"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"
                                        data-original-title="Delete"
                                        data-url="{{ route($prefix.'workers.student-destroy', $item->mhs_code) }}"
                                        data-name="{{ $item->name }}"
                                        onclick="deleteData('{{ $item->mhs_code }}')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </form>
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
@if ($errors->has('import') || $errors->has('class_id') || $errors->has('academic_period'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('importStudent')).show();
});
</script>
@endif
@endsection
