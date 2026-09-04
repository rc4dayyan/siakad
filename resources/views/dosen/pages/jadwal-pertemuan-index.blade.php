@extends('base.base-dash-index')

@section('title', 'Pertemuan Kuliah')
@section('menu', 'Jadwal Mengajar')
@section('submenu', 'Daftar Pertemuan')
@section('urlmenu', route('dosen.akademik.jadwal-index'))
@section('subdesc', 'Kelola presensi pada setiap pertemuan kuliah')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h5 class="mb-1">{{ $schedule->penawaranMataKuliah?->masterMataKuliah?->name ?? 'Mata Kuliah' }}</h5>
                <small class="text-muted">
                    {{ $schedule->kelas?->name }} · {{ $schedule->hari_label }},
                    {{ substr($schedule->mulai, 0, 5) }}–{{ substr($schedule->selesai, 0, 5) }} ·
                    {{ $schedule->ruang?->name ?? 'Tanpa ruangan' }}
                </small>
            </div>
            <a href="{{ route('dosen.akademik.jadwal-index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Jadwal
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Pertemuan</th><th>Tanggal</th><th>Waktu</th><th>Status</th><th>Presensi</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($schedule->pertemuans as $meeting)
                            <tr>
                                <td><strong>Pertemuan {{ $meeting->pertemuan_ke }}</strong></td>
                                <td>{{ $meeting->tanggal->translatedFormat('d F Y') }}</td>
                                <td>{{ substr($meeting->mulai, 0, 5) }}–{{ substr($meeting->selesai, 0, 5) }}</td>
                                <td><span class="badge {{ $meeting->status === \App\Models\PertemuanKuliah::STATUS_COMPLETED ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($meeting->status) }}</span></td>
                                <td>{{ number_format($meeting->absensis_count) }} mahasiswa</td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        <a href="{{ route('dosen.akademik.jadwal-meeting-attendance', [$schedule->code, $meeting->code]) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-user-check me-1"></i> Presensi
                                        </a>
                                        @if ($meeting->legacyJadwalKuliah)
                                            <a href="{{ route('dosen.akademik.jadwal-view-feedback', $meeting->legacyJadwalKuliah->code) }}" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-star me-1"></i> Evaluasi
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Pertemuan belum dibuat. Hubungi Web Administrator untuk membuat pertemuan dari jadwal mingguan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
