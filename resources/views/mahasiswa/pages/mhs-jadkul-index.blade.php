@extends('base.base-dash-index')

@section('title', 'Jadwal Kuliah')
@section('menu', 'Akademik')
@section('submenu', 'Jadwal Kuliah')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Daftar perkuliahan pada periode akademik berjalan')

@section('custom-css')
    <style>
        .student-schedule-card { border: 1px solid var(--dash-line); border-radius: 15px; box-shadow: 0 8px 24px rgba(12, 44, 55, .05); }
        .student-schedule-card .card-header { padding: 21px 22px 14px; background: transparent; }
        .student-schedule-card .card-body { padding: 16px 22px 22px; }
        .student-schedule-filter { padding: 14px; border: 1px solid var(--dash-line); border-radius: 12px; background: #f7faf9; }
        .student-schedule-filter .form-control, .student-schedule-filter .form-select, .student-schedule-filter .input-group-text { min-height: 40px; border-color: #d7e4df; }
        .student-schedule-filter .input-group-text { border-right: 0; border-radius: 9px 0 0 9px; background: #fff; }
        .student-schedule-filter .input-group .form-control { border-left: 0; border-radius: 0 9px 9px 0; }
        .student-schedule-filter .form-select { border-radius: 9px; }
        .student-schedule-table thead th { padding: 12px 14px; color: var(--dash-muted); font-size: 11px; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
        .student-schedule-table td { padding: 16px 14px; vertical-align: middle; }
        .student-schedule-table__primary { display: block; color: var(--dash-navy); font-weight: 700; }
        .student-schedule-table__meta { display: block; margin-top: 4px; color: var(--dash-muted); font-size: 12px; }
        .student-schedule-table__course { min-width: 205px; }
        .student-schedule-table__time { min-width: 175px; }
        .student-schedule-table__place { min-width: 165px; }
        .student-schedule-actions { display: flex; justify-content: flex-end; gap: 7px; white-space: nowrap; }
        .student-schedule-actions .btn { border-radius: 8px; font-weight: 600; }
        .student-schedule-empty { padding: 44px 16px !important; color: var(--dash-muted); text-align: center; }
        .student-schedule-empty i { display: block; margin-bottom: 10px; color: #aab8b4; font-size: 28px; }
        @media (max-width: 767.98px) {
            .student-schedule-header { align-items: flex-start !important; flex-direction: column; }
            .student-schedule-card .card-header, .student-schedule-card .card-body { padding-right: 16px; padding-left: 16px; }
        }
    </style>
@endsection

@section('content')
    <section class="section">
        <div class="card student-schedule-card">
            <div class="card-header student-schedule-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1">Jadwal Kuliah Saya</h5>
                    <small class="text-muted">Periode {{ $selectedPeriod?->name ?? 'belum tersedia' }}</small>
                </div>
                <span class="badge bg-primary rounded-pill px-3 py-2">{{ $jadkul->count() }} jadwal</span>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('mahasiswa.home-jadkul-index') }}" class="student-schedule-filter row g-2 align-items-center mb-3">
                    <div class="col-lg-7">
                        <label for="student_schedule_search" class="visually-hidden">Cari jadwal</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                            <input type="search" name="q" id="student_schedule_search" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari mata kuliah, dosen, atau kode jadwal">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-7">
                        <label for="student_schedule_day" class="visually-hidden">Hari kuliah</label>
                        <select name="days_id" id="student_schedule_day" class="form-select">
                            <option value="">Semua hari</option>
                            @foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'] as $dayId => $dayName)
                                <option value="{{ $dayId }}" @selected(isset($filters['days_id']) && (int) $filters['days_id'] === $dayId)>{{ $dayName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-sm-5">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Tampilkan</button>
                            @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                                <a href="{{ route('mahasiswa.home-jadkul-index') }}" class="btn btn-outline-secondary" title="Reset filter" aria-label="Reset filter"><i class="fas fa-undo"></i></a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover student-schedule-table mb-0">
                        <thead>
                            <tr>
                                <th>Mata Kuliah</th>
                                <th>Jadwal</th>
                                <th>Dosen</th>
                                <th>Kelas &amp; Lokasi</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jadkul as $item)
                                <tr>
                                    <td class="student-schedule-table__course">
                                        <span class="student-schedule-table__primary">{{ $item->matkul->name ?? 'Mata kuliah tidak tersedia' }}</span>
                                        <small class="student-schedule-table__meta">{{ $item->matkul->code ?? $item->code }} · {{ $item->pert_id }} · {{ $item->bsks }} SKS</small>
                                    </td>
                                    <td class="student-schedule-table__time">
                                        <span class="student-schedule-table__primary">{{ $item->days_id }}, {{ \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') }}</span>
                                        <small class="student-schedule-table__meta"><i class="far fa-clock me-1"></i>{{ substr($item->start, 0, 5) }}–{{ substr($item->ended, 0, 5) }}</small>
                                    </td>
                                    <td><span class="student-schedule-table__primary">{{ $item->dosen->dsn_name ?? 'Belum ditentukan' }}</span></td>
                                    <td class="student-schedule-table__place">
                                        <span class="student-schedule-table__primary">{{ $item->kelas->code ?? $item->kelas->name ?? '—' }}</span>
                                        <small class="student-schedule-table__meta"><i class="fas fa-location-dot me-1"></i>{{ $item->ruang->name ?? 'Tanpa ruangan' }}{{ $item->ruang?->gedung?->name ? ' · '.$item->ruang->gedung->name : '' }}</small>
                                        <span class="badge bg-light-primary text-primary mt-2">{{ $item->meth_id }}</span>
                                    </td>
                                    <td>
                                        <div class="student-schedule-actions">
                                            <a href="{{ route('mahasiswa.home-jadkul-absen', $item->code) }}" class="btn btn-sm btn-primary"><i class="fas fa-calendar-check me-1"></i> Absensi</a>
                                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#giveFeedback{{ $item->code }}" title="Berikan feedback" aria-label="Berikan feedback"><i class="fas fa-star"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="student-schedule-empty"><i class="far fa-calendar-xmark"></i>Tidak ada jadwal yang sesuai dengan pencarian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    @foreach ($jadkul as $item)
        <form action="{{ route('mahasiswa.jadkul.feedback-store', $item->code) }}" method="POST">
            @csrf
            <div class="modal fade" id="giveFeedback{{ $item->code }}" tabindex="-1" aria-labelledby="giveFeedbackLabel{{ $item->code }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                    <div class="modal-header"><div><h5 class="modal-title mb-1" id="giveFeedbackLabel{{ $item->code }}">Feedback Perkuliahan</h5><small class="text-muted">{{ $item->matkul->name ?? 'Mata kuliah' }} · {{ $item->pert_id }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label for="feedback_score_{{ $item->code }}" class="form-label">Tingkat Kepuasan</label><select name="fb_score" id="feedback_score_{{ $item->code }}" class="form-select" required><option value="">Pilih tingkat kepuasan</option><option value="Tidak Puas">Tidak Puas</option><option value="Cukup Puas">Cukup Puas</option><option value="Sangat Puas">Sangat Puas</option></select><small class="text-muted">Identitas Anda tidak ditampilkan pada feedback.</small></div>
                        <div><label for="feedback_reason_{{ $item->code }}" class="form-label">Catatan</label><textarea name="fb_reason" id="feedback_reason_{{ $item->code }}" class="form-control" rows="5" placeholder="Tuliskan pengalaman atau saran Anda..."></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Kirim Feedback</button></div>
                </div></div>
            </div>
        </form>
    @endforeach
@endsection
