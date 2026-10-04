<div class="navigation-group">
    <a class="nav-link {{ request()->routeIs('kepsek.dashboard') ? 'active' : '' }}" href="{{ route('kepsek.dashboard') }}">Dashboard Keuangan</a>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Rekap<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? 'active' : '' }}" href="{{ route('rekap-pembayaran.index') }}">Penerimaan</a>
        <span class="nav-link pending" aria-disabled="true">Pengeluaran <small>Pending</small></span>
        <span class="nav-link pending" aria-disabled="true">Keuangan <small>Pending</small></span>
    </div>
</div>
