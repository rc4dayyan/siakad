@extends('base.base-dash-index')

@section('menu', 'Data Akademik')
@section('submenu', 'List KRS')
@section('urlmenu', route($prefix.'krs-list.index'))
@section('subdesc', 'Rekap mata kuliah KRS mahasiswa pada periode '.$period->name)

@section('custom-css')
<style>
    .krs-list-page .filter-panel { background: linear-gradient(135deg, #f8faff 0%, #f4f7fb 100%); border: 1px solid #e4e9f2; border-radius: 14px; padding: 1.25rem; }
    .krs-list-page .summary-card { border: 1px solid #edf0f5; border-radius: 12px; height: 100%; }
    .krs-list-page .summary-icon { align-items: center; background: #eef2ff; border-radius: 10px; color: #435ebe; display: flex; height: 42px; justify-content: center; width: 42px; }
    .krs-list-page .table thead th { background: #f7f8fb; color: #607080; font-size: .74rem; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
    .krs-list-page .student-info, .krs-list-page .course-info { min-width: 210px; }
    .krs-list-page .filter-actions .btn { min-height: 38px; }
    @media (max-width: 767.98px) { .krs-list-page .filter-panel { padding: 1rem; } }
</style>
@endsection

@section('content')
@php
    $statusBadges = [
        'draft' => 'secondary', 'submitted' => 'info', 'approved' => 'success',
        'rejected' => 'danger', 'locked' => 'primary',
    ];
    $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
@endphp

<section class="section krs-list-page">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                <span class="summary-icon"><i class="fas fa-list"></i></span>
                <div><small class="text-muted d-block">Baris KRS</small><strong class="fs-5">{{ number_format($summary['rows']) }}</strong></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                <span class="summary-icon"><i class="fas fa-user-graduate"></i></span>
                <div><small class="text-muted d-block">Mahasiswa</small><strong class="fs-5">{{ number_format($summary['students']) }}</strong></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                <span class="summary-icon"><i class="fas fa-layer-group"></i></span>
                <div><small class="text-muted d-block">Total SKS</small><strong class="fs-5">{{ number_format($summary['credits']) }}</strong></div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h5 class="mb-1">Daftar Kartu Rencana Studi</h5>
                <small class="text-muted">Periode {{ $period->name }} · {{ $period->code }}</small>
            </div>
            <a href="{{ route($prefix.'krs-list.export', request()->query()) }}" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i> Export Sesuai Filter
            </a>
        </div>
        <div class="card-body">
            <div class="filter-panel mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h6 class="mb-1"><i class="fas fa-sliders-h text-primary me-2"></i>Filter List KRS</h6>
                        <p class="text-muted small mb-0">Gunakan satu atau beberapa filter untuk mempersempit data dan hasil export.</p>
                    </div>
                    @if ($hasFilters)
                        <span class="badge bg-light-primary text-primary">Filter aktif</span>
                    @endif
                </div>

                <form method="GET" action="{{ route($prefix.'krs-list.index') }}" class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label for="krs_q" class="form-label">Pencarian</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="search" name="q" id="krs_q" value="{{ $filters['q'] }}"
                                class="form-control border-start-0 ps-0" placeholder="NIM, nama, kode, atau mata kuliah">
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label for="krs_pstudi" class="form-label">Program Studi</label>
                        <select name="pstudi_id" id="krs_pstudi" class="form-select">
                            <option value="">Semua prodi</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected($filters['pstudi_id'] == $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label for="krs_kelas" class="form-label">Kelas</label>
                        <select name="kelas_id" id="krs_kelas" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected($filters['kelas_id'] == $class->id)>
                                    {{ $class->name }} ({{ $class->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label for="krs_course" class="form-label">Mata Kuliah</label>
                        <select name="mata_kuliah_id" id="krs_course" class="form-select">
                            <option value="">Semua mata kuliah</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected($filters['mata_kuliah_id'] == $course->id)>
                                    {{ $course->code ? $course->code.' · ' : '' }}{{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="krs_status" class="form-label">Status KRS</label>
                        <select name="status" id="krs_status" class="form-select">
                            <option value="">Semua status</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="krs_grade" class="form-label">Nilai Huruf</label>
                        <select name="nilai" id="krs_grade" class="form-select">
                            <option value="">Semua nilai</option>
                            @foreach (['A', 'B', 'C', 'D', 'E'] as $grade)
                                <option value="{{ $grade }}" @selected($filters['nilai'] === $grade)>{{ $grade }}</option>
                            @endforeach
                            <option value="empty" @selected($filters['nilai'] === 'empty')>Belum ada nilai</option>
                        </select>
                    </div>
                    <div class="col-xl-4 col-md-4 filter-actions">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Terapkan Filter</button>
                            @if ($hasFilters)
                                <a href="{{ route($prefix.'krs-list.index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter">
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ number_format($items->total()) }} data</small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th>#</th><th>Mahasiswa</th><th>Semester</th><th>Mata Kuliah</th>
                        <th>Kelas</th><th>Program Studi</th><th>Nilai</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $items->firstItem() + $loop->index }}</td>
                                <td class="student-info">
                                    <span class="fw-semibold">{{ $item->nama_mahasiswa }}</span>
                                    <small class="text-muted d-block">NIM {{ $item->nim }}</small>
                                </td>
                                <td>{{ $period->code }}</td>
                                <td class="course-info">
                                    <span class="fw-semibold">{{ $item->nama_mata_kuliah }}</span>
                                    <small class="text-muted d-block">{{ $item->kode_mata_kuliah }} · {{ $item->sks }} SKS</small>
                                </td>
                                <td>
                                    {{ $item->nama_kelas }}
                                    <small class="text-muted d-block">{{ $item->kode_kelas }}</small>
                                </td>
                                <td>
                                    {{ $item->nama_prodi }}
                                    <small class="text-muted d-block">{{ $item->kode_prodi }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $item->nilai_huruf ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">{{ $item->nilai_huruf ?: '—' }}</span>
                                    @if ($item->nilai_huruf)
                                        <small class="text-muted d-block mt-1">Indeks {{ match ($item->nilai_huruf) { 'A' => '4.00', 'B' => '3.00', 'C' => '2.00', 'D' => '1.00', 'E' => '0.00', default => '—' } }}</small>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $statusBadges[$item->status] ?? 'secondary' }}">{{ $statusLabels[$item->status] ?? ucfirst($item->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-5">
                                <div class="text-muted"><i class="fas fa-inbox fa-2x mb-3 d-block"></i><strong>Data KRS tidak ditemukan</strong><br><small>Coba ubah atau reset filter yang digunakan.</small></div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-muted small mt-3 mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Nilai indeks mengikuti konversi A=4, B=3, C=2, D=1, E=0. Nilai angka dikosongkan karena data yang tersimpan saat ini berupa nilai huruf.
            </p>

            @if ($items->hasPages())
                <div class="d-flex justify-content-end mt-4">{{ $items->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
