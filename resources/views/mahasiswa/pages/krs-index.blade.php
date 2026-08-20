@extends('base.base-dash-index')

@section('title', 'Kartu Rencana Studi')
@section('menu', 'Akademik')
@section('submenu', 'Kartu Rencana Studi')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Susun dan ajukan rencana studi pada periode akademik berjalan')

@section('custom-css')
    @include('base.components.professional-dashboard-styles')
    <style>
        .krs-page .krs-summary { padding: 22px; border: 1px solid var(--dash-line); border-radius: 15px; background: #fff; box-shadow: 0 8px 24px rgba(12, 44, 55, .05); }
        .krs-summary__icon { display: grid; width: 46px; height: 46px; flex: 0 0 46px; place-items: center; border-radius: 12px; color: var(--dash-green); background: var(--dash-green-soft); font-size: 19px; }
        .krs-summary__label { display: block; color: var(--dash-muted); font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .krs-summary__value { display: block; margin-top: 3px; color: var(--dash-navy); font-size: 17px; font-weight: 800; }
        .krs-progress { height: 8px; border-radius: 999px; background: #e8efed; }
        .krs-progress .progress-bar { border-radius: 999px; background: var(--dash-green); }
        .krs-table thead th { padding: 12px 14px; color: var(--dash-muted); font-size: 11px; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
        .krs-table td { padding: 15px 14px; vertical-align: middle; }
        .krs-table__primary { display: block; color: var(--dash-navy); font-weight: 700; }
        .krs-table__meta { display: block; margin-top: 3px; color: var(--dash-muted); font-size: 12px; }
        .krs-course-column { min-width: 220px; }
        .krs-offering-toolbar { position: sticky; z-index: 4; top: 78px; padding: 14px 18px; border-bottom: 1px solid var(--dash-line); background: rgba(255, 255, 255, .96); backdrop-filter: blur(8px); }
        .krs-empty { padding: 38px 16px !important; color: var(--dash-muted); text-align: center; }
        .krs-empty i { display: block; margin-bottom: 10px; color: #aab8b4; font-size: 27px; }
        .krs-submit-panel textarea { min-height: 105px; resize: vertical; }
        @media (max-width: 767.98px) {
            .krs-page-header, .krs-offering-toolbar { align-items: flex-start !important; flex-direction: column; }
            .krs-offering-toolbar { position: static; }
        }
    </style>
@endsection

@section('content')
    <section class="section professional-dashboard krs-page">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2"><i class="fas fa-circle-check"></i><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><div class="d-flex gap-2"><i class="fas fa-circle-exclamation mt-1"></i><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
        @endif

        @if (! $registration)
            <div class="card dashboard-panel">
                <div class="card-body dashboard-empty py-5"><i class="fas fa-user-clock"></i><h5>Registrasi akademik belum tersedia</h5><p class="mb-3">Hubungi bagian akademik agar registrasi Anda pada periode aktif dapat dilengkapi.</p><a href="{{ route('mahasiswa.home-index') }}" class="btn btn-outline-primary">Kembali ke Dashboard</a></div>
            </div>
        @else
            @php
                $statusConfig = [
                    'draft' => ['label' => 'Draft', 'class' => 'secondary', 'description' => 'KRS masih dapat diubah'],
                    'submitted' => ['label' => 'Menunggu Persetujuan', 'class' => 'warning', 'description' => 'Sedang ditinjau dosen wali'],
                    'approved' => ['label' => 'Disetujui', 'class' => 'success', 'description' => 'KRS telah disetujui dosen wali'],
                    'rejected' => ['label' => 'Perlu Diperbaiki', 'class' => 'danger', 'description' => 'Periksa catatan dosen wali'],
                    'locked' => ['label' => 'Dikunci', 'class' => 'primary', 'description' => 'KRS telah selesai diproses'],
                ];
                $status = $statusConfig[$krs->status] ?? ['label' => ucfirst($krs->status), 'class' => 'secondary', 'description' => 'Status KRS'];
                $sksPercentage = $registration->batas_sks > 0 ? min(100, (int) round(($krs->total_sks / $registration->batas_sks) * 100)) : 0;
                $selectedOfferingIds = collect(old('penawaran_ids', []))->map(fn ($id) => (int) $id)->all();
                $selectableOfferings = $offerings->reject(fn ($item) => $krs->items->contains('penawaran_mata_kuliah_id', $item->id));
            @endphp

            <div class="dashboard-hero mb-4">
                <div class="dashboard-hero__content">
                    <span class="dashboard-hero__eyebrow">Rencana Studi Mahasiswa</span>
                    <h2>Kartu Rencana Studi</h2>
                    <p>Pilih mata kuliah sesuai batas SKS, periksa kembali susunan KRS, lalu ajukan kepada dosen wali.</p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="btn btn-light disabled"><i class="fas fa-circle-info me-1"></i> {{ $status['label'] }}</span>
                        @if ($krs->items->isNotEmpty())
                            <a href="{{ route('mahasiswa.akademik.krs-print') }}" target="_blank" class="btn btn-outline-light"><i class="fas fa-print me-1"></i> Cetak KRS</a>
                        @endif
                    </div>
                </div>
                <div class="dashboard-hero__aside">
                    <small>Periode akademik</small>
                    <strong>{{ $period->name }}</strong>
                    <span>{{ $registration->kelas?->name ?? 'Kelas belum tersedia' }}</span>
                    <span class="mt-1">Semester {{ $registration->semester_mahasiswa }} · {{ $registration->academic_status_label }}</span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-sm-6"><div class="krs-summary h-100 d-flex align-items-center gap-3"><span class="krs-summary__icon"><i class="fas fa-file-signature"></i></span><span><small class="krs-summary__label">Status KRS</small><strong class="krs-summary__value">{{ $status['label'] }}</strong><small class="text-muted">{{ $status['description'] }}</small></span></div></div>
                <div class="col-xl-3 col-sm-6"><div class="krs-summary h-100 d-flex align-items-center gap-3"><span class="krs-summary__icon"><i class="fas fa-book-open"></i></span><span><small class="krs-summary__label">Mata Kuliah</small><strong class="krs-summary__value">{{ number_format($krs->items->count()) }} dipilih</strong><small class="text-muted">Dari {{ number_format($offerings->count()) }} penawaran</small></span></div></div>
                <div class="col-xl-3 col-sm-6"><div class="krs-summary h-100"><div class="d-flex justify-content-between align-items-end"><span><small class="krs-summary__label">Total SKS</small><strong class="krs-summary__value">{{ $krs->total_sks }} / {{ $registration->batas_sks }} SKS</strong></span><small class="text-muted">{{ $sksPercentage }}%</small></div><div class="progress krs-progress mt-3"><div class="progress-bar" role="progressbar" style="width: {{ $sksPercentage }}%" aria-valuenow="{{ $sksPercentage }}" aria-valuemin="0" aria-valuemax="100"></div></div></div></div>
                <div class="col-xl-3 col-sm-6"><div class="krs-summary h-100 d-flex align-items-center gap-3"><span class="krs-summary__icon"><i class="fas fa-user-tie"></i></span><span><small class="krs-summary__label">Dosen Wali</small><strong class="krs-summary__value">{{ $registration->dosenWali?->dsn_name ?? 'Belum ditentukan' }}</strong><small class="text-muted">Pemberi persetujuan KRS</small></span></div></div>
            </div>

            @if ($krs->status === 'rejected')
                <div class="alert alert-warning d-flex gap-3"><i class="fas fa-comment-dots mt-1"></i><div><strong>Catatan dosen wali</strong><div class="mt-1">{{ $krs->catatan_keputusan }}</div></div></div>
            @endif

            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <div class="card dashboard-panel">
                        <div class="card-header krs-page-header d-flex justify-content-between align-items-start gap-3"><div><h5 class="mb-1">Mata Kuliah Terpilih</h5><small class="text-muted">Periksa susunan KRS sebelum diajukan</small></div><span class="badge bg-{{ $status['class'] }} px-3 py-2">{{ $status['label'] }}</span></div>
                        <div class="card-body pt-2 table-responsive">
                            <table class="table table-hover krs-table mb-0">
                                <thead><tr><th>Mata Kuliah</th><th>Kelas</th><th class="text-center">SKS</th><th class="text-end">Aksi</th></tr></thead>
                                <tbody>
                                    @forelse ($krs->items as $item)
                                        <tr><td class="krs-course-column"><span class="krs-table__primary">{{ $item->penawaranMataKuliah->masterMataKuliah->name }}</span><small class="krs-table__meta">{{ $item->penawaranMataKuliah->code }}</small></td><td><span class="krs-table__primary">{{ $item->penawaranMataKuliah->kelas?->name ?? '—' }}</span></td><td class="text-center fw-bold">{{ $item->sks }}</td><td class="text-end">@if ($krs->isEditable())<form method="POST" action="{{ route('mahasiswa.akademik.krs-remove', $item->id) }}" class="d-inline" onsubmit="return confirm('Hapus mata kuliah ini dari KRS?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Hapus mata kuliah"><i class="fas fa-trash"></i></button></form>@else<span class="text-muted">—</span>@endif</td></tr>
                                    @empty
                                        <tr><td colspan="4" class="krs-empty"><i class="far fa-rectangle-list"></i>Belum ada mata kuliah dalam KRS.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card dashboard-panel krs-submit-panel">
                        <div class="card-header"><h5 class="mb-1">Pengajuan KRS</h5><small class="text-muted">Kirim susunan KRS kepada dosen wali</small></div>
                        <div class="card-body">
                            @if ($krs->isEditable())
                                <form method="POST" action="{{ route('mahasiswa.akademik.krs-submit') }}">@csrf
                                    <label for="krs_student_note" class="form-label">Catatan untuk dosen wali <span class="text-muted">(opsional)</span></label>
                                    <textarea name="catatan_mahasiswa" id="krs_student_note" class="form-control mb-3" maxlength="2000" placeholder="Tambahkan catatan bila diperlukan...">{{ old('catatan_mahasiswa', $krs->catatan_mahasiswa) }}</textarea>
                                    <button class="btn btn-primary w-100" @disabled($krs->items->isEmpty()) onclick="return confirm('Ajukan KRS kepada dosen wali?')"><i class="fas fa-paper-plane me-1"></i> Ajukan KRS</button>
                                    @if ($krs->items->isEmpty())<small class="text-muted d-block text-center mt-2">Pilih minimal satu mata kuliah terlebih dahulu.</small>@endif
                                </form>
                            @else
                                <div class="dashboard-empty py-3"><i class="fas fa-lock"></i><strong class="d-block text-dark">KRS tidak dapat diubah</strong><span>{{ $status['description'] }}.</span></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('mahasiswa.akademik.krs-add-many') }}" id="krs-add-many-form">@csrf
                <div class="card dashboard-panel">
                    <div class="krs-offering-toolbar d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div><h5 class="mb-1">Pilihan Mata Kuliah</h5><small class="text-muted">Penawaran untuk {{ $registration->kelas?->name ?? 'kelas Anda' }}</small></div>
                        @if ($krs->isEditable())<button type="submit" class="btn btn-primary" id="krs-add-selected" disabled><i class="fas fa-plus me-1"></i> Tambah Pilihan (<span id="krs-selected-count">0</span>)</button>@endif
                    </div>
                    <div class="card-body pt-2 table-responsive">
                        <table class="table table-hover krs-table mb-0">
                            <thead><tr><th style="width: 44px"><input type="checkbox" class="form-check-input" id="krs-select-all" aria-label="Pilih semua mata kuliah" @disabled(! $krs->isEditable() || $selectableOfferings->isEmpty())></th><th>Mata Kuliah &amp; Dosen</th><th>Prasyarat</th><th class="text-center">SKS</th><th>Kapasitas</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($offerings as $item)
                                    @php $alreadyAdded = $krs->items->contains('penawaran_mata_kuliah_id', $item->id); @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input krs-offering-checkbox" name="penawaran_ids[]" value="{{ $item->id }}" aria-label="Pilih {{ $item->masterMataKuliah->name }}" @checked(in_array($item->id, $selectedOfferingIds, true) && ! $alreadyAdded) @disabled(! $krs->isEditable() || $alreadyAdded)></td>
                                        <td class="krs-course-column"><span class="krs-table__primary">{{ $item->masterMataKuliah->name }}</span><small class="krs-table__meta">{{ $item->code }} · {{ $item->dosenUtama?->dsn_name ?? 'Dosen belum ditentukan' }}</small></td>
                                        <td><span class="krs-table__primary">{{ $item->prasyaratMaster?->name ?? 'Tidak ada' }}</span></td>
                                        <td class="text-center fw-bold">{{ $item->sks }}</td>
                                        <td><span class="krs-table__primary">{{ $item->krs_items_count }} / {{ $item->kapasitas }}</span><small class="krs-table__meta">peserta</small></td>
                                        <td>@if ($alreadyAdded)<span class="badge bg-light-success text-success">Sudah dipilih</span>@else<span class="badge bg-light-secondary text-secondary">Tersedia</span>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="krs-empty"><i class="far fa-folder-open"></i>Belum ada penawaran mata kuliah untuk kelas ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        @endif
    </section>
@endsection

@section('custom-js')
    @if ($registration)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const selectAll = document.getElementById('krs-select-all');
                const checkboxes = Array.from(document.querySelectorAll('.krs-offering-checkbox:not(:disabled)'));
                const submitButton = document.getElementById('krs-add-selected');
                const selectedCount = document.getElementById('krs-selected-count');
                const updateSelection = () => {
                    const count = checkboxes.filter((checkbox) => checkbox.checked).length;
                    if (selectedCount) selectedCount.textContent = count;
                    if (submitButton) submitButton.disabled = count === 0;
                    if (selectAll) {
                        selectAll.checked = checkboxes.length > 0 && count === checkboxes.length;
                        selectAll.indeterminate = count > 0 && count < checkboxes.length;
                    }
                };
                selectAll?.addEventListener('change', () => { checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; }); updateSelection(); });
                checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
                updateSelection();
            });
        </script>
    @endif
@endsection
