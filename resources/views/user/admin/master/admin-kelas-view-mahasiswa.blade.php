@extends('base.base-dash-index')
@section('title')
Daftar Mahasiswa Kelas {{ $kelas->name }} - Siakad By Internal Developer
@endsection
@section('menu')
Data Kelas
@endsection
@section('submenu')
Daftar Mahasiswa Kelas {{ $kelas->name }}
@endsection
@section('urlmenu')
#
@endsection
@section('subdesc')
Halaman untuk melihat data Mahasiswa pada kelas {{ $kelas->name }}
@endsection
@section('custom-css')
<link rel="stylesheet" href="{{ asset('dist') }}/assets/extensions/choices.js/public/assets/styles/choices.min.css">
<style>
    table {
        border: 1px solid #ccc;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        width: 100%;
        table-layout: fixed;
    }

    table caption {
        font-size: 1.5em;
        margin: .5em 0 .75em;
    }

    table tr {
        /* background-color: #f8f8f8; */
        border: 1px solid #ddd;
        padding: .35em;
    }

    table th,
    table td {
        padding: .625em;
        text-align: center;
    }

    table th {
        font-size: .85em;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .class-student-filter {
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border: 1px solid #e9ecef;
        border-radius: .75rem;
        padding: 1rem;
    }

    @media screen and (max-width: 600px) {
        table {
            border: 0;
        }

        table caption {
            font-size: 1.3em;
        }

        table thead {
            border: none;
            clip: rect(0 0 0 0);
            height: 1px;
            margin: -1px;
            overflow: hidden;
            padding: 0;
            position: absolute;
            width: 1px;
        }

        table tr {
            border-bottom: 3px solid #ddd;
            display: block;
            margin-bottom: .625em;
        }

        table td {
            border-bottom: 1px solid #ddd;
            display: block;
            font-size: .8em;
            text-align: right;
        }

        table td::before {
            /*
    * aria-label has no advantage, it won't be read inside a table
    content: attr(aria-label);
    */
            content: attr(data-label);
            float: left;
            font-weight: bold;
            text-transform: uppercase;
        }

        table td:last-child {
            border-bottom: 0;
        }
    }
</style>
@endsection
@section('content')
<section class="section">
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-1">@yield('submenu')</h5>
                    <small class="text-muted">{{ $classStudentCount }} dari {{ $kelas->capacity }} mahasiswa terisi pada {{ $period->name }}.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if ($canAssignStudents)
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignMahasiswaModal" @disabled($availableStudents->isEmpty() || ($kelas->capacity && $classStudentCount >= $kelas->capacity))>
                            <i class="fa-solid fa-user-plus me-1"></i> Tambah Mahasiswa
                        </button>
                    @endif
                    <a href="{{ route($prefix.'master.kelas-index') }}" class="btn btn-outline-warning"><i class="fa-solid fa-backward"></i></a>
                    <form action="{{ route($prefix.'master.kelas-mahasiswa-cetak', $kelas->code) }}" method="post" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-file-pdf"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if (! $canAssignStudents)
                <div class="alert alert-info">Penempatan mahasiswa tidak dapat diubah karena periode akademik ini sudah ditutup atau diarsipkan.</div>
            @elseif ($kelas->capacity && $classStudentCount >= $kelas->capacity)
                <div class="alert alert-warning">Kelas telah mencapai kapasitas maksimum.</div>
            @elseif ($availableStudents->isEmpty())
                <div class="alert alert-info">Tidak ada mahasiswa lain dari program studi yang sama yang dapat ditempatkan pada kelas ini.</div>
            @endif
            <form method="GET" action="{{ route($prefix.'master.kelas-mahasiswa-view', $kelas->code) }}" class="class-student-filter mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h6 class="mb-1"><i class="fa-solid fa-sliders me-2 text-primary"></i>Filter Daftar Mahasiswa</h6>
                        <small class="text-muted">Temukan mahasiswa dalam kelas berdasarkan identitas dan atributnya.</small>
                    </div>
                    @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                        <a href="{{ route($prefix.'master.kelas-mahasiswa-view', $kelas->code) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-rotate-left me-1"></i> Reset Filter
                        </a>
                    @endif
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-lg-5 col-md-6">
                        <label for="class_student_search" class="form-label small fw-semibold">Cari mahasiswa</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="search" name="q" id="class_student_search" class="form-control border-start-0" value="{{ $filters['q'] ?? '' }}" placeholder="NIM atau nama mahasiswa" maxlength="100">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label for="class_student_gender" class="form-label small fw-semibold">Jenis kelamin</label>
                        <select name="gender" id="class_student_gender" class="form-select">
                            <option value="">Semua</option>
                            <option value="L" @selected(($filters['gender'] ?? '') === 'L')>Laki-laki</option>
                            <option value="P" @selected(($filters['gender'] ?? '') === 'P')>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3">
                        <label for="class_student_sort" class="form-label small fw-semibold">Urutkan</label>
                        <select name="sort" id="class_student_sort" class="form-select">
                            <option value="name_asc" @selected(($filters['sort'] ?? 'name_asc') === 'name_asc')>Nama A–Z</option>
                            <option value="name_desc" @selected(($filters['sort'] ?? '') === 'name_desc')>Nama Z–A</option>
                            <option value="nim_asc" @selected(($filters['sort'] ?? '') === 'nim_asc')>NIM terkecil</option>
                            <option value="nim_desc" @selected(($filters['sort'] ?? '') === 'nim_desc')>NIM terbesar</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-12 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-1"></i> Terapkan</button>
                    </div>
                </div>
                <div class="mt-3 small text-muted" aria-live="polite">
                    Menampilkan <span class="fw-semibold text-dark">{{ $mahasiswa->count() }}</span> dari <span class="fw-semibold text-dark">{{ $classStudentCount }}</span> mahasiswa.
                </div>
            </form>
            <table class="table table-striped" id="table1" data-dashboard-searchable="false">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Nomor NIM</th>
                        <th class="text-center">Nama Mahasiswa</th>
                        <th class="text-center">Gender</th>
                        <th class="text-center">Kehadiran</th>
                        <th class="text-center">Presentase Kehadiran</th>
                        <th class="text-center">Button</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mahasiswa as $key => $item)
                    <tr>
                        <td data-label="Number">{{ ++$key }}</td>
                        <td data-label="Nomor NIM">{{ $item->mhs_nim }}</td>
                        <td data-label="Nama Mahasiswa">{{ $item->mhs_name }}</td>
                        <td data-label="Jenis Kelamin">{{ $item->mhs_gend == null ? '-' : $item->mhs_gend }}</td>
                        @php
                        $dateNow = \Carbon\Carbon::now()->format('m-d-Y');
                        $timeNow = \Carbon\Carbon::now()->format('H:i:s');

                        $jadkul = \App\Models\JadwalKuliah::where('kelas_id', $kelas->id)->get();
                        $absen = \App\Models\AbsensiMahasiswa::where('author_id', $item->id)->get();
                        // dd($absen->count());
                        $totalJadkul = $jadkul->count() > 0 ? $jadkul->count() : 1;
                        @endphp
                        <td data-label="Data Kehadiran">{{ $absen->count() }} / {{ $totalJadkul }} Perkuliahan</td>
                        <td data-label="Presentase Kehadiran">{{ $absen->count() / $totalJadkul * 100 }} %</td>

                        <td class="d-flex justify-content-center align-items-center">
                            <a href="{{ route($prefix.'workers.student-edit', $item->mhs_code) }}" class="btn btn-outline-primary" title="Buka data mahasiswa"><i class="fas fa-edit"></i></a>
                            {{-- <a href="{{ route($prefix.'master.jadkul-view-absen', $item->code) }}" style="margin-right: 10px" class="btn btn-outline-info"><i class="fa-solid fa-user-check"></i></a> --}}
                            {{-- <form id="delete-form-{{ $item->code }}"
                            action="{{ route($prefix.'master.jadkul-destroy', $item->code) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <a type="button" class="bs-tooltip btn btn-rounded btn-outline-danger"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"
                                data-original-title="Delete"
                                data-url="{{ route($prefix.'master.jadkul-destroy', $item->code) }}"
                                data-name="{{ $item->name }}"
                                onclick="deleteData('{{ $item->code }}')">
                                <i class="fas fa-trash"></i>
                            </a>
                            </form> --}}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fa-solid fa-user-slash d-block fs-4 mb-2"></i>
                            {{ $classStudentCount ? 'Tidak ada mahasiswa yang sesuai dengan filter.' : 'Belum ada mahasiswa pada kelas ini.' }}
                        </td>
                    </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>

