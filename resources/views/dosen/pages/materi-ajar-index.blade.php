@extends('base.base-dash-index')
@section('title', 'Materi Ajar - SIAKAD')
@section('menu', 'Materi Ajar')
@section('submenu', $penawaran->masterMataKuliah?->name ?? 'Mata Kuliah')
@section('urlmenu', route('dosen.akademik.matkul-index'))
@section('subdesc', 'Bagikan deskripsi dan berkas pembelajaran kepada peserta mata kuliah.')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h5 class="card-title mb-1">{{ $penawaran->masterMataKuliah?->name ?? '-' }}</h5>
                <small class="text-muted">
                    {{ $penawaran->masterMataKuliah?->code ?? $penawaran->code }} ·
                    {{ $penawaran->kelas?->name ?? '-' }} ·
                    {{ $period?->name ?? '-' }}
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('dosen.akademik.matkul-index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
                @if ($canManage)
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMaterialModal">
                        <i class="fas fa-plus me-1"></i> Tambah Materi
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if (! $canManage)
                <div class="alert alert-info">Periode ini hanya dapat dilihat. Materi tidak dapat ditambah atau dihapus.</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>Materi</th>
                            <th>File</th>
                            <th>Diunggah</th>
                            <th class="text-center" style="width: 190px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold">{{ $material->judul }}</div>
                                    @if ($material->deskripsi)
                                        <div class="text-muted mt-1" style="white-space: pre-line">{{ $material->deskripsi }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($material->file_path)
                                        <div class="fw-semibold"><i class="fas fa-paperclip me-1"></i>{{ $material->file_name }}</div>
                                        <small class="text-muted">{{ $material->file_size_label }}</small>
                                    @else
                                        <span class="text-muted">Tanpa file</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $material->created_at->locale('id')->translatedFormat('d M Y, H:i') }}<br>
                                    <small class="text-muted">{{ $material->dosen?->dsn_name ?? '-' }}</small>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        @if ($material->file_path)
                                            <a href="{{ route('dosen.akademik.matkul-materi-download', [$penawaran, $material]) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download me-1"></i> Unduh
                                            </a>
                                        @endif
                                        @if ($canManage)
                                            <form method="POST" action="{{ route('dosen.akademik.matkul-materi-destroy', [$penawaran, $material]) }}" onsubmit="return confirm('Hapus materi ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Hapus {{ $material->judul }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center text-muted">
                                    <i class="fas fa-book-open fa-2x mb-3"></i>
                                    <p class="mb-1 fw-semibold">Belum ada materi ajar</p>
                                    <small>Tambahkan deskripsi, file, atau keduanya untuk dibagikan kepada mahasiswa.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

@if ($canManage)
    <div class="modal fade" id="createMaterialModal" tabindex="-1" aria-labelledby="createMaterialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('dosen.akademik.matkul-materi-store', $penawaran) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_form" value="create-materi">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createMaterialModalLabel">Tambah Materi Ajar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('_form') === 'create-materi')
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="material-title" class="form-label">Judul Materi <span class="text-danger">*</span></label>
                            <input type="text" name="judul" id="material-title" value="{{ old('judul') }}" class="form-control @error('judul') is-invalid @enderror" maxlength="255" required>
                            @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="material-description" class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="material-description" rows="6" class="form-control @error('deskripsi') is-invalid @enderror" maxlength="10000" placeholder="Tuliskan ringkasan atau isi materi...">{{ old('deskripsi') }}</textarea>
                            @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-2">
                            <label for="material-file" class="form-label">File Materi</label>
                            <input type="file" name="file" id="material-file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.zip">
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">PDF, Word, Excel, PowerPoint, TXT, gambar, atau ZIP. Maksimal 20 MB. Isi deskripsi atau unggah minimal satu file.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Materi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@if ($canManage && $errors->any() && old('_form') === 'create-materi')
    @section('custom-js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createMaterialModal')).show();
            });
        </script>
    @endsection
@endif
