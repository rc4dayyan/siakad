@extends('base.base-dash-index')
@section('title')
Data Pengguna Mahasiswa - Siakad By Internal Developer
@endsection
@section('menu')
Data Pengguna Mahasiswa
@endsection
@section('submenu')
Edit {{ $student->mhs_name }}
@endsection
@section('urlmenu')
{{-- KONDISIONAL BACK BUTTON --}}
{{ route($prefix.'workers.student-index') }}
@endsection
@section('subdesc')
Halaman untuk mengedit data pengguna {{ $student->mhs_name }}
@endsection
@section('content')
<form action="{{ route($prefix.'workers.student-update', $student->mhs_code) }}" method="POST" enctype="multipart/form-data">
    @method('PATCH')
    @csrf
    <section class="section row">

        <div class="col-lg-4 col-12">

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title">Ubah Foto Profile</h4>
                    <div class="">
                        <a href="@yield('urlmenu')" class="btn btn-outline-warning"><i class="fa-solid fa-backward"></i></a>
                        <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('mahasiswa.home-profile-save-image') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <img src="{{ asset('storage/images/' . $student->mhs_image) }}" class="card-img-top" alt="">
                        <hr>
                        <div class="form-group">
                            <label for="mhs_image">Upload Foto Profile</label>
                            <div class="d-flex justify-content-between align-items-center">

                                <input type="file" class="form-control" name="mhs_image" id="mhs_image">
                                @error('mhs_image')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                                <button type="submit" class="btn btn-outline-primary" style="margin-left: 10px"><i class="fa-solid fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8 col-12">
            <div class="card">
                <div class="card-body">

                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true"> Personal</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="contact-tab" data-bs-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false"> Kontak</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false"> Keamanan</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                            <hr>
                            <div class="row">
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_name">Nama Lengkap</label>
                                    <input type="text" name="mhs_name" id="mhs_name" class="form-control" placeholder="Nama lengkap..." readonly value="{{ $student->mhs_name }}">
                                    @error('mhs_name')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_nim">Nomor NIM</label>
                                    <input type="text" name="mhs_nim" id="mhs_nim" class="form-control" placeholder="Nomor NIM..." readonly value="{{ $student->mhs_nim }}">
                                    @error('mhs_nim')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="years_id">Tahun Masuk</label>
                                    <input type="text" name="years_id" id="years_id" class="form-control" placeholder="Tahun Masuk..." readonly value="Angkatan {{ $student->kelas->taka->year_start }}">
                                    @error('years_id')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="faku_id">Fakultas</label>
                                    <input type="text" name="faku_id" id="faku_id" class="form-control" placeholder="Fakultas..." readonly value="{{ $student->kelas->pstudi->fakultas->name }}">
                                    @error('faku_id')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="pstudi_id">Program Studi</label>
                                    <input type="text" name="pstudi_id" id="pstudi_id" class="form-control" placeholder="Nama Program Studi..." readonly value="{{ $student->kelas->pstudi->name . ' - ' . $student->kelas->taka->semester }}">
                                    @error('pstudi_id')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="class_id">Kelas</label>
                                    <select name="class_id" id="class_id" class="form-select">
                                        <option value="" selected>Pilih Jenis Kelamin</option>
                                        @foreach ($kelas as $item)
                                        <option value="{{ $item->id }}" {{ $item->id === $student->class_id ? 'selected' : ''}}>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('class_id')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_gend">Jenis Kelamin</label>
                                    <select name="mhs_gend" id="mhs_gend" class="form-select">
                                        <option value="" selected>Pilih Jenis Kelamin</option>
                                        <option value="L" {{ $student->mhs_gend === 'L' ? 'selected' : ''}}>Laki Laki</option>
                                        <option value="P" {{ $student->mhs_gend === 'P' ? 'selected' : ''}}>Perempuan</option>
                                    </select>
                                    @error('mhs_gend')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_birthplace">Tempat Lahir</label>
                                    <input type="text" name="mhs_birthplace" id="mhs_birthplace" class="form-control" placeholder="Tempat Lahir..." value="{{ $student->mhs_birthplace }}">
                                    @error('mhs_birthplace')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_birthdate">Tanggal Lahir</label>
                                    <input type="date" name="mhs_birthdate" id="mhs_birthdate" class="form-control" placeholder="Tanggal Lahir..." value="{{ $student->mhs_birthdate }}">
                                    @error('mhs_birthdate')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_reli">Agama</label>
                                    <select name="mhs_reli" id="mhs_reli" class="form-select">
                                        <option value="" selected>Pilih Agama</option>
                                        <option value="1" {{ $student->raw_mhs_reli === '1' ? 'selected' : ''}}>1. Agama Islam</option>
                                        <option value="2" {{ $student->raw_mhs_reli === '2' ? 'selected' : ''}}>2. Agama Kristen Protestan</option>
                                        <option value="3" {{ $student->raw_mhs_reli === '3' ? 'selected' : ''}}>3. Agama Kriten Katholik</option>
                                        <option value="4" {{ $student->raw_mhs_reli === '4' ? 'selected' : ''}}>4. Agama Hindu</option>
                                        <option value="5" {{ $student->raw_mhs_reli === '5' ? 'selected' : ''}}>5. Agama Buddha</option>
                                        <option value="6" {{ $student->raw_mhs_reli === '6' ? 'selected' : ''}}>6. Agama Konghuchu</option>
                                    </select>
                                    @error('mhs_reli')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>


                        </div>
                        <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                            <hr>

                            <div class="row">
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_phone">Nomor HandPhone</label>
                                    <input type="text" class="form-control" name="mhs_phone" id="mhs_phone" value="{{ $student->mhs_phone }}">
                                    @error('mhs_phone')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_mail">Alamat Email</label>
                                    <input type="text" class="form-control" name="mhs_mail" id="mhs_mail" readonly value="{{ $student->mhs_mail }}">
                                    @error('mhs_mail')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_parent_father">Nama Ayah</label>
                                    <input type="text" class="form-control" name="mhs_parent_father" id="mhs_parent_father" placeholder="nama ayah..." value="{{ $student->mhs_parent_father }}">
                                    @error('mhs_parent_father')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_parent_father_phone">Nomor Telepon Ayah</label>
                                    <input type="text" class="form-control" name="mhs_parent_father_phone" id="mhs_parent_father_phone" placeholder="nomor telepon ayah..." value="{{ $student->mhs_parent_father_phone }}">
                                    @error('mhs_parent_father_phone')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_parent_mother">Nama Ibu</label>
                                    <input type="text" class="form-control" name="mhs_parent_mother" id="mhs_parent_mother" placeholder="nama ibu..." value="{{ $student->mhs_parent_mother }}">
                                    @error('mhs_parent_mother')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_parent_mother_phone">Nomor Telepon Ibu</label>
                                    <input type="text" class="form-control" name="mhs_parent_mother_phone" id="mhs_parent_mother_phone" placeholder="nomor telepon ibu..." value="{{ $student->mhs_parent_mother_phone }}">
                                    @error('mhs_parent_mother_phone')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_wali_name">Nama Wali Mahasiswa</label>
                                    <input type="text" class="form-control" name="mhs_wali_name" id="mhs_wali_name" placeholder="nama wali..." value="{{ $student->mhs_wali_name }}">
                                    @error('mhs_wali_name')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_wali_phone">Nomor Telepon Wali</label>
                                    <input type="text" class="form-control" name="mhs_wali_phone" id="mhs_wali_phone" placeholder="nomor telepon wali..." value="{{ $student->mhs_wali_phone }}">
                                    @error('mhs_wali_phone')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-12 col-12">
                                    <label for="mhs_addr_domisili">Alamat Lengkap Domisili / Tempat Tinggal</label>
                                    <textarea cols="15" rows="4" class="form-control" name="mhs_addr_domisili" id="mhs_addr_domisili" placeholder="alamat lengkap domisili / tempat tinggal..." value="{{ $student->mhs_addr_domisili }}">{{ $student->mhs_addr_domisili == null ? '' : $student->mhs_addr_domisili }}</textarea>
                                    @error('mhs_addr_domisili')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_addr_kelurahan">Kelurahan</label>
                                    <input type="text" class="form-control" name="mhs_addr_kelurahan" id="mhs_addr_kelurahan" placeholder="nama kelurahan..." value="{{ $student->mhs_addr_kelurahan }}">
                                    @error('mhs_addr_kelurahan')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_addr_kecamatan">Kecamatan</label>
                                    <input type="text" class="form-control" name="mhs_addr_kecamatan" id="mhs_addr_kecamatan" placeholder="nama kecamatan..." value="{{ $student->mhs_addr_kecamatan }}">
                                    @error('mhs_addr_kecamatan')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_addr_kota">Kota</label>
                                    <input type="text" class="form-control" name="mhs_addr_kota" id="mhs_addr_kota" placeholder="nama kota..." value="{{ $student->mhs_addr_kota }}">
                                    @error('mhs_addr_kota')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_addr_provinsi">Provinsi</label>
                                    <input type="text" class="form-control" name="mhs_addr_provinsi" id="mhs_addr_provinsi" placeholder="nama provinsi..." value="{{ $student->mhs_addr_provinsi }}">
                                    @error('mhs_addr_provinsi')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                            </div>
                        </div>
                        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <hr>

                            <div class="row">
                                <div class="form-group col-lg-6 col-12">
                                    <label for="SecurityKey">Security Key</label>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <input type="password" class="form-control" name="mhs_code" id="SecurityKey" value="{{ $student->mhs_code }}" disabled>
                                        <span class="btn btn-sm btn-outline-danger" style="margin-left: 5px" id="showPasswordButton"><i class="fa-solid fa-eye"></i></span>
                                        @error('mhs_code')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="mhs_stat">Pilih Status Mahasiswa</label>
                                    <select name="mhs_stat" id="mhs_stat" class="form-select">
                                        <option value="" selected>Pilih Status Mahasiswa</option>
                                        <option value="0" {{ $student->raw_mhs_stat === 0 ? 'selected' : '' }}>Calon Mahasiswa</option>
                                        <option value="1" {{ $student->raw_mhs_stat === 1 ? 'selected' : '' }}>Mahasiswa Aktif</option>
                                        <option value="2" {{ $student->raw_mhs_stat === 2 ? 'selected' : '' }}>Mahasiswa Non-Aktif</option>
                                        <option value="3" {{ $student->raw_mhs_stat === 3 ? 'selected' : '' }}>Mahasiswa Alumni</option>


                                    </select>
                                    @error('old_password')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="newPassword">Password Baru</label>
                                    <div class="d-flex justify-content-between align-items-center">

                                        <input type="password" class="form-control" name="password" id="newPassword">
                                        <span class="btn btn-sm btn-outline-danger" style="margin-left: 5px" id="showPasswordButton"><i class="fa-solid fa-eye"></i></span>
                                    </div>
                                    @error('password')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-6 col-12">
                                    <label for="newPasswordKonfirm">Konfirmasi Password Baru</label>
                                    <div class="d-flex justify-content-between align-items-center">

                                        <input type="password" class="form-control" name="password_confirmed" id="newPasswordKonfirm">
                                        <span class="btn btn-sm btn-outline-danger" style="margin-left: 5px" id="showPasswordButton"><i class="fa-solid fa-eye"></i></span>
                                    </div>
                                    @error('password_confirmed')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </section>

