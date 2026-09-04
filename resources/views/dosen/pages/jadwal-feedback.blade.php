@extends('base.base-dash-index')

@section('menu', 'Evaluasi Perkuliahan')
@section('submenu', 'Ringkasan Feedback')
@section('urlmenu', route('dosen.akademik.jadwal-index'))
@section('subdesc', 'Analisis anonim evaluasi kinerja dosen')
@section('title', 'Evaluasi Perkuliahan')

@section('custom-css')
    <style>
        .feedback-page { --feedback-primary: #435ebe; --feedback-ink: #25324b; --feedback-muted: #697386; }
        .feedback-hero, .feedback-card { border: 1px solid var(--dash-line); border-radius: 15px; box-shadow: 0 8px 24px rgba(12, 44, 55, .05); }
        .feedback-hero { overflow: hidden; border-top: 6px solid var(--feedback-primary); }
        .feedback-course { padding: 16px; border-radius: 12px; background: #f4f6ff; }
        .feedback-course small { display: block; color: var(--feedback-muted); }
        .feedback-course strong { color: var(--feedback-ink); }
        .feedback-stat { height: 100%; padding: 20px; border: 1px solid var(--dash-line); border-radius: 14px; background: #fff; }
        .feedback-stat__icon { display: inline-flex; width: 42px; height: 42px; align-items: center; justify-content: center; border-radius: 12px; color: var(--feedback-primary); background: #eef1ff; font-size: 18px; }
        .feedback-stat__value { margin-top: 12px; color: var(--feedback-ink); font-size: 28px; font-weight: 750; line-height: 1; }
        .feedback-stat__label { margin-top: 7px; color: var(--feedback-muted); font-size: 13px; }
        .feedback-card .card-header { padding: 20px 22px 10px; background: transparent; }
        .feedback-card .card-body { padding: 14px 22px 22px; }
        .competency-row { padding: 14px 0; border-bottom: 1px solid #edf0f4; }
        .competency-row:last-child { border-bottom: 0; }
        .competency-score { color: var(--feedback-primary); font-size: 19px; font-weight: 750; }
        .competency-progress { height: 7px; border-radius: 20px; background: #edf0f7; }
        .competency-progress .progress-bar { border-radius: 20px; background: var(--feedback-primary); }
        .question-table th { color: var(--feedback-muted); font-size: 11px; letter-spacing: .04em; text-transform: uppercase; }
        .question-table td { padding-top: 13px; padding-bottom: 13px; vertical-align: middle; }
        .question-table__score { color: var(--feedback-primary); font-weight: 750; white-space: nowrap; }
        .response-card { border: 1px solid #e5e9f0; border-radius: 13px; box-shadow: none; }
        .response-card__badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 30px; color: #52616f; background: #f1f4f7; font-size: 12px; font-weight: 650; }
        .response-answer { padding: 13px 15px; border-left: 3px solid #dce2ff; border-radius: 0 9px 9px 0; background: #fafbff; }
        .response-answer__question { display: block; margin-bottom: 5px; color: var(--feedback-muted); font-size: 12px; }
        .response-answer p { color: var(--feedback-ink); white-space: pre-line; }
        .feedback-empty { padding: 50px 20px; color: var(--feedback-muted); text-align: center; }
        .feedback-empty i { display: block; margin-bottom: 12px; color: #aab3c3; font-size: 38px; }
        @media (max-width: 767.98px) {
            .feedback-card .card-header, .feedback-card .card-body { padding-right: 16px; padding-left: 16px; }
        }
    </style>
@endsection

@section('content')
    @php
        $distribution = $analytics['distribution'];
        $dominantScore = $distribution->sortDesc()->keys()->first();
        $scoreColors = ['Tidak Puas' => 'danger', 'Cukup Puas' => 'warning', 'Sangat Puas' => 'success'];
    @endphp
    <section class="section feedback-page">
        <div class="card feedback-hero mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <span class="badge bg-light-primary text-primary mb-2">Feedback anonim</span>
                        <h4 class="mb-1">{{ $jadwal->matkul->name ?? 'Mata kuliah tidak tersedia' }}</h4>
                        <p class="text-muted mb-0">{{ $jadwal->pert_id }} · {{ \Carbon\Carbon::parse($jadwal->date)->translatedFormat('d F Y') }}</p>
                    </div>
                    <a href="{{ route('dosen.akademik.jadwal-index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali ke jadwal</a>
                </div>
                <div class="feedback-course row g-3">
                    <div class="col-md-4"><small>Kelas</small><strong>{{ $jadwal->kelas->name ?? $jadwal->kelas->code ?? '—' }}</strong></div>
                    <div class="col-md-4"><small>Waktu</small><strong>{{ substr($jadwal->start, 0, 5) }}–{{ substr($jadwal->ended, 0, 5) }}</strong></div>
                    <div class="col-md-4"><small>Ruangan</small><strong>{{ $jadwal->ruang->name ?? 'Tanpa ruangan' }}{{ $jadwal->ruang?->gedung?->name ? ' · '.$jadwal->ruang->gedung->name : '' }}</strong></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-sm-6"><div class="feedback-stat"><span class="feedback-stat__icon"><i class="fas fa-comments"></i></span><div class="feedback-stat__value">{{ number_format($analytics['total']) }}</div><div class="feedback-stat__label">Total respons masuk</div></div></div>
            <div class="col-lg-3 col-sm-6"><div class="feedback-stat"><span class="feedback-stat__icon"><i class="fas fa-star"></i></span><div class="feedback-stat__value">{{ $analytics['average'] !== null ? number_format($analytics['average'], 2) : '—' }}<small class="fs-6 text-muted">/5</small></div><div class="feedback-stat__label">Rata-rata kinerja dosen</div></div></div>
            <div class="col-lg-3 col-sm-6"><div class="feedback-stat"><span class="feedback-stat__icon"><i class="fas fa-clipboard-check"></i></span><div class="feedback-stat__value">{{ number_format($analytics['structured']) }}</div><div class="feedback-stat__label">Respons kuesioner lengkap</div></div></div>
            <div class="col-lg-3 col-sm-6"><div class="feedback-stat"><span class="feedback-stat__icon"><i class="fas fa-chart-line"></i></span><div class="feedback-stat__value fs-5">{{ $analytics['total'] > 0 ? $dominantScore : '—' }}</div><div class="feedback-stat__label">Kategori terbanyak</div></div></div>
        </div>

        @if ($analytics['total'] > 0)
            <div class="row g-4 mb-4">
                <div class="col-xl-5">
                    <div class="card feedback-card h-100">
                        <div class="card-header"><h5 class="mb-1">Distribusi Kepuasan</h5><small class="text-muted">Ringkasan seluruh respons, termasuk format feedback lama.</small></div>
                        <div class="card-body"><div id="feedbackDistributionChart"></div></div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="card feedback-card h-100">
                        <div class="card-header"><h5 class="mb-1">Skor per Kompetensi</h5><small class="text-muted">Dihitung dari respons kuesioner lengkap dengan skala 1–5.</small></div>
                        <div class="card-body">
                            @foreach ($analytics['sections'] as $section)
                                <div class="competency-row">
                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-2"><strong>{{ $section['title'] }}</strong><span class="competency-score">{{ $section['average'] !== null ? number_format($section['average'], 2) : '—' }}</span></div>
                                    <div class="progress competency-progress"><div class="progress-bar" role="progressbar" style="width: {{ $section['average'] !== null ? $section['average'] / 5 * 100 : 0 }}%" aria-valuenow="{{ $section['average'] ?? 0 }}" aria-valuemin="0" aria-valuemax="5"></div></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @if ($analytics['structured'] > 0)
                <div class="card feedback-card mb-4">
                    <div class="card-header"><h5 class="mb-1">Detail Indikator Penilaian</h5><small class="text-muted">Gunakan rincian ini untuk mengidentifikasi aspek yang sudah kuat dan yang perlu ditingkatkan.</small></div>
                    <div class="card-body">
                        <div class="accordion" id="feedbackQuestionAccordion">
                            @foreach ($analytics['sections'] as $sectionKey => $section)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading_{{ $sectionKey }}"><button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#section_{{ $sectionKey }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="section_{{ $sectionKey }}">{{ $section['title'] }} <span class="badge bg-light-primary text-primary ms-2">{{ $section['average'] !== null ? number_format($section['average'], 2).'/5' : 'Belum ada nilai' }}</span></button></h2>
                                    <div id="section_{{ $sectionKey }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="heading_{{ $sectionKey }}" data-bs-parent="#feedbackQuestionAccordion">
                                        <div class="accordion-body p-0"><div class="table-responsive"><table class="table question-table mb-0"><thead><tr><th>Indikator</th><th class="text-center">Respons</th><th class="text-end">Rata-rata</th></tr></thead><tbody>
                                            @foreach ($section['questions'] as $question)
                                                <tr><td>{{ $question['question'] }}</td><td class="text-center text-muted">{{ $question['responses'] }}</td><td class="text-end question-table__score">{{ $question['average'] !== null ? number_format($question['average'], 2) : '—' }}</td></tr>
                                            @endforeach
                                        </tbody></table></div></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="card feedback-card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="mb-1">Kesan, Kritik, dan Masukan</h5><small class="text-muted">Identitas pemberi feedback disembunyikan.</small></div><span class="badge bg-light-secondary text-secondary">Halaman {{ $feedback->currentPage() }} dari {{ $feedback->lastPage() }}</span></div>
                <div class="card-body">
                    @foreach ($feedback as $item)
                        <article class="card response-card mb-3">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <span class="response-card__badge"><i class="fas fa-user-secret"></i> Responden anonim #{{ $feedback->firstItem() + $loop->index }}</span>
                                    <div><span class="badge bg-light-{{ $scoreColors[$item->fb_score] ?? 'secondary' }} text-{{ $scoreColors[$item->fb_score] ?? 'secondary' }}">{{ $item->fb_score }}</span>@if ($item->fb_average_score)<span class="badge bg-light-primary text-primary ms-1">{{ number_format((float) $item->fb_average_score, 2) }}/5</span>@endif</div>
                                </div>
                                @php $narratives = data_get($item->fb_answers, 'narratives'); @endphp
                                @if (is_array($narratives))
                                    <div class="row g-3">
                                        @foreach ($evaluation['narratives'] as $key => $question)
                                            <div class="col-lg-6"><div class="response-answer h-100"><span class="response-answer__question">{{ $question }}</span><p class="mb-0">{{ $narratives[$key] ?? '—' }}</p></div></div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="response-answer"><span class="response-answer__question">Catatan feedback format lama</span><p class="mb-0">{{ $item->fb_reason }}</p></div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                    <div class="mt-3">{{ $feedback->links() }}</div>
                </div>
            </div>
        @else
            <div class="card feedback-card"><div class="feedback-empty"><i class="far fa-comment-dots"></i><h5>Belum ada feedback</h5><p class="mb-0">Ringkasan evaluasi akan tampil setelah mahasiswa mengirimkan feedback untuk pertemuan ini.</p></div></div>
        @endif
    </section>
@endsection

@section('custom-js')
    @if ($analytics['total'] > 0)
        <script src="{{ asset('dist') }}/assets/extensions/apexcharts/apexcharts.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const target = document.querySelector('#feedbackDistributionChart');
                if (!target || typeof ApexCharts === 'undefined') return;

                new ApexCharts(target, {
                    chart: { type: 'donut', height: 315, toolbar: { show: false } },
                    series: @json($distribution->values()),
                    labels: @json($distribution->keys()),
                    colors: ['#dc3545', '#ffc107', '#28a745'],
                    dataLabels: { enabled: true },
                    legend: { position: 'bottom', fontSize: '13px' },
                    plotOptions: { pie: { donut: { size: '66%', labels: { show: true, total: { show: true, label: 'Total Respons', formatter: function () { return '{{ $analytics['total'] }}'; } } } } } },
                    noData: { text: 'Belum ada data' }
                }).render();
            });
        </script>
    @endif
@endsection
