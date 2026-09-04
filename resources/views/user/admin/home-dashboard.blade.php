@php
    $dashboard = $webAdminDashboard;
    $academic = $dashboard['academic'];
    $financial = $dashboard['financial'];
    $period = $academic['period'];
    $krsStatuses = $academic['krsStatuses'];
@endphp

<div class="professional-dashboard">
    <div class="dashboard-hero mb-4">
        <div class="dashboard-hero__content">
            <span class="dashboard-hero__eyebrow">Pusat Kendali Sistem</span>
            <h2>Selamat datang, {{ Auth::user()->name }}</h2>
            <p>Pantau kondisi data, operasional akademik, dan layanan keuangan kampus dari satu halaman.</p>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route($prefix.'period-opening.index') }}" class="btn btn-light"><i class="fas fa-clipboard-check me-1"></i> Kesiapan Periode</a>
                <a href="{{ route($prefix.'system.setting-index') }}" class="btn btn-outline-light"><i class="fas fa-gear me-1"></i> Pengaturan Sistem</a>
            </div>
        </div>
        <div class="dashboard-hero__aside">
            <small>Periode dipilih</small>
            <strong>{{ $period?->name ?? 'Belum tersedia' }}</strong>
            <span>{{ $period ? $period->code.' · '.$period->status_label : 'Pilih atau aktifkan periode akademik' }}</span>
            @if ($period?->starts_at || $period?->ends_at)
                <span class="mt-1">{{ $period->starts_at?->translatedFormat('d M Y') ?? '—' }} – {{ $period->ends_at?->translatedFormat('d M Y') ?? '—' }}</span>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><a href="{{ route($prefix.'workers.student-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-user-graduate"></i></span><span class="dashboard-metric__content"><small>Total Mahasiswa</small><strong>{{ number_format($dashboard['students']) }}</strong><em>{{ number_format($academic['registrations']) }} terdaftar pada periode ini</em></span></a></div>
        <div class="col-xl-3 col-sm-6"><a href="{{ route($prefix.'workers.lecture-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-chalkboard-teacher"></i></span><span class="dashboard-metric__content"><small>Dosen</small><strong>{{ number_format($dashboard['lecturers']) }}</strong><em>{{ number_format($dashboard['activeLecturers']) }} akun aktif</em></span></a></div>
        <div class="col-xl-3 col-sm-6"><a href="{{ route($prefix.'workers.staff-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-users-gear"></i></span><span class="dashboard-metric__content"><small>Staf</small><strong>{{ number_format($dashboard['staff']) }}</strong><em>{{ number_format($dashboard['activeStaff']) }} akun aktif</em></span></a></div>
        <div class="col-xl-3 col-sm-6"><a href="{{ route($prefix.'master.jadwal-mingguan-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-calendar-week"></i></span><span class="dashboard-metric__content"><small>Jadwal Perkuliahan</small><strong>{{ number_format($academic['weeklySchedules']) }}</strong><em>{{ number_format($academic['meetings']) }} pertemuan tercatat</em></span></a></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card dashboard-panel">
                <div class="card-header d-flex justify-content-between align-items-start gap-3"><div><h5 class="mb-1">Perlu Perhatian</h5><small class="text-muted">Pekerjaan prioritas pada periode terpilih</small></div><a href="{{ route($prefix.'period-opening.index') }}" class="btn btn-sm btn-outline-primary">Lihat Kesiapan</a></div>
                <div class="card-body pt-2">
                    <a href="{{ route($prefix.'krs-management.index', ['status' => 'submitted']) }}" class="dashboard-list-item"><span class="dashboard-list-item__icon {{ $krsStatuses['submitted'] > 0 ? 'is-gold' : '' }}"><i class="fas fa-file-circle-question"></i></span><span><strong>{{ number_format($krsStatuses['submitted']) }} KRS menunggu proses</strong><small>Tinjau pengajuan mahasiswa yang belum diputuskan</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route($prefix.'krs-management.index', ['status' => 'none']) }}" class="dashboard-list-item"><span class="dashboard-list-item__icon {{ $academic['withoutKrs'] > 0 ? 'is-red' : '' }}"><i class="fas fa-user-clock"></i></span><span><strong>{{ number_format($academic['withoutKrs']) }} mahasiswa belum memiliki KRS</strong><small>Periksa registrasi dan kelayakan pengisian KRS</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route($prefix.'master.penawaran-unscheduled') }}" class="dashboard-list-item"><span class="dashboard-list-item__icon {{ $academic['offeringsWithoutSchedule'] > 0 ? 'is-gold' : '' }}"><i class="fas fa-calendar-xmark"></i></span><span><strong>{{ number_format($academic['offeringsWithoutSchedule']) }} penawaran belum dijadwalkan</strong><small>Lengkapi jadwal mingguan mata kuliah</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route($prefix.'finance.pembayaran-index') }}" class="dashboard-list-item"><span class="dashboard-list-item__icon {{ $dashboard['pendingPayments'] > 0 ? 'is-gold' : '' }}"><i class="fas fa-receipt"></i></span><span><strong>{{ number_format($dashboard['pendingPayments']) }} pembayaran menunggu verifikasi</strong><small>Tindak lanjuti bukti pembayaran yang masuk</small></span><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card dashboard-panel">
                <div class="card-header"><h5 class="mb-1">Ringkasan Periode</h5><small class="text-muted">Gambaran akademik dan keuangan terkini</small></div>
                <div class="card-body pt-2">
                    <div class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-building-user"></i></span><span><strong>{{ number_format($academic['classes']) }} kelas tersedia</strong><small>{{ number_format($academic['offerings']) }} penawaran mata kuliah</small></span><span class="badge bg-light-primary text-primary">Akademik</span></div>
                    <div class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-circle-check"></i></span><span><strong>{{ $academic['krsCompletion'] }}% KRS selesai diproses</strong><small>{{ number_format($krsStatuses['approved'] + $krsStatuses['locked']) }} telah disetujui atau dikunci</small></span><span class="badge bg-light-success text-success">KRS</span></div>
                    <div class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-file-invoice-dollar"></i></span><span><strong>Rp {{ number_format($financial['total_tagihan'], 0, ',', '.') }} tagihan</strong><small>Rp {{ number_format($financial['total_tunggakan'], 0, ',', '.') }} masih tertunggak</small></span><span class="badge bg-light-warning text-warning">Finance</span></div>
                    <div class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-wallet"></i></span><span><strong>Rp {{ number_format($balSekarang, 0, ',', '.') }} saldo berjalan</strong><small>Pemasukan dikurangi pengeluaran tercatat</small></span><span class="badge bg-light-success text-success">Kas</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-panel">
        <div class="card-header"><h5 class="mb-1">Akses Cepat</h5><small class="text-muted">Modul utama pengelolaan sistem akademik</small></div>
        <div class="card-body pt-2"><div class="dashboard-action-grid is-six">
            <a href="{{ route($prefix.'workers.student-index') }}"><i class="fas fa-user-graduate"></i><span>Data Mahasiswa</span></a>
            <a href="{{ route($prefix.'workers.lecture-index') }}"><i class="fas fa-chalkboard-teacher"></i><span>Data Dosen</span></a>
            <a href="{{ route($prefix.'master.kelas-index') }}"><i class="fas fa-building-user"></i><span>Data Kelas</span></a>
            <a href="{{ route($prefix.'master.jadwal-mingguan-index') }}"><i class="fas fa-calendar-days"></i><span>Jadwal Kuliah</span></a>
            <a href="{{ route($prefix.'krs-management.index') }}"><i class="fas fa-file-signature"></i><span>Kelola KRS</span></a>
            <a href="{{ route($prefix.'billing-period.index') }}"><i class="fas fa-file-invoice-dollar"></i><span>Tagihan Periode</span></a>
        </div></div>
    </div>
</div>
