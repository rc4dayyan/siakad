@extends('base.base-dash-index')
@section('title', 'Kartu Rencana Studi')
@section('menu', 'Kartu Rencana Studi')
@section('submenu', 'KRS')
@section('urlmenu', '#')
@section('subdesc', 'Susun dan ajukan rencana studi periode aktif')
@section('content')
<section class="section">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(!$registration)<div class="alert alert-warning">Registrasi mahasiswa untuk periode aktif belum tersedia.</div>@else
<div class="card mb-3"><div class="card-header d-flex justify-content-between"><div><h5 class="mb-1">Draft KRS — {{ $period->name }}</h5><span class="badge bg-{{ $krs->status === 'approved' ? 'success' : ($krs->status === 'rejected' ? 'danger' : 'secondary') }}">{{ strtoupper($krs->status) }}</span></div><a href="{{ route('mahasiswa.akademik.krs-print') }}" target="_blank" class="btn btn-outline-secondary">Cetak KRS</a></div><div class="card-body">
<table class="table"><thead><tr><th>Mata kuliah</th><th>Kelas</th><th>SKS</th><th></th></tr></thead><tbody>@forelse($krs->items as $item)<tr><td>{{ $item->penawaranMataKuliah->masterMataKuliah->name }}</td><td>{{ $item->penawaranMataKuliah->kelas?->name }}</td><td>{{ $item->sks }}</td><td>@if($krs->isEditable())<form method="POST" action="{{ route('mahasiswa.akademik.krs-remove', $item->id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>@endif</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">Draft masih kosong.</td></tr>@endforelse</tbody><tfoot><tr><th colspan="2">Total</th><th>{{ $krs->total_sks }} / {{ $registration->batas_sks }} SKS</th><th></th></tr></tfoot></table>
@if($krs->status === 'rejected')<div class="alert alert-warning">Catatan dosen wali: {{ $krs->catatan_keputusan }}</div>@endif
@if($krs->isEditable())<form method="POST" action="{{ route('mahasiswa.akademik.krs-submit') }}">@csrf<textarea name="catatan_mahasiswa" class="form-control mb-2" placeholder="Catatan untuk dosen wali (opsional)">{{ old('catatan_mahasiswa') }}</textarea><button class="btn btn-primary" onclick="return confirm('Ajukan KRS kepada dosen wali?')">Ajukan KRS</button></form>@endif
</div></div>
@php
    $selectedOfferingIds = collect(old('penawaran_ids', []))->map(fn ($id) => (int) $id)->all();
    $selectableOfferings = $offerings->reject(fn ($item) => $krs->items->contains('penawaran_mata_kuliah_id', $item->id));
@endphp
<form method="POST" action="{{ route('mahasiswa.akademik.krs-add-many') }}" id="krs-add-many-form">
    @csrf
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Penawaran untuk kelas {{ $registration->kelas?->name }}</h5>
            @if($krs->isEditable())
                <button type="submit" class="btn btn-primary" id="krs-add-selected" disabled>
                    Tambah yang dipilih (<span id="krs-selected-count">0</span>)
                </button>
            @endif
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th style="width: 42px">
                            <input type="checkbox" class="form-check-input" id="krs-select-all"
                                aria-label="Pilih semua penawaran"
                                @disabled(! $krs->isEditable() || $selectableOfferings->isEmpty())>
                        </th>
                        <th>Mata kuliah</th><th>Dosen</th><th>Prasyarat</th><th>SKS</th><th>Kapasitas</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($offerings as $item)
                        @php $alreadyAdded = $krs->items->contains('penawaran_mata_kuliah_id', $item->id); @endphp
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input krs-offering-checkbox"
                                    name="penawaran_ids[]" value="{{ $item->id }}"
                                    aria-label="Pilih {{ $item->masterMataKuliah->name }}"
                                    @checked(in_array($item->id, $selectedOfferingIds, true) && ! $alreadyAdded)
                                    @disabled(! $krs->isEditable() || $alreadyAdded)>
                            </td>
                            <td>{{ $item->masterMataKuliah->name }}</td>
                            <td>{{ $item->dosenUtama->dsn_name }}</td>
                            <td>{{ $item->prasyaratMaster?->name ?? '-' }}</td>
                            <td>{{ $item->sks }}</td>
                            <td>{{ $item->krsItems()->count() }} / {{ $item->kapasitas }}</td>
                            <td>
                                @if($alreadyAdded)
                                    <span class="badge bg-success">Sudah ditambahkan</span>
                                @else
                                    <span class="badge bg-secondary">Belum dipilih</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Belum ada penawaran untuk kelas ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('krs-select-all');
    const checkboxes = Array.from(document.querySelectorAll('.krs-offering-checkbox:not(:disabled)'));
    const submitButton = document.getElementById('krs-add-selected');
    const selectedCount = document.getElementById('krs-selected-count');

    const updateSelection = () => {
        const count = checkboxes.filter((checkbox) => checkbox.checked).length;
        if (selectedCount) selectedCount.textContent = count;
        if (submitButton) submitButton.disabled = count === 0;
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && count === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
        updateSelection();
    });
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
    updateSelection();
});
</script>
@endif
</section>
@endsection
