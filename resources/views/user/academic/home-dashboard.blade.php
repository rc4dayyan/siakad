@php
    $dashboard = $academicDashboard;
    $period = $dashboard['period'];
    $krsStatuses = $dashboard['krsStatuses'];
    $statusItems = [
        ['label' => 'Draft', 'value' => $krsStatuses['draft'], 'color' => 'var(--dash-muted)'],
        ['label' => 'Diajukan', 'value' => $krsStatuses['submitted'], 'color' => 'var(--dash-gold)'],
        ['label' => 'Disetujui', 'value' => $krsStatuses['approved'], 'color' => 'var(--dash-green)'],
        ['label' => 'Ditolak', 'value' => $krsStatuses['rejected'], 'color' => '#bd4d58'],
        ['label' => 'Dikunci', 'value' => $krsStatuses['locked'], 'color' => 'var(--dash-navy-soft)'],
    ];
    $totalKrs = collect($krsStatuses)->sum();
@endphp

<div class="academic-dashboard">
    <div class="academic-hero mb-4">
        <div class="academic-hero__content">
            <span class="academic-hero__eyebrow">Ruang Kerja Akademik</span>
            <h2>Selamat datang, {{ Auth::user()->name }}</h2>
            <p>Pantau kesiapan operasional akademik dan tindak lanjuti pekerjaan penting dari satu halaman.</p>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route($prefix.'period-opening.index') }}" class="btn btn-light">
                    <i class="fas fa-clipboard-check me-1"></i> Periksa Kesiapan
                </a>
                <a href="{{ route($prefix.'krs-management.index') }}" class="btn btn-outline-light">
                    <i class="fas fa-file-signature me-1"></i> Kelola KRS
                </a>
            </div>
        </div>
        <div class="academic-hero__period">
            <span class="academic-hero__period-label">Periode dipilih</span>
            @if ($period)
                <strong>{{ $period->name }}</strong>
                <span>{{ $period->code }} · {{ $period->status_label }}</span>
                @if ($period->starts_at || $period->ends_at)
                    <small>
                        {{ $period->starts_at?->translatedFormat('d M Y') ?? '—' }}
                        – {{ $period->ends_at?->translatedFormat('d M Y') ?? '—' }}
                    </small>
                @endif
            @else
                <strong>Belum tersedia</strong>
                <span>Pilih atau aktifkan periode akademik.</span>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <a href="{{ route($prefix.'workers.student-index') }}" class="academic-stat-card">
                <span class="academic-stat-card__icon is-blue"><i class="fas fa-user-graduate"></i></span>
                <span><small>Mahasiswa Terdaftar</small><strong>{{ number_format($dashboard['registrations']) }}</strong><em>{{ number_format($dashboard['activeStudents']) }} berstatus aktif</em></span>
            </a>
        </div>
        <div class="col-xl-3 col-sm-6">
            <a href="{{ route($prefix.'master.penawaran-index') }}" class="academic-stat-card">
                <span class="academic-stat-card__icon is-purple"><i class="fas fa-book-open"></i></span>
                <span><small>Penawaran Mata Kuliah</small><strong>{{ number_format($dashboard['offerings']) }}</strong><em>{{ number_format($dashboard['classes']) }} kelas tersedia</em></span>
            </a>
        </div>
        <div class="col-xl-3 col-sm-6">
            <a href="{{ route($prefix.'krs-management.index', ['status' => 'submitted']) }}" class="academic-stat-card">
                <span class="academic-stat-card__icon is-orange"><i class="fas fa-hourglass-half"></i></span>
                <span><small>KRS Menunggu Proses</small><strong>{{ number_format($krsStatuses['submitted']) }}</strong><em>Perlu ditinjau</em></span>
            </a>
        </div>
        <div class="col-xl-3 col-sm-6">
            <a href="{{ route($prefix.'master.jadwal-mingguan-index') }}" class="academic-stat-card">
                <span class="academic-stat-card__icon is-green"><i class="fas fa-calendar-check"></i></span>
                <span><small>Pertemuan Kuliah</small><strong>{{ number_format($dashboard['meetings']) }}</strong><em>{{ number_format($dashboard['weeklySchedules']) }} jadwal mingguan</em></span>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card academic-panel h-100 mb-0">
                <div class="card-header d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h5 class="mb-1">Ringkasan KRS</h5>
                        <small class="text-muted">Distribusi status KRS pada periode terpilih</small>
                    </div>
                    <a href="{{ route($prefix.'krs-list.index') }}" class="btn btn-sm btn-outline-primary">Lihat List KRS</a>
                </div>
                <div class="card-body">
                    <div class="krs-completion mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <div><span class="text-muted small d-block">KRS selesai diproses</span><strong>{{ $dashboard['krsCompletion'] }}%</strong></div>
                            <small class="text-muted">{{ number_format($totalKrs) }} KRS tercatat</small>
                        </div>
                        <div class="progress"><div class="progress-bar" role="progressbar" style="width: {{ min(100, $dashboard['krsCompletion']) }}%" aria-valuenow="{{ $dashboard['krsCompletion'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                    </div>
                    <div class="row g-3">
                        @foreach ($statusItems as $status)
                            <div class="col-md col-6">
                                <div class="krs-status-item">
                                    <span class="krs-status-item__dot" style="background: {{ $status['color'] }}"></span>
                                    <small>{{ $status['label'] }}</small>
                                    <strong>{{ number_format($status['value']) }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card academic-panel h-100 mb-0">
                <div class="card-header">
                    <h5 class="mb-1">Perlu Perhatian</h5>
                    <small class="text-muted">Prioritas operasional periode ini</small>
                </div>
                <div class="card-body pt-2">
                    <a href="{{ route($prefix.'krs-management.index', ['status' => 'submitted']) }}" class="attention-item">
                        <span class="attention-item__icon is-warning"><i class="fas fa-file-circle-question"></i></span>
                        <span><strong>{{ number_format($krsStatuses['submitted']) }} KRS diajukan</strong><small>Menunggu proses atau persetujuan</small></span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="{{ route($prefix.'krs-management.index', ['status' => 'none']) }}" class="attention-item">
                        <span class="attention-item__icon is-danger"><i class="fas fa-user-clock"></i></span>
                        <span><strong>{{ number_format($dashboard['withoutKrs']) }} mahasiswa belum memiliki KRS</strong><small>Periksa registrasi dan kelayakan pengisian</small></span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="{{ route($prefix.'master.penawaran-unscheduled') }}" class="attention-item">
                        <span class="attention-item__icon is-info"><i class="fas fa-calendar-xmark"></i></span>
                        <span><strong>{{ number_format($dashboard['offeringsWithoutSchedule']) }} penawaran belum dijadwalkan</strong><small>Lengkapi jadwal mingguan mata kuliah</small></span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card academic-panel mb-0">
                <div class="card-header">
                    <h5 class="mb-1">Akses Cepat</h5>
                    <small class="text-muted">Menu yang paling sering digunakan staf akademik</small>
                </div>
                <div class="card-body pt-2">
                    <div class="quick-action-grid">
                        <a href="{{ route($prefix.'workers.student-index') }}"><i class="fas fa-users"></i><span>Data Mahasiswa</span></a>
                        <a href="{{ route($prefix.'master.penawaran-index') }}"><i class="fas fa-book-open"></i><span>Penawaran Kuliah</span></a>
                        <a href="{{ route($prefix.'master.jadwal-mingguan-index') }}"><i class="fas fa-calendar-week"></i><span>Jadwal &amp; Pertemuan</span></a>
                        <a href="{{ route($prefix.'krs-management.index') }}"><i class="fas fa-file-signature"></i><span>Kelola KRS</span></a>
                        <a href="{{ route($prefix.'krs-list.index') }}"><i class="fas fa-list-check"></i><span>List KRS</span></a>
                        <a href="{{ route($prefix.'dosen-pengajar-list.index') }}"><i class="fas fa-chalkboard-teacher"></i><span>Dosen Pengajar</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
