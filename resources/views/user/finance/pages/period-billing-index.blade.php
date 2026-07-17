@extends('base.base-dash-index')

@section('menu', 'Keuangan Periode')
@section('submenu', 'Tagihan dan Laporan')
@section('urlmenu', '#')
@section('subdesc', 'Penerbitan tagihan dan laporan keuangan untuk periode yang sedang dipilih')

@section('content')
    <div class="mb-3"><a class="btn btn-outline-primary" href="{{ route($prefix.'period-opening.index') }}">Dashboard Pembukaan Periode</a></div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="alert alert-info">
        Periode laporan: <strong>{{ $period->name ?? ($period->year_start.' / '.$period->year_end.' - '.$period->term_label) }}</strong>.
        Seluruh angka di halaman ini dibatasi pada periode tersebut.
    </div>

    <div class="row">
        @foreach ([
            'Total tagihan' => $report['ringkasan']['total_tagihan'],
            'Pembayaran' => $report['ringkasan']['total_pembayaran'],
            'Tunggakan' => $report['ringkasan']['total_tunggakan'],
        ] as $label => $amount)
            <div class="col-md-4"><div class="card"><div class="card-body"><small>{{ $label }}</small><h4>Rp {{ number_format($amount, 0, ',', '.') }}</h4></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header"><h5>Buat Template Tagihan</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route($prefix.'billing-period.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4"><label class="form-label">Nama</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
                <div class="col-md-2"><label class="form-label">Jenis</label><input class="form-control" name="jenis" value="{{ old('jenis', 'ukt') }}" required></div>
                <div class="col-md-3"><label class="form-label">Nominal</label><input class="form-control" type="number" min="1" name="nominal" value="{{ old('nominal') }}" required></div>
                <div class="col-md-3"><label class="form-label">Jenis target</label><select class="form-select" name="target_type" required><option value="mahasiswa">Mahasiswa</option><option value="prodi">Program Studi</option><option value="proku">Program Kuliah</option><option value="kelompok">Kelompok status</option></select></div>
                <div class="col-md-3"><label class="form-label">Terbit</label><input class="form-control" type="date" name="tanggal_terbit" value="{{ old('tanggal_terbit', now()->toDateString()) }}" required></div>
                <div class="col-md-3"><label class="form-label">Jatuh tempo</label><input class="form-control" type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo') }}" required></div>
                <div class="col-md-3"><label class="form-label">Mahasiswa</label><select class="form-select" name="target_mahasiswa_id"><option value="">-</option>@foreach ($mahasiswas as $student)<option value="{{ $student->id }}">{{ $student->mhs_name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Program Studi</label><select class="form-select" name="target_prodi_id"><option value="">-</option>@foreach ($prodis as $prodi)<option value="{{ $prodi->id }}">{{ $prodi->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Program Kuliah</label><select class="form-select" name="target_proku_id"><option value="">-</option>@foreach ($prokus as $proku)<option value="{{ $proku->id }}">{{ $proku->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Kelompok status</label><select class="form-select" name="kelompok_target"><option value="">-</option><option value="semua">Semua</option>@foreach ($academicStatuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-3 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="wajib_lunas_krs" value="0"><input class="form-check-input" type="checkbox" name="wajib_lunas_krs" value="1" id="required-krs"><label class="form-check-label" for="required-krs">Wajib lunas sebelum KRS</label></div></div>
                <div class="col-12"><small class="text-muted">Isi hanya satu kolom target sesuai jenis target yang dipilih.</small><br><button class="btn btn-primary mt-2">Simpan template</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>Template dan Penerbitan</h5></div>
        <div class="card-body table-responsive"><table class="table"><thead><tr><th>Template</th><th>Nominal</th><th>Target</th><th>Aksi</th></tr></thead><tbody>
            @forelse ($templates as $template)
                <tr><td>{{ $template->name }}<br><small>{{ $template->jenis }}{{ $template->wajib_lunas_krs ? ' · wajib KRS' : '' }}</small></td><td>Rp {{ number_format($template->nominal, 0, ',', '.') }}</td><td>{{ $template->target_type }}</td><td class="d-flex gap-2"><form method="POST" action="{{ route($prefix.'billing-period.preview', $template) }}">@csrf<button class="btn btn-sm btn-outline-primary">Pratinjau</button></form><form method="POST" action="{{ route($prefix.'billing-period.issue', $template) }}" onsubmit="return confirm('Terbitkan tagihan kepada seluruh calon?')">@csrf<button class="btn btn-sm btn-success">Terbitkan</button></form></td></tr>
            @empty<tr><td colspan="4" class="text-center text-muted">Belum ada template.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    @if ($preview)
        <div class="alert alert-warning"><strong>Pratinjau {{ $previewTemplate->name }}:</strong> {{ $preview['calon'] }} mahasiswa, total Rp {{ number_format($preview['total_nominal'], 0, ',', '.') }}. Pratinjau tidak mengubah data.</div>
    @endif

    <div class="row">
        @foreach (['per_prodi' => 'Program Studi', 'per_proku' => 'Program Kuliah', 'per_status_mahasiswa' => 'Status Mahasiswa'] as $key => $title)
            <div class="col-lg-4"><div class="card"><div class="card-header"><h6>Rincian {{ $title }}</h6></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>{{ $title }}</th><th>Tagihan</th><th>Tunggakan</th></tr></thead><tbody>@forelse ($report[$key] as $row)<tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['tagihan'], 0, ',', '.') }}</td><td>{{ number_format($row['tunggakan'], 0, ',', '.') }}</td></tr>@empty<tr><td colspan="3">Belum ada data.</td></tr>@endforelse</tbody></table></div></div></div>
        @endforeach
    </div>

    <div class="card"><div class="card-header"><h5>Override Administrasi KRS</h5></div><div class="card-body"><form method="POST" id="override-form" class="row g-2" action="">@csrf<div class="col-md-4"><select class="form-select" id="override-registration" required><option value="">Pilih mahasiswa</option>@foreach ($registrations as $registration)<option value="{{ $registration->id }}" data-url="{{ route($prefix.'billing-period.override', $registration) }}">{{ $registration->mahasiswa?->mhs_name }} · {{ $registration->academic_status_label }}</option>@endforeach</select></div><div class="col-md-5"><input class="form-control" name="alasan" minlength="10" placeholder="Alasan override (minimal 10 karakter)" required></div><div class="col-md-2"><input class="form-control" type="date" name="berlaku_sampai"></div><div class="col-md-1"><button class="btn btn-warning">Catat</button></div></form><small class="text-muted">Hanya Web Administrator dan Departemen Finance; alasan serta aktor disimpan untuk audit.</small></div></div>
    <script>
        document.getElementById('override-form').addEventListener('submit', function (event) {
            const id = document.getElementById('override-registration').value;
            if (!id) { event.preventDefault(); return; }
            this.action = document.getElementById('override-registration').selectedOptions[0].dataset.url;
        });
    </script>
@endsection
