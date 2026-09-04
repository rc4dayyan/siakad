@extends('base.base-dash-index')

@section('title', 'Pertemuan Kuliah')
@section('menu', 'Jadwal Kuliah')
@section('submenu', 'Daftar Pertemuan')
@section('urlmenu', route('mahasiswa.home-jadkul-index'))
@section('subdesc', 'Presensi dan evaluasi setiap pertemuan kuliah')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <span class="badge bg-light-primary text-primary mb-2">{{ $selectedPeriod?->name }}</span>
                    <h5 class="mb-1">{{ $schedule->penawaranMataKuliah?->masterMataKuliah?->name ?? 'Mata Kuliah' }}</h5>
                    <small class="text-muted">{{ $schedule->kelas?->name }} · Setiap {{ $schedule->hari_label }}, {{ substr($schedule->mulai, 0, 5) }}–{{ substr($schedule->selesai, 0, 5) }} · {{ $schedule->dosen?->dsn_name ?? 'Dosen belum ditentukan' }} · {{ $schedule->ruang?->name ?? 'Tanpa ruangan' }}</small>
                </div>
                <a href="{{ route('mahasiswa.home-jadkul-index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali ke Jadwal</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Pertemuan</th><th>Tanggal</th><th>Waktu</th><th>Status</th><th>Presensi Saya</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse ($schedule->pertemuans as $meeting)
                                @php
                                    $attendance = $attendances->get($meeting->code);
                                    $canAttend = $meeting->legacyJadwalKuliah && $meeting->tanggal->isToday();
                                    $hasEvaluated = $evaluatedCodes->contains($meeting->legacyJadwalKuliah?->code);
                                @endphp
                                <tr>
                                    <td><strong>Pertemuan {{ $meeting->pertemuan_ke }}</strong></td>
                                    <td>{{ $meeting->tanggal->translatedFormat('d F Y') }}</td>
                                    <td>{{ substr($meeting->mulai, 0, 5) }}–{{ substr($meeting->selesai, 0, 5) }}</td>
                                    <td><span class="badge {{ $meeting->status === \App\Models\PertemuanKuliah::STATUS_COMPLETED ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($meeting->status) }}</span></td>
                                    <td>@if ($attendance)<span class="badge bg-light-success text-success">{{ $attendance->absen_type }}</span>@else<span class="text-muted">Belum tercatat</span>@endif</td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            @if ($attendance)
                                                <span class="btn btn-sm btn-light-success disabled"><i class="fas fa-check me-1"></i> Sudah Presensi</span>
                                            @elseif ($canAttend)
                                                <a href="{{ route('mahasiswa.home-jadkul-absen', $meeting->code) }}" class="btn btn-sm btn-primary"><i class="fas fa-user-check me-1"></i> Presensi</a>
                                            @else
                                                <span class="btn btn-sm btn-light-secondary disabled"><i class="far fa-clock me-1"></i> Presensi pada hari H</span>
                                            @endif

                                            @if ($hasEvaluated)
                                                <span class="btn btn-sm btn-light-success disabled"><i class="fas fa-check me-1"></i> Evaluasi Terkirim</span>
                                            @elseif ($meeting->legacyJadwalKuliah)
                                                <a href="{{ route('mahasiswa.jadkul.feedback-create', $meeting->legacyJadwalKuliah->code) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-star me-1"></i> Evaluasi</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-5">Pertemuan belum dibuat. Hubungi bagian akademik untuk membuat pertemuan dari jadwal mingguan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
