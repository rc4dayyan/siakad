@extends('base.base-dash-index')

@section('title', 'Kenaikan Semester Massal - Siakad')
@section('menu', 'Kenaikan Semester Massal')
@section('submenu', 'Pratinjau dan Proses')
@section('urlmenu', route($prefix.'workers.student-index'))
@section('subdesc', 'Membentuk registrasi periode tujuan dari registrasi kelas pada periode sumber')

@section('content')
<section class="section row">
    <div class="col-12">
        <form action="{{ route($prefix.'workers.student-promotion-preview') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Konfigurasi Kenaikan Semester</h5>
                    <button type="submit" class="btn btn-outline-primary">Tampilkan Pratinjau</button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-lg-3 col-12">
                            <label for="periode_sumber">Periode sumber</label>
                            <select name="periode_sumber" id="periode_sumber" class="form-select" required>
                                <option value="">Pilih periode</option>
                                @foreach ($periods as $period)
                                    <option value="{{ $period->code }}" @selected(old('periode_sumber', $selection['periode_sumber'] ?? '') === $period->code)>{{ $period->name }}</option>
                                @endforeach
                            </select>
                            @error('periode_sumber') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group col-lg-3 col-12">
                            <label for="kelas_sumber">Kelas sumber</label>
                            <select name="kelas_sumber" id="kelas_sumber" class="form-select" required>
                                <option value="">Pilih kelas</option>
                                @foreach ($periods as $period)
                                    @foreach ($period->kelas as $class)
                                        <option value="{{ $class->code }}" data-period="{{ $period->code }}" @selected(old('kelas_sumber', $selection['kelas_sumber'] ?? '') === $class->code)>{{ $class->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            @error('kelas_sumber') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group col-lg-3 col-12">
                            <label for="periode_tujuan">Periode tujuan</label>
                            <select name="periode_tujuan" id="periode_tujuan" class="form-select" required>
                                <option value="">Pilih periode</option>
                                @foreach ($periods->whereIn('status', [\App\Models\TahunAkademik::STATUS_DRAFT, \App\Models\TahunAkademik::STATUS_ACTIVE]) as $period)
                                    <option value="{{ $period->code }}" @selected(old('periode_tujuan', $selection['periode_tujuan'] ?? '') === $period->code)>{{ $period->name }}</option>
                                @endforeach
                            </select>
                            @error('periode_tujuan') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group col-lg-3 col-12">
                            <label for="kelas_tujuan">Kelas tujuan</label>
                            <select name="kelas_tujuan" id="kelas_tujuan" class="form-select" required>
                                <option value="">Pilih kelas</option>
                                @foreach ($periods as $period)
                                    @foreach ($period->kelas as $class)
                                        <option value="{{ $class->code }}" data-period="{{ $period->code }}" @selected(old('kelas_tujuan', $selection['kelas_tujuan'] ?? '') === $class->code)>{{ $class->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            @error('kelas_tujuan') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group col-lg-4 col-12">
                            <label for="dosen_wali">Dosen wali tujuan</label>
                            <select name="dosen_wali" id="dosen_wali" class="form-select" required>
                                <option value="">Pilih dosen wali</option>
                                @foreach ($advisors as $advisor)
                                    <option value="{{ $advisor->dsn_code }}" @selected(old('dosen_wali', $selection['dosen_wali'] ?? '') === $advisor->dsn_code)>{{ $advisor->dsn_name }}</option>
                                @endforeach
                            </select>
                            @error('dosen_wali') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        @foreach (['keputusan_cuti' => 'Mahasiswa cuti', 'keputusan_nonaktif' => 'Mahasiswa nonaktif'] as $field => $label)
                            <div class="form-group col-lg-4 col-12">
                                <label for="{{ $field }}">Keputusan {{ $label }}</label>
                                <select name="{{ $field }}" id="{{ $field }}" class="form-select" required>
                                    <option value="">Pilih keputusan</option>
                                    @foreach ($actions as $action)
                                        <option value="{{ $action }}" @selected(old($field, $selection[$field] ?? '') === $action)>
                                            {{ match ($action) { 'aktifkan' => 'Lanjutkan sebagai aktif', 'pertahankan' => 'Pertahankan status', default => 'Lewati' } }}
                                        </option>
                                    @endforeach
                                </select>
                                @error($field) <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">Mahasiswa lulus, drop out, dan mengundurkan diri selalu dilewati. Data baru belum disimpan pada tahap pratinjau.</small>
                </div>
            </div>
        </form>
    </div>
</section>

@if ($preview !== null)
<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Pratinjau {{ $preview->count() }} Mahasiswa</h5>
                @if ($preview->where('action', '!=', 'lewati')->isNotEmpty())
                    <form action="{{ route($prefix.'workers.student-promotion-execute') }}" method="POST">
                        @csrf
                        @foreach ($selection as $field => $value)
                            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                        @endforeach
                        <button type="submit" class="btn btn-outline-success" onclick="return confirm('Proses registrasi periode tujuan sekarang?')">Jalankan Proses</button>
                    </form>
                @endif
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Mahasiswa</th>
                            <th>Semester</th>
                            <th>Status Sumber</th>
                            <th>Status Tujuan</th>
                            <th>Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preview as $row)
                            <tr>
                                <td>{{ $row['student_number'] }}</td>
                                <td>{{ $row['student_name'] }}</td>
                                <td>{{ $row['source_semester'] }} &rarr; {{ $row['target_semester'] }}</td>
                                <td>{{ \App\Models\RegistrasiMahasiswa::academicStatusLabel($row['source_status']) }}</td>
                                <td>{{ $row['target_status'] ? \App\Models\RegistrasiMahasiswa::academicStatusLabel($row['target_status']) : '-' }}</td>
                                <td>{{ $row['reason'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">Tidak ada registrasi pada kelas sumber.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endif

<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title">Audit Proses Terakhir</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr><th>Waktu</th><th>Sumber</th><th>Tujuan</th><th>Hasil</th><th>Staf</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($runs as $run)
                            <tr>
                                <td>{{ $run->created_at->format('d-m-Y H:i') }}</td>
                                <td>{{ $run->periodeSumber->name }} / {{ $run->kelasSumber->name }}</td>
                                <td>{{ $run->periodeTujuan->name }} / {{ $run->kelasTujuan->name }}</td>
                                <td>{{ $run->jumlah_berhasil }} berhasil, {{ $run->jumlah_dilewati }} dilewati, {{ $run->jumlah_gagal }} gagal</td>
                                <td>{{ $run->diprosesOleh->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">Belum ada proses kenaikan semester.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('custom-js')
<script>
    function filterClasses(periodSelectId, classSelectId) {
        const period = document.getElementById(periodSelectId);
        const classes = document.getElementById(classSelectId);
        const apply = () => {
            Array.from(classes.options).forEach((option, index) => {
                if (index === 0) return;
                option.hidden = option.dataset.period !== period.value;
                if (option.hidden && option.selected) classes.value = '';
            });
        };
        period.addEventListener('change', apply);
        apply();
    }
    filterClasses('periode_sumber', 'kelas_sumber');
    filterClasses('periode_tujuan', 'kelas_tujuan');
</script>
@endsection