</section>
@if ($canAssignStudents && $availableStudents->isNotEmpty() && (! $kelas->capacity || $classStudentCount < $kelas->capacity))
<div class="modal fade" id="assignMahasiswaModal" tabindex="-1" aria-labelledby="assignMahasiswaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route($prefix.'master.kelas-mahasiswa-assign', $kelas->code) }}" method="POST">
                @csrf
                <input type="hidden" name="form" value="assign_student">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignMahasiswaModalLabel">Tambah Mahasiswa ke {{ $kelas->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        Mahasiswa yang sudah terdaftar pada periode {{ $period->name }} akan dipindahkan ke kelas ini. Pemindahan ditolak bila KRS sudah berisi mata kuliah.
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="assign_mahasiswa_id" class="form-label fw-semibold">Pilih mahasiswa</label>
                            <select name="mahasiswa_id" id="assign_mahasiswa_id" class="form-select" required data-placeholder="Cari NIM atau nama mahasiswa...">
                                <option value="">Pilih mahasiswa</option>
                                @foreach ($availableStudents as $student)
                                    @php
                                        $currentRegistration = $student->registrasiAkademik->firstWhere('taka_id', $period->id);
                                        $latestRegistration = $student->registrasiAkademik->first();
                                        $sourceClass = $currentRegistration?->kelas ?? $student->kelas;
                                        $suggestedSemester = $currentRegistration?->semester_mahasiswa
                                            ?? min(14, max(1, ((int) ($latestRegistration?->semester_mahasiswa ?? 0)) + 1));
                                    @endphp
                                    <option value="{{ $student->id }}"
                                        data-existing-registration="{{ $currentRegistration ? '1' : '0' }}"
                                        data-source-class-name="{{ $sourceClass?->name ?? 'Belum memiliki kelas' }}"
                                        data-semester="{{ $suggestedSemester }}"
                                        data-status="{{ $currentRegistration?->status_akademik ?? \App\Models\RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF }}"
                                        data-advisor="{{ $currentRegistration?->dosen_wali_id ?? $kelas->dosen_id }}"
                                        data-credit-limit="{{ $currentRegistration?->batas_sks ?? 24 }}"
                                        @selected((int) old('mahasiswa_id') === $student->id)>
                                        {{ $student->mhs_nim }} — {{ $student->mhs_name }} ({{ $currentRegistration ? 'pindah dari '.($sourceClass?->name ?? 'tanpa kelas') : 'registrasi baru' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('mahasiswa_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <div class="d-flex flex-wrap justify-content-between gap-2 mt-2">
                                <div id="student_selection_meta" class="small text-muted" aria-live="polite">Pilih mahasiswa untuk melihat detail penempatannya.</div>
                                <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>Hanya mahasiswa dari program studi yang sama.</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="assign_semester" class="form-label">Semester</label>
                            <select name="semester_mahasiswa" id="assign_semester" class="form-select" required>
                                @for ($semester = 1; $semester <= 14; $semester++)
                                    <option value="{{ $semester }}" @selected((int) old('semester_mahasiswa', 1) === $semester)>Semester {{ $semester }}</option>
                                @endfor
                            </select>
                            @error('semester_mahasiswa')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-3">
                            <label for="assign_status" class="form-label">Status akademik</label>
                            <select name="status_akademik" id="assign_status" class="form-select" required>
                                @foreach (\App\Models\RegistrasiMahasiswa::academicStatuses() as $status)
                                    <option value="{{ $status }}" @selected(old('status_akademik', \App\Models\RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF) === $status)>
                                        {{ \App\Models\RegistrasiMahasiswa::academicStatusLabel($status) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status_akademik')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="assign_advisor" class="form-label">Dosen wali</label>
                            <select name="dosen_wali_id" id="assign_advisor" class="form-select" required>
                                <option value="">Pilih dosen wali</option>
                                @foreach ($academicAdvisors as $advisor)
                                    <option value="{{ $advisor->id }}" @selected((int) old('dosen_wali_id', $kelas->dosen_id) === $advisor->id)>{{ $advisor->dsn_name }}</option>
                                @endforeach
                            </select>
                            @error('dosen_wali_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-2">
                            <label for="assign_credit_limit" class="form-label">Batas SKS</label>
                            <input type="number" name="batas_sks" id="assign_credit_limit" class="form-control" min="1" max="24" value="{{ old('batas_sks', 24) }}" required>
                            @error('batas_sks')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i> Simpan Penempatan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
@section('custom-js')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="{{ asset('dist') }}/assets/extensions/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const studentSelect = document.getElementById('assign_mahasiswa_id');
        const semester = document.getElementById('assign_semester');
        const status = document.getElementById('assign_status');
        const advisor = document.getElementById('assign_advisor');
        const creditLimit = document.getElementById('assign_credit_limit');
        const selectionMeta = document.getElementById('student_selection_meta');

        if (studentSelect) {
            new Choices(studentSelect, {
                allowHTML: false,
                searchEnabled: true,
                searchChoices: true,
                shouldSort: false,
                itemSelectText: '',
                placeholder: true,
                placeholderValue: 'Cari NIM atau nama mahasiswa...',
                searchPlaceholderValue: 'Ketik NIM atau nama...',
                noResultsText: 'Mahasiswa tidak ditemukan',
                noChoicesText: 'Tidak ada mahasiswa yang tersedia',
                searchResultLimit: 50,
            });

            const setAcademicFieldsDisabled = function (disabled) {
                [semester, status, advisor, creditLimit].forEach(function (field) {
                    field.disabled = disabled;
                    field.classList.toggle('bg-light', disabled);
                });
            };

            const updateSelection = function () {
                const option = studentSelect.options[studentSelect.selectedIndex];
                if (! option || ! option.value) {
                    setAcademicFieldsDisabled(false);
                    selectionMeta.textContent = 'Pilih mahasiswa untuk melihat detail penempatannya.';
                    return;
                }

                semester.value = option.dataset.semester || '1';
                status.value = option.dataset.status || 'aktif';
                advisor.value = option.dataset.advisor || '';
                creditLimit.value = option.dataset.creditLimit || '24';

                const moving = option.dataset.existingRegistration === '1';
                setAcademicFieldsDisabled(moving);
                const badge = document.createElement('span');
                badge.className = moving ? 'badge bg-warning text-dark me-2' : 'badge bg-success me-2';
                badge.textContent = moving ? 'Pindah kelas' : 'Registrasi baru';
                const description = document.createTextNode(moving
                    ? 'Data registrasi tetap dipertahankan dari kelas ' + option.dataset.sourceClassName + '.'
                    : 'Lengkapi semester, status akademik, dosen wali, dan batas SKS.');
                selectionMeta.replaceChildren(badge, description);
            };

            studentSelect.addEventListener('change', function () {
                updateSelection();
            });
            updateSelection();
        }

        @if (old('form') === 'assign_student' && $errors->any())
            bootstrap.Modal.getOrCreateInstance(document.getElementById('assignMahasiswaModal')).show();
        @endif
    });
</script>
@endsection
