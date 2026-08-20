@extends('base.base-dash-index')

@section('title', 'Dashboard Mahasiswa')
@section('menu', 'Dashboard')
@section('submenu', 'Dashboard Mahasiswa')
@section('urlmenu', '#')
@section('subdesc', 'Ringkasan aktivitas akademik dan administrasi Anda')

@section('custom-css')
    @include('base.components.professional-dashboard-styles')
@endsection

@section('content')
    @php
        $student = Auth::guard('mahasiswa')->user();
        $krs = $academicRegistration?->krs;
        $krsLabels = [
            'draft' => 'Draft',
            'submitted' => 'Menunggu persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Perlu diperbaiki',
            'locked' => 'Dikunci',
        ];
        $krsLabel = $krs ? ($krsLabels[$krs->status] ?? ucfirst($krs->status)) : 'Belum dibuat';
    @endphp

    <section class="section professional-dashboard">
        <div class="dashboard-hero mb-4">
            <div class="dashboard-hero__content">
                <span class="dashboard-hero__eyebrow">Portal Mahasiswa</span>
                <h2>Selamat datang, {{ $student->mhs_name }}</h2>
                <p>Pantau jadwal, KRS, tagihan, dan informasi kampus penting dari satu halaman.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('mahasiswa.akademik.krs-index') }}" class="btn btn-light"><i class="fas fa-file-signature me-1"></i> Lihat KRS</a>
                    <a href="{{ route('mahasiswa.home-jadkul-index') }}" class="btn btn-outline-light"><i class="fas fa-calendar-week me-1"></i> Jadwal Kuliah</a>
                </div>
            </div>
            <div class="dashboard-hero__aside">
                <small>Periode akademik</small>
                <strong>{{ $academicPeriod?->name ?? 'Belum tersedia' }}</strong>
                <span>
                    @if ($academicRegistration)
                        Semester {{ $academicRegistration->semester_mahasiswa }} · {{ $academicRegistration->academic_status_label }}
                    @else
                        Registrasi akademik belum tersedia
                    @endif
                </span>
                <span class="mt-1">{{ $academicClass?->name ?? 'Kelas belum ditentukan' }}</span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6"><a href="{{ route('mahasiswa.home-tagihan-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon {{ $sisatagihan > 0 ? 'is-gold' : '' }}"><i class="fas fa-file-invoice-dollar"></i></span><span class="dashboard-metric__content"><small>Sisa Tagihan</small><strong>Rp {{ number_format(max(0, $sisatagihan), 0, ',', '.') }}</strong><em>Lihat rincian pembayaran</em></span></a></div>
            <div class="col-xl-3 col-sm-6"><a href="{{ route('mahasiswa.home-tagihan-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-circle-check"></i></span><span class="dashboard-metric__content"><small>Total Pembayaran</small><strong>Rp {{ number_format($history, 0, ',', '.') }}</strong><em>Pembayaran terverifikasi</em></span></a></div>
            <div class="col-xl-3 col-sm-6"><a href="{{ route('mahasiswa.home-jadkul-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-user-check"></i></span><span class="dashboard-metric__content"><small>Kehadiran</small><strong>{{ number_format($habsen) }}</strong><em>Presensi hadir periode ini</em></span></a></div>
            <div class="col-xl-3 col-sm-6"><a href="{{ route('mahasiswa.home-jadkul-index') }}" class="dashboard-metric"><span class="dashboard-metric__icon"><i class="fas fa-calendar-check"></i></span><span class="dashboard-metric__content"><small>Jadwal Kuliah</small><strong>{{ number_format($jadkul) }}</strong><em>Mata kuliah terjadwal</em></span></a></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-7">
                <div class="card dashboard-panel">
                    <div class="card-header d-flex justify-content-between align-items-start gap-3"><div><h5 class="mb-1">Ringkasan Akademik</h5><small class="text-muted">Status studi pada periode berjalan</small></div><a href="{{ route('mahasiswa.akademik.krs-index') }}" class="btn btn-sm btn-outline-primary">Buka KRS</a></div>
                    <div class="card-body pt-2">
                        <div class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-file-signature"></i></span><span><strong>Status KRS: {{ $krsLabel }}</strong><small>{{ $krs ? number_format($krs->total_sks).' SKS dipilih' : 'Susun rencana studi sesuai jadwal pengisian' }}</small></span><i class="fas fa-chevron-right"></i></div>
                        <div class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-building-user"></i></span><span><strong>{{ $academicClass?->name ?? 'Kelas belum tersedia' }}</strong><small>Kelas perkuliahan periode berjalan</small></span><i class="fas fa-chevron-right"></i></div>
                        <div class="dashboard-list-item"><span class="dashboard-list-item__icon"><i class="fas fa-user-tie"></i></span><span><strong>{{ $academicRegistration?->dosenWali?->dsn_name ?? 'Dosen wali belum ditentukan' }}</strong><small>Dosen wali akademik</small></span><i class="fas fa-chevron-right"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card dashboard-panel">
                    <div class="card-header"><h5 class="mb-1">Pengumuman Terbaru</h5><small class="text-muted">Informasi penting untuk mahasiswa</small></div>
                    <div class="card-body pt-2">
                        @forelse ($notify as $item)
                            <a href="#" class="dashboard-list-item" data-bs-toggle="modal" data-bs-target="#studentNotification{{ $item->code }}"><span class="dashboard-list-item__icon is-gold"><i class="fas fa-bullhorn"></i></span><span><strong>{{ $item->name }}</strong><small>{{ $item->created_at?->translatedFormat('d M Y · H.i') }}</small></span><i class="fas fa-chevron-right"></i></a>
                        @empty
                            <div class="dashboard-empty"><i class="far fa-bell-slash"></i>Belum ada pengumuman terbaru.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="card dashboard-panel">
            <div class="card-header"><h5 class="mb-1">Akses Cepat</h5><small class="text-muted">Menu yang paling sering digunakan</small></div>
            <div class="card-body pt-2"><div class="dashboard-action-grid">
                <a href="{{ route('mahasiswa.akademik.krs-index') }}"><i class="fas fa-file-signature"></i><span>Kartu Rencana Studi</span></a>
                <a href="{{ route('mahasiswa.home-jadkul-index') }}"><i class="fas fa-calendar-week"></i><span>Jadwal Kuliah</span></a>
                <a href="{{ route('mahasiswa.akademik.nilai-index') }}"><i class="fas fa-chart-column"></i><span>Nilai Kuliah</span></a>
                <a href="{{ route('mahasiswa.home-tagihan-index') }}"><i class="fas fa-receipt"></i><span>Tagihan</span></a>
            </div></div>
        </div>
    </section>

    @foreach ($notify as $item)
        <div class="modal fade" id="studentNotification{{ $item->code }}" tabindex="-1" aria-labelledby="studentNotificationLabel{{ $item->code }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="studentNotificationLabel{{ $item->code }}">{{ $item->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body">{!! $item->desc !!}</div>
                <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Tutup</button></div>
            </div></div>
        </div>
    @endforeach
@endsection
