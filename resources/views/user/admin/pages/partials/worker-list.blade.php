<section class="section">
    <div class="worker-summary" aria-label="Ringkasan data pengguna">
        <div class="worker-summary__item">
            <span class="worker-summary__icon worker-summary__icon--total"><i class="fas fa-users"></i></span>
            <div><span class="worker-summary__label">Total Pengguna</span><p class="worker-summary__value">{{ number_format($workerSummary['total']) }}</p></div>
        </div>
        <div class="worker-summary__item">
            <span class="worker-summary__icon worker-summary__icon--active"><i class="fas fa-user-check"></i></span>
            <div><span class="worker-summary__label">Akun Aktif</span><p class="worker-summary__value">{{ number_format($workerSummary['active']) }}</p></div>
        </div>
        <div class="worker-summary__item">
            <span class="worker-summary__icon worker-summary__icon--inactive"><i class="fas fa-user-slash"></i></span>
            <div><span class="worker-summary__label">Akun Nonaktif</span><p class="worker-summary__value">{{ number_format($workerSummary['inactive']) }}</p></div>
        </div>
    </div>

    <div class="worker-filter">
        <div class="worker-filter__header">
            <div>
                <h6 class="worker-filter__title"><i class="fas fa-sliders-h text-primary me-2"></i>Filter Data Pengguna</h6>
                <p class="worker-filter__description">Temukan pengguna berdasarkan identitas, status, jenis kelamin{{ $showRoleFilter ? ', atau unit kerja' : '' }}.</p>
            </div>
            <span class="worker-filter__result"><i class="fas fa-list-ul"></i>{{ number_format($admin->count()) }} data ditemukan</span>
        </div>
        <form method="GET" action="{{ route($indexRoute) }}" class="row g-3 align-items-end">
            <div class="{{ $showRoleFilter ? 'col-xl-4' : 'col-xl-5' }} col-md-6">
                <label for="worker_search" class="form-label">Cari pengguna</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="search" name="search" id="worker_search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nama, username, email, atau telepon">
                </div>
            </div>
            @if ($showRoleFilter)
                <div class="col-xl-2 col-md-3 col-6">
                    <label for="worker_role" class="form-label">Unit kerja</label>
                    <select name="role" id="worker_role" class="form-select">
                        <option value="">Semua unit</option>
                        <option value="1" @selected(($filters['role'] ?? '') === '1')>Finance</option>
                        <option value="2" @selected(($filters['role'] ?? '') === '2')>Officer</option>
                        <option value="3" @selected(($filters['role'] ?? '') === '3')>Academic</option>
                        <option value="4" @selected(($filters['role'] ?? '') === '4')>Admin</option>
                        <option value="5" @selected(($filters['role'] ?? '') === '5')>Support</option>
                    </select>
                </div>
            @endif
            <div class="col-xl-2 col-md-3 col-6">
                <label for="worker_status" class="form-label">Status</label>
                <select name="status" id="worker_status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                    <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                </select>
            </div>
            <div class="col-xl-2 col-md-3 col-6">
                <label for="worker_gender" class="form-label">Jenis kelamin</label>
                <select name="gender" id="worker_gender" class="form-select">
                    <option value="">Semua gender</option>
                    <option value="L" @selected(($filters['gender'] ?? '') === 'L')>Laki-laki</option>
                    <option value="P" @selected(($filters['gender'] ?? '') === 'P')>Perempuan</option>
                </select>
            </div>
            <div class="{{ $showRoleFilter ? 'col-xl-2' : 'col-xl-3' }} col-12">
                <div class="worker-filter__buttons">
                    @if ($hasWorkerFilters)
                        <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary" title="Reset filter"><i class="fas fa-rotate-left"></i></a>
                    @endif
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Terapkan</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card worker-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><h5 class="worker-card__title">{{ $pageTitle }}</h5><p class="worker-card__description">{{ $pageDescription }}</p></div>
            <div class="worker-card__actions d-flex flex-wrap gap-2">
                @if ($showImportExport)
                    <a href="{{ route('web-admin.services.convert.export-users') }}" class="btn btn-outline-success"><i class="fas fa-file-export me-1"></i> Export</a>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importUsers"><i class="fas fa-file-import me-1"></i> Import</button>
                @endif
                <a href="{{ route($createRoute) }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Tambah Pengguna</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover worker-table mb-0" id="table1" data-dashboard-searchable="false">
                    <thead><tr><th>Pengguna</th><th>Unit Kerja</th><th>Kontak</th><th>Gender</th><th>Terdaftar</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($admin as $item)
                            <tr>
                                <td data-label="Pengguna">
                                    <div class="worker-profile">
                                        <span class="worker-profile__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($item->name), 0, 1)) ?: '?' }}</span>
                                        <div><span class="worker-profile__name">{{ $item->name }}</span><span class="worker-profile__username">&#64;{{ $item->user ?: 'username belum tersedia' }}</span></div>
                                    </div>
                                </td>
                                <td data-label="Unit Kerja"><span class="badge bg-light-primary text-primary">{{ $item->type }}</span></td>
                                <td data-label="Kontak"><div class="worker-contact"><span title="{{ $item->email }}"><i class="fas fa-envelope me-1"></i>{{ $item->email ?: '-' }}</span><span title="{{ $item->phone }}"><i class="fas fa-phone me-1"></i>{{ $item->phone ?: '-' }}</span></div></td>
                                <td data-label="Gender">{{ match ($item->gend) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => '-' } }}</td>
                                <td data-label="Terdaftar"><span class="text-nowrap">{{ $item->created_at?->translatedFormat('d M Y') ?? '-' }}</span></td>
                                <td data-label="Status">
                                    @if ((int) $item->status === 1)<span class="worker-status worker-status--active">Aktif</span>@else<span class="worker-status worker-status--inactive">Nonaktif</span>@endif
                                </td>
                                <td data-label="Aksi" class="text-end">
                                    <div class="dropdown worker-action-dropdown d-inline-block">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" id="worker-action-{{ $item->code }}" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-cog me-1"></i> Aksi</button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="worker-action-{{ $item->code }}">
                                            <li><button type="button" class="dropdown-item text-info" data-bs-toggle="modal" data-bs-target="#viewContact{{ $item->code }}"><i class="fas fa-address-card"></i><span>Lihat Kontak</span></button></li>
                                            <li><a href="{{ route($editRoute, $item->code) }}" class="dropdown-item text-primary"><i class="fas fa-pen"></i><span>Edit Data</span></a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><form id="delete-form-{{ $item->code }}" action="{{ route($destroyRoute, $item->code) }}" method="POST">@csrf @method('DELETE')<button type="button" class="dropdown-item text-danger" onclick="deleteData('{{ $item->code }}')"><i class="fas fa-trash"></i><span>Hapus Data</span></button></form></li>
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

