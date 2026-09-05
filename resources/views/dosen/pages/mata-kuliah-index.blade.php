@extends('base.base-dash-index')
@section('title', 'Mata Kuliah dan Nilai - SIAKAD')
@section('menu', 'Mata Kuliah & Nilai')
@section('submenu', 'Mata Kuliah per Kelas')
@section('urlmenu', route('dosen.akademik.matkul-index'))
@section('subdesc', 'Kelola peserta dan nilai akhir pada kelas yang Anda ampu.')

@section('custom-css')
<style>
    .lecturer-course-page .course-summary {
        padding: 18px;
        border: 1px solid #dce9e4;
        border-radius: 13px;
        background: linear-gradient(135deg, #f6fbf9 0%, #fff 100%);
    }

    .lecturer-course-page .summary-value {
        color: #263d36;
        font-size: 24px;
        font-weight: 800;
    }

    .lecturer-course-page .summary-label {
        color: #71837d;
        font-size: 12px;
        font-weight: 600;
    }

    .lecturer-course-page .course-name {
        color: #263d36;
        font-weight: 700;
    }

    .lecturer-course-page .course-meta {
        color: #71837d;
        font-size: 11px;
    }

    .lecturer-course-page .class-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #eef4ff;
        color: #3f65a8;
        font-size: 12px;
        font-weight: 700;
    }

    .lecturer-course-page .grade-progress {
        width: 120px;
        height: 6px;
        margin-top: 6px;
        overflow: hidden;
        border-radius: 999px;
        background: #e4ece9;
    }

    .lecturer-course-page .grade-progress > span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: #27856a;
    }
</style>
@endsection

@section('content')
@php
    $participantCount = $offerings->sum('peserta_count');
    $gradedCount = $offerings->sum('nilai_terisi_count');
@endphp

<section class="section lecturer-course-page">
    <div class="course-summary mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="summary-value">{{ $offerings->count() }}</div>
                <div class="summary-label">Kelas mata kuliah diampu</div>
            </div>
            <div class="col-md-4">
                <div class="summary-value">{{ $participantCount }}</div>
                <div class="summary-label">Peserta KRS disetujui</div>
            </div>
            <div class="col-md-4">
                <div class="summary-value">{{ $gradedCount }}</div>
                <div class="summary-label">Nilai telah terisi</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-1">Mata Kuliah per Kelas</h5>
            <small class="text-muted">Periode: {{ $period?->name ?? 'Belum tersedia' }}</small>
        </div>
        <div class="card-body">
            @if (! $period)
                <div class="alert alert-warning">Belum ada periode akademik aktif yang dipublikasikan.</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Mata Kuliah</th>
                            <th>Kelas</th>
                            <th>Program Studi</th>
                            <th>Peserta</th>
                            <th>Progres Nilai</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($offerings as $item)
                            @php
                                $percentage = $item->peserta_count > 0
                                    ? min(100, round(($item->nilai_terisi_count / $item->peserta_count) * 100))
                                    : 0;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="course-name">{{ $item->masterMataKuliah?->name ?? '-' }}</div>
                                    <div class="course-meta">{{ $item->masterMataKuliah?->code ?? $item->code }} · {{ $item->sks }} SKS</div>
                                </td>
                                <td><span class="class-badge"><i class="fas fa-school"></i>{{ $item->kelas?->name ?? '-' }}</span></td>
                                <td>{{ $item->pstudi?->name ?? '-' }}</td>
                                <td>{{ $item->peserta_count }} mahasiswa</td>
                                <td>
                                    <div class="small fw-semibold">{{ $item->nilai_terisi_count }}/{{ $item->peserta_count }} terisi</div>
                                    <div class="grade-progress"><span style="width: {{ $percentage }}%"></span></div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                        <a href="{{ route('dosen.akademik.matkul-materi', $item) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-book-open me-1"></i> Materi ({{ $item->materi_count }})
                                        </a>
                                        <a href="{{ route('dosen.akademik.matkul-nilai', $item) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-graduation-cap me-1"></i> Kelola Nilai
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-5 text-center text-muted">
                                    <i class="fas fa-book-open fa-2x mb-3"></i>
                                    <p class="mb-1 fw-semibold">Belum ada mata kuliah yang diampu</p>
                                    <small>Penawaran akan tampil setelah dosen ditetapkan sebagai pengampu atau pendamping.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
