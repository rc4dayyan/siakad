@extends('base.base-dash-index')
@section('title', 'Penawaran Belum Dijadwalkan')
@section('menu', 'Penawaran Mata Kuliah')
@section('submenu', 'Belum Dijadwalkan')
@section('urlmenu', '#')
@section('subdesc', 'Daftar penawaran periode berjalan yang belum memiliki jadwal mingguan')
@section('custom-css')
<style>
    .unscheduled-filter { padding: 20px; border: 1px solid #e1e8e6; border-radius: 14px; background: linear-gradient(145deg, #f8fbfa, #fff); }
    .unscheduled-filter .form-label { margin-bottom: 6px; color: #526670; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .unscheduled-filter .form-control, .unscheduled-filter .form-select { min-height: 42px; border-color: #dce5e2; border-radius: 9px; }
    .unscheduled-table thead th { color: #6b7d88; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
    .unscheduled-table td { vertical-align: middle; }
    .unscheduled-course { min-width: 230px; }
    .unscheduled-course strong { display: block; color: #0b2135; }
    .unscheduled-course small { color: #6b7d88; }
</style>
@endsection
@section('content')
<section class="section">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1">Penawaran Belum Dijadwalkan</h5>
                <small class="text-muted">{{ $period?->name ?? 'Periode belum dipilih' }} · {{ number_format($offerings->count()) }} penawaran ditemukan</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route($prefix.'master.penawaran-index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-list me-1"></i> Semua Penawaran
                </a>
                <a href="{{ route($prefix.'master.jadwal-mingguan-index') }}" class="btn btn-primary">
                    <i class="fas fa-calendar-days me-1"></i> Jadwal &amp; Pertemuan
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="unscheduled-filter mb-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-xl-4 col-lg-6">
                        <label for="unscheduled-search" class="form-label">Cari penawaran</label>
                        <input type="search" name="q" id="unscheduled-search" class="form-control" value="{{ $filters['q'] }}" placeholder="Kode atau nama mata kuliah">
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label for="unscheduled-program" class="form-label">Program studi</label>
                        <select name="pstudi_id" id="unscheduled-program" class="form-select">
                            <option value="">Semua program</option>
                            @foreach($programs as $item)<option value="{{ $item->id }}" @selected($filters['pstudi_id'] == $item->id)>{{ $item->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label for="unscheduled-class" class="form-label">Kelas</label>
                        <select name="kelas_id" id="unscheduled-class" class="form-select">
                            <option value="">Semua kelas</option>
                            @foreach($classes as $item)<option value="{{ $item->id }}" @selected($filters['kelas_id'] == $item->id)>{{ $item->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label for="unscheduled-semester" class="form-label">Semester</label>
                        <select name="semester" id="unscheduled-semester" class="form-select">
                            <option value="">Semua semester</option>
                            @for($semester = 1; $semester <= 14; $semester++)<option value="{{ $semester }}" @selected($filters['semester'] === $semester)>Semester {{ $semester }}</option>@endfor
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label for="unscheduled-lecturer" class="form-label">Dosen utama</label>
                        <select name="dosen_id" id="unscheduled-lecturer" class="form-select">
                            <option value="">Semua dosen</option>
                            @foreach($lecturers as $item)<option value="{{ $item->id }}" @selected($filters['dosen_id'] == $item->id)>{{ $item->dsn_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-xl-4 col-lg-6 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Terapkan Filter</button>
                        <a href="{{ route($prefix.'master.penawaran-unscheduled') }}" class="btn btn-outline-secondary"><i class="fas fa-rotate-left"></i></a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover unscheduled-table mb-0">
                    <thead><tr><th>Penawaran</th><th>Program &amp; Semester</th><th>Kelas</th><th>Dosen Utama</th><th>Kapasitas</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($offerings as $item)
                            <tr>
                                <td class="unscheduled-course"><strong>{{ $item->masterMataKuliah?->name ?? 'Mata kuliah tidak ditemukan' }}</strong><small>{{ $item->code }} · {{ $item->sks }} SKS</small></td>
                                <td>{{ $item->pstudi?->name ?? '—' }}<br><small class="text-muted">Semester {{ $item->masterMataKuliah?->semester ?? '—' }}</small></td>
                                <td>{{ $item->kelas?->name ?? '—' }}</td>
                                <td>{{ $item->dosenUtama?->dsn_name ?? 'Belum ditentukan' }}</td>
                                <td>{{ number_format($item->krs_items_count) }} / {{ number_format($item->kapasitas) }}</td>
                                <td class="text-end">
                                    @if($canManage)
                                        <a href="{{ route($prefix.'master.jadwal-mingguan-index', ['penawaran_id' => $item->id, 'buat' => 1]) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-calendar-plus me-1"></i> Jadwalkan
                                        </a>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary">Periode terkunci</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-5"><i class="fas fa-circle-check text-success fs-3 d-block mb-2"></i>Semua penawaran pada periode ini sudah dijadwalkan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
