@extends('base.base-dash-index')

@section('title', 'Data Master Periode Akademik - Siakad')
@section('menu', 'Data Master Periode Akademik')
@section('submenu', 'Daftar Data Periode Akademik')
@section('submenu0', 'Tambah Data Periode Akademik')
@section('urlmenu', '#')
@section('subdesc', 'Halaman untuk mengelola periode Ganjil, Genap, atau Semester Pendek dalam suatu tahun akademik')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Data periode belum dapat diproses.</strong>
            <ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-3 d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ route($prefix.'period-opening.wizard') }}">Wizard Periode Baru</a>
        <a class="btn btn-outline-primary" href="{{ route($prefix.'period-opening.index') }}">Dashboard Pembukaan Periode</a>
        <a class="btn btn-outline-secondary" href="{{ route($prefix.'master.tahun-akademik-index') }}"><i class="fas fa-calendar-days me-1"></i> Kelola Tahun Akademik</a>
    </div>

    @if ($academicYears->isEmpty())
        <div class="alert alert-warning">
            <i class="fas fa-triangle-exclamation me-1"></i>
            Buat Tahun Akademik terlebih dahulu sebelum menambahkan Periode Akademik.
            <a href="{{ route($prefix.'master.tahun-akademik-index') }}" class="alert-link">Buka Tahun Akademik</a>
        </div>
    @endif
<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">@yield('submenu')</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTakaModal" @disabled($academicYears->isEmpty())><i class="fas fa-plus me-1"></i> Tambah Periode</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table1">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tahun Akademik</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Rentang</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($taka as $key => $item)
                                @php
                                    $badge = match ($item->status) {
                                        'active' => 'bg-success',
                                        'closed' => 'bg-secondary',
                                        'archived' => 'bg-dark',
                                        default => 'bg-warning text-dark',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <strong>{{ $item->tahunAkademik?->name ?? ($item->year_start.'/'.$item->year_end) }}</strong><br>
                                        <small class="text-muted">{{ $item->tahunAkademik?->code ?? 'Belum terhubung' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $item->name }}</strong><br>
                                        <small class="text-muted">{{ $item->code }}</small>
                                    </td>
                                    <td>{{ $item->term_label }}</td>
                                    <td>
                                        {{ $item->year_start }}–{{ $item->year_end ?? '?' }}<br>
                                        <small class="text-muted">
                                            {{ $item->starts_at?->format('d-m-Y') ?? '-' }} s.d.
                                            {{ $item->ends_at?->format('d-m-Y') ?? '-' }}
                                        </small>
                                    </td>
                                    <td><span class="badge {{ $badge }}">{{ $item->status_label }}</span></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            @if ($item->status === 'draft')
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#updateTaka{{ $item->id }}"
                                                    title="Ubah periode">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route($prefix.'master.taka-activate', $item->code) }}"
                                                    method="POST" onsubmit="return confirm('Aktifkan periode ini? Periode aktif sebelumnya akan ditutup.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Aktifkan periode">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route($prefix.'master.taka-destroy', $item->code) }}"
                                                    method="POST" onsubmit="return confirm('Hapus periode draft ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus periode">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Terkunci</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">Belum ada periode akademik.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<form action="{{ route($prefix.'master.taka-store') }}" method="POST">
    @csrf
    <div class="modal fade" id="createTakaModal" tabindex="-1" aria-labelledby="createTakaModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="createTakaModalLabel">Tambah Data Periode Akademik</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">
            @include('user.admin.master.partials.periode-akademik-form', ['formId' => 'create-taka', 'formMarker' => 'create-taka'])
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan sebagai Draft</button></div>
    </div></div></div>
</form>

@foreach ($taka->where('status', 'draft') as $item)
    <form action="{{ route($prefix.'master.taka-update', $item->code) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="modal fade" id="updateTaka{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit {{ $item->name }}</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @include('user.admin.master.partials.periode-akademik-form', ['formId' => 'edit-taka-'.$item->id, 'formMarker' => 'edit-taka-'.$item->id, 'item' => $item])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-outline-primary">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endforeach
@endsection
@section('custom-js')
    @if ($errors->any() && old('_form') === 'create-taka')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('createTakaModal')).show());</script>
    @elseif ($errors->any() && str_starts_with((string) old('_form'), 'edit-taka-'))
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('updateTaka{{ str_replace('edit-taka-', '', old('_form')) }}')).show());</script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form').forEach((form) => {
                const year = form.querySelector('.academic-year-select');
                const term = form.querySelector('.academic-term-select');
                const name = form.querySelector('.academic-period-name');
                const code = form.querySelector('.academic-period-code');

                if (!year || !term || !name || !code) return;

                const suggestIdentity = () => {
                    const option = year.options[year.selectedIndex];
                    const yearStart = option?.dataset.yearStart;
                    const yearEnd = option?.dataset.yearEnd;
                    const termValue = term.value;
                    if (!yearStart || !yearEnd || !termValue) return;

                    const termLabel = termValue === 'pendek' ? 'Semester Pendek' : termValue.charAt(0).toUpperCase() + termValue.slice(1);
                    name.value = `${yearStart}/${yearEnd} ${termLabel}`;
                    code.value = `${yearStart}-${yearEnd}-${termValue.toUpperCase()}`;
                };

                year.addEventListener('change', suggestIdentity);
                term.addEventListener('change', suggestIdentity);
            });
        });
    </script>
@endsection
