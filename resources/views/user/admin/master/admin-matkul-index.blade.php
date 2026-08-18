@extends('base.base-dash-index')
@section('title', 'Mata Kuliah per Kelas - SIAKAD')
@section('menu', 'Mata Kuliah per Kelas')
@section('submenu', 'Data Mata Kuliah per Kelas')
@section('urlmenu', route($prefix.'master.matkul-index'))
@section('subdesc', 'Pantau peserta dan progres nilai setiap mata kuliah pada masing-masing kelas.')

@section('custom-css')
<style>
    .course-class-page .summary-card {
        height: 100%;
        padding: 17px;
        border: 1px solid #e1ebe7;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 5px 18px rgba(38, 61, 54, 0.05);
    }

    .course-class-page .summary-card__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        margin-bottom: 12px;
        border-radius: 10px;
        background: #e8f5f0;
        color: #197158;
    }

    .course-class-page .summary-card__value {
        display: block;
        color: #263d36;
        font-size: 23px;
        font-weight: 800;
        line-height: 1;
    }

    .course-class-page .summary-card__label {
        display: block;
        margin-top: 7px;
        color: #71837d;
        font-size: 12px;
        font-weight: 600;
    }

    .course-class-page .filter-panel {
        padding: 18px;
        border: 1px solid #dce9e4;
        border-radius: 13px;
        background: linear-gradient(135deg, #f6fbf9 0%, #fff 100%);
    }

    .course-class-page .filter-panel__heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
    }

    .course-class-page .filter-panel__title {
        margin: 0 0 3px;
        color: #263d36;
        font-size: 15px;
        font-weight: 700;
    }

    .course-class-page .filter-panel__description {
        margin: 0;
        color: #71837d;
        font-size: 12px;
    }

    .course-class-page .filter-panel .form-label {
        color: #415a52;
        font-size: 12px;
        font-weight: 700;
    }

    .course-class-page .filter-panel .form-control,
    .course-class-page .filter-panel .form-select {
        min-height: 40px;
        border-color: #d7e4df;
        border-radius: 9px;
    }

    .course-class-page .course-name {
        color: #263d36;
        font-weight: 700;
    }

    .course-class-page .course-code {
        color: #71837d;
        font-size: 11px;
    }

    .course-class-page .class-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #eef4ff;
        color: #3f65a8;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .course-class-page .grade-progress {
        width: 120px;
        height: 6px;
        margin-top: 6px;
        overflow: hidden;
        border-radius: 999px;
        background: #e4ece9;
    }

    .course-class-page .grade-progress__bar {
        height: 100%;
        border-radius: inherit;
        background: #27856a;
    }

    .course-class-page .action-dropdown .dropdown-menu {
        min-width: 185px;
        padding: 7px;
        border: 1px solid #e4ece9;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(38, 61, 54, 0.14);
    }

    .course-class-page .action-dropdown .dropdown-item {
        padding: 9px 11px;
        border-radius: 7px;
        font-size: 13px;
    }

    @media (max-width: 767.98px) {
        .course-class-page .filter-panel__heading {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
@php
    $offeringCount = $offerings->count();
    $participantCount = $offerings->sum('peserta_count');
    $gradedCount = $offerings->sum('nilai_terisi_count');
@endphp

<section class="section course-class-page">
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="summary-card">
                <span class="summary-card__icon"><i class="fas fa-book-open"></i></span>
                <span class="summary-card__value">{{ $offeringCount }}</span>
                <span class="summary-card__label">Mata kuliah dan kelas</span>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="summary-card">
                <span class="summary-card__icon"><i class="fas fa-user-graduate"></i></span>
                <span class="summary-card__value">{{ $participantCount }}</span>
                <span class="summary-card__label">Total peserta KRS disetujui</span>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="summary-card">
                <span class="summary-card__icon"><i class="fas fa-check-circle"></i></span>
                <span class="summary-card__value">{{ $gradedCount }}</span>
                <span class="summary-card__label">Nilai mahasiswa telah terisi</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1">Mata Kuliah per Kelas</h5>
                <small class="text-muted">
                    Periode: {{ $selectedPeriod?->name ?? 'Belum dipilih' }}
                    @if ($selectedPeriod) — {{ $selectedPeriod->status_label }} @endif
                </small>
            </div>
            <a href="{{ route($prefix.'master.penawaran-index') }}" class="btn btn-outline-primary">
                <i class="fas fa-sliders-h me-1"></i> Kelola Penawaran
            </a>
        </div>
        <div class="card-body">
            @if (! $selectedPeriod)
                <div class="alert alert-warning">Pilih periode akademik untuk menampilkan mata kuliah per kelas.</div>
            @endif

            <div class="filter-panel mb-4">
                <div class="filter-panel__heading">
                    <div>
                        <h6 class="filter-panel__title"><i class="fas fa-filter text-primary me-2"></i>Filter Data</h6>
                        <p class="filter-panel__description">Cari berdasarkan mata kuliah, kode penawaran, atau kelas.</p>
                    </div>
                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ $offeringCount }} data ditemukan</span>
                </div>

                <form method="GET" action="{{ route($prefix.'master.matkul-index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label for="course_class_keyword" class="form-label">Pencarian</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="search" name="q" id="course_class_keyword"
                                class="form-control border-start-0 ps-0" value="{{ $filters['q'] ?? '' }}"
                                placeholder="Nama, kode mata kuliah, atau kelas">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="course_class_program" class="form-label">Program Studi</label>
                        <select name="pstudi_id" id="course_class_program" class="form-select">
                            <option value="">Semua program studi</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected(($filters['pstudi_id'] ?? null) == $program->id)>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="course_class_class" class="form-label">Kelas</label>
                        <select name="kelas_id" id="course_class_class" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(($filters['kelas_id'] ?? null) == $class->id)>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Terapkan</button>
                            @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                                <a href="{{ route($prefix.'master.matkul-index') }}" class="btn btn-outline-secondary"
                                    title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 55px">#</th>
                            <th>Mata Kuliah</th>
                            <th>Program Studi</th>
                            <th>Kelas</th>
                            <th>Dosen Pengampu</th>
                            <th>Peserta</th>
                            <th>Progres Nilai</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($offerings as $item)
                            @php
                                $gradePercentage = $item->peserta_count > 0
                                    ? min(100, round(($item->nilai_terisi_count / $item->peserta_count) * 100))
                                    : 0;
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="course-name">{{ $item->masterMataKuliah?->name ?? '-' }}</div>
                                    <div class="course-code">
                                        {{ $item->masterMataKuliah?->code ?? $item->code }} · {{ $item->sks }} SKS · Semester {{ $item->masterMataKuliah?->semester ?? '-' }}
                                    </div>
                                </td>
                                <td>{{ $item->pstudi?->name ?? '-' }}</td>
                                <td><span class="class-badge"><i class="fas fa-school"></i>{{ $item->kelas?->name ?? '-' }}</span></td>
                                <td>{{ $item->dosenUtama?->dsn_name ?? '-' }}</td>
                                <td><strong>{{ $item->peserta_count }}</strong> mahasiswa</td>
                                <td>
                                    <div class="small fw-semibold">{{ $item->nilai_terisi_count }}/{{ $item->peserta_count }} terisi</div>
                                    <div class="grade-progress" role="progressbar" aria-valuenow="{{ $gradePercentage }}"
                                        aria-valuemin="0" aria-valuemax="100" aria-label="Progres nilai {{ $item->masterMataKuliah?->name }}">
                                        <div class="grade-progress__bar" style="width: {{ $gradePercentage }}%"></div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown action-dropdown d-inline-block">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog me-1"></i> Aksi
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="{{ route($prefix.'master.penawaran-grades', $item) }}" class="dropdown-item">
                                                    <i class="fas fa-graduation-cap text-success me-2"></i>Kelola Nilai
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route($prefix.'master.penawaran-participants', $item) }}" class="dropdown-item">
                                                    <i class="fas fa-users text-info me-2"></i>Lihat Peserta
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-5 text-center text-muted">
                                    <i class="fas fa-book-open fa-2x mb-3"></i>
                                    <p class="mb-1 fw-semibold">Mata kuliah per kelas tidak ditemukan</p>
                                    <small>Coba ubah filter atau buat penawaran mata kuliah pada periode ini.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
