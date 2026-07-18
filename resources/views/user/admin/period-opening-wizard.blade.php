@extends('base.base-dash-index')

@section('menu', 'Pembukaan Periode')
@section('submenu', 'Wizard Aktivasi Periode Baru')
@section('urlmenu', route($prefix.'period-opening.index'))
@section('subdesc', 'Panduan terpadu untuk menyiapkan, mengaktifkan, dan memublikasikan periode akademik')

@section('content')
    @php
        $colors = ['siap' => 'success', 'peringatan' => 'warning', 'gagal' => 'danger'];
        $icons = ['siap' => 'fa-check', 'peringatan' => 'fa-exclamation', 'gagal' => 'fa-xmark'];
    @endphp

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <small class="text-muted">Periode yang sedang disiapkan</small>
                <h4 class="mb-1">{{ $period->name }}</h4>
                <span class="badge bg-secondary">{{ $period->status_label }}</span>
                @if ($period->is_published)<span class="badge bg-success">Dipublikasikan</span>@endif
            </div>
            <form method="POST" action="{{ route($prefix.'academic-period.select') }}" class="d-flex align-items-end gap-2">
                @csrf @method('PATCH')
                <div><label class="form-label mb-1" for="wizard-period">Ganti periode</label><select class="form-select" name="code" id="wizard-period">@foreach ($periods as $item)<option value="{{ $item->code }}" @selected($item->is($period))>{{ $item->name }} · {{ $item->status_label }}</option>@endforeach</select></div>
                <button class="btn btn-outline-primary">Pilih</button>
            </form>
            <div style="min-width: 240px">
                <div class="d-flex justify-content-between"><span>Kesiapan</span><strong>{{ $result['progress'] }}%</strong></div>
                <div class="progress"><div class="progress-bar" style="width: {{ $result['progress'] }}%"></div></div>
                <small>{{ $result['counts']['siap'] }} siap · {{ $result['counts']['peringatan'] }} peringatan · {{ $result['counts']['gagal'] }} gagal</small>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-4" role="tablist" aria-label="Tahapan pembukaan periode">
        @foreach ($steps as $wizardStep)
            <div class="col-6 col-md-4 col-xl-2">
                <button type="button" class="btn w-100 text-start border wizard-step {{ $step === $wizardStep['number'] ? 'border-primary bg-light' : 'btn-light' }}" data-step="{{ $wizardStep['number'] }}">
                    <small class="text-muted">Langkah {{ $wizardStep['number'] }}</small><br>
                    <strong>{{ $wizardStep['label'] }}</strong>
                    <span class="badge bg-{{ $colors[$wizardStep['status']] }} float-end"><i class="fa-solid {{ $icons[$wizardStep['status']] }}"></i></span>
                </button>
            </div>
        @endforeach
    </div>

    <div class="wizard-panel {{ $step === 1 ? '' : 'd-none' }}" data-panel="1">
        <div class="card"><div class="card-header"><h5>1. Identitas dan konfigurasi dasar</h5></div><div class="card-body">
            <div class="alert alert-{{ $colors[$checks['identitas']['status']] }}">{{ $checks['identitas']['message'] }}</div>
            <p>Lengkapi kode, jenis semester, dan rentang tanggal. Selama masih draft, konfigurasi kelas, dosen, penawaran, jadwal, dan template tagihan dapat disalin dari periode sebelumnya.</p>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="{{ route($prefix.'master.taka-index') }}">Kelola identitas periode</a><a class="btn btn-outline-primary" href="{{ route($prefix.'period-opening.index') }}">Salin konfigurasi periode</a></div>
        </div></div>
    </div>

    <div class="wizard-panel {{ $step === 2 ? '' : 'd-none' }}" data-panel="2">
        <div class="card"><div class="card-header"><h5>2. Akademik: registrasi dan kelas</h5></div><div class="card-body">
            <div class="alert alert-{{ $colors[$checks['registrasi_kelas']['status']] }}">{{ $checks['registrasi_kelas']['message'] }}</div>
            <p>Siapkan kelas tujuan, jalankan kenaikan semester, tentukan status mahasiswa cuti/nonaktif, lalu pastikan setiap registrasi memiliki kelas dan dosen wali aktif.</p>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="{{ route($prefix.'workers.student-promotion-index') }}">Jalankan kenaikan semester</a><a class="btn btn-outline-primary" href="{{ route($prefix.'master.kelas-index') }}">Kelola kelas</a><a class="btn btn-outline-primary" href="{{ route($prefix.'workers.student-index') }}">Periksa registrasi</a></div>
        </div></div>
    </div>

    <div class="wizard-panel {{ $step === 3 ? '' : 'd-none' }}" data-panel="3">
        <div class="card"><div class="card-header"><h5>3. Akademik: kurikulum dan penawaran</h5></div><div class="card-body">
            @foreach (['kurikulum_prodi', 'penawaran_dosen'] as $key)<div class="alert alert-{{ $colors[$checks[$key]['status']] }}">{{ $checks[$key]['label'] }}: {{ $checks[$key]['message'] }}</div>@endforeach
            <p>Pastikan semua mata kuliah memiliki program studi, kurikulum, kelas, dan dosen utama aktif. Tentukan juga rentang pengisian KRS.</p>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="{{ route($prefix.'master.penawaran-index') }}">Kelola penawaran dan jadwal KRS</a><a class="btn btn-outline-primary" href="{{ route($prefix.'master.matkul-index') }}">Periksa mata kuliah</a></div>
        </div></div>
    </div>

    <div class="wizard-panel {{ $step === 4 ? '' : 'd-none' }}" data-panel="4">
        <div class="card"><div class="card-header"><h5>4. Akademik: jadwal kuliah</h5></div><div class="card-body">
            <div class="alert alert-{{ $colors[$checks['jadwal']['status']] }}">{{ $checks['jadwal']['message'] }}</div>
            <p>Jadwalkan seluruh penawaran tanpa bentrok dosen, kelas, ruang, atau waktu. Tambahkan kalender libur dan generate pertemuan setelah jadwal final.</p>
            <a class="btn btn-primary" href="{{ route($prefix.'master.jadwal-mingguan-index') }}">Kelola jadwal mingguan</a>
        </div></div>
    </div>

    <div class="wizard-panel {{ $step === 5 ? '' : 'd-none' }}" data-panel="5">
        <div class="card"><div class="card-header"><h5>5. Keuangan: tagihan periode</h5></div><div class="card-body">
            <div class="alert alert-{{ $colors[$checks['tagihan']['status']] }}">{{ $checks['tagihan']['message'] }}</div>
            <p>Buat atau salin template tagihan, periksa target dan nominal melalui pratinjau, lalu terbitkan. Semua mahasiswa aktif harus menerima tagihan yang diwajibkan sebelum KRS.</p>
            <a class="btn btn-primary" href="{{ route($prefix.'billing-period.index') }}">Kelola tagihan periode</a>
        </div></div>
    </div>

    <div class="wizard-panel {{ $step === 6 ? '' : 'd-none' }}" data-panel="6">
        <div class="card"><div class="card-header"><h5>6. Pemeriksaan, aktivasi, dan publikasi</h5></div><div class="card-body">
            @if ($result['counts']['gagal'] > 0)
                <div class="alert alert-danger">Masih ada {{ $result['counts']['gagal'] }} pemeriksaan wajib yang gagal. Kembali ke langkah bertanda merah sebelum mengaktifkan periode.</div>
            @elseif ($result['counts']['peringatan'] > 0)
                <div class="alert alert-warning">Tidak ada kegagalan, tetapi terdapat {{ $result['counts']['peringatan'] }} peringatan. Pastikan peringatan sesuai kebijakan kampus.</div>
            @else
                <div class="alert alert-success">Seluruh pemeriksaan siap. Periode dapat diaktifkan dan dipublikasikan.</div>
            @endif

            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route($prefix.'period-opening.inspect') }}">@csrf<button class="btn btn-outline-primary">Simpan hasil pemeriksaan</button></form>
                @if ($period->status === \App\Models\TahunAkademik::STATUS_DRAFT)
                    <form method="POST" action="{{ route($prefix.'master.taka-activate', $period->code) }}" onsubmit="return confirm('Aktivasi akan menutup periode aktif sebelumnya. Lanjutkan?')">@csrf @method('PATCH')<button class="btn btn-warning" @disabled($result['counts']['gagal'] > 0)>Aktifkan periode</button></form>
                @elseif ($period->status === \App\Models\TahunAkademik::STATUS_ACTIVE && ! $period->is_published)
                    <form method="POST" action="{{ route($prefix.'period-opening.publish') }}" onsubmit="return confirm('Publikasikan periode ke portal dosen dan mahasiswa?')">@csrf<button class="btn btn-success" @disabled($result['counts']['gagal'] > 0)>Publikasikan periode</button></form>
                @elseif ($period->is_published)
                    <span class="btn btn-success disabled"><i class="fa-solid fa-check me-1"></i> Periode sudah dipublikasikan</span>
                @else
                    <span class="text-muted align-self-center">Periode yang ditutup atau diarsipkan tidak dapat diaktifkan dari wizard.</span>
                @endif
            </div>
        </div></div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <button type="button" class="btn btn-outline-secondary" id="wizard-back">Sebelumnya</button>
        <button type="button" class="btn btn-primary" id="wizard-next">Berikutnya</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let current = {{ $step }};
            const show = function (step) {
                current = Math.max(1, Math.min(6, step));
                document.querySelectorAll('.wizard-panel').forEach(panel => panel.classList.toggle('d-none', Number(panel.dataset.panel) !== current));
                document.querySelectorAll('.wizard-step').forEach(button => {
                    const active = Number(button.dataset.step) === current;
                    button.classList.toggle('border-primary', active);
                    button.classList.toggle('bg-light', active);
                });
                document.getElementById('wizard-back').disabled = current === 1;
                document.getElementById('wizard-next').disabled = current === 6;
                window.history.replaceState({}, '', `${window.location.pathname}?step=${current}`);
            };
            document.querySelectorAll('.wizard-step').forEach(button => button.addEventListener('click', () => show(Number(button.dataset.step))));
            document.getElementById('wizard-back').addEventListener('click', () => show(current - 1));
            document.getElementById('wizard-next').addEventListener('click', () => show(current + 1));
            show(current);
        });
    </script>
@endsection
