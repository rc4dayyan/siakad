@extends('base.base-dash-index')
@php
    $gradeCourseName = $penawaran->masterMataKuliah->name;
    $gradeCourseCode = $penawaran->code;
    $gradeClassName = $penawaran->kelas?->name;
    $gradeBackUrl ??= route($prefix.'master.penawaran-index');
    $gradeExportUrl ??= route($prefix.'master.penawaran-grades-export', $penawaran);
    $gradeStoreUrl ??= route($prefix.'master.penawaran-grades-store', $penawaran);
    $gradeImportUrl ??= route($prefix.'master.penawaran-grades-import', $penawaran);
@endphp
@section('title')
Kelola Nilai {{ $gradeCourseName }} - SIAKAD
@endsection
@section('menu')
Nilai Mata Kuliah
@endsection
@section('submenu')
Kelola Nilai Mahasiswa
@endsection
@section('urlmenu')
{{ $gradeBackUrl }}
@endsection
@section('subdesc')
Input, impor, dan ekspor nilai mahasiswa dalam satu halaman
@endsection
@section('custom-css')
<style>
    .grade-page .grade-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }

    .grade-page .grade-header__title {
        margin: 0 0 5px;
        color: #243d35;
        font-size: 20px;
        font-weight: 700;
    }

    .grade-page .grade-header__subtitle {
        margin: 0;
        color: #71837d;
        font-size: 13px;
    }

    .grade-page .grade-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .grade-page .grade-actions .btn {
        min-height: 39px;
        padding: 8px 13px;
        font-weight: 600;
    }

    .grade-page .course-summary {
        padding: 18px;
        border: 1px solid #dce9e4;
        border-radius: 13px;
        background: linear-gradient(135deg, #f6fbf9 0%, #ffffff 100%);
    }

    .grade-page .course-summary__item {
        height: 100%;
        padding: 12px 14px;
        border-left: 3px solid #61a78f;
        border-radius: 5px 9px 9px 5px;
        background: #fff;
        box-shadow: 0 3px 12px rgba(32, 75, 61, 0.05);
    }

    .grade-page .course-summary__label {
        display: block;
        margin-bottom: 5px;
        color: #7a8c86;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .grade-page .course-summary__value {
        display: block;
        overflow: hidden;
        color: #263d36;
        font-size: 14px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .grade-page .grade-progress {
        height: 7px;
        overflow: hidden;
        border-radius: 999px;
        background: #e3eeea;
    }

    .grade-page .grade-progress__bar {
        height: 100%;
        border-radius: inherit;
        background: #27856a;
        transition: width 0.25s ease;
    }

    .grade-page .grade-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin: 22px 0 14px;
    }

    .grade-page .grade-search {
        position: relative;
        width: min(380px, 100%);
    }

    .grade-page .grade-search i {
        position: absolute;
        top: 50%;
        left: 14px;
        color: #789088;
        pointer-events: none;
        transform: translateY(-50%);
    }

    .grade-page .grade-search .form-control {
        width: 100%;
        padding-left: 41px;
        background: #fff;
    }

    .grade-page .grade-result-count {
        color: #71837d;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .grade-page .grade-table-wrap {
        overflow-x: auto;
        border: 1px solid #dce9e4;
        border-radius: 12px;
    }

    .grade-page .grade-table {
        margin: 0;
    }

    .grade-page .grade-table thead th {
        padding: 13px 16px;
        border-bottom: 1px solid #dce9e4;
        background: #f2f8f6;
        color: #526861;
        font-size: 11px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .grade-page .grade-table tbody td {
        padding: 12px 16px;
        vertical-align: middle;
    }

    .grade-page .student-name {
        color: #263d36;
        font-weight: 700;
    }

    .grade-page .student-nim {
        color: #71837d;
        font-size: 12px;
    }

    .grade-page .grade-select {
        min-width: 135px;
        font-weight: 700;
    }

    .grade-page .grade-number {
        min-width: 110px;
        max-width: 145px;
    }

    .grade-page .grade-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 999px;
        background: #f0f2f1;
        color: #71837d;
        font-size: 11px;
        font-weight: 700;
    }

    .grade-page .grade-status.is-graded {
        background: #e5f5ef;
        color: #197158;
    }

    .grade-page .grade-status i {
        font-size: 7px;
    }

    .grade-page .grade-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding-top: 18px;
    }

    .grade-page .import-dropzone {
        padding: 24px;
        border: 1px dashed #9fc7b9;
        border-radius: 12px;
        background: #f6fbf9;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .grade-page .grade-header,
        .grade-page .grade-toolbar,
        .grade-page .grade-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .grade-page .grade-actions {
            justify-content: flex-start;
        }

        .grade-page .grade-actions .btn {
            flex: 1 1 auto;
        }

        .grade-page .grade-search {
            width: 100%;
        }

        .grade-page .grade-table-wrap {
            overflow-x: auto;
        }

        .grade-page .grade-result-count {
            white-space: normal;
        }
    }
