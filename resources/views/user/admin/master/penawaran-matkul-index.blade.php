@extends('base.base-dash-index')
@section('title', 'Penawaran Mata Kuliah')
@section('menu', 'Penawaran Mata Kuliah')
@section('submenu', 'Penawaran Periode')
@section('urlmenu', '#')
@section('subdesc', 'Kelola mata kuliah yang ditawarkan pada periode terpilih')
@section('custom-css')
    <style>
        .offering-filter { padding: 20px; border: 1px solid #e1e8e6; border-radius: 14px; background: linear-gradient(145deg, #f8fbfa, #fff); }
        .offering-filter__icon { display: grid; width: 42px; height: 42px; flex: 0 0 42px; place-items: center; border-radius: 11px; color: #117a65; background: #e7f4f0; font-size: 17px; }
        .offering-filter .form-label { margin-bottom: 6px; color: #526670; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .offering-filter .form-control, .offering-filter .form-select { min-height: 42px; border-color: #dce5e2; border-radius: 9px; }
        .offering-filter .input-group-text { border-color: #dce5e2; border-radius: 9px 0 0 9px; color: #6b7d88; background: #fff; }
        .offering-filter__actions { display: flex; align-items: end; gap: 8px; }
        .offering-filter__actions .btn { min-height: 42px; border-radius: 9px; }
        .offering-result { color: #6b7d88; font-size: 12px; }
        @media (max-width: 767.98px) { .offering-filter__actions { align-items: stretch; flex-direction: column; } }
    </style>
@endsection
@section('content')
    <div class="mb-3"><a class="btn btn-outline-primary" href="{{ route($prefix.'period-opening.index') }}">Dashboard Pembukaan Periode</a></div>
<section class="section">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title">Salin Penawaran</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route($prefix.'master.penawaran-copy-preview') }}" class="row g-2">@csrf
                <div class="col-md-5"><select name="source_period_id" class="form-select" required><option value="">Periode sumber</option>@foreach($periods as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
                <input type="hidden" name="target_period_id" value="{{ $period?->id }}">
                <div class="col-md-3"><button class="btn btn-outline-primary" @disabled(!$canManage)>Pratinjau salin</button></div>
            </form>
        </div>
    </div>
    @if($canManage)
    <div class="card mb-3"><div class="card-header"><h5 class="card-title">Jadwal Pengisian KRS</h5></div><div class="card-body"><form method="POST" action="{{ route($prefix.'master.penawaran-krs-window') }}" class="row g-2">@csrf @method('PATCH')<div class="col-md-4"><label class="form-label">Mulai</label><input type="datetime-local" name="mulai_at" value="{{ $krsWindow?->mulai_at?->format('Y-m-d\\TH:i') }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Selesai</label><input type="datetime-local" name="selesai_at" value="{{ $krsWindow?->selesai_at?->format('Y-m-d\\TH:i') }}" class="form-control" required></div><div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="dipublikasikan" value="1" @checked($krsWindow?->dipublikasikan)><label class="form-check-label">Publikasikan</label></div></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary">Simpan jadwal</button></div></form></div></div>
    <div class="modal fade" id="createOfferingModal" tabindex="-1" aria-labelledby="createOfferingModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="createOfferingModalLabel">Tambah Penawaran — {{ $period->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">@if($errors->any() && old('_form') === 'create-offering')<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<form id="create-offering-form" method="POST" action="{{ route($prefix.'master.penawaran-store') }}" class="row g-3">@csrf<input type="hidden" name="_form" value="create-offering">
            <div class="col-md-4"><label class="form-label">Master mata kuliah</label><select name="master_mata_kuliah_id" class="form-select" required>@foreach($masters as $item)<option value="{{ $item->id }}" @selected(old('master_mata_kuliah_id') == $item->id)>{{ $item->name }} — {{ $masterProgramNames[$item->program_studi] ?? $item->program_studi }} — Semester {{ $item->semester }} ({{ $item->sks }} SKS)</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Program studi</label><select name="pstudi_id" class="form-select" required>@foreach($programs as $item)<option value="{{ $item->id }}" @selected(old('pstudi_id', $filters['pstudi_id'] ?? null) == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Kurikulum</label><select name="kuri_id" class="form-select" required>@foreach($curricula as $item)<option value="{{ $item->id }}" @selected(old('kuri_id', $filters['kuri_id'] ?? null) == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Kelas (dapat lebih dari satu)</label><select name="kelas_ids[]" class="form-select" multiple required>@foreach($classes as $item)<option value="{{ $item->id }}" @selected(in_array($item->id, old('kelas_ids', [])))>{{ $item->name }} — {{ $item->pstudi?->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Dosen utama</label><select name="dosen_utama_id" class="form-select" required>@foreach($lecturers as $item)<option value="{{ $item->id }}" @selected(old('dosen_utama_id') == $item->id)>{{ $item->dsn_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Dosen pendamping</label><select name="dosen_pendamping_1_id" class="form-select"><option value="">-</option>@foreach($lecturers as $item)<option value="{{ $item->id }}" @selected(old('dosen_pendamping_1_id') == $item->id)>{{ $item->dsn_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Pendamping kedua</label><select name="dosen_pendamping_2_id" class="form-select"><option value="">-</option>@foreach($lecturers as $item)<option value="{{ $item->id }}" @selected(old('dosen_pendamping_2_id') == $item->id)>{{ $item->dsn_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Prasyarat</label><select name="prasyarat_master_id" class="form-select"><option value="">Tanpa prasyarat</option>@foreach($masters as $item)<option value="{{ $item->id }}" @selected(old('prasyarat_master_id') == $item->id)>{{ $item->name }} — {{ $masterProgramNames[$item->program_studi] ?? $item->program_studi }} — Semester {{ $item->semester }} ({{ $item->sks }} SKS)</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" value="{{ old('kapasitas', 40) }}" min="1" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control">{{ old('deskripsi') }}</textarea></div>
            <div class="col-12"><hr><div class="form-check"><input class="form-check-input" type="checkbox" name="buat_jadwal" value="1" id="create-offering-with-schedule" @checked(old('buat_jadwal'))><label class="form-check-label fw-bold" for="create-offering-with-schedule">Sekaligus buat jadwal mingguan</label></div><small class="text-muted">Penjadwalan langsung hanya dapat digunakan jika memilih tepat satu kelas.</small></div>
            <div class="col-12 {{ old('buat_jadwal') ? '' : 'd-none' }}" id="create-offering-schedule-fields"><div class="row g-3 rounded border bg-light p-2">
                <div class="col-md-4"><label for="create-offering-room" class="form-label">Ruang</label><select name="jadwal_ruang_id" id="create-offering-room" class="form-select" required @disabled(!old('buat_jadwal'))><option value="">Pilih ruang</option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected(old('jadwal_ruang_id') == $room->id)>{{ $room->name }} ({{ $room->kapasitas }})</option>@endforeach</select></div>
                <div class="col-md-3"><label for="create-offering-day" class="form-label">Hari</label><select name="jadwal_hari" id="create-offering-day" class="form-select" required @disabled(!old('buat_jadwal'))><option value="">Pilih hari</option>@foreach(['Minggu','Senin','Selasa','Rabu','Kamis',"Jum'at",'Sabtu'] as $day)<option value="{{ $loop->index }}" @selected(old('jadwal_hari') !== null && (int) old('jadwal_hari') === $loop->index)>{{ $day }}</option>@endforeach</select></div>
                <div class="col-md-2"><label for="create-offering-start" class="form-label">Jam mulai</label><input type="time" name="jadwal_mulai" id="create-offering-start" value="{{ old('jadwal_mulai') }}" class="form-control" required @disabled(!old('buat_jadwal'))></div>
                <div class="col-md-2"><label for="create-offering-end" class="form-label">Jam selesai</label><input type="time" name="jadwal_selesai" id="create-offering-end" value="{{ old('jadwal_selesai') }}" class="form-control" required @disabled(!old('buat_jadwal'))></div>
            </div></div>
        </form></div>
        <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" form="create-offering-form" class="btn btn-primary">Simpan Penawaran</button></div>
    </div></div></div>
    <div class="modal fade" id="importOfferingModal" tabindex="-1" aria-labelledby="importOfferingModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <form method="POST" action="{{ route($prefix.'master.penawaran-import') }}" enctype="multipart/form-data">@csrf<input type="hidden" name="_form" value="import-offerings">
            <div class="modal-header"><h5 class="modal-title" id="importOfferingModalLabel">Import Penawaran Mata Kuliah — {{ $period->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                @if($errors->any() && old('_form') === 'import-offerings')<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <p class="text-muted">Gunakan hasil export sebagai template. Kode periode harus <strong>{{ $period->code }}</strong>. Semester dapat ditulis dengan angka atau Romawi. Kolom jadwal bersifat opsional; jika jadwal disertakan, Kode Ruang Jadwal, Hari Jadwal, Jam Mulai Jadwal, dan Jam Selesai Jadwal wajib diisi. Penawaran duplikat akan dilewati.</p>
                <label for="import-offering-file" class="form-label">File XLSX atau CSV</label>
                <input type="file" name="import" id="import-offering-file" class="form-control" accept=".xlsx,.csv" required>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Import Penawaran</button></div>
        </form>
    </div></div></div>
    @endif
    @php $activeFilterCount = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->count(); @endphp
    <div class="card"><div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="card-title mb-1">Daftar Penawaran — {{ $period?->name ?? 'Belum ada periode' }}</h5><span class="offering-result">Menampilkan {{ number_format($offerings->count()) }} penawaran mata kuliah</span></div><div class="d-flex flex-wrap gap-2">@if($period)<a class="btn btn-outline-success" href="{{ route($prefix.'master.penawaran-export', array_filter($filters)) }}"><i class="fa-solid fa-file-export me-1"></i> Export Hasil</a>@endif @if($canManage)<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#importOfferingModal"><i class="fa-solid fa-file-import me-1"></i> Import</button><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOfferingModal"><i class="fas fa-plus me-1"></i> Tambah Penawaran</button>@endif</div></div><div class="card-body">
        <div class="offering-filter mb-4">
            <div class="d-flex align-items-center gap-3 mb-3"><span class="offering-filter__icon"><i class="fas fa-filter"></i></span><div><h6 class="mb-1">Filter Penawaran</h6><small class="text-muted">Temukan penawaran berdasarkan informasi akademik yang dibutuhkan.</small></div>@if($activeFilterCount)<span class="badge bg-primary ms-auto">{{ $activeFilterCount }} filter aktif</span>@endif</div>
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-xl-4 col-lg-6"><label for="offering-search" class="form-label">Cari penawaran</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="search" name="q" id="offering-search" class="form-control" value="{{ $filters['q'] }}" placeholder="Kode atau nama mata kuliah"></div></div>
                <div class="col-xl-2 col-lg-3 col-md-6"><label for="offering-program" class="form-label">Program studi</label><select name="pstudi_id" id="offering-program" class="form-select"><option value="">Semua program</option>@foreach($programs as $item)<option value="{{ $item->id }}" @selected($filters['pstudi_id'] == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                <div class="col-xl-2 col-lg-3 col-md-6"><label for="offering-curriculum" class="form-label">Kurikulum</label><select name="kuri_id" id="offering-curriculum" class="form-select"><option value="">Semua kurikulum</option>@foreach($curricula as $item)<option value="{{ $item->id }}" @selected($filters['kuri_id'] == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                <div class="col-xl-2 col-lg-3 col-md-6"><label for="offering-class" class="form-label">Kelas</label><select name="kelas_id" id="offering-class" class="form-select"><option value="">Semua kelas</option>@foreach($classes as $item)<option value="{{ $item->id }}" @selected($filters['kelas_id'] == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                <div class="col-xl-2 col-lg-3 col-md-6"><label for="offering-semester" class="form-label">Semester</label><select name="semester" id="offering-semester" class="form-select"><option value="">Semua semester</option>@for($semester = 1; $semester <= 14; $semester++)<option value="{{ $semester }}" @selected($filters['semester'] === $semester)>Semester {{ $semester }}</option>@endfor</select></div>
                <div class="col-xl-4 col-lg-6"><label for="offering-lecturer" class="form-label">Dosen utama</label><select name="dosen_id" id="offering-lecturer" class="form-select"><option value="">Semua dosen utama</option>@foreach($lecturers as $item)<option value="{{ $item->id }}" @selected($filters['dosen_id'] == $item->id)>{{ $item->dsn_name }}</option>@endforeach</select></div>
                <div class="col-xl-3 col-lg-4 col-md-6"><label for="offering-schedule-status" class="form-label">Status jadwal</label><select name="status_jadwal" id="offering-schedule-status" class="form-select"><option value="">Semua status</option><option value="belum" @selected($filters['status_jadwal'] === 'belum')>Belum dijadwalkan</option><option value="sudah" @selected($filters['status_jadwal'] === 'sudah')>Sudah dijadwalkan</option></select></div>
                <div class="col-xl-4 col-lg-6 offering-filter__actions"><button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Terapkan Filter</button><a href="{{ route($prefix.'master.penawaran-index') }}" class="btn btn-outline-secondary"><i class="fas fa-rotate-left me-1"></i> Reset</a></div>
            </form>
        </div>
        <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Kode</th><th>Mata Kuliah</th><th>Kelas</th><th>Dosen</th><th>SKS</th><th>Kapasitas</th><th>Status Jadwal</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
                @forelse($offerings as $item)
                    <tr @class(['table-warning' => $item->wajib_dijadwalkan && $item->jadwal_mingguans_count === 0])>
                        <td>{{ $item->code }}</td>
                        <td>{{ $item->masterMataKuliah->name }}</td>
                        <td>{{ $item->kelas->name }}</td>
                        <td>{{ $item->dosenUtama->dsn_name }}</td>
                        <td>{{ $item->sks }}</td>
                        <td>{{ $item->krs_items_count }} / {{ $item->kapasitas }}</td>
                        <td>
                            @if(! $item->wajib_dijadwalkan)
                                <span class="badge bg-light-secondary text-secondary"><i class="fas fa-calendar-minus me-1"></i>Tidak perlu dijadwalkan</span>
                            @elseif($item->jadwal_mingguans_count === 0)
                                <span class="badge bg-warning text-dark"><i class="fas fa-calendar-xmark me-1"></i>Belum dijadwalkan</span>
                            @else
                                <span class="badge bg-light-success text-success"><i class="fas fa-calendar-check me-1"></i>Sudah dijadwalkan</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($canManage && $item->wajib_dijadwalkan && $item->jadwal_mingguans_count === 0)
                                <a href="{{ route($prefix.'master.jadwal-mingguan-index', ['penawaran_id' => $item->id, 'buat' => 1]) }}" class="btn btn-sm btn-primary me-1">
                                    <i class="fas fa-calendar-plus me-1"></i> Jadwalkan
                                </a>
                            @endif
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cog me-1"></i> Aksi
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route($prefix.'master.penawaran-grades', $item) }}">
                                            <i class="fas fa-graduation-cap text-success me-2"></i>Kelola Nilai
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route($prefix.'master.penawaran-participants', $item) }}">
                                            <i class="fas fa-users text-info me-2"></i>Lihat Peserta
                                        </a>
                                    </li>
                                    @if($canManage)
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                data-bs-target="#editOffering{{ $item->id }}">
                                                <i class="fas fa-edit text-primary me-2"></i>Edit Penawaran
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route($prefix.'master.penawaran-destroy', $item) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Hapus penawaran?')">
                                                    <i class="fas fa-trash-alt me-2"></i>Hapus Penawaran
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">Belum ada penawaran.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($canManage) @foreach($offerings as $item)
        <div class="modal fade" id="editOffering{{ $item->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST" action="{{ route($prefix.'master.penawaran-update', $item) }}">@csrf @method('PATCH')
            <div class="modal-header"><h5 class="modal-title">Edit {{ $item->masterMataKuliah->name }} — {{ $item->kelas->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3">
                <input type="hidden" name="master_mata_kuliah_id" value="{{ $item->master_mata_kuliah_id }}"><input type="hidden" name="pstudi_id" value="{{ $item->pstudi_id }}"><input type="hidden" name="kuri_id" value="{{ $item->kuri_id }}"><input type="hidden" name="kelas_id" value="{{ $item->kelas_id }}"><input type="hidden" name="prasyarat_master_id" value="{{ $item->prasyarat_master_id }}">
                @if($item->hasParticipants())<div class="col-12"><div class="alert alert-warning mb-0">Penawaran telah dipakai KRS. Identitas mata kuliah, kelas, kurikulum, SKS, dan prasyarat dikunci.</div></div>@endif
                <div class="col-md-6"><label class="form-label">Dosen utama</label><select name="dosen_utama_id" class="form-select" required>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($lecturer->id === $item->dosen_utama_id)>{{ $lecturer->dsn_name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Pendamping pertama</label><select name="dosen_pendamping_1_id" class="form-select"><option value="">-</option>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($lecturer->id === $item->dosen_pendamping_1_id)>{{ $lecturer->dsn_name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Pendamping kedua</label><select name="dosen_pendamping_2_id" class="form-select"><option value="">-</option>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($lecturer->id === $item->dosen_pendamping_2_id)>{{ $lecturer->dsn_name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" value="{{ $item->kapasitas }}" min="1" class="form-control" required></div><div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control">{{ $item->deskripsi }}</textarea></div>
            </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan perubahan</button></div>
        </form></div></div></div>
        @endforeach @endif
    </div></div>
</section>
@if($errors->any() && old('_form') === 'create-offering')<script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('createOfferingModal')).show());</script>@endif
@if($errors->any() && old('_form') === 'import-offerings')<script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('importOfferingModal')).show());</script>@endif
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkbox = document.getElementById('create-offering-with-schedule');
    const fields = document.getElementById('create-offering-schedule-fields');
    if (!checkbox || !fields) return;
    const toggleScheduleFields = () => {
        fields.classList.toggle('d-none', !checkbox.checked);
        fields.querySelectorAll('input, select').forEach((field) => field.disabled = !checkbox.checked);
    };
    checkbox.addEventListener('change', toggleScheduleFields);
    toggleScheduleFields();
});
</script>
@endsection
