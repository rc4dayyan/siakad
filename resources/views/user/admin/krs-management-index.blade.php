@extends('base.base-dash-index')

@section('menu', 'KRS Mahasiswa')
@section('submenu', $canManageKrs ? 'Kelola KRS Mahasiswa' : 'Persetujuan KRS Mahasiswa')
@section('urlmenu', '#')
@section('subdesc', $canManageKrs ? 'Perubahan administratif KRS dengan alasan, audit, dan notifikasi' : 'Persetujuan KRS yang telah diajukan mahasiswa')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="alert alert-info">Periode: <strong>{{ $period->name }}</strong>. @if ($canManageKrs) Perubahan administratif dapat dilakukan di luar jadwal KRS. Penambahan tetap memeriksa status registrasi, kewajiban keuangan, program studi, prasyarat, kapasitas, mata kuliah yang sudah lulus, dan batas SKS. @else Administrator hanya dapat menyetujui KRS berstatus diajukan. Periksa mata kuliah dan total SKS sebelum memberikan persetujuan. @endif</div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="mb-1">Update KRS melalui Excel</h5><small>Kolom wajib: NIM, Nama, Aksi. Maksimal 100 baris beraksi dan 2 MB.</small></div>
            <a href="{{ route($prefix.'krs-management.import-template') }}" class="btn btn-outline-success"><i class="fa-solid fa-file-excel"></i> Unduh template</a>
        </div>
        <div class="card-body">
            <div class="alert alert-secondary mb-3">Nama harus cocok dengan NIM pada periode <strong>{{ $period->name }}</strong>. Baris dengan aksi kosong diabaikan.</div>
            <div class="table-responsive mb-3"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Nilai kolom Aksi</th><th>Keterangan</th><th>Syarat status</th></tr></thead><tbody>
                @if ($canApproveKrs)<tr><td><code>setujui</code></td><td>Menyetujui dan mengunci KRS. Mahasiswa serta dosen wali menerima notifikasi.</td><td><code>submitted</code> / Diajukan</td></tr>@endif
                @if ($canManageKrs)<tr><td><code>buka_kembali</code></td><td>Membuka KRS agar dapat diperbaiki dan diajukan kembali. Alasan minimal 10 karakter wajib diisi.</td><td><code>submitted</code>, <code>approved</code>, atau <code>locked</code></td></tr>@endif
                <tr><td><em>kosong</em></td><td>Baris mahasiswa tidak diproses.</td><td>Semua status</td></tr>
            </tbody></table></div>
            <form method="POST" action="{{ route($prefix.'krs-management.import-preview') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-9"><label class="form-label" for="krs-import-file">File XLSX atau CSV</label><input class="form-control" id="krs-import-file" type="file" name="import" accept=".xlsx,.csv" required></div>
                <div class="col-md-3"><button class="btn btn-primary w-100">Validasi dan pratinjau</button></div>
            </form>

            @if ($importPreview)
                <hr>
                <h6>Pratinjau update ({{ count($importPreview['prepared']) }} KRS)</h6>
                <div class="table-responsive mb-3"><table class="table table-sm table-striped"><thead><tr><th>Baris</th><th>NIM</th><th>Nama</th><th>Status sekarang</th><th>Aksi</th></tr></thead><tbody>
                    @foreach ($importPreview['prepared'] as $row)
                        <tr><td>{{ $row['row'] }}</td><td>{{ $row['nim'] }}</td><td>{{ $row['nama'] }}</td><td>{{ strtoupper($row['status']) }}</td><td>{{ $row['aksi_label'] }}</td></tr>
                    @endforeach
                </tbody></table></div>
                <form method="POST" action="{{ route($prefix.'krs-management.import-execute') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="token" value="{{ $importPreview['token'] }}">
                    <div class="col-md-9"><label class="form-label" for="krs-import-note">Catatan/alasan</label><input class="form-control" id="krs-import-note" name="catatan" maxlength="2000" placeholder="Wajib minimal 10 karakter jika terdapat aksi buka_kembali"></div>
                    <div class="col-md-3"><button class="btn btn-success w-100" onclick="return confirm('Jalankan seluruh update KRS pada pratinjau ini?')">Jalankan update Excel</button></div>
                </form>
                <small class="text-muted">Pratinjau berlaku selama 15 menit. Seluruh file dibatalkan jika satu baris berubah atau tidak lagi memenuhi syarat saat dieksekusi.</small>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            @if (! $selected)
                <div class="card"><div class="card-body text-center text-muted py-5">Pilih mahasiswa untuk mengelola KRS.</div></div>
            @else
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-start"><div><h5 class="mb-1">{{ $selected->mahasiswa->mhs_name }}</h5><small>{{ $selected->mahasiswa->mhs_nim }} · {{ $selected->kelas?->name }} · Dosen wali: {{ $selected->dosenWali?->dsn_name ?? '-' }}</small></div><span class="badge bg-{{ $krs->isEditable() ? 'warning' : 'success' }}">{{ strtoupper($krs->status) }}</span></div>
                    <div class="card-body">
                        @if ($canApproveKrs && $krs->status === \App\Models\Krs::STATUS_SUBMITTED)
                            <div class="alert alert-primary">KRS menunggu keputusan. Persetujuan administrator akan mengunci KRS dan dicatat dalam audit.</div>
                            <form method="POST" action="{{ route($prefix.'krs-management.approve', $krs) }}" class="row g-2 mb-3">@csrf @method('PATCH')<div class="col-md-9"><textarea class="form-control" name="catatan" maxlength="2000" rows="2" placeholder="Catatan persetujuan (opsional)">{{ old('catatan') }}</textarea></div><div class="col-md-3"><button class="btn btn-success w-100" onclick="return confirm('Setujui dan kunci KRS ini?')">Setujui KRS</button></div></form>
                        @endif

                        @if ($canManageKrs && ! $krs->isEditable())
                            <div class="alert alert-warning">KRS telah diajukan atau disetujui. Buka kembali sebelum mengubah isinya; mahasiswa harus mengajukan ulang kepada dosen wali.</div>
                            <form method="POST" action="{{ route($prefix.'krs-management.reopen', $krs) }}" class="row g-2 mb-3">@csrf @method('PATCH')<div class="col-md-9"><input class="form-control" name="alasan" minlength="10" maxlength="1000" placeholder="Alasan membuka kembali KRS (minimal 10 karakter)" required></div><div class="col-md-3"><button class="btn btn-warning w-100" onclick="return confirm('Buka kembali KRS ini?')">Buka kembali</button></div></form>
                        @endif

                        <h6>Mata kuliah dalam KRS</h6>
                        <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Mata kuliah</th><th>SKS</th><th>Aksi</th></tr></thead><tbody>
                            @forelse ($krs->items as $item)
                                <tr><td>{{ $item->penawaranMataKuliah->masterMataKuliah->name }}</td><td>{{ $item->sks }}</td><td>@if ($canManageKrs && $krs->isEditable())<form method="POST" action="{{ route($prefix.'krs-management.remove', $item) }}" class="d-flex gap-1">@csrf @method('DELETE')<input class="form-control form-control-sm" name="alasan" minlength="10" maxlength="1000" placeholder="Alasan penghapusan" required><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus mata kuliah dari KRS?')">Hapus</button></form>@else<span class="text-muted">{{ $krs->isEditable() ? 'Hanya lihat' : 'Dikunci' }}</span>@endif</td></tr>
                            @empty<tr><td colspan="3" class="text-center text-muted">KRS belum memiliki mata kuliah.</td></tr>@endforelse
                        </tbody><tfoot><tr><th>Total</th><th>{{ $krs->total_sks }} SKS</th><th>Batas {{ $selected->batas_sks }} SKS</th></tr></tfoot></table></div>

                        @if ($canManageKrs && $krs->isEditable())
                            <hr><h6>Tambahkan mata kuliah</h6>
                            <form method="POST" action="{{ route($prefix.'krs-management.add', $selected) }}" class="row g-2">@csrf
                                <div class="col-md-6"><select class="form-select" name="penawaran_mata_kuliah_id" required><option value="">Pilih penawaran</option>@foreach ($offerings as $offering)<option value="{{ $offering->id }}">{{ $offering->masterMataKuliah->name }} · {{ $offering->sks }} SKS · {{ $offering->dosenUtama?->dsn_name }}</option>@endforeach</select></div>
                                <div class="col-md-4"><input class="form-control" name="alasan" minlength="10" maxlength="1000" placeholder="Alasan penambahan" required></div>
                                <div class="col-md-2"><button class="btn btn-primary w-100">Tambahkan</button></div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><h5 class="mb-0">Pilih mahasiswa</h5><a href="{{ route($prefix.'krs-management.import-template', ['q' => $search, 'status' => $status]) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel"></i> Unduh daftar Excel</a></div>
                <div class="card-body">
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-md-7"><input class="form-control" name="q" value="{{ $search }}" placeholder="Cari nama atau NIM"></div>
                        <div class="col-md-5"><select class="form-select" name="status" onchange="this.form.submit()"><option value="">Semua status</option><option value="submitted" @selected($status === 'submitted')>Diajukan</option><option value="approved" @selected($status === 'approved')>Disetujui</option><option value="rejected" @selected($status === 'rejected')>Ditolak</option><option value="draft" @selected($status === 'draft')>Draft</option><option value="locked" @selected($status === 'locked')>Dikunci</option><option value="none" @selected($status === 'none')>Belum ada KRS</option></select></div>
                        <div class="col-12"><button class="btn btn-outline-primary w-100">Terapkan pencarian dan filter</button></div>
                    </form>

                    <form method="POST" action="{{ route($prefix.'krs-management.bulk') }}" id="krs-bulk-form">
                        @csrf @method('PATCH')
                        <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th><input type="checkbox" id="select-all-krs" aria-label="Pilih semua KRS pada halaman"></th><th>Mahasiswa</th><th>Kelas</th><th>Status KRS</th><th></th></tr></thead><tbody>
                            @forelse ($registrations as $registration)
                                @php
                                    $rowKrs = $registration->krs;
                                    $canSelectForApproval = $canApproveKrs && $rowKrs?->status === \App\Models\Krs::STATUS_SUBMITTED;
                                    $canSelectForReopen = $canManageKrs && $rowKrs && ! $rowKrs->isEditable();
                                @endphp
                                <tr><td>@if ($canSelectForApproval || $canSelectForReopen)<input class="krs-bulk-item" type="checkbox" name="krs_ids[]" value="{{ $rowKrs->id }}" aria-label="Pilih KRS {{ $registration->mahasiswa?->mhs_name }}">@else<span class="text-muted">—</span>@endif</td><td>{{ $registration->mahasiswa?->mhs_name }}<br><small>{{ $registration->mahasiswa?->mhs_nim }}</small></td><td>{{ $registration->kelas?->name ?? '-' }}</td><td><span class="badge bg-secondary">{{ strtoupper($rowKrs?->status ?? 'belum dibuat') }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route($prefix.'krs-management.index', ['registration' => $registration->id, 'q' => $search, 'status' => $status]) }}">{{ $canManageKrs ? 'Kelola' : 'Tinjau' }}</a></td></tr>
                            @empty<tr><td colspan="5" class="text-center text-muted">Registrasi mahasiswa tidak ditemukan.</td></tr>@endforelse
                        </tbody></table></div>

                        <div class="border rounded p-3 mb-3">
                            <h6>Aksi massal</h6>
                            <div class="row g-2">
                                <div class="col-md-4"><select class="form-select" name="action" required><option value="">Pilih aksi</option>@if ($canApproveKrs)<option value="approve">Setujui dan kunci</option>@endif @if ($canManageKrs)<option value="reopen">Buka kembali</option>@endif</select></div>
                                <div class="col-md-5"><input class="form-control" name="catatan" maxlength="2000" placeholder="Catatan; wajib min. 10 karakter untuk buka kembali"></div>
                                <div class="col-md-3"><button class="btn btn-primary w-100" onclick="return confirm('Jalankan aksi pada seluruh KRS yang dipilih?')">Jalankan</button></div>
                            </div>
                            <small class="text-muted">Aksi dibatalkan seluruhnya jika salah satu KRS tidak memenuhi syarat.</small>
                        </div>
                    </form>
                    {{ $registrations->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('select-all-krs')?.addEventListener('change', function () {
            document.querySelectorAll('.krs-bulk-item').forEach((checkbox) => checkbox.checked = this.checked);
        });
    </script>
@endsection
