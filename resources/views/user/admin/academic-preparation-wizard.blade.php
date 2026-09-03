@extends('base.base-dash-index')

@section('menu', 'Persiapan Akademik Baru')
@section('submenu', 'Wizard Persiapan Akademik Baru')
@section('urlmenu', route('web-admin.academic-preparation.index'))
@section('subdesc', 'Tambahkan periode, kelas, dosen pengajar, dan KRS secara berurutan')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">
            <i class="fas fa-circle-check me-1"></i> {{ session('status') }}
        </div>
    @endif

    @if (session('created_period_code'))
        <div class="card border-success mb-4">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="text-success mb-1">Persiapan dasar selesai</h5>
                    <p class="text-muted mb-0">Periode {{ session('created_period_code') }} sudah dibuat dan siap dilengkapi.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('web-admin.period-opening.index') }}" class="btn btn-primary">Lanjutkan Kesiapan Periode</a>
                    <a href="{{ route('web-admin.master.taka-index') }}" class="btn btn-outline-primary">Lihat Periode Akademik</a>
                </div>
            </div>
        </div>
    @endif

    @if (session('classes_imported'))
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="fas fa-school me-1"></i> Data kelas sudah siap digunakan pada periode yang dipilih.</span>
            <a href="{{ route('web-admin.master.kelas-index') }}" class="btn btn-sm btn-outline-primary">Kelola Data Kelas</a>
        </div>
    @endif

    @if (session('teaching_lecturers_imported'))
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="fas fa-person-chalkboard me-1"></i> Dosen pengajar dan penawaran mata kuliah sudah berhasil disiapkan.</span>
            <div class="d-flex gap-2">
                <a href="{{ route('web-admin.dosen-pengajar-list.index') }}" class="btn btn-sm btn-outline-primary">Lihat Dosen Pengajar</a>
                <a href="{{ route('web-admin.master.penawaran-index') }}" class="btn btn-sm btn-outline-primary">Lihat Penawaran</a>
            </div>
        </div>
    @endif

    @if (session('krs_imported'))
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="fas fa-file-signature me-1"></i> Registrasi mahasiswa dan KRS draft sudah berhasil disiapkan.</span>
            <a href="{{ route('web-admin.krs-management.index') }}" class="btn btn-sm btn-outline-primary">Kelola KRS</a>
        </div>
    @endif

    <div class="row g-3 mb-4" aria-label="Tahapan persiapan akademik">
        <div class="col-md-6 col-xl">
            <a href="{{ route('web-admin.academic-preparation.index', ['step' => 1]) }}"
                class="card h-100 text-decoration-none {{ $step === 1 ? 'border-primary' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge rounded-pill {{ $step === 1 ? 'bg-primary' : 'bg-light-primary text-primary' }} fs-6">1</span>
                    <div><strong class="d-block">Tambah Tahun Akademik</strong><small class="text-muted">Buat rentang tahun sebagai induk periode.</small></div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl">
            <a href="{{ route('web-admin.academic-preparation.index', ['step' => 2, 'tid' => $selectedAcademicYearId]) }}"
                class="card h-100 text-decoration-none {{ $step === 2 ? 'border-primary' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge rounded-pill {{ $step === 2 ? 'bg-primary' : 'bg-light-primary text-primary' }} fs-6">2</span>
                    <div><strong class="d-block">Tambah Periode Akademik</strong><small class="text-muted">Buat periode Ganjil, Genap, atau Semester Pendek.</small></div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl">
            <a href="{{ route('web-admin.academic-preparation.index', ['step' => 3, 'taka_id' => $selectedPeriodId]) }}"
                class="card h-100 text-decoration-none {{ $step === 3 ? 'border-primary' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge rounded-pill {{ $step === 3 ? 'bg-primary' : 'bg-light-primary text-primary' }} fs-6">3</span>
                    <div><strong class="d-block">Import Data Kelas</strong><small class="text-muted">Unggah kelas dari format OpenFeeder.</small></div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl">
            <a href="{{ route('web-admin.academic-preparation.index', ['step' => 4, 'taka_id' => $selectedPeriodId]) }}"
                class="card h-100 text-decoration-none {{ $step === 4 ? 'border-primary' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge rounded-pill {{ $step === 4 ? 'bg-primary' : 'bg-light-primary text-primary' }} fs-6">4</span>
                    <div><strong class="d-block">Import Dosen Pengajar</strong><small class="text-muted">Siapkan dosen dan penawaran kuliah.</small></div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl">
            <a href="{{ route('web-admin.academic-preparation.index', ['step' => 5, 'taka_id' => $selectedPeriodId]) }}"
                class="card h-100 text-decoration-none {{ $step === 5 ? 'border-primary' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge rounded-pill {{ $step === 5 ? 'bg-primary' : 'bg-light-primary text-primary' }} fs-6">5</span>
                    <div><strong class="d-block">Import Data KRS</strong><small class="text-muted">Import per prodi dan semester.</small></div>
                </div>
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Data belum dapat disimpan.</strong>
            <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($step === 1)
        <div class="card">
            <div class="card-header"><h5 class="mb-1">Langkah 1 · Tambah Tahun Akademik</h5><small class="text-muted">Tahun akademik menjadi induk untuk satu atau beberapa periode semester.</small></div>
            <form method="POST" action="{{ route('web-admin.academic-preparation.academic-year.store') }}">
                @csrf
                <input type="hidden" name="_wizard_step" value="1">
                <div class="card-body">
                    @include('user.admin.master.partials.tahun-akademik-form', [
                        'formId' => 'academic-preparation-year',
                        'formMarker' => 'academic-preparation-year',
                    ])
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Simpan &amp; Lanjut ke Periode <i class="fas fa-arrow-right ms-1"></i></button>
                </div>
            </form>
        </div>
    @elseif ($step === 2)
        <div class="card">
            <div class="card-header"><h5 class="mb-1">Langkah 2 · Tambah Periode Akademik</h5><small class="text-muted">Periode baru akan disimpan sebagai draft dan belum langsung diaktifkan.</small></div>
            @if ($academicYears->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-xmark fa-2x text-muted mb-3"></i>
                    <p>Belum ada tahun akademik. Selesaikan langkah pertama sebelum membuat periode.</p>
                    <a href="{{ route('web-admin.academic-preparation.index', ['step' => 1]) }}" class="btn btn-primary">Tambah Tahun Akademik</a>
                </div>
            @else
                <form method="POST" action="{{ route('web-admin.academic-preparation.academic-period.store') }}">
                    @csrf
                    <input type="hidden" name="_wizard_step" value="2">
                    <div class="card-body">
                        @include('user.admin.master.partials.periode-akademik-form', [
                            'formId' => 'academic-preparation-period',
                            'formMarker' => 'academic-preparation-period',
                        ])
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                        <a href="{{ route('web-admin.academic-preparation.index', ['step' => 1]) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan Periode Akademik</button>
                    </div>
                </form>
            @endif
        </div>
    @elseif ($step === 3)
        <div class="card">
            <div class="card-header"><h5 class="mb-1">Langkah 3 · Import Data Kelas</h5><small class="text-muted">Unggah file xlsx atau csv sesuai format kelas OpenFeeder yang dilampirkan.</small></div>
            @if ($periods->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-xmark fa-2x text-muted mb-3"></i>
                    <p>Belum ada periode draft atau aktif yang dapat menerima data kelas.</p>
                    <a href="{{ route('web-admin.academic-preparation.index', ['step' => 2]) }}" class="btn btn-primary">Tambah Periode Akademik</a>
                </div>
            @else
                <form method="POST" action="{{ route('web-admin.academic-preparation.classes.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_wizard_step" value="3">
                    <div class="card-body">
                        <div class="alert alert-light-primary">
                            <strong>Cara pembacaan file:</strong> jika Kode Matakuliah belum tersedia, master mata kuliah akan dibuat otomatis dari file dan katalog bawaan. Semester mahasiswa dikenali dari kode tersebut, kemudian baris dengan kelas yang sama digabung agar tidak menjadi duplikat.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="class-import-period" class="form-label">Periode Akademik</label>
                                <select name="taka_id" id="class-import-period" class="form-select" required>
                                    <option value="">Pilih periode akademik</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}" @selected((string) old('taka_id', $selectedPeriodId) === (string) $period->id)>
                                            {{ $period->name }} · {{ $period->status_label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('taka_id')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="class-import-capacity" class="form-label">Kapasitas per Kelas</label>
                                <input type="number" name="capacity" id="class-import-capacity" class="form-control" value="{{ old('capacity', 30) }}" min="1" max="100" required>
                                <small class="text-muted">Berlaku untuk semua kelas baru.</small>
                                @error('capacity')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-12">
                                <label for="class-import-file" class="form-label">File Data Kelas</label>
                                <input type="file" name="import" id="class-import-file" class="form-control" accept=".xlsx,.csv" required>
                                <small class="text-muted">Maksimal 2MB. Kolom mengikuti template: Semester, Kode Matakuliah, Nama Matakuliah, Nama Kelas, Kode Prodi, komponen SKS, dan kolom OpenFeeder lainnya.</small>
                                @error('import')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="hidden" name="dry_run" value="0">
                                    <input type="checkbox" name="dry_run" id="class-import-dry-run" class="form-check-input" value="1" @checked(old('dry_run'))>
                                    <label for="class-import-dry-run" class="form-check-label">Validasi saja, jangan simpan data</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                        <a href="{{ route('web-admin.academic-preparation.index', ['step' => 2]) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-file-import me-1"></i> Import Data Kelas</button>
                    </div>
                </form>
            @endif
        </div>
    @elseif ($step === 4)
        <div class="card">
            <div class="card-header"><h5 class="mb-1">Langkah 4 · Import Data Dosen Pengajar</h5><small class="text-muted">Unggah file xlsx atau csv sesuai format dosen pengajar OpenFeeder yang dilampirkan.</small></div>
            @if ($periods->isEmpty() || $curricula->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="fas fa-triangle-exclamation fa-2x text-muted mb-3"></i>
                    <p>{{ $periods->isEmpty() ? 'Belum ada periode yang dapat digunakan.' : 'Belum ada kurikulum. Tambahkan kurikulum sebelum mengimpor dosen pengajar.' }}</p>
                    <a href="{{ $periods->isEmpty() ? route('web-admin.academic-preparation.index', ['step' => 2]) : route('web-admin.master.kurikulum-index') }}" class="btn btn-primary">
                        {{ $periods->isEmpty() ? 'Tambah Periode Akademik' : 'Kelola Kurikulum' }}
                    </a>
                </div>
            @else
                <form method="POST" action="{{ route('web-admin.academic-preparation.teaching-lecturers.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_wizard_step" value="4">
                    <div class="card-body">
                        <div class="alert alert-light-primary">
                            Dosen dengan NIDN yang belum terdaftar akan dibuat otomatis. Setiap baris kemudian dihubungkan ke mata kuliah dan kelas hasil langkah ke-3 untuk membentuk penawaran mata kuliah.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="lecturer-import-period" class="form-label">Periode Akademik</label>
                                <select name="taka_id" id="lecturer-import-period" class="form-select" required>
                                    <option value="">Pilih periode akademik</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}" @selected((string) old('taka_id', $selectedPeriodId) === (string) $period->id)>{{ $period->name }} · {{ $period->status_label }}</option>
                                    @endforeach
                                </select>
                                @error('taka_id')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="lecturer-import-curriculum" class="form-label">Kurikulum</label>
                                <select name="kuri_id" id="lecturer-import-curriculum" class="form-select" required>
                                    <option value="">Pilih kurikulum</option>
                                    @foreach ($curricula as $curriculum)
                                        <option value="{{ $curriculum->id }}" @selected((string) old('kuri_id') === (string) $curriculum->id)>{{ $curriculum->name }} · {{ $curriculum->code }}</option>
                                    @endforeach
                                </select>
                                @error('kuri_id')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-12">
                                <label for="lecturer-import-file" class="form-label">File Dosen Pengajar</label>
                                <input type="file" name="import" id="lecturer-import-file" class="form-control" accept=".xlsx,.csv" required>
                                <small class="text-muted">Maksimal 2MB. Kolom mengikuti template Semester, NIDN, Nama Dosen, Kode Matakuliah, Nama Kelas, Kode Prodi, Sks Ajar, dan kolom OpenFeeder lainnya.</small>
                                @error('import')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="hidden" name="dry_run" value="0">
                                    <input type="checkbox" name="dry_run" id="lecturer-import-dry-run" class="form-check-input" value="1" @checked(old('dry_run'))>
                                    <label for="lecturer-import-dry-run" class="form-check-label">Validasi saja, jangan simpan data</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                        <a href="{{ route('web-admin.academic-preparation.index', ['step' => 3, 'taka_id' => $selectedPeriodId]) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-file-import me-1"></i> Import Dosen Pengajar</button>
                    </div>
                </form>
            @endif
        </div>
    @else
        <div class="card">
            <div class="card-header"><h5 class="mb-1">Langkah 5 · Import Data KRS</h5><small class="text-muted">Import satu file untuk satu program studi dan satu semester mahasiswa.</small></div>
            @if ($periods->isEmpty() || $programs->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="fas fa-triangle-exclamation fa-2x text-muted mb-3"></i>
                    <p>Periode akademik dan program studi harus tersedia sebelum mengimpor KRS.</p>
                </div>
            @else
                <form method="POST" action="{{ route('web-admin.academic-preparation.krs.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_wizard_step" value="5">
                    <div class="card-body">
                        <div class="alert alert-light-primary">
                            File akan diperiksa agar seluruh baris berasal dari prodi dan semester mahasiswa yang dipilih. Mahasiswa, registrasi periode, dan KRS draft dibuat otomatis jika belum tersedia. Kapasitas kelas serta penawaran akan dinaikkan otomatis sesuai jumlah mahasiswa unik dalam file KRS.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="krs-import-period" class="form-label">Periode Akademik</label>
                                <select name="taka_id" id="krs-import-period" class="form-select" required>
                                    <option value="">Pilih periode akademik</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}" @selected((string) old('taka_id', $selectedPeriodId) === (string) $period->id)>{{ $period->name }} · {{ $period->status_label }}</option>
                                    @endforeach
                                </select>
                                @error('taka_id')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="krs-import-program" class="form-label">Program Studi/Jurusan</label>
                                <select name="pstudi_id" id="krs-import-program" class="form-select" required>
                                    <option value="">Pilih program studi</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}" @selected((string) old('pstudi_id') === (string) $program->id)>{{ $program->name }} · {{ $program->code }}</option>
                                    @endforeach
                                </select>
                                @error('pstudi_id')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="krs-import-semester" class="form-label">Semester Mahasiswa</label>
                                <select name="student_semester" id="krs-import-semester" class="form-select" required>
                                    <option value="">Pilih semester</option>
                                    @for ($semester = 1; $semester <= 14; $semester++)
                                        <option value="{{ $semester }}" @selected((string) old('student_semester') === (string) $semester)>Semester {{ $semester }}</option>
                                    @endfor
                                </select>
                                @error('student_semester')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-12">
                                <label for="krs-import-file" class="form-label">File Data KRS</label>
                                <input type="file" name="import" id="krs-import-file" class="form-control" accept=".xlsx,.csv" required>
                                <small class="text-muted">Maksimal 4MB. Kolom mengikuti template NIM, Nama, Semester, Kode Mata Kuliah, Nama Kelas, Kode Prodi, dan kolom nilai.</small>
                                @error('import')<small class="text-danger d-block">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="hidden" name="dry_run" value="0">
                                    <input type="checkbox" name="dry_run" id="krs-import-dry-run" class="form-check-input" value="1" @checked(old('dry_run'))>
                                    <label for="krs-import-dry-run" class="form-check-label">Validasi saja, jangan simpan data</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                        <a href="{{ route('web-admin.academic-preparation.index', ['step' => 4, 'taka_id' => $selectedPeriodId]) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-file-import me-1"></i> Import Data KRS</button>
                    </div>
                </form>
            @endif
        </div>
    @endif

@endsection

@section('custom-js')
    @if ($step === 2 && $academicYears->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const year = document.getElementById('academic-year-academic-preparation-period');
                const term = document.getElementById('term-academic-preparation-period');
                const name = document.getElementById('name-academic-preparation-period');
                const code = document.getElementById('code-academic-preparation-period');

                const suggestIdentity = function () {
                    const option = year.options[year.selectedIndex];
                    const yearStart = option?.dataset.yearStart;
                    const yearEnd = option?.dataset.yearEnd;
                    if (!yearStart || !yearEnd || !term.value) return;

                    const termLabel = term.value === 'pendek'
                        ? 'Semester Pendek'
                        : term.value.charAt(0).toUpperCase() + term.value.slice(1);

                    name.value = `${yearStart}/${yearEnd} ${termLabel}`;
                    code.value = `${yearStart}-${yearEnd}-${term.value.toUpperCase()}`;
                };

                year.addEventListener('change', suggestIdentity);
                term.addEventListener('change', suggestIdentity);
            });
        </script>
    @endif
@endsection