</style>
@endsection
@section('content')
@php
    $participantCount = $mahasiswas->count();
    $gradedCount = $mahasiswas->filter(
        fn ($student) => filled($existingNilais[$student->id]->nilai ?? null)
            && filled($existingNilais[$student->id]->nilai_indeks ?? null)
            && filled($existingNilais[$student->id]->nilai_angka ?? null)
    )->count();
    $gradedPercentage = $participantCount > 0 ? round(($gradedCount / $participantCount) * 100) : 0;
@endphp

<section class="section grade-page">
    <div class="card">
        <div class="card-header">
            <div class="grade-header">
                <div>
                    <h5 class="grade-header__title">{{ $gradeCourseName }}</h5>
                    <p class="grade-header__subtitle">Kelola nilai akhir peserta berdasarkan KRS yang telah disetujui.</p>
                </div>
                <div class="grade-actions">
                    <a href="{{ $gradeBackUrl }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="{{ $gradeExportUrl }}"
                        class="btn btn-outline-success" title="Unduh template dan nilai saat ini">
                        <i class="fas fa-file-excel me-1"></i> Ekspor
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                        data-bs-target="#importNilaiModal" @disabled(! $canManageNilai)>
                        <i class="fas fa-file-import me-1"></i> Impor
                    </button>
                    <button type="submit" form="gradeForm" class="btn btn-primary" @disabled(! $canManageNilai || $participantCount === 0)>
                        <i class="fas fa-save me-1"></i> Simpan Nilai
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                </div>
            @endif

            @if (! $canManageNilai)
                <div class="alert alert-warning">
                    <i class="fas fa-lock me-1"></i>
                    Nilai tidak dapat diubah karena periode akademik berada dalam mode hanya-baca.
                </div>
            @endif

            @error('kelas_id')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="course-summary">
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <div class="course-summary__item">
                            <span class="course-summary__label">Mata Kuliah</span>
                            <span class="course-summary__value" title="{{ $gradeCourseName }}">
                                {{ $gradeCourseCode }} — {{ $gradeCourseName }}
                            </span>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="course-summary__item">
                            <span class="course-summary__label">Kelas</span>
                            <span class="course-summary__value">{{ $gradeClassName ?? 'Belum ditentukan' }}</span>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="course-summary__item">
                            <span class="course-summary__label">Periode Akademik</span>
                            <span class="course-summary__value">{{ $period->name }}</span>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="course-summary__item">
                            <span class="course-summary__label">Peserta</span>
                            <span class="course-summary__value">{{ $participantCount }} mahasiswa</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                    <span class="small fw-semibold text-muted">Progres kelengkapan tiga nilai</span>
                    <span class="small fw-bold text-success">
                        <span id="gradedCount">{{ $gradedCount }}</span>/{{ $participantCount }} lengkap
                    </span>
                </div>
                <div class="grade-progress" role="progressbar" aria-label="Progres pengisian nilai"
                    aria-valuemin="0" aria-valuemax="{{ $participantCount }}" aria-valuenow="{{ $gradedCount }}">
                    <div class="grade-progress__bar" id="gradeProgressBar" style="width: {{ $gradedPercentage }}%"></div>
                </div>
            </div>

            <div class="grade-toolbar">
                <div class="grade-search">
                    <i class="fas fa-search"></i>
                    <input type="search" id="gradeSearch" class="form-control"
                        placeholder="Cari NIM atau nama mahasiswa..." autocomplete="off"
                        aria-label="Cari mahasiswa">
                </div>
                <span class="grade-result-count" id="gradeResultCount">
                    Menampilkan {{ $participantCount }} mahasiswa
                </span>
            </div>

            @if ($errors->has('nilai') || $errors->has('nilai.*'))
                <div class="alert alert-danger">
                    Periksa kolom yang ditandai. Nilai huruf A–E, indeks 0–4, dan angka 0–100; masing-masing boleh kosong.
                </div>
            @endif

            <p class="small text-muted">Isi nilai angka (0–100) untuk mengisi huruf dan indeks otomatis. Jika angka kosong, huruf dan indeks dapat diisi manual. Saat disimpan, angka menjadi acuan konversi. A ≥86, B ≥71, C ≥56, D ≥41, E &lt;41; contoh 85,5 → B (3).</p>
            <form id="gradeForm" method="POST" action="{{ $gradeStoreUrl }}">
                @csrf
                <input type="hidden" name="_form" value="manual-nilai">

                <div class="grade-table-wrap">
                    <table class="table table-hover align-middle grade-table">
                        <thead>
                            <tr>
                                <th style="width: 65px">No.</th>
                                <th style="width: 170px">NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th style="width: 145px">Status</th>
                                <th style="width: 145px">Nilai Angka <small>(0–100)</small></th>
                                <th style="width: 155px">Nilai Huruf</th>
                                <th style="width: 145px">Nilai Indeks <small>(0–4)</small></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mahasiswas as $index => $student)
                                @php
                                    $existingGrade = $existingNilais[$student->id]->nilai ?? null;
                                    $selectedGrade = old("nilai.{$student->id}.nilai", $existingGrade);
                                    $selectedIndex = old("nilai.{$student->id}.nilai_indeks", $existingNilais[$student->id]->nilai_indeks ?? null);
                                    $selectedNumber = old("nilai.{$student->id}.nilai_angka", $existingNilais[$student->id]->nilai_angka ?? null);
                                    $complete = filled($selectedGrade) && filled($selectedIndex) && filled($selectedNumber);
                                    $partial = filled($selectedGrade) || filled($selectedIndex) || filled($selectedNumber);
                                @endphp
                                <tr data-grade-row data-search="{{ strtolower($student->mhs_nim.' '.$student->mhs_name) }}">
                                    <td class="text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="student-nim">{{ $student->mhs_nim }}</span>
                                    </td>
                                    <td>
                                        <span class="student-name">{{ $student->mhs_name }}</span>
                                        <input type="hidden" name="nilai[{{ $student->id }}][mahasiswa_id]" value="{{ $student->id }}">
                                    </td>
                                    <td>
                                        <span class="grade-status {{ $complete ? 'is-graded' : '' }}" data-grade-status>
                                            <i class="fas fa-circle"></i>
                                            <span>{{ $complete ? 'Lengkap' : ($partial ? 'Belum lengkap' : 'Belum dinilai') }}</span>
                                        </span>
                                    </td>
                                    @foreach (['nilai_angka' => ['Nilai angka', $selectedNumber, 100]] as $field => [$label, $value, $maximum])
                                        <td>
                                            <input type="number" name="nilai[{{ $student->id }}][{{ $field }}]"
                                                value="{{ $value }}" min="0" max="{{ $maximum }}" step="0.01"
                                                class="form-control grade-number @error("nilai.{$student->id}.{$field}") is-invalid @enderror"
                                                data-grade-number @disabled(! $canManageNilai)
                                                aria-label="{{ $label }} {{ $student->mhs_name }}" placeholder="Kosong">
                                            @error("nilai.{$student->id}.{$field}")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    @endforeach
                                    <td>
                                        <select name="nilai[{{ $student->id }}][nilai]"
                                            class="form-select grade-select @error("nilai.{$student->id}.nilai") is-invalid @enderror"
                                            data-grade-select @disabled(! $canManageNilai)
                                            aria-label="Nilai huruf {{ $student->mhs_name }}">
                                            <option value="">Belum dinilai</option>
                                            @foreach (['A', 'B', 'C', 'D', 'E'] as $grade)
                                                <option value="{{ $grade }}" @selected($selectedGrade === $grade)>{{ $grade }}</option>
                                            @endforeach
                                        </select>
                                        @error("nilai.{$student->id}.nilai")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    @foreach (['nilai_indeks' => ['Nilai indeks', $selectedIndex, 4]] as $field => [$label, $value, $maximum])
                                        <td>
                                            <input type="number" name="nilai[{{ $student->id }}][{{ $field }}]"
                                                value="{{ $value }}" min="0" max="{{ $maximum }}" step="0.01"
                                                class="form-control grade-number @error("nilai.{$student->id}.{$field}") is-invalid @enderror"
                                                data-grade-number @disabled(! $canManageNilai)
                                                aria-label="{{ $label }} {{ $student->mhs_name }}" placeholder="Kosong">
                                            @error("nilai.{$student->id}.{$field}")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-5 text-center">
                                        <i class="fas fa-user-graduate fa-2x text-muted mb-3"></i>
                                        <p class="mb-1 fw-semibold">Belum ada peserta mata kuliah</p>
                                        <small class="text-muted">Pastikan mahasiswa sudah terdaftar pada kelas atau KRS yang disetujui.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="grade-footer">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Nilai kosong akan disimpan sebagai belum dinilai.
                </small>
                <button type="submit" form="gradeForm" class="btn btn-primary"
                    @disabled(! $canManageNilai || $participantCount === 0)>
                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="importNilaiModal" tabindex="-1" aria-labelledby="importNilaiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ $gradeImportUrl }}"
            enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="_form" value="import-nilai">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="importNilaiModalLabel">Impor Nilai Mahasiswa</h5>
                    <small class="text-muted">{{ $gradeCourseName }} — {{ $gradeClassName }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    <i class="fas fa-circle-info me-1"></i>
                    Unduh template OpenFeeder melalui tombol <strong>Ekspor</strong>, isi kolom <strong>Nilai Angka</strong>, lalu unggah kembali. Huruf dan indeks otomatis dihitung dari angka. Jika angka kosong, nilai huruf dan indeks pada file digunakan.
                    Huruf A–E, indeks 0–4, angka 0–100, atau kosong. Periode, mata kuliah, kelas, dan prodi harus sesuai halaman ini. File lama dengan kolom Nilai tetap dapat diimpor.
                </div>

                <p class="small text-muted">Import ulang memperbarui nilai peserta yang tercantum di file. Seluruh import dibatalkan jika ada baris tidak valid.</p>
                <a href="{{ $gradeExportUrl }}" class="btn btn-outline-success mb-3"><i class="fas fa-file-excel me-1"></i> Unduh Template dan Nilai Saat Ini</a>
                <div class="import-dropzone">
                    <i class="fas fa-file-excel fa-2x text-success mb-3"></i>
                    <label for="grade_import" class="form-label d-block">Pilih file XLSX atau CSV</label>
                    <input type="file" name="import" id="grade_import"
                        class="form-control @error('import') is-invalid @enderror"
                        accept=".xlsx,.csv" required>
                    <small class="text-muted d-block mt-2">Ukuran file maksimal 5 MB.</small>
                    @error('import')
                        <div class="invalid-feedback d-block text-start mt-2">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" @disabled(! $canManageNilai)>
                    <i class="fas fa-file-import me-1"></i> Impor Nilai
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('custom-js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('gradeSearch');
    const rows = Array.from(document.querySelectorAll('[data-grade-row]'));
    const resultCount = document.getElementById('gradeResultCount');
    const gradeCount = document.getElementById('gradedCount');
    const progressBar = document.getElementById('gradeProgressBar');
    const totalStudents = rows.length;
    const scale = @json(\App\Services\Academic\GradeConversionService::SCALE);
    const canManage = @json($canManageNilai);

    const updateProgress = function () {
        const completed = rows.filter((row) =>
            Array.from(row.querySelectorAll('[data-grade-select], [data-grade-number]')).every((input) => input.value !== '')
        ).length;
        const percentage = totalStudents > 0 ? Math.round((completed / totalStudents) * 100) : 0;

        if (gradeCount) {
            gradeCount.textContent = completed;
        }

        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.parentElement.setAttribute('aria-valuenow', completed);
        }
    };

    rows.forEach(function (row) {
        const inputs = Array.from(row.querySelectorAll('[data-grade-select], [data-grade-number]'));
        const refreshStatus = function () {
            const complete = inputs.every((input) => input.value !== '');
            const partial = inputs.some((input) => input.value !== '');
            const status = row.querySelector('[data-grade-status]');
            status.classList.toggle('is-graded', complete);
            status.querySelector('span').textContent = complete ? 'Lengkap' : (partial ? 'Belum lengkap' : 'Belum dinilai');
            updateProgress();
        };
        const scoreInput = row.querySelector('[name$="[nilai_angka]"]');
        const letterInput = row.querySelector('[data-grade-select]');
        const indexInput = row.querySelector('[name$="[nilai_indeks]"]');
        const updateEditable = function () {
            letterInput.disabled = !canManage || scoreInput.value !== '';
            indexInput.readOnly = scoreInput.value !== '';
        };
        scoreInput.addEventListener('input', function () {
            if (!canManage) return;
            if (scoreInput.value === '') {
                letterInput.value = '';
                indexInput.value = '';
            } else if (scoreInput.validity.valid) {
                const score = Number(scoreInput.value);
                const category = scale.find((item) => score >= item.minimum);
                if (category) {
                    letterInput.value = category.huruf;
                    indexInput.value = category.indeks;
                }
            }
            updateEditable();
            refreshStatus();
        });
        updateEditable();
        inputs.forEach((input) => input.addEventListener('input', refreshStatus));
    });
    updateProgress();

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const keyword = searchInput.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach(function (row) {
                const matches = !keyword || row.dataset.search.includes(keyword);
                row.hidden = !matches;
                visible += matches ? 1 : 0;
            });

            resultCount.textContent = 'Menampilkan ' + visible + ' dari ' + totalStudents + ' mahasiswa';
        });
    }
});
</script>
@if (old('_form') === 'import-nilai' && $errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('importNilaiModal')).show();
});
</script>
@endif
@endsection
