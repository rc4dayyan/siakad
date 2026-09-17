@extends('base.base-dash-index')

@php
    $student = Auth::guard('mahasiswa')->user();
    $activeTab = old('profile_section', 'personal');
    $semester = $academicRegistration?->semester_mahasiswa ?? '-';
    $studyProgram = $academicClass?->pstudi?->name ?? '-';
    $faculty = $academicClass?->pstudi?->fakultas?->name ?? '-';
    $entryYear = $academicRegistration?->taka?->year_start ?? $academicClass?->taka?->year_start ?? '-';
@endphp

@section('title', config('app.name'))
@section('menu', 'Profil')
@section('submenu', 'Profil Mahasiswa')
@section('urlmenu', route('mahasiswa.home-index'))
@section('subdesc', 'Kelola identitas, informasi kontak, dan keamanan akun Anda.')

@section('custom-css')
    <style>
        .student-profile { --profile-radius: 16px; }
        .student-profile .profile-card { overflow: hidden; border: 1px solid var(--dash-line); border-radius: var(--profile-radius); background: var(--dash-surface); box-shadow: 0 10px 28px rgba(12, 44, 55, .06); }
        .student-profile .identity-card { position: sticky; top: 92px; }
        .student-profile .identity-cover { height: 112px; background: radial-gradient(circle at 78% 12%, rgba(255, 255, 255, .16), transparent 32%), linear-gradient(135deg, var(--dash-navy), var(--dash-green-dark)); }
        .student-profile .identity-body { padding: 0 24px 24px; }
        .student-profile .avatar-wrap { position: relative; width: 124px; height: 124px; margin: -62px auto 14px; }
        .student-profile .profile-avatar { width: 124px; height: 124px; border: 5px solid #fff; border-radius: 50%; background: #eef3f1; box-shadow: 0 8px 24px rgba(7, 35, 48, .18); object-fit: cover; }
        .student-profile .avatar-badge { position: absolute; right: 4px; bottom: 8px; display: grid; width: 34px; height: 34px; place-items: center; border: 3px solid #fff; border-radius: 50%; color: #fff; background: var(--dash-green); font-size: 13px; }
        .student-profile .identity-name { margin: 0; color: var(--dash-navy); font-size: 19px; font-weight: 800; text-align: center; }
        .student-profile .identity-number { margin: 4px 0 12px; color: var(--dash-muted); font-size: 12px; font-weight: 700; letter-spacing: .04em; text-align: center; }
        .student-profile .status-badge { display: table; margin: 0 auto 20px; padding: 6px 11px; border-radius: 999px; color: var(--dash-green-dark); background: var(--dash-green-soft); font-size: 10px; font-weight: 800; }
        .student-profile .identity-meta { margin: 0; padding: 16px 0; border-top: 1px solid var(--dash-line); border-bottom: 1px solid var(--dash-line); }
        .student-profile .identity-meta li { display: flex; align-items: flex-start; gap: 11px; padding: 7px 0; list-style: none; }
        .student-profile .identity-meta i { width: 18px; margin-top: 2px; color: var(--dash-green); text-align: center; }
        .student-profile .identity-meta span { display: block; color: var(--dash-muted); font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .student-profile .identity-meta strong { display: block; margin-top: 1px; color: var(--dash-ink); font-size: 12px; line-height: 1.45; }
        .student-profile .photo-form { margin-top: 20px; }
        .student-profile .photo-form .form-label, .student-profile .form-label { margin-bottom: 7px; color: var(--dash-ink); font-size: 11px; font-weight: 800; }
        .student-profile .photo-hint, .student-profile .field-hint { display: block; margin-top: 6px; color: var(--dash-muted); font-size: 10px; line-height: 1.5; }
        .student-profile .photo-hint { margin-bottom: 12px; }
        .student-profile .profile-main-card { min-height: 100%; }
        .student-profile .profile-tabs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 0; padding: 8px; border: 0; border-bottom: 1px solid var(--dash-line); border-radius: 0; background: #f7faf9; }
        .student-profile .profile-tabs .nav-link { display: flex; min-height: 48px; align-items: center; justify-content: center; gap: 8px; border-radius: 10px; font-size: 12px; }
        .student-profile .tab-pane { padding: 25px 26px 28px; }
        .student-profile .form-heading { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 22px; }
        .student-profile .form-heading__icon { display: grid; width: 42px; height: 42px; flex: 0 0 42px; place-items: center; border-radius: 11px; color: var(--dash-green); background: var(--dash-green-soft); }
        .student-profile .form-heading h4 { margin: 1px 0 3px; font-size: 16px; font-weight: 800; }
        .student-profile .form-heading p { margin: 0; color: var(--dash-muted); font-size: 11px; line-height: 1.5; }
        .student-profile .section-label { display: flex; align-items: center; gap: 9px; margin: 6px 0 14px; color: var(--dash-navy); font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .student-profile .section-label::after { height: 1px; flex: 1; background: var(--dash-line); content: ''; }
        .student-profile .form-control, .student-profile .form-select { min-height: 43px; border-color: var(--dash-line); border-radius: 9px; font-size: 12px; }
        .student-profile textarea.form-control { min-height: 104px; resize: vertical; }
        .student-profile .form-control:focus, .student-profile .form-select:focus { border-color: rgba(17, 122, 101, .55); box-shadow: 0 0 0 .2rem rgba(17, 122, 101, .1); }
        .student-profile .form-control[readonly], .student-profile .form-control:disabled { color: #687985; background: #f3f6f5; }
        .student-profile .invalid-feedback { display: block; font-size: 10px; }
        .student-profile .form-actions { display: flex; justify-content: flex-end; margin-top: 8px; padding-top: 20px; border-top: 1px solid var(--dash-line); }
        .student-profile .form-actions .btn { min-width: 155px; border-radius: 9px; font-size: 12px; font-weight: 800; }
        .student-profile .password-field { position: relative; }
        .student-profile .password-field .form-control { padding-right: 44px; }
        .student-profile .password-toggle { position: absolute; top: 50%; right: 5px; width: 34px; height: 34px; padding: 0; border: 0; border-radius: 7px; color: var(--dash-muted); background: transparent; transform: translateY(-50%); }
        .student-profile .password-toggle:hover { color: var(--dash-green-dark); background: var(--dash-green-soft); }
        .student-profile .security-note { display: flex; gap: 11px; margin-bottom: 20px; padding: 13px 15px; border: 1px solid #ead9ac; border-radius: 10px; color: #72571b; background: #fff9e9; font-size: 11px; line-height: 1.5; }
        @media (max-width: 991.98px) { .student-profile .identity-card { position: static; } }
        @media (max-width: 575.98px) { .student-profile .profile-tabs .nav-link { flex-direction: column; gap: 3px; padding: 8px 4px; font-size: 10px; } .student-profile .tab-pane { padding: 21px 17px 24px; } .student-profile .identity-body { padding-right: 18px; padding-left: 18px; } .student-profile .form-actions .btn { width: 100%; } }
    </style>
@endsection

@section('content')
    <section class="section student-profile">
        <div class="row g-4">
            <div class="col-12 col-lg-4 col-xl-3">
                <aside class="profile-card identity-card" aria-label="Ringkasan profil mahasiswa">
                    <div class="identity-cover"></div>
                    <div class="identity-body">
                        <div class="avatar-wrap">
                            <img id="profilePhotoPreview" class="profile-avatar" src="{{ asset('storage/images/'.$student->mhs_image) }}" alt="Foto profil {{ $student->mhs_name }}">
                            <span class="avatar-badge" aria-hidden="true"><i class="fa-solid fa-camera"></i></span>
                        </div>
                        <h4 class="identity-name">{{ $student->mhs_name }}</h4>
                        <p class="identity-number">NIM {{ $student->mhs_nim }}</p>
                        <span class="status-badge"><i class="fa-solid fa-circle-check me-1"></i>{{ $student->mhs_stat }}</span>
                        <ul class="identity-meta">
                            <li><i class="fa-solid fa-graduation-cap"></i><div><span>Program Studi</span><strong>{{ $studyProgram }}</strong></div></li>
                            <li><i class="fa-solid fa-layer-group"></i><div><span>Semester & Kelas</span><strong>Semester {{ $semester }} · {{ $academicClass?->code ?? '-' }}</strong></div></li>
                            <li><i class="fa-solid fa-envelope"></i><div><span>Email Akademik</span><strong>{{ $student->mhs_mail ?: '-' }}</strong></div></li>
                        </ul>
                        <form class="photo-form" action="{{ route('mahasiswa.home-profile-save-image') }}" method="POST" enctype="multipart/form-data">
                            @csrf @method('PATCH')
                            <input type="hidden" name="profile_section" value="personal">
                            <label class="form-label" for="mhs_image">Perbarui foto profil</label>
                            <input class="form-control @error('mhs_image') is-invalid @enderror" type="file" name="mhs_image" id="mhs_image" accept="image/jpeg,image/png,image/gif,image/svg+xml" required>
                            @error('mhs_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="photo-hint">Gunakan foto formal JPG atau PNG. Ukuran maksimum 8 MB.</small>
                            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Simpan Foto</button>
                        </form>
                    </div>
                </aside>
            </div>

            <div class="col-12 col-lg-8 col-xl-9">
                <div class="profile-card profile-main-card">
                    <ul class="nav nav-tabs profile-tabs" id="profileTabs" role="tablist">
                        @foreach ([['personal', 'fa-regular fa-id-card', 'Data Pribadi'], ['contact', 'fa-regular fa-address-book', 'Kontak & Alamat'], ['security', 'fa-solid fa-shield-halved', 'Keamanan']] as [$tab, $icon, $label])
                            <li class="nav-item" role="presentation"><button class="nav-link {{ $activeTab === $tab ? 'active' : '' }}" id="{{ $tab }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tab }}-pane" type="button" role="tab" aria-controls="{{ $tab }}-pane" aria-selected="{{ $activeTab === $tab ? 'true' : 'false' }}"><i class="{{ $icon }}"></i>{{ $label }}</button></li>
                        @endforeach
                    </ul>
                    <div class="tab-content" id="profileTabContent">
                        <div class="tab-pane fade {{ $activeTab === 'personal' ? 'show active' : '' }}" id="personal-pane" role="tabpanel" aria-labelledby="personal-tab" tabindex="0">
                            <div class="form-heading"><span class="form-heading__icon"><i class="fa-regular fa-user"></i></span><div><h4>Informasi Pribadi</h4><p>Pastikan data identitas Anda sesuai dengan dokumen akademik.</p></div></div>
                            <form action="{{ route('mahasiswa.home-profile-save-data') }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="profile_section" value="personal">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6"><label class="form-label" for="mhs_name">Nama Lengkap</label><input class="form-control @error('mhs_name') is-invalid @enderror" type="text" name="mhs_name" id="mhs_name" value="{{ old('mhs_name', $student->mhs_name) }}" readonly>@error('mhs_name')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="field-hint"><i class="fa-solid fa-lock me-1"></i>Perubahan nama melalui bagian akademik.</small></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="mhs_nim">Nomor Induk Mahasiswa</label><input class="form-control @error('mhs_nim') is-invalid @enderror" type="text" name="mhs_nim" id="mhs_nim" value="{{ old('mhs_nim', $student->mhs_nim) }}" readonly>@error('mhs_nim')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="field-hint"><i class="fa-solid fa-lock me-1"></i>NIM merupakan identitas akademik permanen.</small></div>
                                    <div class="col-12"><div class="section-label">Informasi Akademik</div></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="entry_year">Tahun Masuk</label><input class="form-control" id="entry_year" value="Angkatan {{ $entryYear }}" readonly></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="faculty">Fakultas</label><input class="form-control" id="faculty" value="{{ $faculty }}" readonly></div>
                                    <div class="col-12 col-md-8"><label class="form-label" for="study_program">Program Studi</label><input class="form-control" id="study_program" value="{{ $studyProgram }}" readonly></div>
                                    <div class="col-12 col-md-4"><label class="form-label" for="academic_class">Kelas / Semester</label><input class="form-control" id="academic_class" value="{{ $academicClass?->code ?? '-' }} / {{ $semester }}" readonly></div>
                                    <div class="col-12"><div class="section-label">Biodata</div></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="mhs_gend">Jenis Kelamin</label><select class="form-select @error('mhs_gend') is-invalid @enderror" name="mhs_gend" id="mhs_gend"><option value="">Pilih jenis kelamin</option><option value="L" @selected(old('mhs_gend', $student->mhs_gend) === 'L')>Laki-laki</option><option value="P" @selected(old('mhs_gend', $student->mhs_gend) === 'P')>Perempuan</option></select>@error('mhs_gend')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="mhs_reli">Agama</label><select class="form-select @error('mhs_reli') is-invalid @enderror" name="mhs_reli" id="mhs_reli"><option value="">Pilih agama</option>@foreach ([1 => 'Islam', 2 => 'Kristen Katolik', 3 => 'Kristen Protestan', 4 => 'Hindu', 5 => 'Buddha', 6 => 'Konghucu'] as $value => $religion)<option value="{{ $value }}" @selected((string) old('mhs_reli', $student->raw_mhs_reli) === (string) $value)>{{ $religion }}</option>@endforeach</select>@error('mhs_reli')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="mhs_birthplace">Tempat Lahir</label><input class="form-control @error('mhs_birthplace') is-invalid @enderror" type="text" name="mhs_birthplace" id="mhs_birthplace" placeholder="Contoh: Bandung" value="{{ old('mhs_birthplace', $student->mhs_birthplace) }}">@error('mhs_birthplace')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="mhs_birthdate">Tanggal Lahir</label><input class="form-control @error('mhs_birthdate') is-invalid @enderror" type="date" name="mhs_birthdate" id="mhs_birthdate" value="{{ old('mhs_birthdate', $student->getRawOriginal('mhs_birthdate')) }}">@error('mhs_birthdate')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-12 form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan</button></div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'contact' ? 'show active' : '' }}" id="contact-pane" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">
                            <div class="form-heading"><span class="form-heading__icon"><i class="fa-regular fa-address-book"></i></span><div><h4>Kontak & Alamat</h4><p>Informasi ini digunakan untuk komunikasi akademik dan keadaan darurat.</p></div></div>
                            <form action="{{ route('mahasiswa.home-profile-save-kontak') }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="profile_section" value="contact">
                                <div class="row g-3">
                                    <div class="col-12"><div class="section-label">Kontak Utama</div></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="mhs_phone">Nomor Handphone</label><input class="form-control @error('mhs_phone') is-invalid @enderror" type="tel" name="mhs_phone" id="mhs_phone" inputmode="numeric" placeholder="Contoh: 081234567890" value="{{ old('mhs_phone', $student->mhs_phone) }}">@error('mhs_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="mhs_mail">Email Akademik</label>
                                        <input class="form-control @error('mhs_mail') is-invalid @enderror" type="email" name="mhs_mail" id="mhs_mail" autocomplete="email" value="{{ old('mhs_mail', $student->mhs_mail) }}" @readonly($student->mhs_email_changed_at !== null)>
                                        @error('mhs_mail')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        @if ($student->mhs_email_changed_at)
                                            <small class="field-hint"><i class="fa-solid fa-lock me-1"></i>Email sudah pernah diubah. Hubungi bagian akademik untuk perubahan berikutnya.</small>
                                        @else
                                            <small class="field-hint"><i class="fa-solid fa-circle-info me-1"></i>Email hanya dapat Anda ubah satu kali. Periksa kembali sebelum menyimpan.</small>
                                        @endif
                                    </div>
                                    <div class="col-12"><div class="section-label">Kontak Orang Tua / Wali</div></div>
                                    @foreach ([['mhs_parent_father', 'Nama Ayah', 'text'], ['mhs_parent_father_phone', 'Nomor Telepon Ayah', 'tel'], ['mhs_parent_mother', 'Nama Ibu', 'text'], ['mhs_parent_mother_phone', 'Nomor Telepon Ibu', 'tel'], ['mhs_wali_name', 'Nama Wali', 'text'], ['mhs_wali_phone', 'Nomor Telepon Wali', 'tel']] as [$field, $label, $type])
                                        <div class="col-12 col-md-6"><label class="form-label" for="{{ $field }}">{{ $label }}</label><input class="form-control @error($field) is-invalid @enderror" type="{{ $type }}" name="{{ $field }}" id="{{ $field }}" @if($type === 'tel') inputmode="numeric" @endif placeholder="{{ str_contains($field, 'wali') ? 'Opsional' : $label }}" value="{{ old($field, $student->{$field}) }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    @endforeach
                                    <div class="col-12"><div class="section-label">Alamat Domisili</div></div>
                                    <div class="col-12"><label class="form-label" for="mhs_addr_domisili">Alamat Lengkap</label><textarea class="form-control @error('mhs_addr_domisili') is-invalid @enderror" name="mhs_addr_domisili" id="mhs_addr_domisili" rows="4" placeholder="Nama jalan, nomor rumah, RT/RW, dan informasi alamat lainnya">{{ old('mhs_addr_domisili', $student->mhs_addr_domisili) }}</textarea>@error('mhs_addr_domisili')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    @foreach ([['mhs_addr_kelurahan', 'Kelurahan / Desa'], ['mhs_addr_kecamatan', 'Kecamatan'], ['mhs_addr_kota', 'Kabupaten / Kota'], ['mhs_addr_provinsi', 'Provinsi']] as [$field, $label])
                                        <div class="col-12 col-md-6"><label class="form-label" for="{{ $field }}">{{ $label }}</label><input class="form-control @error($field) is-invalid @enderror" type="text" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, $student->{$field}) }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    @endforeach
                                    <div class="col-12 form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Kontak</button></div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'security' ? 'show active' : '' }}" id="security-pane" role="tabpanel" aria-labelledby="security-tab" tabindex="0">
                            <div class="form-heading"><span class="form-heading__icon"><i class="fa-solid fa-shield-halved"></i></span><div><h4>Keamanan Akun</h4><p>Perbarui password secara berkala untuk menjaga keamanan data akademik.</p></div></div>
                            <div class="security-note"><i class="fa-solid fa-lightbulb"></i><span>Gunakan password yang unik dan sulit ditebak. Hindari memakai NIM, tanggal lahir, atau password yang sama dengan akun lain.</span></div>
                            <form action="{{ route('mahasiswa.home-profile-save-password') }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="profile_section" value="security">
                                <div class="row g-3">
                                    <div class="col-12"><label class="form-label" for="security_key">Kode Akun</label><div class="password-field"><input class="form-control" type="password" id="security_key" value="{{ $student->mhs_code }}" disabled><button class="password-toggle" type="button" data-password-toggle="security_key" aria-label="Tampilkan kode akun" aria-pressed="false"><i class="fa-regular fa-eye"></i></button></div><small class="field-hint">Kode akun bersifat rahasia dan tidak dapat diubah dari halaman ini.</small></div>
                                    <div class="col-12"><label class="form-label" for="old_password">Password Saat Ini</label><div class="password-field"><input class="form-control @error('old_password') is-invalid @enderror" type="password" name="old_password" id="old_password" autocomplete="current-password"><button class="password-toggle" type="button" data-password-toggle="old_password" aria-label="Tampilkan password saat ini" aria-pressed="false"><i class="fa-regular fa-eye"></i></button></div>@error('old_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="new_password">Password Baru</label><div class="password-field"><input class="form-control @error('new_password') is-invalid @enderror" type="password" name="new_password" id="new_password" autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="new_password" aria-label="Tampilkan password baru" aria-pressed="false"><i class="fa-regular fa-eye"></i></button></div>@error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="new_password_confirmed">Konfirmasi Password Baru</label><div class="password-field"><input class="form-control @error('new_password_confirmed') is-invalid @enderror" type="password" name="new_password_confirmed" id="new_password_confirmed" autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="new_password_confirmed" aria-label="Tampilkan konfirmasi password" aria-pressed="false"><i class="fa-regular fa-eye"></i></button></div>@error('new_password_confirmed')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-12 form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-key me-2"></i>Perbarui Password</button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('custom-js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const photoInput = document.getElementById('mhs_image');
            const photoPreview = document.getElementById('profilePhotoPreview');
            photoInput?.addEventListener('change', function (event) {
                const file = event.target.files?.[0];
                if (!file || !file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.addEventListener('load', () => photoPreview.src = reader.result);
                reader.readAsDataURL(file);
            });
            document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const input = document.getElementById(button.dataset.passwordToggle);
                    const shouldShow = input.type === 'password';
                    input.type = shouldShow ? 'text' : 'password';
                    button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
                    button.setAttribute('aria-label', shouldShow ? 'Sembunyikan nilai' : 'Tampilkan nilai');
                    button.innerHTML = shouldShow ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
                });
            });
        });
    </script>
@endsection
