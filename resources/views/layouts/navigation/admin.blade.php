<div class="navigation-group">
    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Master Data</p>
    <a class="nav-link {{ request()->routeIs('master.siswa.*', 'portal.*.master.siswa.*') ? 'active' : '' }}" href="{{ route('master.siswa.index') }}">Data Siswa</a>
    <a class="nav-link {{ request()->routeIs('master.jurusan.*', 'portal.*.master.jurusan.*') ? 'active' : '' }}" href="{{ route('master.jurusan.index') }}">Data Jurusan</a>
    <a class="nav-link {{ request()->routeIs('master.kelas.*', 'portal.*.master.kelas.*') ? 'active' : '' }}" href="{{ route('master.kelas.index') }}">Data Kelas</a>
    <a class="nav-link {{ request()->routeIs('master.tahun-ajaran.*', 'portal.*.master.tahun-ajaran.*') ? 'active' : '' }}" href="{{ route('master.tahun-ajaran.index') }}">Tahun Ajaran</a>
    <a class="nav-link {{ request()->routeIs('master.tarif-spp.*', 'portal.*.master.tarif-spp.*') ? 'active' : '' }}" href="{{ route('master.tarif-spp.index') }}">Tarif SPP</a>
    <a class="nav-link {{ request()->routeIs('kenaikan-kelas.*', 'portal.*.kenaikan-kelas.*') ? 'active' : '' }}" href="{{ route('kenaikan-kelas.preview') }}">Kenaikan Kelas</a>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Transaksi</p>
    <a class="nav-link {{ request()->routeIs('riwayat-pembayaran.*', 'portal.*.riwayat-pembayaran.*') ? 'active' : '' }}" href="{{ route('riwayat-pembayaran.index') }}">Pembatalan Transaksi</a>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Rekap</p>
    <a class="nav-link {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? 'active' : '' }}" href="{{ route('rekap-pembayaran.index') }}">Rekap Pembayaran</a>
</div>

<div class="navigation-group">
    <p class="navigation-heading">Sistem</p>
    <a class="nav-link {{ request()->routeIs('admin.accounts.staff.*') ? 'active' : '' }}" href="{{ route('admin.accounts.staff.index') }}">Akun Staff</a>
    <a class="nav-link {{ request()->routeIs('admin.accounts.siswa.*') ? 'active' : '' }}" href="{{ route('admin.accounts.siswa.index') }}">Akun Siswa</a>
    <span class="nav-link pending" aria-disabled="true">Backup Data <small>Pending</small></span>
</div>
