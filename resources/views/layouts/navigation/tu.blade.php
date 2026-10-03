<div class="navigation-group">
    <a class="nav-link {{ request()->routeIs('tu.dashboard') ? 'active' : '' }}" href="{{ route('tu.dashboard') }}">Dashboard</a>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Penerimaan</p>
    <a class="nav-link {{ request()->routeIs('pembayaran.*', 'portal.*.pembayaran.*') ? 'active' : '' }}" href="{{ route('pembayaran.index') }}">SPP</a>
    <span class="nav-link pending" aria-disabled="true">UJIKOM <small>Pending</small></span>
    <span class="nav-link pending" aria-disabled="true">Pembayaran Lainnya <small>Pending</small></span>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Pengeluaran</p>
    <span class="nav-link pending" aria-disabled="true">Input Pengeluaran <small>Pending</small></span>
    <span class="nav-link pending" aria-disabled="true">Riwayat Pengeluaran <small>Pending</small></span>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Kelola Pembayaran</p>
    <a class="nav-link {{ request()->routeIs('riwayat-pembayaran.*', 'portal.*.riwayat-pembayaran.*') ? 'active' : '' }}" href="{{ route('riwayat-pembayaran.index') }}">Riwayat Pembayaran</a>
    <a class="nav-link {{ request()->routeIs('arsip-kwitansi.*', 'portal.*.arsip-kwitansi.*') ? 'active' : '' }}" href="{{ route('arsip-kwitansi.index') }}">Arsip Kwitansi Siswa</a>
    <a class="nav-link {{ request()->routeIs('status-spp.*', 'portal.*.status-spp.*') ? 'active' : '' }}" href="{{ route('status-spp.index') }}">Status Pembayaran SPP</a>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Laporan / Rekap</p>
    <a class="nav-link {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? 'active' : '' }}" href="{{ route('rekap-pembayaran.index') }}">Rekap Pembayaran</a>
    <a class="nav-link {{ request()->routeIs('laporan-tunggakan.*', 'portal.*.laporan-tunggakan.*') ? 'active' : '' }}" href="{{ route('laporan-tunggakan.index') }}">Laporan Tunggakan</a>
</div>
