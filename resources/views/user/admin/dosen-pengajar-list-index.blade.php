@extends('base.base-dash-index')

@section('menu', 'Data Akademik')
@section('submenu', 'List Dosen Pengajar')
@section('urlmenu', route($prefix.'dosen-pengajar-list.index'))
@section('subdesc', 'Rekap penugasan dosen pengajar pada periode '.$period->name)

@section('custom-css')
<style>
    .lecturer-list-page .filter-panel { background: linear-gradient(135deg, #f8faff 0%, #f4f7fb 100%); border: 1px solid #e4e9f2; border-radius: 14px; padding: 1.25rem; }
    .lecturer-list-page .summary-card { border: 1px solid #edf0f5; border-radius: 12px; height: 100%; }
    .lecturer-list-page .summary-icon { align-items: center; background: #eef2ff; border-radius: 10px; color: #435ebe; display: flex; height: 42px; justify-content: center; width: 42px; }
    .lecturer-list-page .table thead th { background: #f7f8fb; color: #607080; font-size: .72rem; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .lecturer-list-page .lecturer-info, .lecturer-list-page .course-info { min-width: 210px; }
    .lecturer-list-page .meeting-progress { background: #e9edf4; border-radius: 20px; height: 5px; margin-top: 7px; overflow: hidden; width: 100px; }
    .lecturer-list-page .meeting-progress span { background: #435ebe; display: block; height: 100%; }
    @media (max-width: 767.98px) { .lecturer-list-page .filter-panel { padding: 1rem; } }
</style>
@endsection

@section('content')
@php $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(); @endphp

<section class="section lecturer-list-page">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                <span class="summary-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                <div><small class="text-muted d-block">Penugasan Mengajar</small><strong class="fs-5">{{ number_format($summary['assignments']) }}</strong></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                <span class="summary-icon"><i class="fas fa-user-tie"></i></span>
                <div><small class="text-muted d-block">Dosen</small><strong class="fs-5">{{ number_format($summary['lecturers']) }}</strong></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                <span class="summary-icon"><i class="fas fa-layer-group"></i></span>
                <div><small class="text-muted d-block">Total SKS Ajar</small><strong class="fs-5">{{ number_format($summary['credits']) }}</strong></div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h5 class="mb-1">Daftar Dosen Pengajar</h5>
                <small class="text-muted">Periode {{ $period->name }} · {{ $period->code }}</small>
            </div>
            <a href="{{ route($prefix.'dosen-pengajar-list.export', request()->query()) }}" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i> Export Sesuai Filter
            </a>
        </div>
        <div class="card-body">
            <div class="filter-panel mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h6 class="mb-1"><i class="fas fa-sliders-h text-primary me-2"></i>Filter Dosen Pengajar</h6>
                        <p class="text-muted small mb-0">Filter yang aktif otomatis diterapkan pada file export.</p>
                    </div>
                    @if ($hasFilters)<span class="badge bg-light-primary text-primary">Filter aktif</span>@endif
                </div>

                <form method="GET" action="{{ route($prefix.'dosen-pengajar-list.index') }}" class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label for="lecturer_q" class="form-label">Pencarian</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="search" name="q" id="lecturer_q" value="{{ $filters['q'] }}"
                                class="form-control border-start-0 ps-0" placeholder="NIDN, nama dosen, atau mata kuliah">
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label for="lecturer_id" class="form-label">Dosen</label>
                        <select name="dosen_id" id="lecturer_id" class="form-select">
                            <option value="">Semua dosen</option>
                            @foreach ($lecturers as $lecturer)
                                <option value="{{ $lecturer->id }}" @selected($filters['dosen_id'] == $lecturer->id)>{{ $lecturer->dsn_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label for="lecturer_program" class="form-label">Program Studi</label>
                        <select name="pstudi_id" id="lecturer_program" class="form-select">
                            <option value="">Semua prodi</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected($filters['pstudi_id'] == $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label for="lecturer_class" class="form-label">Kelas</label>
                        <select name="kelas_id" id="lecturer_class" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected($filters['kelas_id'] == $class->id)>{{ $class->name }} ({{ $class->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label for="lecturer_course" class="form-label">Mata Kuliah</label>
                        <select name="mata_kuliah_id" id="lecturer_course" class="form-select">
                            <option value="">Semua mata kuliah</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected($filters['mata_kuliah_id'] == $course->id)>
                                    {{ $course->code ? $course->code.' · ' : '' }}{{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label for="lecturer_role" class="form-label">Peran Mengajar</label>
                        <select name="peran" id="lecturer_role" class="form-select">
                            <option value="">Semua peran</option>
                            @foreach ($roleLabels as $value => $label)
                                <option value="{{ $value }}" @selected($filters['peran'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Terapkan Filter</button>
                            @if ($hasFilters)
                                <a href="{{ route($prefix.'dosen-pengajar-list.index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ number_format($items->total()) }} penugasan</small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th>#</th><th>Semester</th><th>Dosen</th><th>Mata Kuliah</th>
                        <th>Kelas &amp; Prodi</th><th>Tatap Muka</th><th>Evaluasi</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $meetingPercentage = $item->tatap_muka > 0
                                    ? min(100, round(($item->tatap_muka_realisasi / $item->tatap_muka) * 100))
                                    : 0;
                            @endphp
                            <tr>
                                <td>{{ $items->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">{{ $period->code }}</td>
                                <td class="lecturer-info">
                                    <span class="fw-semibold">{{ $item->nama_dosen }}</span>
                                    <small class="text-muted d-block">NIDN {{ $item->nidn }}</small>
                                    <span class="badge bg-light-primary text-primary mt-1">{{ $roleLabels[$item->peran] ?? $item->peran }}</span>
                                </td>
                                <td class="course-info">
                                    <span class="fw-semibold">{{ $item->nama_mata_kuliah }}</span>
                                    <small class="text-muted d-block">{{ $item->kode_mata_kuliah }} · {{ $item->sks_ajar }} SKS ajar</small>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $item->nama_kelas }}</span>
                                    <small class="text-muted d-block">{{ $item->kode_kelas }} · {{ $item->nama_prodi }} ({{ $item->kode_prodi }})</small>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fw-semibold">{{ $item->tatap_muka_realisasi }} / {{ $item->tatap_muka }}</span>
                                    <small class="text-muted d-block">Realisasi / target</small>
                                    <div class="meeting-progress"><span style="width: {{ $meetingPercentage }}%"></span></div>
                                </td>
                                <td><span class="badge bg-light-secondary text-secondary">1</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-3 d-block"></i><strong>Data dosen pengajar tidak ditemukan</strong>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-muted small mt-3 mb-0">
                <i class="fas fa-info-circle me-1"></i>
                NUPTK dikosongkan karena belum tersedia pada data dosen. Jenis evaluasi mengikuti nilai standar template, yaitu 1.
            </p>

            @if ($items->hasPages())
                <div class="d-flex justify-content-end mt-4">{{ $items->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
