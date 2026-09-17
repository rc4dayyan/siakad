@extends('base.base-dash-index')

@section('title', 'Materi Ajar - SIAKAD')
@section('menu', 'Akademik')
@section('submenu', 'Materi Ajar')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Akses bahan pembelajaran dari mata kuliah dalam KRS Anda')

@section('custom-css')
    @include('base.components.student-records-styles')
    <style>
        .course-materials { display: grid; gap: 14px; }
        .course-material { overflow: hidden; border: 1px solid var(--dash-line); border-radius: 14px; background: #fff; }
        .course-material .accordion-button { gap: 14px; padding: 17px 18px; color: var(--dash-navy); background: #fff; box-shadow: none; }
        .course-material .accordion-button:not(.collapsed) { color: var(--dash-navy); background: linear-gradient(135deg, var(--dash-green-soft), #fff); }
        .course-material .accordion-button:focus { border-color: transparent; box-shadow: inset 0 0 0 2px rgba(17, 122, 101, .16); }
        .course-material__icon { display: grid; width: 43px; height: 43px; flex: 0 0 43px; place-items: center; border-radius: 11px; color: var(--dash-green); background: var(--dash-green-soft); font-size: 18px; }
        .course-material .accordion-button:not(.collapsed) .course-material__icon { color: #fff; background: var(--dash-green); }
        .course-material__heading { min-width: 0; flex: 1; }
        .course-material__heading strong { display: block; overflow: hidden; font-size: 14px; text-overflow: ellipsis; white-space: nowrap; }
        .course-material__meta { display: flex; flex-wrap: wrap; gap: 5px 14px; margin-top: 5px; color: var(--dash-muted); font-size: 10px; }
        .course-material__meta span { display: inline-flex; align-items: center; gap: 5px; }
        .course-material__count { margin-right: 10px; padding: 5px 9px; border-radius: 999px; color: var(--dash-green-dark); background: var(--dash-green-soft); font-size: 10px; font-weight: 800; white-space: nowrap; }
        .course-material .accordion-body { padding: 6px 18px 18px; background: #fbfcfc; }
        .material-entry { display: grid; grid-template-columns: 42px minmax(0, 1fr) auto; align-items: start; gap: 13px; padding: 16px 0; border-bottom: 1px solid var(--dash-line); }
        .material-entry:last-child { padding-bottom: 0; border-bottom: 0; }
        .material-entry__icon { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 10px; color: #3867a8; background: #edf4ff; font-size: 17px; }
        .material-entry__title { margin: 0 0 4px; color: var(--dash-navy); font-size: 13px; font-weight: 800; }
        .material-entry__meta { color: var(--dash-muted); font-size: 10px; }
        .material-entry__description { margin: 9px 0 0; color: #52615d; font-size: 11px; line-height: 1.65; white-space: pre-line; }
        .material-entry__file { display: inline-flex; max-width: 100%; align-items: center; gap: 6px; margin-top: 8px; color: var(--dash-muted); font-size: 10px; }
        .material-entry__file span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .material-entry__action .btn { border-radius: 8px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .course-material-empty { padding: 28px 14px 12px; color: var(--dash-muted); text-align: center; font-size: 11px; }
        .course-material-empty i { display: block; margin-bottom: 8px; color: #aab8b4; font-size: 24px; }
        .student-records .record-metric strong.record-metric__date { font-size: 16px; }
        @media (max-width: 767.98px) {
            .course-material__count { display: none; }
            .material-entry { grid-template-columns: 38px minmax(0, 1fr); }
            .material-entry__icon { width: 38px; height: 38px; }
            .material-entry__action { grid-column: 1 / -1; }
            .material-entry__action .btn { width: 100%; }
        }
    </style>
@endsection

@section('content')
<section class="section student-records">
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-blue"><i class="fas fa-book-open"></i></span><div><small>Mata kuliah</small><strong>{{ number_format($summary['courses']) }}</strong><span>Dalam KRS disetujui</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon"><i class="fas fa-book"></i></span><div><small>Total materi</small><strong>{{ number_format($summary['materials']) }}</strong><span>Periode {{ $period?->name ?? '-' }}</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-gold"><i class="fas fa-paperclip"></i></span><div><small>Lampiran tersedia</small><strong>{{ number_format($summary['attachments']) }}</strong><span>Siap diunduh</span></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="record-metric"><span class="record-metric__icon is-blue"><i class="far fa-calendar"></i></span><div><small>Pembaruan terakhir</small><strong class="record-metric__date">{{ $summary['latest']?->translatedFormat('d M Y') ?? '-' }}</strong><span>{{ $summary['latest']?->diffForHumans() ?? 'Belum ada materi' }}</span></div></div></div>
    </div>

    <div class="card record-panel">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="mb-1">Pustaka Materi Kuliah</h5><small class="text-muted">Materi dikelompokkan berdasarkan mata kuliah</small></div><span class="badge bg-light-primary text-primary">{{ $offerings->count() }} mata kuliah ditemukan</span></div>
        <div class="card-body">
            @if (! $period)
                <div class="alert alert-warning"><i class="fas fa-triangle-exclamation me-2"></i>Belum ada periode akademik aktif yang dipublikasikan.</div>
            @endif

            <div class="record-filter">
                <h6 class="record-filter__title"><i class="fas fa-filter me-2"></i>Filter materi</h6>
                <p class="record-filter__description">Cari mata kuliah, judul materi, dosen, atau nama berkas dan persempit ketersediaannya.</p>
                <form method="GET" action="{{ route('mahasiswa.akademik.materi-index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-5"><label for="material-search" class="form-label">Pencarian</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="search" name="q" id="material-search" class="form-control" value="{{ $filters['q'] }}" placeholder="Mata kuliah, materi, dosen, atau berkas" maxlength="100"></div></div>
                    <div class="col-lg-3"><label for="material-availability" class="form-label">Ketersediaan materi</label><select name="availability" id="material-availability" class="form-select"><option value="">Semua mata kuliah</option><option value="tersedia" @selected($filters['availability'] === 'tersedia')>Materi tersedia</option><option value="belum_tersedia" @selected($filters['availability'] === 'belum_tersedia')>Belum ada materi</option></select></div>
                    <div class="col-lg-2"><label for="material-attachment" class="form-label">Lampiran</label><select name="attachment" id="material-attachment" class="form-select"><option value="">Semua materi</option><option value="dengan_file" @selected($filters['attachment'] === 'dengan_file')>Dengan lampiran</option><option value="tanpa_file" @selected($filters['attachment'] === 'tanpa_file')>Tanpa lampiran</option></select></div>
                    <div class="col-lg-2"><div class="d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-filter me-1"></i>Terapkan</button>@if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())<a href="{{ route('mahasiswa.akademik.materi-index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>@endif</div></div>
                </form>
            </div>

            <div class="accordion course-materials" id="courseMaterialsAccordion">
                @forelse ($offerings as $offering)
                    <article class="accordion-item course-material">
                        <h2 class="accordion-header" id="course-material-heading-{{ $offering->id }}">
                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#course-material-{{ $offering->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="course-material-{{ $offering->id }}">
                                <span class="course-material__icon"><i class="fas fa-graduation-cap"></i></span>
                                <span class="course-material__heading"><strong>{{ $offering->masterMataKuliah?->name ?? 'Mata kuliah tidak tersedia' }}</strong><span class="course-material__meta"><span><i class="fas fa-hashtag"></i>{{ $offering->masterMataKuliah?->code ?? $offering->code }}</span><span><i class="fas fa-users"></i>{{ $offering->kelas?->name ?? 'Tanpa kelas' }}</span><span><i class="fas fa-user-tie"></i>{{ $offering->dosenUtama?->dsn_name ?? 'Dosen belum ditentukan' }}</span></span></span>
                                <span class="course-material__count">{{ $offering->materiAjars->count() }} materi</span>
                            </button>
                        </h2>
                        <div id="course-material-{{ $offering->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="course-material-heading-{{ $offering->id }}" data-bs-parent="#courseMaterialsAccordion">
                            <div class="accordion-body">
                                @forelse ($offering->materiAjars as $material)
                                    @php
                                        $extension = strtolower(pathinfo((string) $material->file_name, PATHINFO_EXTENSION));
                                        $fileIcon = match ($extension) { 'pdf' => 'fa-file-pdf', 'doc', 'docx' => 'fa-file-word', 'xls', 'xlsx' => 'fa-file-excel', 'ppt', 'pptx' => 'fa-file-powerpoint', 'jpg', 'jpeg', 'png', 'gif', 'webp' => 'fa-file-image', 'zip', 'rar', '7z' => 'fa-file-zipper', default => 'fa-file-lines' };
                                    @endphp
                                    <article class="material-entry">
                                        <span class="material-entry__icon"><i class="far {{ $fileIcon }}"></i></span>
                                        <div><h6 class="material-entry__title">{{ $material->judul }}</h6><div class="material-entry__meta"><i class="fas fa-user-tie me-1"></i>{{ $material->dosen?->dsn_name ?? $offering->dosenUtama?->dsn_name ?? 'Dosen belum ditentukan' }} · <i class="far fa-clock mx-1"></i>{{ $material->created_at->translatedFormat('d M Y · H.i') }}</div>@if ($material->deskripsi)<p class="material-entry__description">{{ $material->deskripsi }}</p>@endif @if ($material->file_path)<span class="material-entry__file"><i class="fas fa-paperclip"></i><span>{{ $material->file_name }}</span><strong>{{ $material->file_size_label }}</strong></span>@else<span class="record-badge is-muted mt-2">Tanpa lampiran</span>@endif</div>
                                        <div class="material-entry__action">@if ($material->file_path)<a href="{{ route('mahasiswa.akademik.materi-download', $material) }}" class="btn btn-primary"><i class="fas fa-download me-1"></i>Unduh <span class="d-none d-sm-inline">materi</span></a>@endif</div>
                                    </article>
                                @empty
                                    <div class="course-material-empty"><i class="far fa-folder-open"></i><strong class="d-block mb-1">Belum ada materi</strong><span>Dosen belum membagikan materi untuk mata kuliah ini.</span></div>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="record-empty"><i class="fas fa-book-open"></i><strong>Materi ajar tidak ditemukan</strong><span>Coba ubah filter atau materi tersedia setelah KRS mata kuliah disetujui.</span></div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
