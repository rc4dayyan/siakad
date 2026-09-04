@extends('base.base-dash-index')

@section('title', 'Detail Tugas Kuliah')
@section('menu', 'Tugas Kuliah')
@section('submenu', $stask->title)
@section('urlmenu', route('mahasiswa.akademik.tugas-index'))
@section('subdesc', 'Baca instruksi dan kumpulkan jawaban tugas')

@section('custom-css')
    <style>
        .task-page { max-width: 1120px; margin: 0 auto; --task-primary: #435ebe; --task-ink: #25324b; --task-muted: #6c7689; }
        .task-hero, .task-card { border: 1px solid var(--dash-line); border-radius: 16px; box-shadow: 0 8px 24px rgba(12, 44, 55, .05); }
        .task-hero { overflow: hidden; border-top: 6px solid var(--task-primary); }
        .task-kicker { color: var(--task-primary); font-size: 12px; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .task-title { color: var(--task-ink); font-size: clamp(24px, 3vw, 34px); line-height: 1.25; }
        .task-meta { height: 100%; padding: 14px 15px; border: 1px solid #e5e9f2; border-radius: 12px; background: #fafbfe; }
        .task-meta__icon { display: inline-flex; width: 34px; height: 34px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 10px; color: var(--task-primary); background: #ecefff; }
        .task-meta small { display: block; color: var(--task-muted); }
        .task-meta strong { display: block; color: var(--task-ink); line-height: 1.35; }
        .deadline-box { padding: 17px; border-radius: 13px; }
        .deadline-box--open { border: 1px solid #bfe7d1; background: #f0fbf5; }
        .deadline-box--late { border: 1px solid #f2c6c9; background: #fff5f5; }
        .task-card .card-header { padding: 22px 24px 11px; background: transparent; }
        .task-card .card-body { padding: 15px 24px 24px; }
        .task-description { color: #354052; font-size: 15px; line-height: 1.75; overflow-wrap: anywhere; }
        .task-description img { max-width: 100%; height: auto; }
        .task-description table { width: 100%; }
        .answer-textarea { min-height: 190px; border-radius: 11px; resize: vertical; }
        .upload-item { height: 100%; padding: 16px; border: 1px dashed #bdc7da; border-radius: 12px; background: #fbfcff; transition: border-color .2s, background .2s; }
        .upload-item:focus-within { border-color: var(--task-primary); background: #f6f7ff; }
        .upload-item--required { border-style: solid; border-color: #aebbe0; }
        .upload-item .form-control { border-radius: 9px; background: #fff; }
        .upload-hint { color: var(--task-muted); font-size: 12px; }
        .answer-counter { color: var(--task-muted); font-size: 12px; }
        .task-submit { position: sticky; bottom: 12px; z-index: 10; padding: 14px 16px; border: 1px solid var(--dash-line); border-radius: 13px; background: rgba(255, 255, 255, .96); box-shadow: 0 10px 28px rgba(30, 40, 70, .14); backdrop-filter: blur(8px); }
        @media (max-width: 767.98px) {
            .task-card .card-header, .task-card .card-body { padding-right: 16px; padding-left: 16px; }
            .task-submit { align-items: stretch !important; flex-direction: column; }
            .task-submit .btn { width: 100%; }
        }
    </style>
@endsection

@section('content')
    <section class="section task-page">
        <div class="card task-hero mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <div class="task-kicker mb-2">{{ $stask->jadkul->matkul->name ?? 'Mata kuliah' }} · {{ $stask->jadkul->pert_id ?? 'Pertemuan' }}</div>
                        <h2 class="task-title mb-2">{{ $stask->title }}</h2>
                        <p class="text-muted mb-0">Pastikan Anda membaca seluruh instruksi sebelum mengirim jawaban.</p>
                    </div>
                    <a href="{{ route('mahasiswa.akademik.tugas-index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Daftar tugas</a>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-lg-4"><div class="task-meta d-flex gap-3"><span class="task-meta__icon"><i class="fas fa-user-tie"></i></span><div><small>Dosen Pengampu</small><strong>{{ $stask->dosen->dsn_name ?? 'Belum ditentukan' }}</strong></div></div></div>
                    <div class="col-lg-4"><div class="task-meta d-flex gap-3"><span class="task-meta__icon"><i class="fas fa-users"></i></span><div><small>Kelas</small><strong>{{ $stask->jadkul->kelas->name ?? $stask->jadkul->kelas->code ?? '—' }}</strong></div></div></div>
                    <div class="col-lg-4"><div class="task-meta d-flex gap-3"><span class="task-meta__icon"><i class="fas fa-hashtag"></i></span><div><small>Kode Tugas</small><strong>{{ $stask->code }}</strong></div></div></div>
                </div>

                <div class="deadline-box {{ $isOverdue ? 'deadline-box--late' : 'deadline-box--open' }} d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="task-meta__icon"><i class="{{ $isOverdue ? 'fas fa-triangle-exclamation text-danger' : 'far fa-clock' }}"></i></span>
                        <div><small class="text-muted">Batas pengumpulan</small><strong>{{ $deadline->translatedFormat('l, d F Y · H:i') }} WIB</strong></div>
                    </div>
                    <span class="badge {{ $isOverdue ? 'bg-danger' : 'bg-success' }} rounded-pill px-3 py-2">{{ $isOverdue ? 'Lewat '.$deadline->diffForHumans(null, true) : 'Tersisa '.$deadline->diffForHumans(null, true) }}</span>
                </div>
            </div>
        </div>

        <div class="card task-card mb-4">
            <div class="card-header"><h5 class="mb-1"><i class="fas fa-list-check text-primary me-2"></i>Instruksi Tugas</h5><small class="text-muted">Ketentuan yang diberikan oleh dosen.</small></div>
            <div class="card-body"><div class="task-description">{!! $safeTaskDescription !!}</div></div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-4"><div class="fw-bold mb-1"><i class="fas fa-circle-exclamation me-1"></i> Tugas belum dapat dikirim</div><div>Periksa kembali jawaban dan lampiran yang ditandai.</div></div>
        @endif

        <form action="{{ route('mahasiswa.akademik.tugas-store', $stask->code) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card task-card mb-4">
                <div class="card-header"><h5 class="mb-1"><i class="fas fa-pen-to-square text-primary me-2"></i>Jawaban Anda</h5><small class="text-muted">Berikan ringkasan jawaban atau catatan yang membantu dosen memeriksa lampiran.</small></div>
                <div class="card-body">
                    <label for="task_answer" class="form-label fw-bold">Deskripsi Jawaban <span class="text-danger">*</span></label>
                    <textarea name="desc" id="task_answer" class="form-control answer-textarea @error('desc') is-invalid @enderror" maxlength="10000" required placeholder="Tuliskan penjelasan singkat mengenai jawaban tugas Anda...">{{ old('desc') }}</textarea>
                    <div class="d-flex justify-content-between gap-3 mt-2"><div>@error('desc')<span class="text-danger small">{{ $message }}</span>@else<span class="upload-hint">Jelaskan isi lampiran atau hal penting yang perlu diketahui dosen.</span>@enderror</div><span class="answer-counter"><span id="answerLength">{{ strlen(old('desc', '')) }}</span>/10.000</span></div>
                </div>
            </div>

            <div class="card task-card mb-4">
                <div class="card-header"><h5 class="mb-1"><i class="fas fa-paperclip text-primary me-2"></i>Lampiran Tugas</h5><small class="text-muted">PDF, Word, Excel, PowerPoint, atau gambar. Maksimal 20 MB per file.</small></div>
                <div class="card-body">
                    <div class="row g-3">
                        @for ($fileNumber = 1; $fileNumber <= 3; $fileNumber++)
                            <div class="col-lg-4">
                                <div class="upload-item {{ $fileNumber === 1 ? 'upload-item--required' : '' }}">
                                    <label for="file_{{ $fileNumber }}" class="form-label fw-bold">Lampiran {{ $fileNumber }} @if ($fileNumber === 1)<span class="text-danger">*</span>@else<small class="fw-normal text-muted">(opsional)</small>@endif</label>
                                    <input type="file" id="file_{{ $fileNumber }}" name="file_{{ $fileNumber }}" class="form-control @error('file_'.$fileNumber) is-invalid @enderror" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif" @required($fileNumber === 1)>
                                    @error('file_'.$fileNumber)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        @endfor
                    </div>

                    <button class="btn btn-sm btn-outline-secondary mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#additionalAttachments" aria-expanded="false" aria-controls="additionalAttachments"><i class="fas fa-plus me-1"></i> Tambah lampiran lainnya</button>
                    <div class="collapse mt-3" id="additionalAttachments">
                        <div class="row g-3">
                            @for ($fileNumber = 4; $fileNumber <= 8; $fileNumber++)
                                <div class="col-lg-4 col-md-6"><div class="upload-item"><label for="file_{{ $fileNumber }}" class="form-label fw-bold">Lampiran {{ $fileNumber }} <small class="fw-normal text-muted">(opsional)</small></label><input type="file" id="file_{{ $fileNumber }}" name="file_{{ $fileNumber }}" class="form-control @error('file_'.$fileNumber) is-invalid @enderror" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif">@error('file_'.$fileNumber)<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <div class="task-submit d-flex justify-content-between align-items-center gap-3">
                <div><strong class="d-block">Periksa sebelum mengirim</strong><small class="text-muted">Tugas yang sudah dikumpulkan tidak dapat dikirim ulang dari halaman ini.</small></div>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-1"></i> Kumpulkan Tugas</button>
            </div>
        </form>
    </section>
@endsection

@section('custom-js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const answer = document.querySelector('#task_answer');
            const counter = document.querySelector('#answerLength');
            if (answer && counter) answer.addEventListener('input', function () { counter.textContent = this.value.length.toLocaleString('id-ID'); });

            @if (collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'file_') && (int) str_replace('file_', '', $key) >= 4))
                const additionalAttachments = document.querySelector('#additionalAttachments');
                if (additionalAttachments && typeof bootstrap !== 'undefined') bootstrap.Collapse.getOrCreateInstance(additionalAttachments).show();
            @endif
        });
    </script>
@endsection
