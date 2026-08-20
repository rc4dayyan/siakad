<li class="sidebar-title">Periode Berjalan</li>
<li class="sidebar-item {{ Route::is($prefix.'period-opening.*') ? 'active' : '' }}">
    <a href="{{ route($prefix.'period-opening.index') }}" class="sidebar-link">
        <i class="fa-solid fa-list-check"></i>
        <span>Kesiapan Periode</span>
    </a>
</li>

<li class="sidebar-title">Keuangan</li>
<li class="sidebar-item has-sub {{ Route::is($prefix.'billing-period.*', $prefix.'finance.*') ? 'active' : '' }}">
    <a href="#" class='sidebar-link'>
        <i class="fa-solid fa-vault"></i>
        <span>Tagihan &amp; Pembayaran</span>
    </a>
    <ul class="submenu">
        <li class="submenu-item {{ Route::is($prefix.'billing-period.*') ? 'active' : '' }}">
            <a href="{{ route($prefix.'billing-period.index') }}" class="submenu-link">Tagihan Periode</a>
        </li>
        <li class="submenu-item {{ Route::is($prefix.'finance.tagihan-*') ? 'active' : '' }}">
            <a href="{{ route($prefix.'finance.tagihan-index') }}" class="submenu-link">Daftar Tagihan</a>
        </li>
        <li class="submenu-item {{ Route::is($prefix.'finance.pembayaran-*') ? 'active' : '' }}">
            <a href="{{ route($prefix.'finance.pembayaran-index') }}" class="submenu-link">Pembayaran</a>
        </li>
        <li class="submenu-item {{ Route::is($prefix.'finance.keuangan-*') ? 'active' : '' }}">
            <a href="{{ route($prefix.'finance.keuangan-index') }}" class="submenu-link">Arus Keuangan</a>
        </li>
    </ul>
</li>
