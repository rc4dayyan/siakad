@php($current = $item ?? null)
<div class="row">
    <div class="form-group col-md-6">
        <label for="program_studi_{{ $formId }}">Program Studi</label>
        <input type="text" name="program_studi" id="program_studi_{{ $formId }}" class="form-control" maxlength="10" value="{{ old('program_studi', $current?->program_studi) }}" placeholder="Contoh: PAI" required>
        @error('program_studi')<small class="text-danger">{{ $message }}</small>@enderror
    </div>
    <div class="form-group col-md-6">
        <label for="semester_{{ $formId }}">Semester</label>
        <input type="number" name="semester" id="semester_{{ $formId }}" class="form-control" min="1" max="14" value="{{ old('semester', $current?->semester) }}" required>
        @error('semester')<small class="text-danger">{{ $message }}</small>@enderror
    </div>
    <div class="form-group col-md-9">
        <label for="name_{{ $formId }}">Nama Mata Kuliah</label>
        <input type="text" name="name" id="name_{{ $formId }}" class="form-control" maxlength="255" value="{{ old('name', $current?->name) }}" required>
        @error('name')<small class="text-danger">{{ $message }}</small>@enderror
    </div>
    <div class="form-group col-md-3">
        <label for="sks_{{ $formId }}">SKS</label>
        <input type="number" name="sks" id="sks_{{ $formId }}" class="form-control" min="1" max="24" value="{{ old('sks', $current?->sks) }}" required>
        @error('sks')<small class="text-danger">{{ $message }}</small>@enderror
    </div>
</div>
