@extends('base.base-dash-index')

@section('menu', 'Keuangan Periode')
@section('submenu', 'Pratinjau Penerbitan')
@section('urlmenu', route($prefix.'billing-period.index'))
@section('subdesc', 'Verifikasi target dan nominal sebelum tagihan diterbitkan')

@section('custom-css')
<style>
    .billing-preview .preview-banner { background: linear-gradient(135deg, #435ebe 0%, #6378d8 100%); border: 0; border-radius: 16px; color: #fff; overflow: hidden; position: relative; }
    .billing-preview .preview-banner::after { background: rgba(255, 255, 255, .08); border-radius: 50%; content: ''; height: 180px; position: absolute; right: -45px; top: -75px; width: 180px; }
    .billing-preview .preview-banner__content { position: relative; z-index: 1; }
    .billing-preview .metric-card { border: 1px solid #e8ecf3; border-radius: 13px; height: 100%; }
    .billing-preview .metric-icon { align-items: center; background: #eef2ff; border-radius: 10px; color: #435ebe; display: flex; flex: 0 0 42px; height: 42px; justify-content: center; width: 42px; }
    .billing-preview .detail-list dt { color: #7a8494; font-size: .75rem; letter-spacing: .03em; margin-bottom: .25rem; text-transform: uppercase; }
    .billing-preview .detail-list dd { font-weight: 600; margin-bottom: 1rem; }
    .billing-preview .table thead th { background: #f7f8fb; color: #607080; font-size: .74rem; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
    .billing-preview .student-info { min-width: 210px; }
</style>
@endsection

@section('content')
@php
    $targetLabels = ['mahasiswa' => 'Mahasiswa', 'prodi' => 'Program Studi', 'proku' => 'Program Kuliah', 'kelompok' => 'Kelompok Status'];
    $targetName = match ($template->target_type) {
        'mahasiswa' => $template->targetMahasiswa?->mhs_name,
        'prodi' => $template->targetProdi?->name,
        'proku' => $template->targetProku?->name,
        'kelompok' => $template->kelompok_target === 'semua' ? 'Semua mahasiswa' : \App\Models\RegistrasiMahasiswa::academicStatusLabel($template->kelompok_target),
        default => 'Tidak diketahui',
    };
    $periodName = $period->name ?? ($period->year_start.' / '.$period->year_end.' - '.$period->term_label);
@endphp

<section class="section billing-preview">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card preview-banner mb-4">
        <div class="card-body preview-banner__content d-flex flex-wrap justify-content-between align-items-center gap-3 p-4">
            <div>
                <span class="badge bg-light text-primary mb-2"><i class="fas fa-eye me-1"></i> Mode Pratinjau</span>
                <h3 class="text-white mb-1">{{ $template->name }}</h3>
                <p class="mb-0 opacity-75">Periode {{ $periodName }} · Tidak ada tagihan yang dibuat pada tahap ini.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route($prefix.'billing-period.index') }}" class="btn btn-light-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                @if ($preview['siap'] > 0 && $period->isWritable())
                    <form method="POST" action="{{ route($prefix.'billing-period.issue', $template) }}" onsubmit="return confirm('Terbitkan {{ $preview['siap'] }} tagihan dengan total Rp {{ number_format($preview['total_nominal'], 0, ',', '.') }}?')">
                        @csrf
                        <button class="btn btn-success"><i class="fas fa-paper-plane me-1"></i> Terbitkan {{ number_format($preview['siap']) }} Tagihan</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if (! $period->isWritable())
        <div class="alert alert-warning"><i class="fas fa-lock me-1"></i> Periode ini sudah ditutup atau diarsipkan. Pratinjau dapat dilihat, tetapi penerbitan tidak diizinkan.</div>
    @elseif ($preview['siap'] === 0)
        <div class="alert alert-warning"><i class="fas fa-circle-info me-1"></i> Tidak ada tagihan baru yang dapat diterbitkan. Seluruh kandidat sudah memiliki jenis tagihan yang sama pada periode ini.</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card metric-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3"><span class="metric-icon"><i class="fas fa-users"></i></span><div><small class="text-muted d-block">Total Kandidat</small><strong class="fs-5">{{ number_format($preview['calon']) }}</strong></div></div></div></div>
        <div class="col-md-3"><div class="card metric-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3"><span class="metric-icon text-success"><i class="fas fa-circle-check"></i></span><div><small class="text-muted d-block">Siap Diterbitkan</small><strong class="fs-5 text-success">{{ number_format($preview['siap']) }}</strong></div></div></div></div>
        <div class="col-md-3"><div class="card metric-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3"><span class="metric-icon text-warning"><i class="fas fa-forward"></i></span><div><small class="text-muted d-block">Akan Dilewati</small><strong class="fs-5 text-warning">{{ number_format($preview['dilewati']) }}</strong></div></div></div></div>
        <div class="col-md-3"><div class="card metric-card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3"><span class="metric-icon"><i class="fas fa-wallet"></i></span><div><small class="text-muted d-block">Total Penerbitan</small><strong class="fs-6">Rp {{ number_format($preview['total_nominal'], 0, ',', '.') }}</strong></div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100 mb-0">
                <div class="card-header"><h5 class="mb-1">Rincian Template</h5><small class="text-muted">Konfigurasi yang akan digunakan saat penerbitan.</small></div>
                <div class="card-body"><dl class="row detail-list mb-0">
                    <div class="col-md-6"><dt>Jenis Tagihan</dt><dd>{{ \App\Models\TemplateTagihan::JENIS_LABELS[$template->jenis] ?? ucfirst($template->jenis) }}</dd></div>
                    <div class="col-md-6"><dt>Nominal per Mahasiswa</dt><dd>Rp {{ number_format($template->nominal, 0, ',', '.') }}</dd></div>
                    <div class="col-md-6"><dt>Tanggal Terbit</dt><dd>{{ $template->tanggal_terbit?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="col-md-6"><dt>Jatuh Tempo</dt><dd>{{ $template->jatuh_tempo?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="col-md-6"><dt>Ketentuan KRS</dt><dd>{{ $template->wajib_lunas_krs ? 'Wajib lunas sebelum KRS' : 'Tidak wajib lunas sebelum KRS' }}</dd></div>
                    <div class="col-md-6"><dt>Periode</dt><dd>{{ $periodName }}</dd></div>
                </dl></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100 mb-0">
                <div class="card-header"><h5 class="mb-1">Target Penerbitan</h5><small class="text-muted">Sumber kandidat mahasiswa.</small></div>
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="metric-icon"><i class="fas fa-bullseye"></i></span>
                    <div><span class="badge bg-light-primary text-primary mb-2">{{ $targetLabels[$template->target_type] ?? 'Target' }}</span><h5 class="mb-1">{{ $targetName }}</h5><small class="text-muted">Kandidat diambil dari registrasi akademik periode terpilih.</small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div><h5 class="mb-1">Daftar Kandidat Mahasiswa</h5><small class="text-muted">Mahasiswa dengan tagihan sejenis akan dilewati otomatis saat penerbitan.</small></div>
            <small class="text-muted">Menampilkan {{ $registrations->firstItem() ?? 0 }}–{{ $registrations->lastItem() ?? 0 }} dari {{ number_format($registrations->total()) }}</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th class="ps-4">#</th><th>Mahasiswa</th><th>Kelas</th><th>Program</th><th>Status Akademik</th><th>Hasil</th></tr></thead>
                <tbody>
                @forelse ($registrations as $registration)
                    @php($willSkip = in_array($registration->mahasiswa_id, $preview['mahasiswa_ids_tertagih'], true))
                    <tr>
                        <td class="ps-4">{{ $registrations->firstItem() + $loop->index }}</td>
                        <td class="student-info"><span class="fw-semibold">{{ $registration->mahasiswa?->mhs_name ?? 'Mahasiswa tidak tersedia' }}</span><small class="text-muted d-block">NIM {{ $registration->mahasiswa?->mhs_nim ?? '—' }}</small></td>
                        <td>{{ $registration->kelas?->name ?? '—' }}<small class="text-muted d-block">{{ $registration->kelas?->code ?? '' }}</small></td>
                        <td>{{ $registration->kelas?->pstudi?->name ?? '—' }}<small class="text-muted d-block">{{ $registration->kelas?->proku?->name ?? '—' }}</small></td>
                        <td><span class="badge bg-light-secondary text-secondary">{{ $registration->academic_status_label }}</span></td>
                        <td><span class="badge {{ $willSkip ? 'bg-light-warning text-warning' : 'bg-light-success text-success' }}"><i class="fas {{ $willSkip ? 'fa-forward' : 'fa-check' }} me-1"></i>{{ $willSkip ? 'Akan dilewati' : 'Siap diterbitkan' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-users-slash fa-2x d-block mb-2"></i>Tidak ada kandidat mahasiswa.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        @if ($registrations->hasPages())<div class="card-footer d-flex justify-content-end">{{ $registrations->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
    </div>
</section>
@endsection