</form>

@if ($academicPeriod && ! $registration)
<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Registrasi Mahasiswa ke {{ $academicPeriod->name }}</h4>
            </div>
            <div class="card-body">
                @if (! $academicPeriod->isWritable())
                    <div class="alert alert-info mb-0">Registrasi tidak dapat dibuat karena periode ini sudah ditutup atau diarsipkan.</div>
                @elseif ($latestRegistration?->hasTerminalAcademicStatus())
                    <div class="alert alert-warning mb-0">
                        Registrasi baru tidak dapat dibuat karena status terakhir mahasiswa adalah {{ $latestRegistration->academic_status_label }}.
                    </div>
                @elseif ($academicPeriodClasses->isEmpty())
                    <div class="alert alert-warning mb-0">Belum ada kelas pada periode ini. Buat kelas sebelum melakukan registrasi.</div>
                @elseif ($academicAdvisors->isEmpty())
                    <div class="alert alert-warning mb-0">Belum ada dosen aktif yang dapat dipilih sebagai dosen wali.</div>
                @else
                    <form action="{{ route($prefix.'workers.student-registration-store', $student->mhs_code) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="form-group col-lg-2 col-12">
                                <label for="semester_mahasiswa">Semester</label>
                                <select name="semester_mahasiswa" id="semester_mahasiswa" class="form-select" required>
                                    @for ($semester = 1; $semester <= 14; $semester++)
                                        <option value="{{ $semester }}" @selected((int) old('semester_mahasiswa', $suggestedSemester) === $semester)>
                                            Semester {{ $semester }}
                                        </option>
                                    @endfor
                                </select>
                                @error('semester_mahasiswa')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-2 col-12">
                                <label for="registration_status_akademik">Status akademik</label>
                                <select name="status_akademik" id="registration_status_akademik" class="form-select" required>
                                    @foreach (\App\Models\RegistrasiMahasiswa::academicStatuses() as $status)
                                        <option value="{{ $status }}" @selected(old('status_akademik', \App\Models\RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF) === $status)>
                                            {{ \App\Models\RegistrasiMahasiswa::academicStatusLabel($status) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status_akademik')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="registration_kelas_id">Kelas</label>
                                <select name="kelas_id" id="registration_kelas_id" class="form-select" required>
                                    <option value="">Pilih kelas</option>
                                    @foreach ($academicPeriodClasses as $class)
                                        <option value="{{ $class->id }}" @selected((int) old('kelas_id') === $class->id)>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                @error('kelas_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-3 col-12">
                                <label for="dosen_wali_id">Dosen wali</label>
                                <select name="dosen_wali_id" id="dosen_wali_id" class="form-select" required>
                                    <option value="">Pilih dosen wali</option>
                                    @foreach ($academicAdvisors as $advisor)
                                        <option value="{{ $advisor->id }}" @selected((int) old('dosen_wali_id') === $advisor->id)>{{ $advisor->dsn_name }}</option>
                                    @endforeach
                                </select>
                                @error('dosen_wali_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-lg-2 col-12">
                                <label for="batas_sks">Batas SKS</label>
                                <input type="number" name="batas_sks" id="batas_sks" class="form-control" min="1" max="24" value="{{ old('batas_sks', 24) }}" required>
                                @error('batas_sks')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        @error('registration')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-outline-primary">Registrasikan mahasiswa</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Status Akademik Periode</h4>
            </div>
            <div class="card-body">
                @if (! $academicPeriod)
                    <div class="alert alert-warning mb-0">Belum ada periode akademik yang dapat digunakan.</div>
                @elseif (! $registration)
                    <div class="alert alert-warning mb-0">
                        Mahasiswa belum memiliki registrasi pada {{ $academicPeriod->name }}.
                    </div>
                @else
                    <div class="row">
                        <div class="col-lg-4 col-12 mb-3">
                            <strong>Periode</strong><br>
                            {{ $academicPeriod->name }}
                        </div>
                        <div class="col-lg-4 col-12 mb-3">
                            <strong>Status saat ini</strong><br>
                            <span class="badge bg-primary">{{ $registration->academic_status_label }}</span>
                        </div>
                        <div class="col-lg-4 col-12 mb-3">
                            <strong>Semester mahasiswa</strong><br>
                            Semester {{ $registration->semester_mahasiswa }}
                        </div>
                    </div>

                    @if ($academicPeriod->isWritable() && count($academicStatusTransitions) > 0)
                        <form action="{{ route($prefix.'workers.student-academic-status-update', $student->mhs_code) }}" method="POST" class="border rounded p-3 mb-4">
                            @csrf
                            @method('PATCH')
                            <div class="row">
                                <div class="form-group col-lg-4 col-12">
                                    <label for="status_akademik">Status baru</label>
                                    <select name="status_akademik" id="status_akademik" class="form-select" required>
                                        <option value="">Pilih status</option>
                                        @foreach ($academicStatusTransitions as $status)
                                            <option value="{{ $status }}" @selected(old('status_akademik') === $status)>
                                                {{ \App\Models\RegistrasiMahasiswa::academicStatusLabel($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status_akademik')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-3 col-12">
                                    <label for="berlaku_mulai">Tanggal berlaku</label>
                                    <input type="date" name="berlaku_mulai" id="berlaku_mulai" class="form-control" max="{{ now()->toDateString() }}" value="{{ old('berlaku_mulai', now()->toDateString()) }}" required>
                                    @error('berlaku_mulai')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-lg-5 col-12">
                                    <label for="alasan">Alasan perubahan</label>
                                    <textarea name="alasan" id="alasan" class="form-control" rows="2" maxlength="1000" required>{{ old('alasan') }}</textarea>
                                    @error('alasan')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Simpan perubahan status</button>
                        </form>
                    @elseif (! $academicPeriod->isWritable())
                        <div class="alert alert-info">Periode ditutup atau diarsipkan sehingga status hanya dapat dilihat.</div>
                    @else
                        <div class="alert alert-info">Status ini bersifat terminal dan tidak dapat diubah kembali.</div>
                    @endif

                    <h5>Riwayat Perubahan</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal berlaku</th>
                                    <th>Perubahan</th>
                                    <th>Alasan</th>
                                    <th>Diubah oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($registration->riwayatStatus->sortByDesc('created_at') as $history)
                                    <tr>
                                        <td>{{ $history->berlaku_mulai->format('d-m-Y') }}</td>
                                        <td>
                                            {{ \App\Models\RegistrasiMahasiswa::academicStatusLabel($history->status_sebelumnya) }}
                                            &rarr;
                                            {{ \App\Models\RegistrasiMahasiswa::academicStatusLabel($history->status_baru) }}
                                        </td>
                                        <td>{{ $history->alasan }}</td>
                                        <td>{{ $history->changedBy->name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Belum ada perubahan status pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Riwayat Registrasi Akademik</h4>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Semester</th>
                            <th>Status Akademik</th>
                            <th>Status Registrasi</th>
                            <th>Kelas</th>
                            <th>Dosen Wali</th>
                            <th>Batas SKS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registrationHistory as $historyRegistration)
                            <tr>
                                <td>{{ $historyRegistration->taka->name }}</td>
                                <td>{{ $historyRegistration->semester_mahasiswa }}</td>
                                <td>{{ $historyRegistration->academic_status_label }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $historyRegistration->status_registrasi)) }}</td>
                                <td>{{ $historyRegistration->kelas?->name ?? '-' }}</td>
                                <td>{{ $historyRegistration->dosenWali?->dsn_name ?? '-' }}</td>
                                <td>{{ $historyRegistration->batas_sks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Belum ada riwayat registrasi akademik.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
@section('custom-js')
<script>
    document.getElementById("mhs_image").onchange = function(event) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.querySelector('.card-img-top');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    };
</script>
<script>
    const showPasswordButtons = document.querySelectorAll('.btn-outline-danger');
    showPasswordButtons.forEach((btn, index) => {
        const passwordInput = btn.previousElementSibling;
        btn.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text'; // Show password
                btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>'; // Change icon to eye-slash
            } else {
                passwordInput.type = 'password'; // Hide password
                btn.innerHTML = '<i class="fa-solid fa-eye"></i>'; // Change icon back to eye
            }
        });
    });
</script>
@endsection
