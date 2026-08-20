@php
    $current = $item ?? null;
    $useOld = old('_form') === $formMarker;
@endphp

<input type="hidden" name="_form" value="{{ $formMarker }}">

<div class="row g-3">
    <div class="col-12">
        <label for="name-{{ $formId }}" class="form-label">Nama Tahun Akademik</label>
        <input type="text" name="name" id="name-{{ $formId }}" class="form-control"
            value="{{ $useOld ? old('name') : $current?->name }}" placeholder="Contoh: Tahun Akademik 2026/2027" maxlength="255" required>
        @if ($useOld) @error('name')<small class="text-danger">{{ $message }}</small>@enderror @endif
    </div>
    <div class="col-md-6">
        <label for="code-{{ $formId }}" class="form-label">Kode</label>
        <input type="text" name="code" id="code-{{ $formId }}" class="form-control text-uppercase"
            value="{{ $useOld ? old('code') : $current?->code }}" placeholder="Contoh: 2026-2027" maxlength="32" required>
        <small class="text-muted">Gunakan kode unik tanpa spasi.</small>
        @if ($useOld) @error('code')<small class="text-danger d-block">{{ $message }}</small>@enderror @endif
    </div>
    <div class="col-md-3">
        <label for="year-start-{{ $formId }}" class="form-label">Tahun Mulai</label>
        <input type="number" name="year_start" id="year-start-{{ $formId }}" class="form-control"
            value="{{ $useOld ? old('year_start') : ($current?->year_start ?? now()->year) }}" min="2000" max="2100" required>
        @if ($useOld) @error('year_start')<small class="text-danger">{{ $message }}</small>@enderror @endif
    </div>
    <div class="col-md-3">
        <label for="year-end-{{ $formId }}" class="form-label">Tahun Selesai</label>
        <input type="number" name="year_end" id="year-end-{{ $formId }}" class="form-control"
            value="{{ $useOld ? old('year_end') : ($current?->year_end ?? now()->year + 1) }}" min="2001" max="2101" required>
        @if ($useOld) @error('year_end')<small class="text-danger">{{ $message }}</small>@enderror @endif
    </div>
</div>

@if ($current?->periode_akademiks_count > 0)
    <div class="alert alert-light-warning mt-3 mb-0">
        <i class="fas fa-lock me-1"></i>
        Rentang tahun dikunci karena sudah digunakan oleh {{ $current->periode_akademiks_count }} periode. Nama dan kode masih dapat diperbarui.
    </div>
@endif
