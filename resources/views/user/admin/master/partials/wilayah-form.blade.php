<div class="row g-3">
    <div class="col-md-4">
        <label for="code-{{ $formId }}" class="form-label">Kode Wilayah</label>
        <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control" id="code-{{ $formId }}" name="code" value="{{ old('code', $item->code ?? '') }}" placeholder="Contoh: 016202" required>
        <small class="text-muted">Enam digit sesuai kode OpenFeeder.</small>
    </div>
    <div class="col-md-8">
        <label for="kecamatan-{{ $formId }}" class="form-label">Kecamatan</label>
        <input type="text" maxlength="255" class="form-control" id="kecamatan-{{ $formId }}" name="kecamatan" value="{{ old('kecamatan', $item->kecamatan ?? '') }}" placeholder="Contoh: Kec. Kebon Jeruk" required>
    </div>
    <div class="col-md-6">
        <label for="kabupaten-{{ $formId }}" class="form-label">Kabupaten/Kota</label>
        <input type="text" maxlength="255" class="form-control" id="kabupaten-{{ $formId }}" name="kabupaten" value="{{ old('kabupaten', $item->kabupaten ?? '') }}" placeholder="Contoh: Kota Jakarta Barat">
    </div>
    <div class="col-md-6">
        <label for="provinsi-{{ $formId }}" class="form-label">Provinsi</label>
        <input type="text" maxlength="255" class="form-control" id="provinsi-{{ $formId }}" name="provinsi" value="{{ old('provinsi', $item->provinsi ?? '') }}" placeholder="Contoh: Prov. D.K.I. Jakarta">
    </div>
</div>
