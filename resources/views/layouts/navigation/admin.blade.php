<div class="navigation-group">
    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('master.*', 'kenaikan-kelas.*', 'portal.*.master.*', 'portal.*.kenaikan-kelas.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Master Data<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('master.siswa.*', 'portal.*.master.siswa.*') ? 'active' : '' }}" href="{{ route('master.siswa.index') }}">Data Siswa</a>
        <a class="nav-link {{ request()->routeIs('master.jurusan.*', 'portal.*.master.jurusan.*') ? 'active' : '' }}" href="{{ route('master.jurusan.index') }}">Data Jurusan</a>
        <a class="nav-link {{ request()->routeIs('master.kelas.*', 'portal.*.master.kelas.*') ? 'active' : '' }}" href="{{ route('master.kelas.index') }}">Data Kelas</a>
        <a class="nav-link {{ request()->routeIs('master.tahun-ajaran.*', 'portal.*.master.tahun-ajaran.*') ? 'active' : '' }}" href="{{ route('master.tahun-ajaran.index') }}">Tahun Ajaran</a>
        <a class="nav-link {{ request()->routeIs('master.tarif-spp.*', 'portal.*.master.tarif-spp.*') ? 'active' : '' }}" href="{{ route('master.tarif-spp.index') }}">Tarif SPP</a>
        <a class="nav-link {{ request()->routeIs('kenaikan-kelas.*', 'portal.*.kenaikan-kelas.*') ? 'active' : '' }}" href="{{ route('kenaikan-kelas.preview') }}">Kenaikan Kelas</a>
        <a class="nav-link {{ request()->routeIs('master.jenis-pembayaran.*') ? 'active' : '' }}" href="{{ route('master.jenis-pembayaran.index') }}">Jenis Pembayaran</a>
        <a class="nav-link {{ request()->routeIs('master.kategori-pengeluaran.*') ? 'active' : '' }}" href="{{ route('master.kategori-pengeluaran.index') }}">Kategori Pengeluaran</a>
        <a class="nav-link {{ request()->routeIs('master.tagihan-non-spp.*') ? 'active' : '' }}" href="{{ route('master.tagihan-non-spp.index') }}">Tagihan Non-SPP</a>
    </div>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('penerimaan.*', 'pengeluaran.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Transaksi<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('penerimaan.*') ? 'active' : '' }}" href="{{ route('penerimaan.riwayat') }}">Pembatalan Pembayaran</a>
        <a class="nav-link {{ request()->routeIs('pengeluaran.*') ? 'active' : '' }}" href="{{ route('pengeluaran.riwayat') }}">Pembatalan Pengeluaran</a>
    </div>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Rekap<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('rekap-pembayaran.*', 'portal.*.rekap-pembayaran.*') ? 'active' : '' }}" href="{{ route('rekap-pembayaran.index') }}">Rekap Penerimaan</a>
    </div>
</div>

<div class="navigation-group has-submenu {{ request()->routeIs('admin.accounts.*') ? '' : 'is-collapsed' }}">
    <button class="navigation-group-header" type="button">
        Sistem<span class="indicator">▼</span>
    </button>
    <div class="navigation-group-content">
        <a class="nav-link {{ request()->routeIs('admin.accounts.staff.*') ? 'active' : '' }}" href="{{ route('admin.accounts.staff.index') }}">Akun Staff</a>
        <a class="nav-link {{ request()->routeIs('admin.accounts.siswa.*') ? 'active' : '' }}" href="{{ route('admin.accounts.siswa.index') }}">Akun Siswa</a>
        <span class="nav-link pending" aria-disabled="true">Backup Data <small>Pending</small></span>
    </div>
</div>
