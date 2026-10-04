<div class="navigation-group">
    <a class="nav-link {{ request()->routeIs('tu.dashboard') ? 'active' : '' }}" href="{{ route('tu.dashboard') }}">Dashboard</a>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('pembayaran.*', 'portal.*.pembayaran.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Penerimaan<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('pembayaran.*', 'portal.*.pembayaran.*') ? 'active' : '' }}" href="{{ route('pembayaran.index') }}">SPP</a>
        <span class="nav-link pending" aria-disabled="true">UJIKOM <small>Pending</small></span>
        <span class="nav-link pending" aria-disabled="true">Pembayaran Lainnya <small>Pending</small></span>
    </div>
</div>

<div class="navigation-group has-submenu is-collapsed">
    <button class="navigation-group-header" type="button">
        Pengeluaran<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <span class="nav-link pending" aria-disabled="true">Input Pengeluaran <small>Pending</small></span>
        <span class="nav-link pending" aria-disabled="true">Riwayat Pengeluaran <small>Pending</small></span>
    </div>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('riwayat-pembayaran.*', 'arsip-kwitansi.*', 'status-spp.*', 'portal.*.riwayat-pembayaran.*', 'portal.*.arsip-kwitansi.*', 'portal.*.status-spp.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Kelola Pembayaran<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('riwayat-pembayaran.*', 'portal.*.riwayat-pembayaran.*') ? 'active' : '' }}" href="{{ route('riwayat-pembayaran.index') }}">Riwayat Pembayaran</a>
        <a class="nav-link {{ request()->routeIs('arsip-kwitansi.*', 'portal.*.arsip-kwitansi.*') ? 'active' : '' }}" href="{{ route('arsip-kwitansi.index') }}">Arsip Kwitansi Siswa</a>
        <a class="nav-link {{ request()->routeIs('status-spp.*', 'portal.*.status-spp.*') ? 'active' : '' }}" href="{{ route('status-spp.index') }}">Status Pembayaran SPP</a>
    </div>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('rekap-pembayaran.*', 'laporan-tunggakan.*', 'portal.*.rekap-pembayaran.*', 'portal.*.laporan-tunggakan.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Laporan / Rekap<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? 'active' : '' }}" href="{{ route('rekap-pembayaran.index') }}">Rekap Pembayaran</a>
        <a class="nav-link {{ request()->routeIs('laporan-tunggakan.*', 'portal.*.laporan-tunggakan.*') ? 'active' : '' }}" href="{{ route('laporan-tunggakan.index') }}">Laporan Tunggakan</a>
    </div>
</div>
