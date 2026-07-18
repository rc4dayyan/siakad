        <li class="sidebar-item {{ Route::is($prefix.'home-index') ? 'active' : '' }}">
            <a href="{{ route($prefix . 'home-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-home"></i>
                <span>Beranda</span>
            </a>
        </li>
        @if ((int) Auth::user()->raw_type === 4)
            <li class="sidebar-item {{ Route::is('admin.krs-management.*') ? 'active' : '' }}">
                <a href="{{ route('admin.krs-management.index') }}" class="sidebar-link">
                    <i class="fa-solid fa-file-signature"></i>
                    <span>KRS Mahasiswa</span>
                </a>
            </li>
        @endif
