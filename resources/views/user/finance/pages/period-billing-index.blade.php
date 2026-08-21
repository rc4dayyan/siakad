@extends('base.base-dash-index')

@section('menu', 'Keuangan Periode')
@section('submenu', 'Tagihan dan Laporan')
@section('urlmenu', '#')
@section('subdesc', 'Penerbitan tagihan dan laporan keuangan untuk periode yang sedang dipilih')

@section('custom-css')
<style>
    .period-billing-page .template-card { border: 1px solid #e8ecf3; border-radius: 14px; overflow: hidden; }
    .period-billing-page .template-card .card-header { background: #fff; border-bottom: 1px solid #edf0f5; padding: 1.25rem; }
    .period-billing-page .template-icon { align-items: center; background: #eef2ff; border-radius: 10px; color: #435ebe; display: flex; flex: 0 0 42px; height: 42px; justify-content: center; width: 42px; }
    .period-billing-page .template-name { min-width: 220px; }
    .period-billing-page .target-info { min-width: 180px; }
    .period-billing-page .table thead th { background: #f7f8fb; color: #607080; font-size: .74rem; letter-spacing: .03em; padding-bottom: .85rem; padding-top: .85rem; text-transform: uppercase; white-space: nowrap; }
    .period-billing-page .table tbody td { padding-bottom: 1rem; padding-top: 1rem; }
    .period-billing-page .action-cell { white-space: nowrap; width: 1%; }
    .period-billing-page .action-cell .dropdown-menu { min-width: 13rem; }
    @media (min-width: 992px) {
        .period-billing-page .template-card,
        .period-billing-page .template-card .table-responsive { overflow: visible; }
    }
</style>
@endsection

@section('content')
<section class="period-billing-page">
    <div class="mb-3"><a class="btn btn-outline-primary" href="{{ route($prefix.'period-opening.index') }}">Dashboard Pembukaan Periode</a></div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="alert alert-info">
        Periode laporan: <strong>{{ $period->name ?? ($period->year_start.' / '.$period->year_end.' - '.$period->term_label) }}</strong>.
        Seluruh angka di halaman ini dibatasi pada periode tersebut.
    </div>

    <div class="row">
        @foreach ([
            'Total tagihan' => $report['ringkasan']['total_tagihan'],
            'Pembayaran' => $report['ringkasan']['total_pembayaran'],
            'Tunggakan' => $report['ringkasan']['total_tunggakan'],
        ] as $label => $amount)
            <div class="col-md-4"><div class="card"><div class="card-body"><small>{{ $label }}</small><h4>Rp {{ number_format($amount, 0, ',', '.') }}</h4></div></div></div>
        @endforeach
    </div>

    <div class="card template-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div><h5 class="mb-1">Template dan Penerbitan</h5><small class="text-muted">{{ number_format($templates->total()) }} template pada periode ini. Gunakan pratinjau sebelum menerbitkan.</small></div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBillingTemplateModal"><i class="fas fa-plus me-1"></i> Tambah Template</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th class="ps-4">Template</th><th>Target</th><th>Jadwal</th><th>Nominal</th><th>Status</th><th class="text-center pe-4">Aksi</th></tr></thead>
                <tbody>
                @forelse ($templates as $template)
                    @php
                        $hasBeenIssued = $template->tagihans_count > 0 || $template->penerbitan_batches_count > 0;
                        $targetLabel = ['mahasiswa' => 'Mahasiswa', 'prodi' => 'Program Studi', 'proku' => 'Program Kuliah', 'kelompok' => 'Kelompok Status'][$template->target_type] ?? 'Target';
                        $targetName = match ($template->target_type) {
                            'mahasiswa' => $template->targetMahasiswa?->mhs_name,
                            'prodi' => $template->targetProdi?->name,
                            'proku' => $template->targetProku?->name,
                            'kelompok' => $template->kelompok_target === 'semua' ? 'Semua mahasiswa' : \App\Models\RegistrasiMahasiswa::academicStatusLabel((string) $template->kelompok_target),
                            default => null,
                        };
                    @endphp
                    <tr>
                        <td class="ps-4 template-name"><div class="d-flex align-items-center gap-3"><span class="template-icon"><i class="fas fa-file-invoice"></i></span><div><span class="fw-semibold d-block">{{ $template->name }}</span><small class="text-muted">{{ \App\Models\TemplateTagihan::JENIS_LABELS[$template->jenis] ?? ucfirst($template->jenis) }}</small>@if ($template->wajib_lunas_krs)<span class="badge bg-light-warning text-warning ms-1">Wajib KRS</span>@endif</div></div></td>
                        <td class="target-info"><span class="badge bg-light-primary text-primary">{{ $targetLabel }}</span><small class="text-muted d-block mt-1">{{ $targetName ?: 'Target tidak tersedia' }}</small></td>
                        <td><span class="d-block"><i class="far fa-calendar text-muted me-1"></i>{{ $template->tanggal_terbit?->format('d M Y') ?? '—' }}</span><small class="text-muted d-block mt-1">Jatuh tempo {{ $template->jatuh_tempo?->format('d M Y') ?? '—' }}</small></td>
                        <td class="fw-semibold text-nowrap">Rp {{ number_format($template->nominal, 0, ',', '.') }}</td>
                        <td>
                            @if ($hasBeenIssued)
                                <span class="badge bg-light-success text-success"><i class="fas fa-check-circle me-1"></i>Sudah diterbitkan</span>
                                <small class="text-muted d-block mt-1">{{ number_format($template->tagihans_count) }} tagihan · {{ number_format($template->penerbitan_batches_count) }} proses</small>
                            @else
                                <span class="badge bg-light-secondary text-secondary"><i class="far fa-clock me-1"></i>Belum diterbitkan</span>
                                <small class="text-muted d-block mt-1">Masih dapat dihapus</small>
                            @endif
                        </td>
                        <td class="action-cell text-center pe-4">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="template-action-{{ $template->id }}" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-v me-1"></i> Aksi</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="template-action-{{ $template->id }}">
                                    <li><a class="dropdown-item" href="{{ route($prefix.'billing-period.preview', $template) }}"><i class="fas fa-eye text-primary me-2"></i>Pratinjau</a></li>
                                    @if (! $hasBeenIssued && $period->isWritable())
                                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editBillingTemplateModal-{{ $template->id }}"><i class="fas fa-pen text-warning me-2"></i>Edit template</button></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><form method="POST" action="{{ route($prefix.'billing-period.destroy', $template) }}" class="mb-0" onsubmit="return confirm('Hapus template ini? Template yang dihapus tidak dapat digunakan untuk penerbitan.')">@csrf @method('DELETE')<button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Hapus template</button></form></li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5"><div class="text-muted"><i class="fas fa-file-circle-plus fa-2x d-block mb-3"></i><strong>Belum ada template tagihan</strong><br><small>Buat template pertama untuk menyiapkan penerbitan pada periode ini.</small></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <small class="text-muted">Menampilkan {{ $templates->firstItem() ?? 0 }}–{{ $templates->lastItem() ?? 0 }} dari {{ number_format($templates->total()) }} template</small>
                <form method="GET" action="{{ route($prefix.'billing-period.index') }}" class="d-flex align-items-center gap-2 mb-0">
                    <label for="template-per-page" class="small text-muted text-nowrap">Per halaman</label>
                    <select name="template_per_page" id="template-per-page" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach ([10, 25, 50] as $size)<option value="{{ $size }}" @selected($templatePerPage === $size)>{{ $size }}</option>@endforeach
                    </select>
                </form>
            </div>
            @if ($templates->hasPages())<div>{{ $templates->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
        </div>
    </div>

    @foreach ($templates as $template)
        @php
            $hasBeenIssued = $template->tagihans_count > 0 || $template->penerbitan_batches_count > 0;
            $editForm = 'edit-billing-template-'.$template->id;
            $editingFailed = old('_form') === $editForm;
            $editValue = fn (string $field) => $editingFailed ? old($field) : $template->{$field};
        @endphp
        @if (! $hasBeenIssued && $period->isWritable())
            <form method="POST" action="{{ route($prefix.'billing-period.update', $template) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_form" value="{{ $editForm }}">
                <div class="modal fade" id="editBillingTemplateModal-{{ $template->id }}" tabindex="-1" aria-labelledby="editBillingTemplateModalLabel-{{ $template->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                        <div class="modal-header"><div><h5 class="modal-title" id="editBillingTemplateModalLabel-{{ $template->id }}">Edit Template Tagihan</h5><small class="text-muted">{{ $template->name }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                        <div class="modal-body">
                            @if ($editingFailed && $errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                            <div class="alert alert-light-primary"><i class="fas fa-circle-info me-2"></i>Perubahan nominal atau target akan memengaruhi calon penerima. Periksa kembali pratinjau sebelum menerbitkan.</div>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label" for="edit-name-{{ $template->id }}">Nama</label><input class="form-control" id="edit-name-{{ $template->id }}" name="name" value="{{ $editValue('name') }}" required></div>
                                <div class="col-md-2"><label class="form-label" for="edit-type-{{ $template->id }}">Jenis</label><select class="form-select" id="edit-type-{{ $template->id }}" name="jenis" required>@foreach (\App\Models\TemplateTagihan::JENIS_LABELS as $value => $label)<option value="{{ $value }}" @selected($editValue('jenis') === $value)>{{ $label }}</option>@endforeach</select></div>
                                <div class="col-md-3"><label class="form-label" for="edit-amount-{{ $template->id }}">Nominal</label><input class="form-control" id="edit-amount-{{ $template->id }}" type="number" min="1" name="nominal" value="{{ $editValue('nominal') }}" required></div>
                                <div class="col-md-3"><label class="form-label" for="edit-target-type-{{ $template->id }}">Jenis target</label><select class="form-select" id="edit-target-type-{{ $template->id }}" name="target_type" required>@foreach (['mahasiswa' => 'Mahasiswa', 'prodi' => 'Program Studi', 'proku' => 'Program Kuliah', 'kelompok' => 'Kelompok status'] as $value => $label)<option value="{{ $value }}" @selected($editValue('target_type') === $value)>{{ $label }}</option>@endforeach</select></div>
                                <div class="col-md-3"><label class="form-label" for="edit-publish-date-{{ $template->id }}">Tanggal terbit</label><input class="form-control" id="edit-publish-date-{{ $template->id }}" type="date" name="tanggal_terbit" value="{{ $editingFailed ? old('tanggal_terbit') : $template->tanggal_terbit?->format('Y-m-d') }}" required></div>
                                <div class="col-md-3"><label class="form-label" for="edit-due-date-{{ $template->id }}">Jatuh tempo</label><input class="form-control" id="edit-due-date-{{ $template->id }}" type="date" name="jatuh_tempo" value="{{ $editingFailed ? old('jatuh_tempo') : $template->jatuh_tempo?->format('Y-m-d') }}" required></div>
                                <div class="col-md-3"><label class="form-label" for="edit-student-{{ $template->id }}">Mahasiswa</label><select class="form-select" id="edit-student-{{ $template->id }}" name="target_mahasiswa_id"><option value="">-</option>@foreach ($mahasiswas as $student)<option value="{{ $student->id }}" @selected($editValue('target_mahasiswa_id') == $student->id)>{{ $student->mhs_name }}</option>@endforeach</select></div>
                                <div class="col-md-3"><label class="form-label" for="edit-study-program-{{ $template->id }}">Program Studi</label><select class="form-select" id="edit-study-program-{{ $template->id }}" name="target_prodi_id"><option value="">-</option>@foreach ($prodis as $prodi)<option value="{{ $prodi->id }}" @selected($editValue('target_prodi_id') == $prodi->id)>{{ $prodi->name }}</option>@endforeach</select></div>
                                <div class="col-md-3"><label class="form-label" for="edit-program-{{ $template->id }}">Program Kuliah</label><select class="form-select" id="edit-program-{{ $template->id }}" name="target_proku_id"><option value="">-</option>@foreach ($prokus as $proku)<option value="{{ $proku->id }}" @selected($editValue('target_proku_id') == $proku->id)>{{ $proku->name }}</option>@endforeach</select></div>
                                <div class="col-md-3"><label class="form-label" for="edit-group-{{ $template->id }}">Kelompok status</label><select class="form-select" id="edit-group-{{ $template->id }}" name="kelompok_target"><option value="">-</option><option value="semua" @selected($editValue('kelompok_target') === 'semua')>Semua</option>@foreach ($academicStatuses as $value => $label)<option value="{{ $value }}" @selected($editValue('kelompok_target') === $value)>{{ $label }}</option>@endforeach</select></div>
                                <div class="col-12"><div class="form-check"><input type="hidden" name="wajib_lunas_krs" value="0"><input class="form-check-input" type="checkbox" name="wajib_lunas_krs" value="1" id="edit-required-krs-{{ $template->id }}" @checked((bool) $editValue('wajib_lunas_krs'))><label class="form-check-label" for="edit-required-krs-{{ $template->id }}">Wajib lunas sebelum KRS</label></div><small class="text-muted">Pilih target yang sesuai dengan jenis target. Pilihan target lain akan diabaikan.</small></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Perubahan</button></div>
                    </div></div>
                </div>
            </form>
        @endif
    @endforeach

    <form method="POST" action="{{ route($prefix.'billing-period.store') }}">
        @csrf
        <input type="hidden" name="_form" value="create-billing-template">
        <div class="modal fade" id="createBillingTemplateModal" tabindex="-1" aria-labelledby="createBillingTemplateModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="createBillingTemplateModalLabel">Tambah Template Tagihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body row g-3">
                @if ($errors->any() && old('_form') === 'create-billing-template')<div class="col-12"><div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
                <div class="col-md-4"><label class="form-label">Nama</label><input class="form-control" name="name" value="{{ old('name') }}" required></div><div class="col-md-2"><label class="form-label">Jenis</label><select class="form-select" name="jenis" required>@foreach (\App\Models\TemplateTagihan::JENIS_LABELS as $value => $label)<option value="{{ $value }}" @selected(old('jenis', 'ukt') === $value)>{{ $label }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Nominal</label><input class="form-control" type="number" min="1" name="nominal" value="{{ old('nominal') }}" required></div><div class="col-md-3"><label class="form-label">Jenis target</label><select class="form-select" name="target_type" required>@foreach (['mahasiswa' => 'Mahasiswa', 'prodi' => 'Program Studi', 'proku' => 'Program Kuliah', 'kelompok' => 'Kelompok status'] as $value => $label)<option value="{{ $value }}" @selected(old('target_type') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Terbit</label><input class="form-control" type="date" name="tanggal_terbit" value="{{ old('tanggal_terbit', now()->toDateString()) }}" required></div><div class="col-md-3"><label class="form-label">Jatuh tempo</label><input class="form-control" type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo') }}" required></div>
                <div class="col-md-3"><label class="form-label">Mahasiswa</label><select class="form-select" name="target_mahasiswa_id"><option value="">-</option>@foreach ($mahasiswas as $student)<option value="{{ $student->id }}" @selected(old('target_mahasiswa_id') == $student->id)>{{ $student->mhs_name }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Program Studi</label><select class="form-select" name="target_prodi_id"><option value="">-</option>@foreach ($prodis as $prodi)<option value="{{ $prodi->id }}" @selected(old('target_prodi_id') == $prodi->id)>{{ $prodi->name }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Program Kuliah</label><select class="form-select" name="target_proku_id"><option value="">-</option>@foreach ($prokus as $proku)<option value="{{ $proku->id }}" @selected(old('target_proku_id') == $proku->id)>{{ $proku->name }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Kelompok status</label><select class="form-select" name="kelompok_target"><option value="">-</option><option value="semua" @selected(old('kelompok_target') === 'semua')>Semua</option>@foreach ($academicStatuses as $value => $label)<option value="{{ $value }}" @selected(old('kelompok_target') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-12"><div class="form-check"><input type="hidden" name="wajib_lunas_krs" value="0"><input class="form-check-input" type="checkbox" name="wajib_lunas_krs" value="1" id="create-required-krs" @checked(old('wajib_lunas_krs'))><label class="form-check-label" for="create-required-krs">Wajib lunas sebelum KRS</label></div><small class="text-muted">Pilih target yang sesuai dengan jenis target. Pilihan target lain akan diabaikan.</small></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Template</button></div>
        </div></div></div>
    </form>

    <div class="row">
        @foreach (['per_prodi' => 'Program Studi', 'per_proku' => 'Program Kuliah', 'per_status_mahasiswa' => 'Status Mahasiswa'] as $key => $title)
            <div class="col-lg-4"><div class="card"><div class="card-header"><h6>Rincian {{ $title }}</h6></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>{{ $title }}</th><th>Tagihan</th><th>Tunggakan</th></tr></thead><tbody>@forelse ($report[$key] as $row)<tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['tagihan'], 0, ',', '.') }}</td><td>{{ number_format($row['tunggakan'], 0, ',', '.') }}</td></tr>@empty<tr><td colspan="3">Belum ada data.</td></tr>@endforelse</tbody></table></div></div></div>
        @endforeach
    </div>

    <div class="card"><div class="card-header"><h5>Override Administrasi KRS</h5></div><div class="card-body"><form method="POST" id="override-form" class="row g-2" action="">@csrf<div class="col-md-4"><select class="form-select" id="override-registration" required><option value="">Pilih mahasiswa</option>@foreach ($registrations as $registration)<option value="{{ $registration->id }}" data-url="{{ route($prefix.'billing-period.override', $registration) }}">{{ $registration->mahasiswa?->mhs_name }} · {{ $registration->academic_status_label }}</option>@endforeach</select></div><div class="col-md-5"><input class="form-control" name="alasan" minlength="10" placeholder="Alasan override (minimal 10 karakter)" required></div><div class="col-md-2"><input class="form-control" type="date" name="berlaku_sampai"></div><div class="col-md-1"><button class="btn btn-warning">Catat</button></div></form><small class="text-muted">Hanya Web Administrator dan Departemen Finance; alasan serta aktor disimpan untuk audit.</small></div></div>
    <script>
        document.getElementById('override-form').addEventListener('submit', function (event) {
            const id = document.getElementById('override-registration').value;
            if (!id) { event.preventDefault(); return; }
            this.action = document.getElementById('override-registration').selectedOptions[0].dataset.url;
        });
    </script>
    @if ($errors->any() && old('_form') === 'create-billing-template')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('createBillingTemplateModal')).show());</script>
    @endif
    @if ($errors->any() && str_starts_with((string) old('_form'), 'edit-billing-template-'))
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('editBillingTemplateModal-{{ (int) str_replace('edit-billing-template-', '', old('_form')) }}')).show());</script>
    @endif
</section>
@endsection
