@extends('base.base-dash-index')

@section('menu', 'KRS Mahasiswa')
@section('submenu', $canManageKrs ? 'Kelola KRS Mahasiswa' : 'Persetujuan KRS Mahasiswa')
@section('urlmenu', '#')
@section('subdesc', $canManageKrs ? 'Perubahan administratif KRS dengan alasan, audit, dan notifikasi' : 'Persetujuan KRS yang telah diajukan mahasiswa')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="alert alert-info">Periode: <strong>{{ $period->name }}</strong>. @if ($canManageKrs) Perubahan administratif dapat dilakukan di luar jadwal KRS. Penambahan tetap memeriksa status registrasi, kewajiban keuangan, program studi, prasyarat, kapasitas, mata kuliah yang sudah lulus, dan batas SKS. @else Administrator hanya dapat menyetujui KRS berstatus diajukan. Periksa mata kuliah dan total SKS sebelum memberikan persetujuan. @endif</div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="mb-1">Update KRS melalui Excel</h5><small>Kolom wajib: NIM, Nama, Aksi. Maksimal 100 baris beraksi dan 2 MB.</small></div>
            <a href="{{ route($prefix.'krs-management.import-template') }}" class="btn btn-outline-success"><i class="fa-solid fa-file-excel"></i> Unduh template</a>
        </div>
        <div class="card-body">
            <div class="alert alert-secondary mb-3">Nama harus cocok dengan NIM pada periode <strong>{{ $period->name }}</strong>. Baris dengan aksi kosong diabaikan.</div>
            <div class="table-responsive mb-3"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Nilai kolom Aksi</th><th>Keterangan</th><th>Syarat status</th></tr></thead><tbody>
                @if ($canManageKrs && $canApproveKrs)<tr><td><code>ajukan_setujui</code></td><td>Menambahkan seluruh penawaran kelas yang belum ada, mengajukan, lalu menyetujui KRS dalam satu proses. Catatan minimal 10 karakter wajib diisi.</td><td>Belum dibuat, <code>draft</code>, atau <code>rejected</code></td></tr>@endif
                @if ($canApproveKrs)<tr><td><code>setujui</code></td><td>Menyetujui dan mengunci KRS. Mahasiswa serta dosen wali menerima notifikasi.</td><td><code>submitted</code> / Diajukan</td></tr>@endif
                @if ($canManageKrs)<tr><td><code>buka_kembali</code></td><td>Membuka KRS agar dapat diperbaiki dan diajukan kembali. Alasan minimal 10 karakter wajib diisi.</td><td><code>submitted</code>, <code>approved</code>, atau <code>locked</code></td></tr>@endif
                <tr><td><em>kosong</em></td><td>Baris mahasiswa tidak diproses.</td><td>Semua status</td></tr>
            </tbody></table></div>
            <form method="POST" action="{{ route($prefix.'krs-management.import-preview') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-9"><label class="form-label" for="krs-import-file">File XLSX atau CSV</label><input class="form-control" id="krs-import-file" type="file" name="import" accept=".xlsx,.csv" required></div>
                <div class="col-md-3"><button class="btn btn-primary w-100">Validasi dan pratinjau</button></div>
            </form>

            @if ($importPreview)
                <hr>
                <h6>Pratinjau update ({{ count($importPreview['prepared']) }} KRS)</h6>
                <div class="table-responsive mb-3"><table class="table table-sm table-striped"><thead><tr><th>Baris</th><th>NIM</th><th>Nama</th><th>Status sekarang</th><th>Aksi</th></tr></thead><tbody>
                    @foreach ($importPreview['prepared'] as $row)
                        <tr><td>{{ $row['row'] }}</td><td>{{ $row['nim'] }}</td><td>{{ $row['nama'] }}</td><td>{{ strtoupper($row['status']) }}</td><td>{{ $row['aksi_label'] }}</td></tr>
                    @endforeach
                </tbody></table></div>
                <form method="POST" action="{{ route($prefix.'krs-management.import-execute') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="token" value="{{ $importPreview['token'] }}">
                    <div class="col-md-9"><label class="form-label" for="krs-import-note">Catatan/alasan</label><input class="form-control" id="krs-import-note" name="catatan" maxlength="2000" placeholder="Wajib minimal 10 karakter untuk buka_kembali atau ajukan_setujui"></div>
                    <div class="col-md-3"><button class="btn btn-success w-100" onclick="return confirm('Jalankan seluruh update KRS pada pratinjau ini?')">Jalankan update Excel</button></div>
                </form>
                <small class="text-muted">Pratinjau berlaku selama 15 menit. Seluruh file dibatalkan jika satu baris berubah atau tidak lagi memenuhi syarat saat dieksekusi.</small>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            @if (! $selected)
                <div class="card"><div class="card-body text-center text-muted py-5">Pilih mahasiswa untuk mengelola KRS.</div></div>
            @else
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-start"><div><h5 class="mb-1">{{ $selected->mahasiswa->mhs_name }}</h5><small>{{ $selected->mahasiswa->mhs_nim }} · {{ $selected->kelas?->name }} · Dosen wali: {{ $selected->dosenWali?->dsn_name ?? '-' }}</small></div><span class="badge bg-{{ $krs->isEditable() ? 'warning' : 'success' }}">{{ strtoupper($krs->status) }}</span></div>
                    <div class="card-body">
                        @if ($canApproveKrs && $krs->status === \App\Models\Krs::STATUS_SUBMITTED)
                            <div class="alert alert-primary">KRS menunggu keputusan. Persetujuan administrator akan mengunci KRS dan dicatat dalam audit.</div>
                            <form method="POST" action="{{ route($prefix.'krs-management.approve', $krs) }}" class="row g-2 mb-3">@csrf @method('PATCH')<div class="col-md-9"><textarea class="form-control" name="catatan" maxlength="2000" rows="2" placeholder="Catatan persetujuan (opsional)">{{ old('catatan') }}</textarea></div><div class="col-md-3"><button class="btn btn-success w-100" onclick="return confirm('Setujui dan kunci KRS ini?')">Setujui KRS</button></div></form>
                        @endif

                        @if ($canManageKrs && $krs->isEditable())
                            <div class="alert alert-info">Administrator dapat mengajukan KRS ini atas nama mahasiswa. Alasan, identitas administrator, dan perubahan status akan dicatat dalam audit serta diberitahukan kepada mahasiswa dan dosen wali.</div>
                            <form method="POST" action="{{ route($prefix.'krs-management.submit-on-behalf', $krs) }}" class="row g-2 mb-3">
                                @csrf @method('PATCH')
                                <div class="col-md-9"><input class="form-control" name="alasan_pengajuan" value="{{ old('alasan_pengajuan') }}" minlength="10" maxlength="1000" placeholder="Alasan pengajuan atas nama mahasiswa (minimal 10 karakter)" required></div>
                                <div class="col-md-3"><button class="btn btn-info w-100" onclick="return confirm('Ajukan KRS ini atas nama mahasiswa?')" @disabled($krs->items->isEmpty())>Ajukan atas nama mahasiswa</button></div>
                                @if ($krs->items->isEmpty())<div class="col-12"><small class="text-muted">Tambahkan minimal satu mata kuliah sebelum mengajukan KRS.</small></div>@endif
                            </form>
                        @endif

                        @if ($canManageKrs && ! $krs->isEditable())
                            <div class="alert alert-warning">KRS telah diajukan atau disetujui. Buka kembali sebelum mengubah isinya; setelah diperbaiki, mahasiswa atau administrator dapat mengajukannya ulang kepada dosen wali.</div>
                            <form method="POST" action="{{ route($prefix.'krs-management.reopen', $krs) }}" class="row g-2 mb-3">@csrf @method('PATCH')<div class="col-md-9"><input class="form-control" name="alasan" minlength="10" maxlength="1000" placeholder="Alasan membuka kembali KRS (minimal 10 karakter)" required></div><div class="col-md-3"><button class="btn btn-warning w-100" onclick="return confirm('Buka kembali KRS ini?')">Buka kembali</button></div></form>
                        @endif

                        <h6>Mata kuliah dalam KRS</h6>
                        <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Mata kuliah</th><th>SKS</th><th>Aksi</th></tr></thead><tbody>
                            @forelse ($krs->items as $item)
                                <tr><td>{{ $item->penawaranMataKuliah->masterMataKuliah->name }}</td><td>{{ $item->sks }}</td><td>@if ($canManageKrs && $krs->isEditable())<form method="POST" action="{{ route($prefix.'krs-management.remove', $item) }}" class="d-flex gap-1">@csrf @method('DELETE')<input class="form-control form-control-sm" name="alasan" minlength="10" maxlength="1000" placeholder="Alasan penghapusan" required><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus mata kuliah dari KRS?')">Hapus</button></form>@else<span class="text-muted">{{ $krs->isEditable() ? 'Hanya lihat' : 'Dikunci' }}</span>@endif</td></tr>
                            @empty<tr><td colspan="3" class="text-center text-muted">KRS belum memiliki mata kuliah.</td></tr>@endforelse
                        </tbody><tfoot><tr><th>Total</th><th>{{ $krs->total_sks }} SKS</th><th>Batas {{ $selected->batas_sks }} SKS</th></tr></tfoot></table></div>

                        @if ($canManageKrs && $krs->isEditable())
                            @php
                                $selectedOfferingIds = collect(old('penawaran_ids', []))->map(fn ($id) => (int) $id)->all();
                                $existingOfferingIds = $krs->items->pluck('penawaran_mata_kuliah_id');
                                $selectableOfferingCount = $offerings->whereNotIn('id', $existingOfferingIds)->count();
                            @endphp
                            <hr><h6>Tambahkan mata kuliah</h6>
                            <form method="POST" action="{{ route($prefix.'krs-management.add-many', $selected) }}" id="krs-add-many-form">@csrf
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead><tr><th style="width: 42px"><input type="checkbox" class="form-check-input" id="select-all-krs-offerings" aria-label="Pilih semua penawaran mata kuliah" @disabled($selectableOfferingCount === 0)></th><th>Mata kuliah</th><th>Dosen</th><th>SKS</th><th>Kapasitas</th><th>Status</th></tr></thead>
                                        <tbody>
                                            @forelse ($offerings as $offering)
                                                @php $alreadyAdded = $existingOfferingIds->contains($offering->id); @endphp
                                                <tr>
                                                    <td><input type="checkbox" class="form-check-input krs-offering-item" name="penawaran_ids[]" value="{{ $offering->id }}" aria-label="Pilih {{ $offering->masterMataKuliah->name }}" @checked(in_array($offering->id, $selectedOfferingIds, true) && ! $alreadyAdded) @disabled($alreadyAdded)></td>
                                                    <td>{{ $offering->masterMataKuliah->name }}@if($offering->prasyaratMaster)<br><small class="text-muted">Prasyarat: {{ $offering->prasyaratMaster->name }}</small>@endif</td>
                                                    <td>{{ $offering->dosenUtama?->dsn_name ?? '-' }}</td>
                                                    <td>{{ $offering->sks }}</td>
                                                    <td>{{ $offering->krsItems()->count() }} / {{ $offering->kapasitas }}</td>
                                                    <td>@if($alreadyAdded)<span class="badge bg-success">Sudah ditambahkan</span>@else<span class="badge bg-secondary">Tersedia</span>@endif</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="text-center text-muted">Belum ada penawaran untuk kelas mahasiswa ini.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-9"><input class="form-control" name="alasan_penambahan" value="{{ old('alasan_penambahan') }}" minlength="10" maxlength="1000" placeholder="Alasan penambahan mata kuliah (minimal 10 karakter)" required></div>
                                    <div class="col-md-3"><button class="btn btn-primary w-100" id="krs-add-many-submit" disabled onclick="return confirm('Tambahkan seluruh mata kuliah yang dipilih?')">Tambahkan (<span id="krs-offering-selected-count">0</span>)</button></div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="mb-1">Pilih Mahasiswa</h5>
                        <small class="text-muted">Temukan mahasiswa berdasarkan identitas, kelas, atau status KRS.</small>
                    </div>
                    <a href="{{ route($prefix.'krs-management.import-template', array_filter(['q' => $search, 'status' => $status, 'kelas_id' => $classId], fn ($value) => filled($value))) }}" class="btn btn-sm btn-outline-success">
                        <i class="fa-solid fa-file-excel me-1"></i> Unduh Daftar Excel
                    </a>
                </div>
                <div class="card-body">
                    <div class="border rounded-3 bg-light p-3 mb-4 mt-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="mb-1"><i class="fa-solid fa-filter me-2 text-primary"></i>Filter Daftar Mahasiswa</h6>
                                <small class="text-muted">Gunakan satu atau beberapa filter untuk mempersempit hasil.</small>
                            </div>
                            @if ($search !== '' || $status !== '' || $classId)
                                <a href="{{ route($prefix.'krs-management.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Filter
                                </a>
                            @endif
                        </div>

                        <form method="GET" action="{{ route($prefix.'krs-management.index') }}" class="row g-3 align-items-end">
                            <div class="col-lg-4 col-md-6">
                                <label for="student-search" class="form-label fw-semibold">Nama atau NIM</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                    <input type="search" class="form-control" id="student-search" name="q" value="{{ $search }}" placeholder="Contoh: Ahmad atau 20260001">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="student-class-filter" class="form-label fw-semibold">Kelas</label>
                                <select class="form-select" id="student-class-filter" name="kelas_id">
                                    <option value="">Semua kelas</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="student-status-filter" class="form-label fw-semibold">Status KRS</label>
                                <select class="form-select" id="student-status-filter" name="status">
                                    <option value="">Semua status</option>
                                    <option value="submitted" @selected($status === 'submitted')>Diajukan</option>
                                    <option value="approved" @selected($status === 'approved')>Disetujui</option>
                                    <option value="rejected" @selected($status === 'rejected')>Perlu perbaikan</option>
                                    <option value="draft" @selected($status === 'draft')>Draf</option>
                                    <option value="locked" @selected($status === 'locked')>Dikunci</option>
                                    <option value="none" @selected($status === 'none')>Belum ada KRS</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa-solid fa-filter me-1"></i> Terapkan
                                </button>
                            </div>
                        </form>

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
                            <span class="text-muted small">Hasil:</span>
                            <span class="badge bg-primary">{{ $registrations->total() }} mahasiswa</span>
                            @if ($search !== '')
                                <span class="badge bg-light-secondary text-secondary">Pencarian: “{{ $search }}”</span>
                            @endif
                            @if ($classId)
                                <span class="badge bg-light-secondary text-secondary">Kelas: {{ $classes->firstWhere('id', $classId)?->name }}</span>
                            @endif
                            @if ($status !== '')
                                @php
                                    $filterStatusLabels = [
                                        'submitted' => 'Diajukan',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Perlu perbaikan',
                                        'draft' => 'Draf',
                                        'locked' => 'Dikunci',
                                        'none' => 'Belum ada KRS',
                                    ];
                                @endphp
                                <span class="badge bg-light-secondary text-secondary">Status: {{ $filterStatusLabels[$status] }}</span>
                            @endif
                        </div>
                    </div>

                    @php
                        $selectedKrsIds = collect(old('krs_ids', []))
                            ->map(fn ($id) => (int) $id)
                            ->all();
                    @endphp
                    <form method="POST" action="{{ route($prefix.'krs-management.bulk') }}" id="krs-bulk-form">
                        @csrf @method('PATCH')
                        <div class="table-responsive"><table class="table table-sm table-striped table-hover align-middle"><thead class="table-light"><tr><th><input type="checkbox" id="select-all-krs" aria-label="Pilih semua KRS yang sesuai dengan aksi pada halaman ini"></th><th>Mahasiswa</th><th>Kelas</th><th>Status KRS</th><th class="text-end">Aksi</th></tr></thead><tbody>
                            @forelse ($registrations as $registration)
                                @php
                                    $rowKrs = $registration->krs;
                                    $canSelectForApproval = $canApproveKrs && $rowKrs?->status === \App\Models\Krs::STATUS_SUBMITTED;
                                    $canSelectForReopen = $canManageKrs && $rowKrs && ! $rowKrs->isEditable();
                                @endphp
                                <tr><td><input class="krs-bulk-item" type="checkbox" name="krs_ids[]" value="{{ $rowKrs?->id ?? '' }}" data-can-approve="{{ $canSelectForApproval ? '1' : '0' }}" data-can-reopen="{{ $canSelectForReopen ? '1' : '0' }}" @checked($rowKrs && in_array((int) $rowKrs->id, $selectedKrsIds, true)) @disabled(! $canSelectForApproval && ! $canSelectForReopen) title="{{ $rowKrs ? ((! $canSelectForApproval && ! $canSelectForReopen) ? 'Status KRS ini belum mendukung aksi massal.' : 'Pilih KRS untuk aksi massal.') : 'Mahasiswa belum memiliki KRS.' }}" aria-label="Pilih KRS {{ $registration->mahasiswa?->mhs_name }}"></td><td><span class="fw-semibold text-dark">{{ $registration->mahasiswa?->mhs_name }}</span><br><small class="text-muted">{{ $registration->mahasiswa?->mhs_nim }}</small></td><td>{{ $registration->kelas?->name ?? '-' }}</td><td><span class="badge bg-secondary">{{ strtoupper($rowKrs?->status ?? 'belum dibuat') }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route($prefix.'krs-management.index', ['registration' => $registration->id, 'q' => $search, 'status' => $status, ...($classId ? ['kelas_id' => $classId] : []), 'students_page' => $registrations->currentPage()]) }}"><i class="fa-solid fa-eye me-1"></i>{{ $canManageKrs ? 'Kelola' : 'Tinjau' }}</a></td></tr>
                            @empty<tr><td colspan="5" class="text-center text-muted">Registrasi mahasiswa tidak ditemukan.</td></tr>@endforelse
                        </tbody></table></div>

                        <div class="border rounded p-3 mb-3">
                            <h6>Aksi massal</h6>
                            <div class="row g-2">
                                <div class="col-md-4"><select class="form-select" name="action" id="krs-bulk-action" required><option value="">Pilih aksi</option>@if ($canApproveKrs)<option value="approve" @selected(old('action') === 'approve')>Setujui dan kunci</option>@endif @if ($canManageKrs)<option value="reopen" @selected(old('action') === 'reopen')>Buka kembali</option>@endif</select></div>
                                <div class="col-md-5"><input class="form-control" name="catatan" value="{{ old('catatan') }}" maxlength="2000" placeholder="Catatan; wajib min. 10 karakter untuk buka kembali"></div>
                                <div class="col-md-3"><button class="btn btn-primary w-100" id="krs-bulk-submit" disabled onclick="return confirm('Jalankan aksi pada seluruh KRS yang dipilih?')">Jalankan (<span id="krs-selected-count">0</span>)</button></div>
                            </div>
                            <small class="text-muted">Checklist KRS yang memenuhi syarat dapat dipilih sebelum atau sesudah menentukan aksi. Checklist abu-abu berarti mahasiswa belum memiliki KRS atau statusnya belum mendukung aksi massal.</small>
                        </div>
                    </form>
                    @if ($registrations->hasPages())
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <small class="text-muted">Menampilkan {{ $registrations->firstItem() }}–{{ $registrations->lastItem() }} dari {{ $registrations->total() }} mahasiswa</small>
                            {{ $registrations->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const offeringSelectAll = document.getElementById('select-all-krs-offerings');
            const offeringSubmit = document.getElementById('krs-add-many-submit');
            const offeringSelectedCount = document.getElementById('krs-offering-selected-count');
            const offeringItems = Array.from(document.querySelectorAll('.krs-offering-item:not(:disabled)'));

            const syncOfferingSelection = () => {
                const checked = offeringItems.filter((checkbox) => checkbox.checked);
                if (offeringSelectAll) {
                    offeringSelectAll.checked = offeringItems.length > 0 && checked.length === offeringItems.length;
                    offeringSelectAll.indeterminate = checked.length > 0 && checked.length < offeringItems.length;
                }
                if (offeringSelectedCount) offeringSelectedCount.textContent = checked.length;
                if (offeringSubmit) offeringSubmit.disabled = checked.length === 0;
            };

            offeringSelectAll?.addEventListener('change', () => {
                offeringItems.forEach((checkbox) => { checkbox.checked = offeringSelectAll.checked; });
                syncOfferingSelection();
            });
            offeringItems.forEach((checkbox) => checkbox.addEventListener('change', syncOfferingSelection));
            syncOfferingSelection();

            const form = document.getElementById('krs-bulk-form');
            const selectAll = document.getElementById('select-all-krs');
            const action = document.getElementById('krs-bulk-action');
            const submit = document.getElementById('krs-bulk-submit');
            const selectedCount = document.getElementById('krs-selected-count');
            const items = Array.from(document.querySelectorAll('.krs-bulk-item'));

            if (!form || !selectAll || !action || !submit || !selectedCount) return;

            const isCompatible = (checkbox) => {
                if (action.value === 'approve') return checkbox.dataset.canApprove === '1';
                if (action.value === 'reopen') return checkbox.dataset.canReopen === '1';
                return checkbox.dataset.canApprove === '1' || checkbox.dataset.canReopen === '1';
            };

            const syncState = () => {
                const enabled = items.filter((checkbox) => !checkbox.disabled);
                const checked = enabled.filter((checkbox) => checkbox.checked);
                selectAll.disabled = enabled.length === 0;
                selectAll.checked = enabled.length > 0 && checked.length === enabled.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < enabled.length;
                selectedCount.textContent = checked.length;
                submit.disabled = action.value === '' || checked.length === 0;
            };

            const syncCompatibility = () => {
                items.forEach((checkbox) => {
                    checkbox.disabled = !isCompatible(checkbox);
                    if (checkbox.disabled) checkbox.checked = false;
                });
                syncState();
            };

            action.addEventListener('change', syncCompatibility);
            selectAll.addEventListener('change', () => {
                items.filter((checkbox) => !checkbox.disabled).forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
                syncState();
            });
            items.forEach((checkbox) => checkbox.addEventListener('change', syncState));
            syncCompatibility();
        });
    </script>
@endsection
