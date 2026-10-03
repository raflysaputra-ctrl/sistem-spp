<div class="navigation-group">
    <a class="nav-link {{ request()->routeIs('kepsek.dashboard') ? 'active' : '' }}" href="{{ route('kepsek.dashboard') }}">Dashboard Keuangan</a>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Rekap</p>
    <a class="nav-link {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? 'active' : '' }}" href="{{ route('rekap-pembayaran.index') }}">Penerimaan</a>
</div>
