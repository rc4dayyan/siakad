        <!-- Bagian menu untuk pengguna yang telah login -->
        {{-- HAK AKSES WEB ADMINISTRATOR --}}
        <li class="sidebar-item {{ Route::is($prefix . 'home-index', request()->path()) ? 'active' : '' }}">
            <a href="{{ route($prefix . 'home-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-home"></i>
                <span>Home</span>
            </a>
        </li>
        <!-- <li class="sidebar-item {{ Route::is($prefix . 'home-profile', request()->path()) ? 'active' : '' }}">
            <a href="{{ route($prefix . 'home-profile') }}" class='sidebar-link'>
                <i class="fa-solid fa-user-edit"></i>
                <span>Profile User</span>
            </a>
        </li> -->

