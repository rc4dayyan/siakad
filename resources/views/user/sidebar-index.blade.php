        <li class="sidebar-title">Publikasi</li>
        <li class="sidebar-item {{ Route::is($prefix.'system.notify-*') ? 'active' : '' }}">
            <a href="{{ route($prefix . 'system.notify-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-bell"></i>
                <span>Pemberitahuan</span>
            </a>
        </li>


        <li class="sidebar-item has-sub {{ Route::is($prefix.'news.*') ? 'active' : '' }}">
            <a href="#" class='sidebar-link'>
                <i class="fa-solid fa-newspaper"></i>
                <span>Berita</span>
            </a>
            <ul class="submenu">
                <li class="submenu-item {{ Route::is($prefix.'news.post-*') ? 'active' : '' }}">
                    <a href="{{ route($prefix . 'news.post-index') }}" class="submenu-link">Daftar Berita</a>
                </li>
                <li class="submenu-item {{ Route::is($prefix.'news.category-*') ? 'active' : '' }}">
                    <a href="{{ route($prefix . 'news.category-index') }}" class="submenu-link">Kategori</a>
                </li>
            </ul>
        </li>
        <li class="sidebar-item {{ Route::is($prefix.'publish.album-*') ? 'active' : '' }}">
            <a href="{{ route($prefix . 'publish.album-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-images"></i>
                <span>Album Foto</span>
            </a>
        </li>
        <li class="sidebar-item {{ Route::is($prefix.'document-*') ? 'active' : '' }}">
            <a href="{{ route($prefix . 'document-index') }}" class='sidebar-link'>
                <i class="fa-solid fa-file-pdf"></i>
                <span>Dokumen</span>
            </a>
        </li>
