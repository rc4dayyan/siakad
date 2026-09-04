@extends('base.base-dash-index')

@section('title', 'Feedback Perkuliahan')
@section('menu', 'Akademik')
@section('submenu', 'Feedback Perkuliahan')
@section('urlmenu', route('mahasiswa.home-jadkul-index'))
@section('subdesc', 'Kuesioner evaluasi kinerja dosen')

@section('custom-css')
    <style>
        .evaluation-wrap { max-width: 1050px; margin: 0 auto; }
        .evaluation-hero, .evaluation-card { border: 1px solid var(--dash-line); border-radius: 15px; box-shadow: 0 8px 24px rgba(12, 44, 55, .05); }
        .evaluation-hero { border-top: 6px solid #435ebe; }
        .evaluation-context { padding: 15px; border-radius: 11px; background: #f5f7ff; }
        .evaluation-context strong { color: var(--dash-navy); }
        .evaluation-card .card-header { padding: 20px 22px 12px; background: transparent; }
        .evaluation-card .card-body { padding: 12px 22px 22px; }
        .evaluation-table th { color: var(--dash-muted); font-size: 12px; text-align: center; white-space: nowrap; }
        .evaluation-table th:first-child { min-width: 360px; text-align: left; }
        .evaluation-table td { padding: 13px 9px; vertical-align: middle; }
        .evaluation-table td:not(:first-child) { min-width: 62px; text-align: center; }
        .evaluation-table .form-check { display: inline-flex; min-height: auto; margin: 0; padding: 0; }
        .evaluation-table .form-check-input { width: 20px; height: 20px; margin: 0; cursor: pointer; }
        .evaluation-number { display: inline-flex; width: 26px; height: 26px; margin-right: 8px; align-items: center; justify-content: center; border-radius: 50%; color: #435ebe; background: #eef1ff; font-size: 12px; font-weight: 700; }
        .evaluation-required { color: #dc3545; }
        .evaluation-narrative { min-height: 125px; border-radius: 10px; }
        .evaluation-submit { position: sticky; bottom: 12px; z-index: 10; padding: 13px 16px; border: 1px solid var(--dash-line); border-radius: 12px; background: rgba(255, 255, 255, .96); box-shadow: 0 8px 26px rgba(30, 40, 70, .13); backdrop-filter: blur(8px); }
        @media (max-width: 767.98px) {
            .evaluation-card .card-header, .evaluation-card .card-body { padding-right: 15px; padding-left: 15px; }
            .evaluation-table th:first-child { min-width: 280px; }
            .evaluation-table td:not(:first-child) { min-width: 52px; }
        }
    </style>
@endsection

@section('content')
    <section class="section evaluation-wrap">
        <div class="card evaluation-hero mb-4">
            <div class="card-body p-4">
                <a href="{{ route('mahasiswa.home-jadkul-index') }}" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-1"></i> Kembali ke jadwal</a>
                <h4 class="mb-2">{{ $evaluation['title'] }}</h4>
                <p class="text-muted mb-3">{{ $evaluation['description'] }}</p>
                <div class="evaluation-context row g-3">
                    <div class="col-md-6"><small class="d-block text-muted">Mata Kuliah</small><strong>{{ $jadwal->matkul->name ?? 'Mata kuliah tidak tersedia' }}</strong></div>
                    <div class="col-md-6"><small class="d-block text-muted">Dosen</small><strong>{{ $jadwal->dosen->dsn_name ?? 'Belum ditentukan' }}</strong></div>
                    <div class="col-md-4"><small class="d-block text-muted">Pertemuan</small><strong>{{ $jadwal->pert_id ?? '—' }}</strong></div>
                    <div class="col-md-4"><small class="d-block text-muted">Kelas</small><strong>{{ $jadwal->kelas->code ?? $jadwal->kelas->name ?? '—' }}</strong></div>
                    <div class="col-md-4"><small class="d-block text-muted">Jadwal</small><strong>{{ \Carbon\Carbon::parse($jadwal->date)->translatedFormat('d M Y') }}, {{ substr($jadwal->start, 0, 5) }}</strong></div>
                </div>
                <div class="alert alert-light-primary mt-3 mb-0"><i class="fas fa-user-shield me-2"></i>Jawaban ditampilkan kepada dosen tanpa identitas mahasiswa. Semua pertanyaan wajib diisi.</div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Formulir belum dapat dikirim.</strong> Periksa kembali pertanyaan yang belum diisi atau tidak valid.
            </div>
        @endif

        <form action="{{ route('mahasiswa.jadkul.feedback-store', $jadwal->code) }}" method="POST">
            @csrf
            @foreach ($evaluation['sections'] as $section)
                <div class="card evaluation-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-1">{{ $section['title'] }}</h5>
                        <small class="text-muted">Pilih nilai 1 (sangat kurang) sampai 5 (sangat baik).</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover evaluation-table mb-0">
                                <thead><tr><th>Pernyataan</th>@for ($score = 1; $score <= 5; $score++)<th>{{ $score }}</th>@endfor</tr></thead>
                                <tbody>
                                    @foreach ($section['questions'] as $key => $question)
                                        <tr class="@error('ratings.'.$key) table-danger @enderror">
                                            <td><span class="evaluation-number">{{ $loop->iteration }}</span>{{ $question }} <span class="evaluation-required">*</span></td>
                                            @for ($score = 1; $score <= 5; $score++)
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="ratings[{{ $key }}]" id="{{ $key }}_{{ $score }}" value="{{ $score }}" @checked((string) old('ratings.'.$key) === (string) $score) required aria-label="Nilai {{ $score }} untuk {{ $question }}">
                                                    </div>
                                                </td>
                                            @endfor
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="card evaluation-card mb-4">
                <div class="card-header">
                    <h5 class="mb-1">Kesan, Kritik, dan Masukan</h5>
                    <small class="text-muted">Sampaikan pengalaman Anda secara spesifik dan konstruktif.</small>
                </div>
                <div class="card-body">
                    @foreach ($evaluation['narratives'] as $key => $question)
                        <div class="mb-4">
                            <label for="narrative_{{ $key }}" class="form-label fw-bold">{{ $question }} <span class="evaluation-required">*</span></label>
                            <textarea name="narratives[{{ $key }}]" id="narrative_{{ $key }}" class="form-control evaluation-narrative @error('narratives.'.$key) is-invalid @enderror" maxlength="5000" required placeholder="Tuliskan jawaban Anda...">{{ old('narratives.'.$key) }}</textarea>
                            @error('narratives.'.$key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="evaluation-submit d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted"><i class="fas fa-lock me-1"></i> Evaluasi hanya dapat dikirim satu kali.</small>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-1"></i> Kirim Evaluasi</button>
            </div>
        </form>
    </section>
@endsection
