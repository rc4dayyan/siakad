@extends('base.base-dash-index')

@section('menu', 'Persiapan Akademik Baru')
@section('submenu', 'Wizard Persiapan Akademik Baru')
@section('urlmenu', route('web-admin.academic-preparation.index'))
@section('subdesc', 'Tambahkan periode, kelas, dosen pengajar, dan KRS secara berurutan hingga disetujui')

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

    @if (session('krs_finalized'))
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="fas fa-circle-check me-1"></i> Seluruh KRS yang dapat diproses sudah diajukan dan disetujui.</span>
            <a href="{{ route('web-admin.krs-management.index', ['status' => 'approved']) }}" class="btn btn-sm btn-outline-success">Lihat KRS Disetujui</a>
        </div>
    @endif

    @if (session('schedules_generated'))
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="fas fa-calendar-check me-1"></i> Jadwal kuliah berhasil dibuat tanpa bentrok sumber daya.</span>
            <a href="{{ route('web-admin.master.jadwal-mingguan-index') }}" class="btn btn-sm btn-outline-success">Lihat Jadwal Kuliah</a>
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
        <div class="col-md-6 col-xl">
            <a href="{{ route('web-admin.academic-preparation.index', ['step' => 6, 'taka_id' => $selectedPeriodId]) }}"
                class="card h-100 text-decoration-none {{ $step === 6 ? 'border-primary' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge rounded-pill {{ $step === 6 ? 'bg-primary' : 'bg-light-primary text-primary' }} fs-6">6</span>
                    <div><strong class="d-block">Ajukan &amp; Setujui KRS</strong><small class="text-muted">Finalisasi seluruh KRS periode.</small></div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl">
            <a href="{{ route('web-admin.academic-preparation.index', ['step' => 7, 'taka_id' => $selectedPeriodId]) }}"
                class="card h-100 text-decoration-none {{ $step === 7 ? 'border-primary' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge rounded-pill {{ $step === 7 ? 'bg-primary' : 'bg-light-primary text-primary' }} fs-6">7</span>
                    <div><strong class="d-block">Generate Jadwal Kuliah</strong><small class="text-muted">Susun jadwal tanpa bentrok.</small></div>
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
    @elseif ($step === 5)
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
                        <button type="submit" class="btn btn-primary"><i class="fas fa-file-import me-1"></i> Import &amp; Lanjut ke Finalisasi</button>
                    </div>
                </form>
            @endif
        </div>
    @elseif ($step === 6)
        <div class="card">
            <div class="card-header"><h5 class="mb-1">Langkah 6 · Ajukan dan Setujui Seluruh KRS</h5><small class="text-muted">Finalisasi KRS secara massal untuk satu periode akademik.</small></div>
            @if ($periods->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-xmark fa-2x text-muted mb-3"></i>
                    <p>Belum ada periode draft atau aktif yang dapat diproses.</p>
                </div>
            @else
                <div class="card-body">
                    <form method="GET" action="{{ route('web-admin.academic-preparation.index') }}" class="row g-3 align-items-end mb-4">
                        <input type="hidden" name="step" value="6">
                        <div class="col-md-9">
                            <label for="finalize-krs-period" class="form-label">Periode Akademik</label>
                            <select name="taka_id" id="finalize-krs-period" class="form-select" required>
                                @foreach ($periods as $period)
                                    <option value="{{ $period->id }}" @selected((string) $selectedPeriodId === (string) $period->id)>{{ $period->name }} · {{ $period->status_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-rotate me-1"></i> Tampilkan Ringkasan</button>
                        </div>
                    </form>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Total KRS</small><strong class="fs-4">{{ number_format($krsSummary['total']) }}</strong></div></div>
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Draft/Ditolak</small><strong class="fs-4 text-primary">{{ number_format($krsSummary['editable']) }}</strong></div></div>
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Sudah Diajukan</small><strong class="fs-4 text-warning">{{ number_format($krsSummary['submitted']) }}</strong></div></div>
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Sudah Selesai</small><strong class="fs-4 text-success">{{ number_format($krsSummary['completed']) }}</strong></div></div>
                    </div>

                    @if ($krsSummary['empty'] > 0)
                        <div class="alert alert-warning">
                            <i class="fas fa-triangle-exclamation me-1"></i> Terdapat {{ number_format($krsSummary['empty']) }} KRS yang belum memiliki mata kuliah. Lengkapi KRS tersebut sebelum finalisasi.
                        </div>
                    @elseif ($krsSummary['actionable'] > 0)
                        <div class="alert alert-light-primary">
                            KRS draft atau ditolak akan diajukan terlebih dahulu. Seluruh KRS berstatus diajukan kemudian disetujui oleh Web Administrator. Proses dilakukan dalam satu transaksi; bila satu KRS gagal, tidak ada status yang diubah.
                        </div>
                        <form method="POST" action="{{ route('web-admin.academic-preparation.krs.finalize') }}" onsubmit="return confirm('Ajukan dan setujui seluruh KRS pada periode ini?')">
                            @csrf
                            <input type="hidden" name="_wizard_step" value="6">
                            <input type="hidden" name="taka_id" value="{{ $selectedPeriodId }}">
                            <div class="form-check mb-3">
                                <input type="checkbox" name="confirmation" id="finalize-krs-confirmation" class="form-check-input" value="1" required>
                                <label for="finalize-krs-confirmation" class="form-check-label">Saya sudah memeriksa data dan menyetujui finalisasi seluruh KRS periode ini.</label>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-check-double me-1"></i> Ajukan &amp; Setujui Seluruh KRS</button>
                        </form>
                    @elseif ($krsSummary['total'] > 0)
                        <div class="alert alert-success mb-0"><i class="fas fa-circle-check me-1"></i> Seluruh KRS pada periode ini sudah disetujui atau dikunci.</div>
                    @else
                        <div class="alert alert-light mb-0">Belum ada KRS pada periode ini. Lakukan import KRS pada langkah ke-5 terlebih dahulu.</div>
                    @endif
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                    <a href="{{ route('web-admin.academic-preparation.index', ['step' => 5, 'taka_id' => $selectedPeriodId]) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <a href="{{ route('web-admin.krs-management.index') }}" class="btn btn-outline-primary">Kelola KRS</a>
                </div>
            @endif
        </div>
    @else
        <div class="card">
            <div class="card-header"><h5 class="mb-1">Langkah 7 · Generate Jadwal Kuliah</h5><small class="text-muted">Susun jadwal mingguan otomatis untuk seluruh penawaran yang belum dijadwalkan.</small></div>
            @if ($periods->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-xmark fa-2x text-muted mb-3"></i>
                    <p>Belum ada periode draft atau aktif yang dapat menerima jadwal.</p>
                </div>
            @else
                <div class="card-body">
                    <form method="GET" action="{{ route('web-admin.academic-preparation.index') }}" class="row g-3 align-items-end mb-4">
                        <input type="hidden" name="step" value="7">
                        <div class="col-md-9">
                            <label for="generate-schedule-period" class="form-label">Periode Akademik</label>
                            <select name="taka_id" id="generate-schedule-period" class="form-select" required>
                                @foreach ($periods as $period)
                                    <option value="{{ $period->id }}" @selected((string) $selectedPeriodId === (string) $period->id)>{{ $period->name }} · {{ $period->status_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-rotate me-1"></i> Tampilkan Ringkasan</button>
                        </div>
                    </form>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Penawaran</small><strong class="fs-4">{{ number_format($scheduleSummary['offerings']) }}</strong></div></div>
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Sudah Terjadwal</small><strong class="fs-4 text-success">{{ number_format($scheduleSummary['scheduled_offerings']) }}</strong></div></div>
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Belum Terjadwal</small><strong class="fs-4 text-warning">{{ number_format($scheduleSummary['unscheduled_offerings']) }}</strong></div></div>
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Dikecualikan</small><strong class="fs-4 text-secondary">{{ number_format($scheduleSummary['excluded_offerings']) }}</strong></div></div>
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Estimasi Ruang Dibutuhkan</small><strong id="schedule-required-rooms" class="fs-4 text-info">{{ number_format($scheduleSummary['estimated_rooms']) }}</strong></div></div>
                        <div class="col-6 col-lg"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Ruang Tersedia</small><strong class="fs-4 text-primary">{{ number_format($scheduleSummary['rooms']) }}</strong></div></div>
                    </div>

                    <div class="alert {{ $scheduleSummary['room_shortage'] > 0 ? 'alert-warning' : 'alert-light' }} d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <strong id="schedule-room-requirement-status">
                                @if ($scheduleSummary['room_shortage'] > 0)
                                    Kekurangan sekitar {{ number_format($scheduleSummary['room_shortage']) }} ruang berdasarkan pengaturan awal.
                                @else
                                    Jumlah ruang secara umum mencukupi berdasarkan pengaturan awal.
                                @endif
                            </strong>
                            <small class="d-block mt-1">
                                Estimasi memakai {{ number_format($scheduleSummary['total_credits']) }} SKS, hari dan jam operasional, durasi per SKS, serta jeda yang dipilih.
                                @if ($scheduleSummary['largest_capacity'] > 0)
                                    Kelas belum terjadwal terbesar memerlukan kapasitas {{ number_format($scheduleSummary['largest_capacity']) }}; tersedia {{ number_format($scheduleSummary['adequate_rooms']) }} ruang yang mampu menampungnya.
                                @endif
                            </small>
                        </div>
                        <a href="{{ route('web-admin.inventory.ruang-index', ['buat' => 1]) }}" class="btn btn-primary text-nowrap"><i class="fas fa-plus me-1"></i> Buat Ruangan</a>
                    </div>

                    @if ($scheduleSummary['offerings'] === 0)
                        <div class="alert alert-warning mb-0">Belum ada penawaran mata kuliah pada periode ini. Selesaikan langkah ke-4 terlebih dahulu.</div>
                    @elseif ($scheduleSummary['unscheduled_offerings'] === 0 && $scheduleSummary['excluded_offerings'] === 0)
                        <div class="alert alert-success mb-0"><i class="fas fa-circle-check me-1"></i> Seluruh penawaran pada periode ini sudah memiliki jadwal.</div>
                    @elseif ($scheduleSummary['rooms'] === 0 || $scheduleSummary['adequate_rooms'] === 0)
                        <div class="alert alert-warning mb-0">Belum ada ruang kelas atau laboratorium dengan kapasitas yang mencukupi. Gunakan tombol <strong>Buat Ruangan</strong> sebelum menjalankan generator jadwal.</div>
                    @else
                        <div class="alert alert-light-primary">
                            Generator menggunakan dosen utama dan kelas pada setiap penawaran, memilih ruang dengan kapasitas mencukupi, lalu mencari waktu tanpa bentrok dosen, kelas, atau ruang. Jadwal yang sudah ada tidak diubah.
                        </div>
                        <form method="POST" action="{{ route('web-admin.academic-preparation.schedules.generate') }}">
                            @csrf
                            <input type="hidden" name="_wizard_step" value="7">
                            <input type="hidden" name="taka_id" value="{{ $selectedPeriodId }}">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="border rounded p-3">
                                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                            <div><strong>Mata Kuliah yang Tidak Perlu Dijadwalkan</strong><small class="text-muted d-block">Centang kegiatan lapangan atau mata kuliah yang tidak memerlukan ruang dan jadwal mingguan.</small></div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-schedule-exclusions">Kosongkan Pilihan</button>
                                        </div>
                                        @php
                                            $selectedExclusions = collect(old('excluded_offering_ids', $scheduleOfferings->where('wajib_dijadwalkan', false)->pluck('id')->all()))->map(fn ($id) => (int) $id);
                                            $configurableOfferings = $scheduleOfferings->where('jadwal_mingguans_count', 0)->groupBy(fn ($offering) => $offering->kelas?->name ?? 'Tanpa Kelas');
                                        @endphp
                                        <div class="row g-3" style="max-height: 320px; overflow-y: auto;">
                                            @forelse ($configurableOfferings as $className => $classOfferings)
                                                <div class="col-lg-6">
                                                    <div class="border rounded p-3 h-100">
                                                        <strong class="d-block mb-2">{{ $className }}</strong>
                                                        @foreach ($classOfferings as $offering)
                                                            <div class="form-check mb-2">
                                                                <input type="checkbox" name="excluded_offering_ids[]" id="exclude-offering-{{ $offering->id }}" class="form-check-input schedule-exclusion" value="{{ $offering->id }}" @checked($selectedExclusions->contains($offering->id))>
                                                                <label for="exclude-offering-{{ $offering->id }}" class="form-check-label">{{ $offering->masterMataKuliah?->name }} <small class="text-muted">({{ $offering->sks }} SKS)</small></label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-12"><small class="text-muted">Tidak ada penawaran tanpa jadwal yang dapat dikecualikan.</small></div>
                                            @endforelse
                                        </div>
                                        @error('excluded_offering_ids.*')<small class="text-danger d-block mt-2">{{ $message }}</small>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-block">Hari Kuliah</label>
                                    @foreach ([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => "Jum'at", 6 => 'Sabtu'] as $dayValue => $dayLabel)
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" name="days[]" id="schedule-day-{{ $dayValue }}" class="form-check-input" value="{{ $dayValue }}" @checked(in_array($dayValue, array_map('intval', (array) old('days', [1, 2, 3, 4, 5, 6])), true))>
                                            <label for="schedule-day-{{ $dayValue }}" class="form-check-label">{{ $dayLabel }}</label>
                                        </div>
                                    @endforeach
                                    @error('days')<small class="text-danger d-block">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="schedule-day-start" class="form-label">Jam Operasional Mulai</label>
                                    <input type="time" name="day_starts_at" id="schedule-day-start" class="form-control" value="{{ old('day_starts_at', '08:00') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="schedule-day-end" class="form-label">Jam Operasional Selesai</label>
                                    <input type="time" name="day_ends_at" id="schedule-day-end" class="form-control" value="{{ old('day_ends_at', '17:00') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="schedule-credit-minutes" class="form-label">Menit per SKS</label>
                                    <input type="number" name="minutes_per_credit" id="schedule-credit-minutes" class="form-control" value="{{ old('minutes_per_credit', 50) }}" min="30" max="60" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="schedule-gap-minutes" class="form-label">Jeda Antarjadwal</label>
                                    <input type="number" name="gap_minutes" id="schedule-gap-minutes" class="form-control" value="{{ old('gap_minutes', 10) }}" min="0" max="60" required>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="hidden" name="dry_run" value="0">
                                        <input type="checkbox" name="dry_run" id="schedule-dry-run" class="form-check-input" value="1" @checked(old('dry_run'))>
                                        <label for="schedule-dry-run" class="form-check-label">Validasi susunan jadwal saja, jangan simpan data</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success mt-4" onclick="return confirm('Generate jadwal untuk seluruh penawaran yang belum dijadwalkan?')"><i class="fas fa-calendar-plus me-1"></i> Generate Jadwal Kuliah</button>
                        </form>
                    @endif
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                    <a href="{{ route('web-admin.academic-preparation.index', ['step' => 6, 'taka_id' => $selectedPeriodId]) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <a href="{{ route('web-admin.master.jadwal-mingguan-index') }}" class="btn btn-outline-primary">Kelola Jadwal Mingguan</a>
                </div>
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
    @if ($step === 7)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const requiredRooms = document.getElementById('schedule-required-rooms');
                const status = document.getElementById('schedule-room-requirement-status');
                const startsAt = document.getElementById('schedule-day-start');
                const endsAt = document.getElementById('schedule-day-end');
                const minutesPerCredit = document.getElementById('schedule-credit-minutes');
                const gapMinutes = document.getElementById('schedule-gap-minutes');
                const days = [...document.querySelectorAll('input[name="days[]"]')];
                const offeringCount = {{ (int) $scheduleSummary['required_offerings'] }};
                const totalCredits = {{ (int) $scheduleSummary['total_credits'] }};
                const availableRooms = {{ (int) $scheduleSummary['rooms'] }};
                const clearExclusions = document.getElementById('clear-schedule-exclusions');

                clearExclusions?.addEventListener('click', function () {
                    document.querySelectorAll('.schedule-exclusion').forEach(input => input.checked = false);
                });

                if (!requiredRooms || !status || !startsAt || !endsAt || !minutesPerCredit || !gapMinutes) return;

                const toMinutes = function (value) {
                    const parts = value.split(':').map(Number);
                    return parts.length === 2 ? (parts[0] * 60) + parts[1] : 0;
                };

                const updateRoomEstimate = function () {
                    const selectedDays = days.filter(day => day.checked).length;
                    const dailyMinutes = toMinutes(endsAt.value) - toMinutes(startsAt.value);
                    const workload = (totalCredits * Number(minutesPerCredit.value || 0))
                        + (offeringCount * Number(gapMinutes.value || 0));

                    if (selectedDays < 1 || dailyMinutes <= 0 || workload <= 0) {
                        requiredRooms.textContent = '—';
                        status.textContent = 'Lengkapi pengaturan untuk menghitung estimasi kebutuhan ruang.';
                        return;
                    }

                    const estimate = Math.ceil(workload / (selectedDays * dailyMinutes));
                    const shortage = Math.max(0, estimate - availableRooms);
                    requiredRooms.textContent = new Intl.NumberFormat('id-ID').format(estimate);
                    status.textContent = shortage > 0
                        ? `Kekurangan sekitar ${new Intl.NumberFormat('id-ID').format(shortage)} ruang berdasarkan pengaturan saat ini.`
                        : 'Jumlah ruang secara umum mencukupi berdasarkan pengaturan saat ini.';
                };

                [...days, startsAt, endsAt, minutesPerCredit, gapMinutes].forEach(input => {
                    input.addEventListener('change', updateRoomEstimate);
                    input.addEventListener('input', updateRoomEstimate);
                });
                updateRoomEstimate();
            });
        </script>
    @endif
@endsection
