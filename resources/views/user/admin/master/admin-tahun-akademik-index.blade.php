@extends('base.base-dash-index')

@section('title', 'Tahun Akademik - Siakad')
@section('menu', 'Perkuliahan')
@section('submenu', 'Tahun Akademik')
@section('urlmenu', '#')
@section('subdesc', 'Kelola tahun akademik sebagai induk periode Ganjil, Genap, dan Semester Pendek')

@section('custom-css')
<style>
    .academic-year-stat { border: 0; box-shadow: 0 10px 28px rgba(31, 45, 61, .07); }
    .academic-year-stat__icon { display: grid; width: 46px; height: 46px; place-items: center; border-radius: 13px; }
    .academic-year-code { display: inline-flex; padding: .3rem .65rem; border-radius: 999px; background: #eef4ff; color: #435ebe; font-size: .75rem; font-weight: 700; }
    .period-chip { display: inline-flex; align-items: center; gap: .3rem; margin: .15rem; padding: .28rem .55rem; border: 1px solid #e6e8ec; border-radius: 999px; background: #fff; color: #52606d; font-size: .72rem; }
    .academic-year-filter { border: 1px solid #edf0f4; border-radius: 14px; background: #fafbfc; }
</style>
@endsection

@section('content')
<section class="section">
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Data belum dapat diproses.</strong>
            <ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Total Tahun Akademik', 'value' => $summary['total'], 'icon' => 'fa-calendar-days', 'class' => 'bg-light-primary text-primary'],
            ['label' => 'Sudah Digunakan', 'value' => $summary['used'], 'icon' => 'fa-link', 'class' => 'bg-light-success text-success'],
            ['label' => 'Periode Terhubung', 'value' => $summary['periods'], 'icon' => 'fa-layer-group', 'class' => 'bg-light-info text-info'],
        ] as $stat)
            <div class="col-md-4">
                <div class="card academic-year-stat h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="academic-year-stat__icon {{ $stat['class'] }}"><i class="fas {{ $stat['icon'] }}"></i></span>
                    <div><small class="text-muted">{{ $stat['label'] }}</small><h3 class="mb-0">{{ number_format($stat['value'], 0, ',', '.') }}</h3></div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1">Daftar Tahun Akademik</h5>
                <small class="text-muted">{{ number_format($academicYears->total(), 0, ',', '.') }} data sesuai filter</small>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAcademicYear">
                <i class="fas fa-plus me-1"></i> Tambah Tahun Akademik
            </button>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route($prefix.'master.tahun-akademik-index') }}" class="academic-year-filter p-3 mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5">
                        <label for="academic-year-search" class="form-label">Pencarian</label>
                        <input type="search" id="academic-year-search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama, kode, atau tahun">
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label for="academic-year-start-filter" class="form-label">Tahun Mulai</label>
                        <select id="academic-year-start-filter" name="year_start" class="form-select">
                            <option value="">Semua tahun</option>
                            @foreach ($yearOptions as $year)<option value="{{ $year }}" @selected(($filters['year_start'] ?? null) == $year)>{{ $year }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label for="academic-year-usage-filter" class="form-label">Penggunaan</label>
                        <select id="academic-year-usage-filter" name="usage" class="form-select">
                            <option value="">Semua status</option>
                            <option value="used" @selected(($filters['usage'] ?? null) === 'used')>Sudah memiliki periode</option>
                            <option value="unused" @selected(($filters['usage'] ?? null) === 'unused')>Belum memiliki periode</option>
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                        <a href="{{ route($prefix.'master.tahun-akademik-index') }}" class="btn btn-outline-secondary" title="Reset filter"><i class="fas fa-rotate-left"></i></a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th class="text-center">#</th><th>Tahun Akademik</th><th>Rentang</th><th>Periode Akademik</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($academicYears as $item)
                            <tr>
                                <td class="text-center">{{ $academicYears->firstItem() + $loop->index }}</td>
                                <td><strong class="d-block">{{ $item->name }}</strong><span class="academic-year-code mt-1">{{ $item->code }}</span></td>
                                <td><span class="fw-semibold">{{ $item->year_start }}/{{ $item->year_end }}</span></td>
                                <td>
                                    @forelse ($item->periodeAkademiks as $period)
                                        <span class="period-chip"><i class="fas fa-circle text-{{ $period->is_active ? 'success' : 'secondary' }}" style="font-size:.45rem"></i>{{ $period->term_label }}</span>
                                    @empty
                                        <span class="text-muted small">Belum ada periode</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge bg-{{ $item->periode_akademiks_count > 0 ? 'light-success text-success' : 'light-secondary text-secondary' }}">
                                        {{ $item->periode_akademiks_count > 0 ? $item->periode_akademiks_count.' periode' : 'Belum digunakan' }}
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAcademicYear{{ $item->id }}" title="Ubah"><i class="fas fa-edit"></i></button>
                                    @if ($item->periode_akademiks_count === 0)
                                        <form class="d-inline" method="POST" action="{{ route($prefix.'master.tahun-akademik-destroy', $item->code) }}" onsubmit="return confirm('Hapus tahun akademik {{ $item->name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Masih digunakan oleh periode"><i class="fas fa-lock"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="fas fa-calendar-xmark fa-2x mb-3 d-block"></i>Belum ada tahun akademik yang sesuai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <small class="text-muted">Menampilkan {{ $academicYears->firstItem() ?? 0 }}–{{ $academicYears->lastItem() ?? 0 }} dari {{ $academicYears->total() }}</small>
                {{ $academicYears->links() }}
            </div>
        </div>
    </div>
</section>

<form method="POST" action="{{ route($prefix.'master.tahun-akademik-store') }}">
    @csrf
    <div class="modal fade" id="createAcademicYear" tabindex="-1" aria-labelledby="createAcademicYearLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title" id="createAcademicYearLabel">Tambah Tahun Akademik</h5><small class="text-muted">Tahun akademik menjadi induk bagi periode semester.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">@include('user.admin.master.partials.tahun-akademik-form', ['formId' => 'create', 'formMarker' => 'create-academic-year'])</div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan Tahun Akademik</button></div>
    </div></div></div>
</form>

@foreach ($academicYears as $item)
    <form method="POST" action="{{ route($prefix.'master.tahun-akademik-update', $item->code) }}">
        @csrf @method('PATCH')
        <div class="modal fade" id="editAcademicYear{{ $item->id }}" tabindex="-1" aria-labelledby="editAcademicYearLabel{{ $item->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
            <div class="modal-header"><div><h5 class="modal-title" id="editAcademicYearLabel{{ $item->id }}">Ubah Tahun Akademik</h5><small class="text-muted">{{ $item->name }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">@include('user.admin.master.partials.tahun-akademik-form', ['formId' => 'edit-'.$item->id, 'formMarker' => 'edit-academic-year-'.$item->id, 'item' => $item])</div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan Perubahan</button></div>
        </div></div></div>
    </form>
@endforeach
@endsection

@section('custom-js')
    @if ($errors->any() && old('_form') === 'create-academic-year')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('createAcademicYear')).show());</script>
    @elseif ($errors->any() && str_starts_with((string) old('_form'), 'edit-academic-year-'))
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('editAcademicYear{{ str_replace('edit-academic-year-', '', old('_form')) }}')).show());</script>
    @endif
@endsection
