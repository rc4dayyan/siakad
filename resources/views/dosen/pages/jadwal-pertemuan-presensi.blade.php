@extends('base.base-dash-index')

@section('title', 'Presensi Pertemuan')
@section('menu', 'Jadwal Mengajar')
@section('submenu', 'Presensi Pertemuan '.$meeting->pertemuan_ke)
@section('urlmenu', route('dosen.akademik.jadwal-meetings', $schedule->code))
@section('subdesc', 'Catat kehadiran peserta KRS pada satu pertemuan')

@section('content')
<section class="section">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h5 class="mb-1">Pertemuan {{ $meeting->pertemuan_ke }} · {{ $schedule->penawaranMataKuliah?->masterMataKuliah?->name }}</h5>
                <small class="text-muted">
                    {{ $meeting->tanggal->translatedFormat('l, d F Y') }} ·
                    {{ substr($meeting->mulai, 0, 5) }}–{{ substr($meeting->selesai, 0, 5) }} ·
                    {{ $schedule->kelas?->name }}
                </small>
            </div>
            <a href="{{ route('dosen.akademik.jadwal-meetings', $schedule->code) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Daftar Pertemuan</a>
        </div>
        <div class="card-body">
            @if ($participants->isEmpty())
                <div class="alert alert-warning mb-0">Belum ada peserta dengan KRS disetujui pada mata kuliah ini.</div>
            @else
                <form method="POST" action="{{ route('dosen.akademik.jadwal-meeting-attendance-update', [$schedule->code, $meeting->code]) }}">
                    @csrf
                    @method('PATCH')
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>#</th><th>NIM</th><th>Nama Mahasiswa</th><th style="min-width: 160px">Status</th><th style="min-width: 260px">Keterangan</th></tr></thead>
                            <tbody>
                                @foreach ($participants as $participant)
                                    @php
                                        $student = $participant['student'];
                                        $attendance = $attendances->get($student->id);
                                        $selectedStatus = old("presences.{$student->id}.status", $attendance?->getRawOriginal('absen_type') ?? 'A');
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $student->mhs_nim }}</td>
                                        <td><strong>{{ $student->mhs_name }}</strong></td>
                                        <td>
                                            <select name="presences[{{ $student->id }}][status]" class="form-select" required>
                                                @foreach ($attendanceStatuses as $status => $label)
                                                    <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="presences[{{ $student->id }}][description]" class="form-control" maxlength="1000" value="{{ old("presences.{$student->id}.description", $attendance?->absen_desc) }}" placeholder="Opsional"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Simpan presensi seluruh peserta pada pertemuan ini?')"><i class="fas fa-save me-1"></i> Simpan Presensi</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
