@extends('base.base-dash-index')
@section('title', 'Persetujuan KRS')
@section('menu', 'Persetujuan KRS')
@section('submenu', 'Mahasiswa Bimbingan')
@section('urlmenu', '#')
@section('subdesc', 'Tinjau KRS mahasiswa yang menjadi bimbingan Anda')
@section('custom-css')
    <style>
        .krs-summary-card {
            border: 0;
            box-shadow: 0 0.125rem 0.5rem rgba(31, 45, 61, 0.08);
        }

        .krs-summary-icon {
            align-items: center;
            border-radius: 0.75rem;
            display: inline-flex;
            flex: 0 0 2.75rem;
            height: 2.75rem;
            justify-content: center;
            width: 2.75rem;
        }

        .krs-submission-card {
            border: 1px solid #e9ecef;
            box-shadow: 0 0.125rem 0.75rem rgba(31, 45, 61, 0.06);
            overflow: hidden;
        }

        .krs-student-avatar {
            align-items: center;
            background: rgba(67, 94, 190, 0.12);
            border-radius: 50%;
            color: #435ebe;
            display: inline-flex;
            flex: 0 0 3rem;
            font-size: 1.1rem;
            height: 3rem;
            justify-content: center;
            width: 3rem;
        }

        .krs-meta-item {
            align-items: center;
            color: #6c757d;
            display: inline-flex;
            gap: 0.4rem;
        }

        .krs-status-badge {
            align-items: center;
            display: inline-flex;
            font-size: 0.75rem;
            gap: 0.4rem;
            letter-spacing: 0.02em;
            padding: 0.55rem 0.75rem;
        }

        .krs-course-table th {
            background: #f8f9fa;
            color: #607080;
            font-size: 0.75rem;
            letter-spacing: 0.035em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .krs-course-table td {
            vertical-align: middle;
        }

        .krs-decision-panel {
            background: #f8fafc;
            border: 1px solid #e9ecef;
            border-radius: 0.65rem;
        }

        .krs-toggle-button .krs-toggle-icon {
            transition: transform 0.2s ease;
        }

        .krs-toggle-button[aria-expanded="true"] .krs-toggle-icon {
            transform: rotate(180deg);
        }

        .krs-toggle-button[aria-expanded="true"] .krs-toggle-label::after {
            content: "Sembunyikan detail";
        }

        .krs-toggle-button[aria-expanded="false"] .krs-toggle-label::after {
            content: "Lihat detail";
        }
    </style>
@endsection
@section('content')
    @php
        $statusMeta = [
            'draft' => ['label' => 'Draf', 'color' => 'secondary', 'icon' => 'fa-pen'],
            'submitted' => ['label' => 'Menunggu Persetujuan', 'color' => 'warning', 'icon' => 'fa-clock'],
            'approved' => ['label' => 'Disetujui', 'color' => 'success', 'icon' => 'fa-circle-check'],
            'rejected' => ['label' => 'Perlu Perbaikan', 'color' => 'danger', 'icon' => 'fa-circle-exclamation'],
            'locked' => ['label' => 'Dikunci', 'color' => 'primary', 'icon' => 'fa-lock'],
        ];
        $pendingCount = $submissions->where('status', 'submitted')->count();
        $approvedCount = $submissions->whereIn('status', ['approved', 'locked'])->count();
        $revisionCount = $submissions->where('status', 'rejected')->count();
    @endphp

    <section class="section">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card krs-summary-card h-100 mb-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="krs-summary-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                        <div>
                            <div class="text-muted small">Menunggu persetujuan</div>
                            <div class="fs-4 fw-bold text-dark">{{ $pendingCount }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card krs-summary-card h-100 mb-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="krs-summary-icon bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>
                        <div>
                            <div class="text-muted small">Telah disetujui</div>
                            <div class="fs-4 fw-bold text-dark">{{ $approvedCount }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card krs-summary-card h-100 mb-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="krs-summary-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fa-solid fa-rotate-left"></i>
                        </span>
                        <div>
                            <div class="text-muted small">Perlu perbaikan</div>
                            <div class="fs-4 fw-bold text-dark">{{ $revisionCount }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1">Daftar Pengajuan KRS</h4>
                <p class="text-muted mb-0">Periksa mata kuliah dan total SKS sebelum memberikan keputusan.</p>
            </div>
            <span class="badge bg-light-secondary text-secondary px-3 py-2">
                {{ $submissions->count() }} pengajuan
            </span>
        </div>

        @forelse ($submissions as $krs)
            @php
                $registration = $krs->registrasiMahasiswa;
                $student = $registration->mahasiswa;
                $meta = $statusMeta[$krs->status] ?? ['label' => ucfirst($krs->status), 'color' => 'secondary', 'icon' => 'fa-circle-info'];
                $shouldExpand = $errors->any() && (int) old('krs_id') === $krs->id;
            @endphp

            <article class="card krs-submission-card mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="krs-student-avatar" aria-hidden="true">
                                <i class="fa-solid fa-user-graduate"></i>
                            </span>
                            <div>
                                <h5 class="mb-1">{{ $student->mhs_name }}</h5>
                                <div class="d-flex flex-wrap gap-3 small">
                                    <span class="krs-meta-item">
                                        <i class="fa-solid fa-id-card"></i>{{ $student->mhs_nim }}
                                    </span>
                                    <span class="krs-meta-item">
                                        <i class="fa-solid fa-users"></i>{{ $registration->kelas?->name ?? 'Kelas belum ditentukan' }}
                                    </span>
                                    <span class="krs-meta-item">
                                        <i class="fa-solid fa-calendar-days"></i>{{ $registration->taka->name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge rounded-pill bg-{{ $meta['color'] }} krs-status-badge">
                                <i class="fa-solid {{ $meta['icon'] }}"></i>{{ $meta['label'] }}
                            </span>
                            <button
                                class="btn btn-sm btn-outline-primary krs-toggle-button"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#krs-detail-{{ $krs->id }}"
                                aria-expanded="{{ $shouldExpand ? 'true' : 'false' }}"
                                aria-controls="krs-detail-{{ $krs->id }}"
                            >
                                <span class="krs-toggle-label"></span>
                                <i class="fa-solid fa-chevron-down ms-1 krs-toggle-icon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="krs-detail-{{ $krs->id }}" class="collapse{{ $shouldExpand ? ' show' : '' }}">
                    <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table krs-course-table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4" style="width: 55px">No.</th>
                                    <th>Mata Kuliah</th>
                                    <th>Kelas</th>
                                    <th>Dosen Pengampu</th>
                                    <th class="text-center">SKS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($krs->items as $item)
                                    @php $offering = $item->penawaranMataKuliah; @endphp
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $offering->masterMataKuliah->name }}</div>
                                            <small class="text-muted">{{ $offering->code ?? 'Kode belum tersedia' }}</small>
                                        </td>
                                        <td>{{ $offering->kelas?->name ?? '-' }}</td>
                                        <td>{{ $offering->dosenUtama?->dsn_name ?? '-' }}</td>
                                        <td class="text-center"><span class="badge bg-light-primary text-primary">{{ $item->sks }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-muted">Belum ada mata kuliah dalam KRS ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <td colspan="4" class="ps-4 text-end fw-semibold">Total Beban Studi</td>
                                    <td class="text-center"><strong class="text-primary">{{ $krs->total_sks }} SKS</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="p-4 border-top">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="small text-muted mb-1">Catatan mahasiswa</div>
                                <div class="text-dark">
                                    {{ filled($krs->catatan_mahasiswa) ? $krs->catatan_mahasiswa : 'Tidak ada catatan dari mahasiswa.' }}
                                </div>
                            </div>
                            <div class="col-lg-5 text-lg-end">
                                <div class="small text-muted mb-1">Waktu pengajuan</div>
                                <div class="text-dark">
                                    <i class="fa-regular fa-clock me-1 text-muted"></i>
                                    {{ $krs->diajukan_at?->format('d/m/Y H:i') ?? 'Belum diajukan' }}
                                </div>
                            </div>
                        </div>

                        @if ($krs->status === 'submitted')
                            <form method="POST" action="{{ route('dosen.akademik.krs-decide', $krs) }}" class="krs-decision-panel mt-4 p-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="krs_id" value="{{ $krs->id }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-7">
                                        <label for="decision-note-{{ $krs->id }}" class="form-label fw-semibold">Catatan keputusan</label>
                                        <textarea name="catatan" id="decision-note-{{ $krs->id }}" class="form-control" rows="2" maxlength="2000" placeholder="Tambahkan catatan atau arahan perbaikan (opsional)">{{ old('catatan') }}</textarea>
                                        <small class="text-muted">Catatan akan dapat dilihat oleh mahasiswa.</small>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                                            <button type="submit" name="status" value="rejected" class="btn btn-outline-danger" onclick="return confirm('Kembalikan KRS ini untuk diperbaiki?')">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Minta Perbaikan
                                            </button>
                                            <button type="submit" name="status" value="approved" class="btn btn-success" onclick="return confirm('Setujui dan kunci KRS ini?')">
                                                <i class="fa-solid fa-check me-1"></i> Setujui KRS
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @elseif (filled($krs->catatan_keputusan))
                            <div class="alert alert-light border mt-4 mb-0">
                                <div class="small text-muted mb-1">Catatan keputusan</div>
                                <div>{{ $krs->catatan_keputusan }}</div>
                            </div>
                        @endif
                    </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="card krs-submission-card">
                <div class="card-body py-5 text-center">
                    <span class="krs-summary-icon bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </span>
                    <h5>Belum ada pengajuan KRS</h5>
                    <p class="text-muted mb-0">Pengajuan KRS dari mahasiswa bimbingan akan tampil di halaman ini.</p>
                </div>
            </div>
        @endforelse
    </section>
@endsection