@if ($showImportExport)
<form action="{{ route('web-admin.services.convert.import-users') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade" id="importUsers" tabindex="-1" aria-labelledby="importUsersLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><div><h4 class="modal-title" id="importUsersLabel">Import Pengguna</h4><small class="text-muted">Unggah data pengguna dalam format spreadsheet.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><label for="import_users" class="form-label fw-semibold">Pilih file</label><input type="file" name="import" id="import_users" class="form-control" accept=".xls,.xlsx,.csv" required>@error('import')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="fas fa-file-import me-1"></i> Import Data</button></div>
        </div></div>
    </div>
</form>
@endif

@foreach ($admin as $item)
<div class="modal fade" id="viewContact{{ $item->code }}" tabindex="-1" aria-labelledby="contactLabel{{ $item->code }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div><h4 class="modal-title" id="contactLabel{{ $item->code }}">Informasi Kontak</h4><small class="text-muted">{{ $item->name }} · {{ $item->type }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body"><div class="d-grid gap-3">
            <div class="worker-contact-box"><span class="worker-contact-box__icon"><i class="fas fa-phone"></i></span><div class="worker-contact-box__content"><small>Nomor telepon</small><strong>{{ $item->phone ?: 'Belum tersedia' }}</strong></div>@if ($item->phone)<a href="https://wa.me/{{ $item->phone }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm"><i class="fab fa-whatsapp me-1"></i> Hubungi</a>@endif</div>
            <div class="worker-contact-box"><span class="worker-contact-box__icon"><i class="fas fa-envelope"></i></span><div class="worker-contact-box__content"><small>Alamat email</small><strong>{{ $item->email ?: 'Belum tersedia' }}</strong></div>@if ($item->email)<a href="mailto:{{ $item->email }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-paper-plane me-1"></i> Email</a>@endif</div>
        </div></div>
    </div></div>
</div>
@endforeach
