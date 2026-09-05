@extends('base.base-dash-index')
@section('title', 'Materi Ajar - SIAKAD')
@section('menu', 'Materi Ajar')
@section('submenu', 'Daftar Materi')
@section('urlmenu', route('mahasiswa.akademik.materi-index'))
@section('subdesc', 'Materi pembelajaran dari mata kuliah yang tercantum dalam KRS Anda.')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-1">Materi Ajar</h5>
            <small class="text-muted">Periode: {{ $period?->name ?? 'Belum tersedia' }}</small>
        </div>
        <div class="card-body">
            @if (! $period)
                <div class="alert alert-warning">Belum ada periode akademik aktif yang dipublikasikan.</div>
            @endif

            <div class="accordion" id="courseMaterialsAccordion">
                @forelse ($offerings as $offering)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="course-material-heading-{{ $offering->id }}">
                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#course-material-{{ $offering->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="course-material-{{ $offering->id }}">
                                <span>
                                    <strong>{{ $offering->masterMataKuliah?->name ?? '-' }}</strong>
                                    <small class="d-block text-muted mt-1">
                                        {{ $offering->masterMataKuliah?->code ?? $offering->code }} ·
                                        {{ $offering->kelas?->name ?? '-' }} ·
                                        {{ $offering->materiAjars->count() }} materi
                                    </small>
                                </span>
                            </button>
                        </h2>
                        <div id="course-material-{{ $offering->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="course-material-heading-{{ $offering->id }}" data-bs-parent="#courseMaterialsAccordion">
                            <div class="accordion-body">
                                @forelse ($offering->materiAjars as $material)
                                    <article class="border rounded p-3 mb-3">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                            <div>
                                                <h6 class="mb-1">{{ $material->judul }}</h6>
                                                <small class="text-muted">
                                                    {{ $material->dosen?->dsn_name ?? $offering->dosenUtama?->dsn_name ?? '-' }} ·
                                                    {{ $material->created_at->locale('id')->translatedFormat('d M Y, H:i') }}
                                                </small>
                                            </div>
                                            @if ($material->file_path)
                                                <a href="{{ route('mahasiswa.akademik.materi-download', $material) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-download me-1"></i> Unduh {{ $material->file_size_label }}
                                                </a>
                                            @endif
                                        </div>
                                        @if ($material->deskripsi)
                                            <div class="mt-3" style="white-space: pre-line">{{ $material->deskripsi }}</div>
                                        @endif
                                        @if ($material->file_path)
                                            <div class="small text-muted mt-2"><i class="fas fa-paperclip me-1"></i>{{ $material->file_name }}</div>
                                        @endif
                                    </article>
                                @empty
                                    <div class="text-center text-muted py-3">Belum ada materi yang dibagikan untuk mata kuliah ini.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-5 text-center text-muted">
                        <i class="fas fa-book-open fa-2x mb-3"></i>
                        <p class="mb-1 fw-semibold">Belum ada mata kuliah yang dapat diakses</p>
                        <small>Materi tersedia setelah KRS mata kuliah disetujui.</small>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
