        <li class="sidebar-title">Periode Berjalan</li>
        <li class="sidebar-item has-sub {{ Route::is($prefix.'period-opening.*', $prefix.'master.penawaran-*', $prefix.'master.jadwal-mingguan-*', $prefix.'krs-management.*') ? 'active' : '' }}">
            <a href="#" class="sidebar-link">
                <i class="fa-solid fa-arrows-rotate"></i>
                <span>Operasional Akademik</span>
            </a>
            <ul class="submenu">
                <li class="submenu-item {{ Route::is($prefix.'period-opening.*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'period-opening.index') }}" class="submenu-link">Kesiapan Periode</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'master.penawaran-*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'master.penawaran-index') }}" class="submenu-link">Penawaran Mata Kuliah</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'master.jadwal-mingguan-*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'master.jadwal-mingguan-index') }}" class="submenu-link">Jadwal &amp; Pertemuan</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'krs-management.*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'krs-management.index') }}" class="submenu-link">Kelola KRS</a>
                </li>
            </ul>
        </li>

        <li class="sidebar-title">Data Akademik</li>
        <li class="sidebar-item {{ Route::is($prefix.'workers.student-*') ? 'active' : '' }}">
            <a href="{{ route($prefix . 'workers.student-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-users"></i>
                <span>Mahasiswa</span>
            </a>
        </li>
        <li class="sidebar-item has-sub {{ Route::is($prefix.'master.kurikulum-*', $prefix.'master.kelas-*', $prefix.'master.matkul-*', $prefix.'master.jadkul-*', $prefix.'krs-list.*', $prefix.'dosen-pengajar-list.*') ? 'active' : '' }}">
            <a href="#" class='sidebar-link'>
                <i class="fa-solid fa-school"></i>
                <span>Perkuliahan</span>
            </a>
            <ul class="submenu">
                <li class="submenu-item {{ Route::is($prefix.'master.kurikulum-*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'master.kurikulum-index') }}" class="submenu-link">Kurikulum</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'master.kelas-*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'master.kelas-index') }}" class="submenu-link">Kelas</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'master.matkul-*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'master.matkul-index') }}" class="submenu-link">Mata Kuliah Periode</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'master.jadkul-*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'master.jadkul-index') }}" class="submenu-link">Jadwal Kuliah</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'krs-list.*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'krs-list.index') }}" class="submenu-link">List KRS</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'dosen-pengajar-list.*') ? 'active' : '' }}">
                    <a href="{{ route($prefix.'dosen-pengajar-list.index') }}" class="submenu-link">List Dosen Pengajar</a>
                </li>
            </ul>
        </li>
