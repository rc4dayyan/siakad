        <li class="sidebar-item {{ Route::is('dosen.home-index') ? 'active' : '' }}">
            <a href="{{ route('dosen.home-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-home"></i>
                <span>Beranda</span>
            </a>
        </li>
        <li class="sidebar-title">Perkuliahan</li>
        <li class="sidebar-item {{ Route::is('dosen.akademik.jadwal-*') ? 'active' : '' }}">
            <a href="{{ route('dosen.akademik.jadwal-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-calendar"></i>
                <span>Jadwal Mengajar</span>
            </a>
        </li>
        <li class="sidebar-item {{ Route::is('dosen.akademik.matkul-*') ? 'active' : '' }}">
            <a href="{{ route('dosen.akademik.matkul-index') }}" class="sidebar-link">
                <i class="fa-solid fa-book-open"></i>
                <span>Mata Kuliah &amp; Nilai</span>
            </a>
        </li>
        <li class="sidebar-item {{ Route::is('dosen.akademik.stask-*') ? 'active' : '' }}">
            <a href="{{ route('dosen.akademik.stask-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-tasks"></i>
                <span>Tugas &amp; Penilaian</span>
            </a>
        </li>

        <li class="sidebar-title">Bimbingan Akademik</li>
        <li class="sidebar-item {{ Route::is('dosen.akademik.krs-*') ? 'active' : '' }}">
            <a href="{{ route('dosen.akademik.krs-index') }}" class="sidebar-link">
                <i class="fa-solid fa-file-signature"></i>
                <span>Persetujuan KRS</span>
            </a>
        </li>
