{{-- Alur operasional periode aktif --}}
<li class="sidebar-title">Periode Berjalan</li>
<li class="sidebar-item has-sub {{ Route::is('web-admin.period-opening.*', 'web-admin.master.penawaran-*', 'web-admin.master.jadwal-mingguan-*', 'web-admin.krs-management.*') ? 'active' : '' }}">
    <a href="#" class="sidebar-link">
        <i class="fa-solid fa-arrows-rotate"></i>
        <span>Operasional Akademik</span>
    </a>
    <ul class="submenu">
        <li class="submenu-item {{ Route::is('web-admin.period-opening.*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.period-opening.index') }}" class="submenu-link">Kesiapan Periode</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.penawaran-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.penawaran-index') }}" class="submenu-link">Penawaran Mata Kuliah</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.jadwal-mingguan-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.jadwal-mingguan-index') }}" class="submenu-link">Jadwal &amp; Pertemuan</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.krs-management.*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.krs-management.index') }}" class="submenu-link">Kelola KRS</a>
        </li>
    </ul>
</li>

{{-- Data yang paling sering dipakai dalam pekerjaan akademik --}}
<li class="sidebar-title">Data Akademik</li>
<li class="sidebar-item {{ Route::is('web-admin.workers.student-*') ? 'active' : '' }}">
    <a href="{{ route('web-admin.workers.student-index') }}" class="sidebar-link">
        <i class="fa-solid fa-user-graduate"></i>
        <span>Mahasiswa</span>
    </a>
</li>
<li class="sidebar-item has-sub {{ Route::is('web-admin.master.kelas-*', 'web-admin.master.kurikulum-*', 'web-admin.master.master-matkul-*', 'web-admin.master.matkul-*', 'web-admin.master.jadkul-*') ? 'active' : '' }}">
    <a href="#" class="sidebar-link">
        <i class="fa-solid fa-book-open"></i>
        <span>Perkuliahan</span>
    </a>
    <ul class="submenu">
        <li class="submenu-item {{ Route::is('web-admin.master.kurikulum-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.kurikulum-index') }}" class="submenu-link">Kurikulum</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.master-matkul-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.master-matkul-index') }}" class="submenu-link">Master Mata Kuliah</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.kelas-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.kelas-index') }}" class="submenu-link">Kelas</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.matkul-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.matkul-index') }}" class="submenu-link">Mata Kuliah Periode</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.jadkul-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.jadkul-index') }}" class="submenu-link">Jadwal Kuliah</a>
        </li>
    </ul>
</li>

{{-- Referensi yang relatif jarang berubah --}}
<li class="sidebar-title">Referensi</li>
<li class="sidebar-item has-sub {{ Route::is('web-admin.master.fakultas-*', 'web-admin.master.pstudi-*', 'web-admin.master.proku-*', 'web-admin.master.taka-*', 'web-admin.master.wilayah-*') ? 'active' : '' }}">
    <a href="#" class="sidebar-link">
        <i class="fa-solid fa-building-columns"></i>
        <span>Struktur Akademik</span>
    </a>
    <ul class="submenu">
        <li class="submenu-item {{ Route::is('web-admin.master.fakultas-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.fakultas-index') }}" class="submenu-link">Fakultas</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.pstudi-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.pstudi-index') }}" class="submenu-link">Program Studi</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.taka-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.taka-index') }}" class="submenu-link">Tahun Akademik</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.proku-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.proku-index') }}" class="submenu-link">Program Kuliah</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.master.wilayah-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.master.wilayah-index') }}" class="submenu-link">Wilayah</a>
        </li>
    </ul>
</li>
<li class="sidebar-item has-sub {{ Route::is('web-admin.inventory.*') ? 'active' : '' }}">
    <a href="#" class="sidebar-link">
        <i class="fa-solid fa-building"></i>
        <span>Gedung &amp; Ruangan</span>
    </a>
    <ul class="submenu">
        <li class="submenu-item {{ Route::is('web-admin.inventory.gedung-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.inventory.gedung-index') }}" class="submenu-link">Gedung</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.inventory.ruang-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.inventory.ruang-index') }}" class="submenu-link">Ruangan</a>
        </li>
    </ul>
</li>

<li class="sidebar-title">Pengguna</li>
<li class="sidebar-item has-sub {{ Route::is('web-admin.workers.admin-*', 'web-admin.workers.staff-*', 'web-admin.workers.lecture-*') ? 'active' : '' }}">
    <a href="#" class="sidebar-link">
        <i class="fa-solid fa-users-gear"></i>
        <span>Staf &amp; Dosen</span>
    </a>
    <ul class="submenu">
        <li class="submenu-item {{ Route::is('web-admin.workers.admin-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.workers.admin-index') }}" class="submenu-link">Administrator</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.workers.staff-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.workers.staff-index') }}" class="submenu-link">Pegawai</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.workers.lecture-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.workers.lecture-index') }}" class="submenu-link">Dosen</a>
        </li>
    </ul>
</li>

<li class="sidebar-title">Keuangan</li>
<li class="sidebar-item has-sub {{ Route::is('web-admin.billing-period.*', 'web-admin.finance.*') ? 'active' : '' }}">
    <a href="#" class="sidebar-link">
        <i class="fa-solid fa-wallet"></i>
        <span>Tagihan &amp; Pembayaran</span>
    </a>
    <ul class="submenu">
        <li class="submenu-item {{ Route::is('web-admin.billing-period.*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.billing-period.index') }}" class="submenu-link">Tagihan Periode</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.finance.tagihan-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.finance.tagihan-index') }}" class="submenu-link">Daftar Tagihan</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.finance.pembayaran-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.finance.pembayaran-index') }}" class="submenu-link">Pembayaran</a>
        </li>
        <li class="submenu-item {{ Route::is('web-admin.finance.keuangan-*') ? 'active' : '' }}">
            <a href="{{ route('web-admin.finance.keuangan-index') }}" class="submenu-link">Arus Keuangan</a>
        </li>
    </ul>
</li>
