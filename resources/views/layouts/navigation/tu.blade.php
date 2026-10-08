<div class="navigation-group">
    <a class="nav-link {{ request()->routeIs('tu.dashboard') ? 'active' : '' }}" href="{{ route('tu.dashboard') }}">Dashboard</a>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('penerimaan.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Penerimaan<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('penerimaan.index', 'penerimaan.show', 'penerimaan.store') ? 'active' : '' }}" href="{{ route('penerimaan.index') }}">Input Pembayaran</a>
        <a class="nav-link {{ request()->routeIs('penerimaan.riwayat', 'penerimaan.detail', 'penerimaan.kwitansi') ? 'active' : '' }}" href="{{ route('penerimaan.riwayat') }}">Riwayat Pembayaran</a>
    </div>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('pengeluaran.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Pengeluaran<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('pengeluaran.index', 'pengeluaran.store') ? 'active' : '' }}" href="{{ route('pengeluaran.index') }}">Input Pengeluaran</a>
        <a class="nav-link {{ request()->routeIs('pengeluaran.riwayat', 'pengeluaran.detail') ? 'active' : '' }}" href="{{ route('pengeluaran.riwayat') }}">Riwayat Pengeluaran</a>
    </div>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('arsip-kwitansi.*', 'status-spp.*', 'portal.*.arsip-kwitansi.*', 'portal.*.status-spp.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Kelola Pembayaran<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('arsip-kwitansi.*', 'portal.*.arsip-kwitansi.*') ? 'active' : '' }}" href="{{ route('arsip-kwitansi.index') }}">Arsip Kwitansi Siswa</a>
        <a class="nav-link {{ request()->routeIs('status-spp.*', 'portal.*.status-spp.*') ? 'active' : '' }}" href="{{ route('status-spp.index') }}">Status Pembayaran SPP</a>
    </div>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('rekap-pembayaran.*', 'laporan-tunggakan.*', 'portal.*.rekap-pembayaran.*', 'portal.*.laporan-tunggakan.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Laporan / Rekap<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? 'active' : '' }}" href="{{ route('rekap-pembayaran.index') }}">Rekap Penerimaan</a>
        <a class="nav-link {{ request()->routeIs('laporan-tunggakan.*', 'portal.*.laporan-tunggakan.*') ? 'active' : '' }}" href="{{ route('laporan-tunggakan.index') }}">Laporan Tunggakan</a>
    </div>
</div>
