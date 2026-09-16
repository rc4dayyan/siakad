@extends('base.base-dash-index')

@section('title') Data Pengguna Dosen - Siakad By Internal Developer @endsection
@section('menu') Data Pengguna Dosen @endsection
@section('submenu') Daftar Dosen @endsection
@section('urlmenu') # @endsection
@section('subdesc') Kelola akun, status, dan informasi kontak dosen dalam satu halaman. @endsection

@section('custom-css')
<style>
    .lecturer-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .lecturer-summary__item { display: flex; align-items: center; gap: 14px; min-height: 92px; padding: 18px; border: 1px solid #e8ecef; border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgba(37, 50, 55, .05); }
    .lecturer-summary__icon { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; flex: 0 0 46px; border-radius: 12px; font-size: 18px; }
    .lecturer-summary__icon--total { background: #eef2ff; color: #435ebe; }
    .lecturer-summary__icon--active { background: #eaf8f2; color: #198754; }
    .lecturer-summary__icon--inactive { background: #fff0f1; color: #dc3545; }
    .lecturer-summary__label { display: block; margin-bottom: 2px; color: #7b8794; font-size: 12px; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; }
    .lecturer-summary__value { margin: 0; color: #263238; font-size: 24px; font-weight: 700; line-height: 1.2; }
    .lecturer-filter { margin-bottom: 20px; padding: 20px; border: 1px solid #dfe7e4; border-radius: 14px; background: linear-gradient(135deg, #f5faf8 0%, #fff 72%); }
    .lecturer-filter__header { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
    .lecturer-filter__title { margin: 0 0 3px; color: #263d36; font-size: 15px; font-weight: 700; }
    .lecturer-filter__description { margin: 0; color: #71837d; font-size: 12px; }
    .lecturer-filter__result { display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; border-radius: 999px; background: #e8f5f0; color: #176b55; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .lecturer-filter .form-label { margin-bottom: 6px; color: #46534f; font-size: 12px; font-weight: 700; }
    .lecturer-filter .form-control, .lecturer-filter .form-select { min-height: 42px; border-color: #dce4e1; border-radius: 9px; }
    .lecturer-filter .input-group-text { border-color: #dce4e1; border-radius: 9px 0 0 9px; background: #fff; color: #87938f; }
    .lecturer-filter__buttons { display: flex; gap: 8px; }
    .lecturer-filter__buttons .btn { min-height: 42px; border-radius: 9px; white-space: nowrap; }
    .lecturer-card { overflow: hidden; border: 0; border-radius: 14px; box-shadow: 0 7px 24px rgba(37, 50, 55, .07); }
    .lecturer-card .card-header { padding: 20px 22px; border-bottom: 1px solid #edf0f2; background: #fff; }
    .lecturer-card__title { margin: 0 0 4px; color: #263238; font-size: 18px; font-weight: 700; }
    .lecturer-card__description { margin: 0; color: #7b8794; font-size: 13px; }
    .lecturer-card .card-body { padding: 8px 22px 22px; }
    .lecturer-table th { padding-top: 15px; padding-bottom: 15px; border-bottom-width: 1px !important; color: #66727d; font-size: 11px; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
    .lecturer-table td { padding-top: 14px; padding-bottom: 14px; vertical-align: middle; }
    .lecturer-profile { display: flex; align-items: center; gap: 12px; min-width: 210px; }
    .lecturer-profile__avatar { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px; background: linear-gradient(135deg, #435ebe, #6f85db); color: #fff; font-size: 15px; font-weight: 700; text-transform: uppercase; }
    .lecturer-profile__name { display: block; margin-bottom: 2px; color: #263238; font-size: 14px; font-weight: 700; }
    .lecturer-profile__username, .lecturer-contact { color: #7b8794; font-size: 12px; }
    .lecturer-contact span { display: block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lecturer-contact i { width: 16px; color: #9aa4ad; text-align: center; }
    .lecturer-status { display: inline-flex; align-items: center; gap: 7px; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .lecturer-status::before { width: 7px; height: 7px; border-radius: 50%; background: currentColor; content: ''; }
    .lecturer-status--active { background: #eaf8f2; color: #198754; }
    .lecturer-status--inactive { background: #fff0f1; color: #dc3545; }
    .lecturer-action-dropdown .dropdown-toggle { min-width: 88px; border-radius: 9px; font-weight: 600; }
    .lecturer-action-dropdown .dropdown-menu { min-width: 185px; padding: 7px; border: 1px solid #e6ece9; border-radius: 10px; box-shadow: 0 10px 28px rgba(38, 61, 54, .14); }
    .lecturer-action-dropdown .dropdown-item { display: flex; align-items: center; gap: 9px; padding: 9px 11px; border: 0; border-radius: 7px; background: transparent; font-size: 13px; }
    .lecturer-action-dropdown .dropdown-item:hover { background: #f4f7f6; }
    .lecturer-action-dropdown .dropdown-item i { width: 16px; text-align: center; }
    .lecturer-contact-box { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid #e8ecef; border-radius: 11px; background: #fafbfc; }
    .lecturer-contact-box__icon { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex: 0 0 40px; border-radius: 10px; background: #eef2ff; color: #435ebe; }
    .lecturer-contact-box__content { min-width: 0; flex: 1; }
    .lecturer-contact-box__content small, .lecturer-contact-box__content strong { display: block; }
    .lecturer-contact-box__content small { color: #7b8794; }
    .lecturer-contact-box__content strong { overflow: hidden; color: #263238; font-size: 14px; text-overflow: ellipsis; white-space: nowrap; }
    @media (max-width: 767.98px) {
        .lecturer-summary { grid-template-columns: 1fr; gap: 10px; }
        .lecturer-summary__item { min-height: 76px; padding: 14px; }
        .lecturer-filter__header { align-items: flex-start; flex-direction: column; }
        .lecturer-filter__buttons, .lecturer-filter__buttons .btn { width: 100%; }
        .lecturer-card .card-header { align-items: flex-start !important; flex-direction: column; }
        .lecturer-card__actions { width: 100%; }
        .lecturer-card__actions .btn { flex: 1; }
        .lecturer-card .card-body { padding-right: 14px; padding-left: 14px; }
    }
</style>
@endsection

@section('content')
<section class="section">
    <div class="lecturer-summary" aria-label="Ringkasan data dosen">
        <div class="lecturer-summary__item">
            <span class="lecturer-summary__icon lecturer-summary__icon--total"><i class="fas fa-chalkboard-teacher"></i></span>
            <div><span class="lecturer-summary__label">Total Dosen</span><p class="lecturer-summary__value">{{ number_format($lectureSummary['total']) }}</p></div>
        </div>
        <div class="lecturer-summary__item">
            <span class="lecturer-summary__icon lecturer-summary__icon--active"><i class="fas fa-user-check"></i></span>
            <div><span class="lecturer-summary__label">Dosen Aktif</span><p class="lecturer-summary__value">{{ number_format($lectureSummary['active']) }}</p></div>
        </div>
        <div class="lecturer-summary__item">
            <span class="lecturer-summary__icon lecturer-summary__icon--inactive"><i class="fas fa-user-slash"></i></span>
            <div><span class="lecturer-summary__label">Dosen Nonaktif</span><p class="lecturer-summary__value">{{ number_format($lectureSummary['inactive']) }}</p></div>
        </div>
    </div>

    <div class="lecturer-filter">
        <div class="lecturer-filter__header">
            <div>
                <h6 class="lecturer-filter__title"><i class="fas fa-sliders-h text-primary me-2"></i>Filter Data Dosen</h6>
                <p class="lecturer-filter__description">Temukan dosen berdasarkan identitas, status akun, atau jenis kelamin.</p>
            </div>
            <span class="lecturer-filter__result"><i class="fas fa-list-ul"></i>{{ number_format($dosen->count()) }} data ditemukan</span>
        </div>
        <form method="GET" action="{{ route('web-admin.workers.lecture-index') }}" class="row g-3 align-items-end">
            <div class="col-xl-5 col-md-6">
                <label for="lecturer_search" class="form-label">Cari dosen</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="search" name="search" id="lecturer_search" class="form-control"
                        value="{{ $filters['search'] ?? '' }}" placeholder="Nama, NIDN, username, email, atau telepon">
                </div>
            </div>
            <div class="col-xl-2 col-md-3 col-6">
                <label for="lecturer_status" class="form-label">Status</label>
                <select name="status" id="lecturer_status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                    <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                </select>
            </div>
            <div class="col-xl-2 col-md-3 col-6">
                <label for="lecturer_gender" class="form-label">Jenis kelamin</label>
                <select name="gender" id="lecturer_gender" class="form-select">
                    <option value="">Semua gender</option>
                    <option value="L" @selected(($filters['gender'] ?? '') === 'L')>Laki-laki</option>
                    <option value="P" @selected(($filters['gender'] ?? '') === 'P')>Perempuan</option>
                </select>
            </div>
            <div class="col-xl-3 col-12">
                <div class="lecturer-filter__buttons">
                    @if ($hasLectureFilters)
                        <a href="{{ route('web-admin.workers.lecture-index') }}" class="btn btn-outline-secondary"><i class="fas fa-rotate-left me-1"></i> Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Terapkan Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card lecturer-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="lecturer-card__title">Daftar Dosen</h5>
                <p class="lecturer-card__description">Cari dan kelola seluruh akun dosen yang terdaftar.</p>
            </div>
            <div class="lecturer-card__actions d-flex flex-wrap gap-2">
                <a href="{{ route('web-admin.workers.lecture-export', array_filter($filters, fn ($value) => filled($value))) }}" class="btn btn-outline-success"><i class="fa-solid fa-file-export me-1"></i> Export Hasil</a>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importLecture"><i class="fa-solid fa-file-import me-1"></i> Import</button>
                <a href="{{ route('web-admin.workers.lecture-create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Tambah Dosen</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover lecturer-table mb-0" id="table1" data-dashboard-searchable="false">
                    <thead><tr><th>Dosen</th><th>NIDN</th><th>Kontak</th><th>Gender</th><th>Terdaftar</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($dosen as $item)
                            <tr>
                                <td data-label="Dosen">
                                    <div class="lecturer-profile">
                                        <span class="lecturer-profile__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($item->dsn_name), 0, 1)) ?: '?' }}</span>
                                        <div><span class="lecturer-profile__name">{{ $item->dsn_name }}</span><span class="lecturer-profile__username">&#64;{{ $item->dsn_user ?: 'username belum tersedia' }}</span></div>
                                    </div>
                                </td>
                                <td data-label="NIDN"><span class="font-monospace fw-semibold">{{ $item->dsn_nidn ?: '-' }}</span></td>
                                <td data-label="Kontak">
                                    <div class="lecturer-contact">
                                        <span title="{{ $item->dsn_mail }}"><i class="fas fa-envelope me-1"></i>{{ $item->dsn_mail ?: '-' }}</span>
                                        <span title="{{ $item->dsn_phone }}"><i class="fas fa-phone me-1"></i>{{ $item->dsn_phone ?: '-' }}</span>
                                    </div>
                                </td>
                                <td data-label="Gender">{{ match ($item->dsn_gend) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => '-' } }}</td>
                                <td data-label="Terdaftar"><span class="text-nowrap">{{ $item->created_at?->translatedFormat('d M Y') ?? '-' }}</span></td>
                                <td data-label="Status">
                                    @if ((int) $item->raw_dsn_stat === 1)
                                        <span class="lecturer-status lecturer-status--active">Aktif</span>
                                    @else
                                        <span class="lecturer-status lecturer-status--inactive">Nonaktif</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" class="text-end">
                                    <div class="dropdown lecturer-action-dropdown d-inline-block">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" id="lecturer-action-{{ $item->dsn_code }}" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-cog me-1"></i> Aksi</button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="lecturer-action-{{ $item->dsn_code }}">
                                            <li><button type="button" class="dropdown-item text-info" data-bs-toggle="modal" data-bs-target="#viewContact{{ $item->dsn_code }}"><i class="fas fa-address-card"></i><span>Lihat Kontak</span></button></li>
                                            <li><a href="{{ route('web-admin.workers.lecture-edit', $item->dsn_code) }}" class="dropdown-item text-primary"><i class="fas fa-pen"></i><span>Edit Data</span></a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><form id="delete-form-{{ $item->dsn_code }}" action="{{ route('web-admin.workers.lecture-destroy', $item->dsn_code) }}" method="POST">@csrf @method('DELETE')<button type="button" class="dropdown-item text-danger" onclick="deleteData('{{ $item->dsn_code }}')"><i class="fas fa-trash"></i><span>Hapus Data</span></button></form></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<form action="{{ route('web-admin.workers.lecture-import') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade text-left w-100" id="importLecture" tabindex="-1" role="dialog" aria-labelledby="importLectureLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div><h4 class="modal-title" id="importLectureLabel">Import Data Dosen</h4><small class="text-muted">Gunakan format OpenFeeder XLSX atau CSV.</small></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-3" role="alert">
                        <i class="fas fa-circle-info mt-1"></i>
                        <div><strong>Format file</strong><div>Gunakan hasil Export sebagai template. Kolom yang diproses adalah <strong>NIDN</strong> dan <strong>Nama Dosen</strong>.</div></div>
                    </div>
                    <div class="alert alert-light border">Username dan password awal akun baru adalah NIDN. NIDN yang sudah terdaftar akan dilewati otomatis.</div>
                    <div class="form-group mb-0">
                        <label for="import_dosen" class="form-label fw-semibold">Pilih file</label>
                        <input type="file" name="import" id="import_dosen" class="form-control" accept=".xlsx,.csv" required>
                        <small class="text-muted">Format XLSX atau CSV, maksimal 2 MB.</small>
                        @error('import') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-import me-1"></i> Import Data</button>
                </div>
            </div>
        </div>
    </div>
</form>

@foreach ($dosen as $item)
    <div class="modal fade text-left w-100" id="viewContact{{ $item->dsn_code }}" tabindex="-1" role="dialog" aria-labelledby="contactLabel{{ $item->dsn_code }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div><h4 class="modal-title" id="contactLabel{{ $item->dsn_code }}">Informasi Kontak</h4><small class="text-muted">{{ $item->dsn_name }} · {{ $item->dsn_nidn ?: 'NIDN belum tersedia' }}</small></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-3">
                        <div class="lecturer-contact-box">
                            <span class="lecturer-contact-box__icon"><i class="fas fa-phone"></i></span>
                            <div class="lecturer-contact-box__content"><small>Nomor telepon</small><strong>{{ $item->dsn_phone ?: 'Belum tersedia' }}</strong></div>
                            @if ($item->dsn_phone)
                                <a href="https://wa.me/{{ $item->dsn_phone }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm" aria-label="Hubungi melalui WhatsApp"><i class="fab fa-whatsapp me-1"></i> Hubungi</a>
                            @endif
                        </div>
                        <div class="lecturer-contact-box">
                            <span class="lecturer-contact-box__icon"><i class="fas fa-envelope"></i></span>
                            <div class="lecturer-contact-box__content"><small>Alamat email</small><strong>{{ $item->dsn_mail ?: 'Belum tersedia' }}</strong></div>
                            @if ($item->dsn_mail)
                                <a href="mailto:{{ $item->dsn_mail }}" class="btn btn-outline-primary btn-sm" aria-label="Kirim email"><i class="fas fa-paper-plane me-1"></i> Email</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@section('custom-js')
@if ($errors->has('import'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('importLecture')).show();
});
</script>
@endif
@endsection
