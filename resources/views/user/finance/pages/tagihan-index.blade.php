@extends('base.base-dash-index')
@section('title')
    Data Tagihan - Siakad By Internal Developer
@endsection
@section('menu')
    Data Tagihan
@endsection
@section('submenu')
    Lihat
@endsection
@section('urlmenu')
    #
@endsection
@section('subdesc')
    Halaman untuk melihat data tagihan
@endsection
@section('custom-css')
    <style>
        .billing-page .summary-card { border: 1px solid #edf0f5; border-radius: 12px; height: 100%; }
        .billing-page .summary-icon { align-items: center; background: #eef2ff; border-radius: 10px; color: #435ebe; display: flex; flex: 0 0 42px; height: 42px; justify-content: center; width: 42px; }
        .billing-page .filter-panel { background: linear-gradient(135deg, #f8faff 0%, #f4f7fb 100%); border: 1px solid #e4e9f2; border-radius: 14px; padding: 1.25rem; }
        .billing-page .filter-panel .form-control, .billing-page .filter-panel .form-select { min-height: 40px; }
        .billing-page .table thead th { background: #f7f8fb; color: #607080; font-size: .74rem; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
        .billing-page .student-cell, .billing-page .bill-cell { min-width: 190px; }
        .billing-page .action-cell { white-space: nowrap; width: 1%; }
        @media (max-width: 767.98px) { .billing-page .filter-panel { padding: 1rem; } }
    </style>
@endsection
@section('content')
    @php
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
        $statusLabels = ['draft' => 'Draft', 'terbit' => 'Terbit', 'dibatalkan' => 'Dibatalkan'];
        $statusBadges = ['draft' => 'secondary', 'terbit' => 'success', 'dibatalkan' => 'danger'];
    @endphp
    <section class="section billing-page">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <a href="{{ route($prefix . 'finance.tagihan-index') }}" class="text-reset">
                    <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="summary-icon"><i class="fa-solid fa-file-invoice"></i></span>
                        <div><small class="text-muted d-block">Total Tagihan</small><strong class="fs-5">{{ number_format($totalTagihan) }}</strong></div>
                    </div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route($prefix . 'finance.pembayaran-index') }}" class="text-reset">
                    <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="summary-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                        <div><small class="text-muted d-block">Pembayaran Lunas</small><strong class="fs-5">{{ number_format($totalPembayaran) }}</strong></div>
                    </div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route($prefix . 'finance.keuangan-index') }}" class="text-reset">
                    <div class="card summary-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="summary-icon"><i class="fa-solid fa-wallet"></i></span>
                        <div><small class="text-muted d-block">Total Pendapatan</small><strong class="fs-5">Rp {{ number_format($income, 0, ',', '.') }}</strong></div>
                    </div></div>
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <h5 class="mb-1">Daftar Tagihan</h5>
                            <small class="text-muted">Kelola tagihan dan identitas mahasiswa penerima dalam satu tampilan.</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route($prefix . 'billing-period.index') }}" class="btn btn-outline-primary"><i class="fa-solid fa-calendar-days me-1"></i> Keuangan Periode</a>
                            <a href="{{ route($prefix . 'finance.tagihan-create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Tambah Tagihan</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="filter-panel mb-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <h6 class="mb-1"><i class="fas fa-sliders-h text-primary me-2"></i>Filter Data Tagihan</h6>
                                    <p class="text-muted small mb-0">Cari tagihan atau persempit hasil berdasarkan mahasiswa, sasaran, dan status.</p>
                                </div>
                                @if ($hasFilters)<span class="badge bg-light-primary text-primary">Filter aktif</span>@endif
                            </div>
                            <form method="GET" action="{{ route($prefix . 'finance.tagihan-index') }}" class="row g-3 align-items-end">
                                <div class="col-xl-4 col-md-6">
                                    <label for="billing_search" class="form-label">Pencarian</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="search" name="q" id="billing_search" value="{{ $filters['q'] }}" class="form-control border-start-0 ps-0" placeholder="Kode, tagihan, NIM, atau nama" maxlength="100">
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <label for="billing_student" class="form-label">Mahasiswa</label>
                                    <select name="mahasiswa_id" id="billing_student" class="form-select choices">
                                        <option value="">Semua mahasiswa</option>
                                        @foreach ($mahasiswa as $student)
                                            <option value="{{ $student->id }}" @selected($filters['mahasiswa_id'] == $student->id)>{{ $student->mhs_name }} · {{ $student->mhs_nim }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xl-2 col-md-6">
                                    <label for="billing_target" class="form-label">Sasaran</label>
                                    <select name="target" id="billing_target" class="form-select">
                                        <option value="">Semua sasaran</option>
                                        <option value="mahasiswa" @selected($filters['target'] === 'mahasiswa')>Mahasiswa</option>
                                        <option value="prodi" @selected($filters['target'] === 'prodi')>Program Studi</option>
                                        <option value="proku" @selected($filters['target'] === 'proku')>Program Kuliah</option>
                                        <option value="kelompok" @selected($filters['target'] === 'kelompok')>Kelompok</option>
                                    </select>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <label for="billing_status" class="form-label">Status</label>
                                    <div class="d-flex gap-2">
                                        <select name="status" id="billing_status" class="form-select">
                                            <option value="">Semua status</option>
                                            @foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary" title="Terapkan filter"><i class="fas fa-filter"></i></button>
                                        @if ($hasFilters)<a href="{{ route($prefix . 'finance.tagihan-index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>@endif
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <small class="text-muted">Menampilkan {{ $tagihan->firstItem() ?? 0 }}–{{ $tagihan->lastItem() ?? 0 }} dari {{ number_format($tagihan->total()) }} data</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>#</th><th>Tagihan</th><th>Mahasiswa</th><th>Sasaran</th><th>Status</th><th>Nominal</th><th class="text-center">Aksi</th></tr></thead>
                                <tbody>
                                @forelse ($tagihan as $item)
                                    @php
                                        $student = $item->targetMahasiswa ?? $item->mahasiswa;
                                        $targetType = $item->target_type ?: ((int) $item->users_id > 0 ? 'mahasiswa' : ((int) $item->prodi_id > 0 ? 'prodi' : ((int) $item->proku_id > 0 ? 'proku' : null)));
                                        $targetName = match ($targetType) {
                                            'mahasiswa' => $student?->mhs_name,
                                            'prodi' => ($item->targetProdi ?? $item->prodi)?->name,
                                            'proku' => ($item->targetProku ?? $item->prokuu)?->name,
                                            'kelompok' => $item->kelompok_target ? ucfirst($item->kelompok_target) : 'Kelompok mahasiswa',
                                            default => null,
                                        };
                                        $targetLabel = ['mahasiswa' => 'Mahasiswa', 'prodi' => 'Program Studi', 'proku' => 'Program Kuliah', 'kelompok' => 'Kelompok'][$targetType] ?? 'Belum ditentukan';
                                    @endphp
                                    <tr>
                                        <td>{{ $tagihan->firstItem() + $loop->index }}</td>
                                        <td class="bill-cell"><span class="fw-semibold">{{ $item->name }}</span><small class="text-muted d-block text-uppercase">{{ $item->code }}</small></td>
                                        <td class="student-cell">
                                            @if ($student)
                                                <span class="fw-semibold">{{ $student->mhs_name }}</span><small class="text-muted d-block">NIM {{ $student->mhs_nim }}</small>
                                            @else
                                                <span class="text-muted">—</span><small class="text-muted d-block">Tagihan non-individu</small>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-light-primary text-primary">{{ $targetLabel }}</span>@if ($targetName && $targetType !== 'mahasiswa')<small class="text-muted d-block mt-1">{{ $targetName }}</small>@endif</td>
                                        <td><span class="badge bg-light-{{ $statusBadges[$item->status] ?? 'secondary' }} text-{{ $statusBadges[$item->status] ?? 'secondary' }}">{{ $statusLabels[$item->status] ?? ucfirst($item->status ?? '—') }}</span></td>
                                        <td class="fw-semibold text-nowrap">Rp {{ number_format($item->nominal ?? $item->price, 0, ',', '.') }}</td>
                                        <td class="action-cell text-center">
                                            @if ($item->status === \App\Models\TagihanKuliah::STATUS_DRAFT)
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#prepareTagihan{{ $item->id }}" title="Proses penerbitan" aria-label="Proses penerbitan {{ $item->name }}"><i class="fas fa-paper-plane"></i></button>
                                            @endif
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#updateTagihan{{ $item->code }}" class="btn btn-sm btn-outline-primary" title="Edit tagihan" aria-label="Edit tagihan {{ $item->name }}"><i class="fas fa-edit"></i></a>
                                            <form id="delete-form-{{ $item->code }}" action="{{ route($prefix . 'finance.tagihan-destroy', $item->code) }}" method="POST" class="d-inline-block mb-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="bs-tooltip btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus tagihan" data-url="{{ route($prefix . 'finance.tagihan-destroy', $item->code) }}" data-name="{{ $item->name }}" onclick="deleteData('{{ $item->code }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-5"><div class="text-muted"><i class="fas fa-inbox fa-2x mb-3 d-block"></i><strong>Data tagihan tidak ditemukan</strong><br><small>Coba ubah atau reset filter yang digunakan.</small></div></td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($tagihan->hasPages())<div class="d-flex justify-content-end mt-4">{{ $tagihan->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @foreach ($tagihan as $item)
        @if ($item->status === \App\Models\TagihanKuliah::STATUS_DRAFT)
            @php
                $prepareForm = 'prepare-billing-'.$item->id;
                $draftStudent = $item->targetMahasiswa ?? $item->mahasiswa;
                $draftTargetType = $item->target_type ?: ((int) $item->users_id > 0 ? 'mahasiswa' : ((int) $item->prodi_id > 0 ? 'prodi' : ((int) $item->proku_id > 0 ? 'proku' : null)));
                $draftTargetName = match ($draftTargetType) {
                    'mahasiswa' => $draftStudent?->mhs_name,
                    'prodi' => ($item->targetProdi ?? $item->prodi)?->name,
                    'proku' => ($item->targetProku ?? $item->prokuu)?->name,
                    'kelompok' => $item->kelompok_target ? ucfirst($item->kelompok_target) : null,
                    default => null,
                };
                $draftNominal = $item->nominal ?? (int) preg_replace('/[^0-9]/', '', (string) $item->price);
            @endphp
            <form method="POST" action="{{ route($prefix . 'finance.tagihan-prepare', $item->code) }}">
                @csrf
                <input type="hidden" name="_form" value="{{ $prepareForm }}">
                <div class="modal fade text-start" id="prepareTagihan{{ $item->id }}" tabindex="-1" aria-labelledby="prepare-tagihan-title-{{ $item->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div><h5 class="modal-title mb-1" id="prepare-tagihan-title-{{ $item->id }}">Proses Penerbitan Tagihan</h5><small class="text-muted">{{ $item->name }} · {{ strtoupper($item->code) }}</small></div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                @if ($errors->any() && old('_form') === $prepareForm)
                                    <div class="alert alert-danger"><strong>Data belum dapat diproses.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                                @endif
                                <div class="alert alert-info">
                                    <i class="fas fa-shield-alt me-1"></i> Sistem akan membuat template pada periode yang sedang dipilih dan membuka pratinjau. Draft sumber diarsipkan sebagai <strong>Dibatalkan</strong>; tagihan belum terlihat oleh mahasiswa sampai Anda menekan <strong>Terbitkan</strong> di halaman pratinjau.
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label">Target draft</label><div class="form-control bg-light">{{ $draftTargetName ?: 'Target belum valid' }}</div><small class="text-muted">{{ ['mahasiswa' => 'Mahasiswa', 'prodi' => 'Program Studi', 'proku' => 'Program Kuliah', 'kelompok' => 'Kelompok'][$draftTargetType] ?? 'Belum ditentukan' }}</small></div>
                                    <div class="col-md-6"><label for="prepare-jenis-{{ $item->id }}" class="form-label">Jenis tagihan</label><select name="jenis" id="prepare-jenis-{{ $item->id }}" class="form-select" required>@foreach (\App\Models\TemplateTagihan::JENIS_LABELS as $value => $label)<option value="{{ $value }}" @selected((old('_form') === $prepareForm ? old('jenis') : 'ukt') === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="col-md-4"><label for="prepare-nominal-{{ $item->id }}" class="form-label">Nominal</label><input type="number" name="nominal" id="prepare-nominal-{{ $item->id }}" class="form-control" min="1" value="{{ old('_form') === $prepareForm ? old('nominal') : $draftNominal }}" required></div>
                                    <div class="col-md-4"><label for="prepare-date-{{ $item->id }}" class="form-label">Tanggal terbit</label><input type="date" name="tanggal_terbit" id="prepare-date-{{ $item->id }}" class="form-control" value="{{ old('_form') === $prepareForm ? old('tanggal_terbit') : now()->toDateString() }}" required></div>
                                    <div class="col-md-4"><label for="prepare-due-{{ $item->id }}" class="form-label">Jatuh tempo</label><input type="date" name="jatuh_tempo" id="prepare-due-{{ $item->id }}" class="form-control" value="{{ old('_form') === $prepareForm ? old('jatuh_tempo') : now()->addMonth()->toDateString() }}" required></div>
                                    <div class="col-12"><div class="form-check"><input type="hidden" name="wajib_lunas_krs" value="0"><input class="form-check-input" type="checkbox" name="wajib_lunas_krs" value="1" id="prepare-krs-{{ $item->id }}" @checked(old('_form') === $prepareForm && old('wajib_lunas_krs'))><label class="form-check-label" for="prepare-krs-{{ $item->id }}">Wajib lunas sebelum pengajuan KRS</label></div></div>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success"><i class="fas fa-search me-1"></i> Siapkan &amp; Pratinjau</button></div>
                        </div>
                    </div>
                </div>
            </form>
            @if ($errors->any() && old('_form') === $prepareForm)
                <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('prepareTagihan{{ $item->id }}')).show());</script>
            @endif
        @endif
    @endforeach
    <div class="me-1 mb-1 d-inline-block">

        <!--Extra Large Modal -->
        @foreach ($tagihan as $item)
            <form action="{{ route($prefix . 'finance.tagihan-update', $item->code) }}" method="POST" enctype="multipart/form-data">
                @method('patch')
                @csrf
                <div class="modal fade text-left w-100" id="updateTagihan{{ $item->code }}" tabindex="-1" role="dialog" aria-labelledby="update-tagihan-title-{{ $item->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="update-tagihan-title-{{ $item->id }}">Edit Tagihan - {{ $item->name }}</h4>
                                <div class="">

                                    <button type="submit" class="mt-1 btn btn-outline-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    <button type="button" class="mt-1 btn btn-outline-danger" data-bs-dismiss="modal" aria-label="Close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="modal-body row">
                                <div class="form-group col-lg-6 col-12">
                                    <label for="tagihan-name-{{ $item->id }}">Nama Tagihan</label>
                                    <input type="text" name="name" id="tagihan-name-{{ $item->id }}" class="form-control" value="{{ $item->name }}" placeholder="Nama tagihan...">
                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="tagihan-price-{{ $item->id }}">Nominal Tagihan</label>
                                    <input type="text" name="price" id="tagihan-price-{{ $item->id }}" class="form-control" value="{{ $item->price }}" placeholder="Nominal tagihan...">
                                    @error('price')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-4 col-12">
                                    <label for="tagihan-student-{{ $item->id }}">Tagihan Mahasiswa</label>
                                    <select name="users_id" id="tagihan-student-{{ $item->id }}" class="choices form-select">
                                        <option value="0" selected>Pilih Mahasiswa</option>
                                        @foreach ($mahasiswa as $mhs)
                                            <option value="{{ $mhs->id }}" {{ $item->users_id == $mhs->id ? 'selected' : '' }}>{{ $mhs->mhs_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('users_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-4 col-12">
                                    <label for="tagihan-prodi-{{ $item->id }}">Tagihan Program Studi</label>
                                    <select name="prodi_id" id="tagihan-prodi-{{ $item->id }}" class="choices form-select">
                                        <option value="0" selected>Pilih Program Studi</option>
                                        @foreach ($prodi as $prd)
                                            <option value="{{ $prd->id }}" {{ $item->prodi_id == $prd->id ? 'selected' : '' }}>{{ $prd->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('prodi_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-4 col-12">
                                    <label for="tagihan-proku-{{ $item->id }}">Tagihan Program Kuliah</label>
                                    <select name="proku_id" id="tagihan-proku-{{ $item->id }}" class="choices form-select">
                                        <option value="0" selected>Pilih Program Kuliah</option>
                                        @foreach ($proku as $prk)
                                            <option value="{{ $prk->id }}" {{ $item->proku_id == $prk->id ? 'selected' : '' }}>{{ $prk->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('proku_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endforeach
    </div>
@endsection
