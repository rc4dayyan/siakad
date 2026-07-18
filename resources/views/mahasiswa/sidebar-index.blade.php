<li class="sidebar-item {{ Route::is('mahasiswa.home-index') ? 'active' : '' }}">
    <a href="{{ route('mahasiswa.home-index') }}" class='sidebar-link'>
        <i class="fa-solid fa-home"></i>
        <span>Beranda</span>
    </a>
</li>
<li class="sidebar-title">Aktivitas Saya</li>
<li class="sidebar-item {{ Route::is('mahasiswa.home-jadkul-*') ? 'active' : '' }}">
    <a href="{{ route('mahasiswa.home-jadkul-index') }}" class='sidebar-link'>
        <i class="fa-solid fa-calendar"></i>
        <span>Jadwal Kuliah</span>
    </a>
</li>
<li class="sidebar-item {{ Route::is('mahasiswa.akademik.krs-*') ? 'active' : '' }}">
    <a href="{{ route('mahasiswa.akademik.krs-index') }}" class="sidebar-link">
        <i class="fa-solid fa-file-signature"></i>
        <span>KRS</span>
    </a>
</li>
<li class="sidebar-item {{ Route::is('mahasiswa.akademik.tugas-*') ? 'active' : '' }}">
    <a href="{{ route('mahasiswa.akademik.tugas-index') }}" class='sidebar-link'>
        <i class="fa-solid fa-list-check"></i>
        <span>Tugas Kuliah</span>
    </a>
</li>
<li class="sidebar-item {{ Route::is('mahasiswa.home-tagihan-*') ? 'active' : '' }}">
    <a href="{{ route('mahasiswa.home-tagihan-index') }}" class="sidebar-link">
        <i class="fa-solid fa-file-invoice"></i>
        <span>Tagihan</span>
    </a>
</li>

<li class="sidebar-title">Hasil Studi</li>
<li class="sidebar-item {{ Route::is('mahasiswa.akademik.nilai-*') ? 'active' : '' }}">
    <a href="{{ route('mahasiswa.akademik.nilai-index') }}" class='sidebar-link'>
        <i class="fa-solid fa-list-check"></i>
        <span>Nilai</span>
    </a>
</li>

<li class="sidebar-title">Bantuan</li>
<li class="sidebar-item {{ Route::is('mahasiswa.support.*') ? 'active' : '' }}">
    <a href="{{ route('mahasiswa.support.ticket-index') }}" class="sidebar-link">
        <i class="fa-solid fa-ticket"></i>
        <span>Pusat Bantuan</span>
    </a>
</li>
