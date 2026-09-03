@php
    $current = $item ?? null;
    $useOld = old('_form') === $formMarker;
    $selectedYearId = $useOld ? old('tid') : ($current?->tid ?? $selectedAcademicYearId ?? null);
    $selectedTerm = $useOld ? old('term') : $current?->term;
@endphp

<input type="hidden" name="_form" value="{{ $formMarker }}">

<div class="row g-3">
    <div class="col-12">
        <label for="academic-year-{{ $formId }}" class="form-label">Tahun Akademik</label>
        <select name="tid" id="academic-year-{{ $formId }}" class="form-select academic-year-select" required>
            <option value="">Pilih tahun akademik</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}"
                    data-year-start="{{ $academicYear->year_start }}"
                    data-year-end="{{ $academicYear->year_end }}"
                    @selected((string) $selectedYearId === (string) $academicYear->id)>
                    {{ $academicYear->name }} ({{ $academicYear->year_start }}/{{ $academicYear->year_end }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Rentang tahun periode akan mengikuti tahun akademik yang dipilih.</small>
        @if ($useOld) @error('tid')<small class="text-danger d-block">{{ $message }}</small>@enderror @endif
    </div>

    <div class="col-md-5">
        <label for="term-{{ $formId }}" class="form-label">Jenis Periode</label>
        <select name="term" id="term-{{ $formId }}" class="form-select academic-term-select" required>
            <option value="">Pilih jenis periode</option>
            @foreach ($terms as $term)
                <option value="{{ $term }}" @selected($selectedTerm === $term)>
                    {{ $term === 'pendek' ? 'Semester Pendek' : ucfirst($term) }}
                </option>
            @endforeach
        </select>
        @if ($useOld) @error('term')<small class="text-danger d-block">{{ $message }}</small>@enderror @endif
    </div>

    <div class="col-md-7">
        <label for="name-{{ $formId }}" class="form-label">Nama Periode Akademik</label>
        <input type="text" name="name" id="name-{{ $formId }}" class="form-control academic-period-name"
            value="{{ $useOld ? old('name') : $current?->name }}" placeholder="Contoh: 2026/2027 Genap" maxlength="255" required>
        @if ($useOld) @error('name')<small class="text-danger d-block">{{ $message }}</small>@enderror @endif
    </div>

    <div class="col-md-6">
        <label for="code-{{ $formId }}" class="form-label">Kode Periode</label>
        <input type="text" name="code" id="code-{{ $formId }}" class="form-control text-uppercase academic-period-code"
            value="{{ $useOld ? old('code') : $current?->code }}" placeholder="Contoh: 2026-2027-GENAP" maxlength="32" required>
        <small class="text-muted">Kode unik tanpa spasi.</small>
        @if ($useOld) @error('code')<small class="text-danger d-block">{{ $message }}</small>@enderror @endif
    </div>

    <div class="col-md-3">
        <label for="starts-at-{{ $formId }}" class="form-label">Tanggal Mulai</label>
        <input type="date" name="starts_at" id="starts-at-{{ $formId }}" class="form-control"
            value="{{ $useOld ? old('starts_at') : $current?->starts_at?->format('Y-m-d') }}" required>
        @if ($useOld) @error('starts_at')<small class="text-danger d-block">{{ $message }}</small>@enderror @endif
    </div>

    <div class="col-md-3">
        <label for="ends-at-{{ $formId }}" class="form-label">Tanggal Selesai</label>
        <input type="date" name="ends_at" id="ends-at-{{ $formId }}" class="form-control"
            value="{{ $useOld ? old('ends_at') : $current?->ends_at?->format('Y-m-d') }}" required>
        @if ($useOld) @error('ends_at')<small class="text-danger d-block">{{ $message }}</small>@enderror @endif
    </div>
</div>

<div class="alert alert-light-primary mt-3 mb-0">
    <i class="fas fa-circle-info me-1"></i>
    Periode disimpan sebagai <strong>draft</strong>. Data transaksi tetap berelasi ke periode melalui <code>taka_id</code>.
</div>
