@extends('layouts.app')

@section('title', 'Pembayaran Non-SPP | Sistem Informasi Keuangan')
@section('page-title', 'Pembayaran Non-SPP')

@section('content')
    <div class="page-header">
        <div>
            <h2>Pilih Siswa</h2>
            <p>Pilih siswa yang memiliki tagihan non-SPP (PTS, PAS, PKL, UJIKOM, Biaya Awal Masuk) untuk melakukan pembayaran.</p>
        </div>
    </div>

    <section class="data-card">
        <form class="filter-bar" method="GET" action="{{ route('pembayaran-non-spp.index') }}">
            <div class="filter-field" style="min-width: min(100%, 18rem); flex: 1;">
                <label for="cari">NIPD atau Nama Siswa</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Masukkan NIPD atau nama siswa" autofocus>
            </div>
            <div class="filter-field">
                <label for="id_jenis_pembayaran">Jenis Pembayaran</label>
                <select id="id_jenis_pembayaran" name="id_jenis_pembayaran">
                    <option value="">Semua Jenis</option>
                    @foreach ($jenisPembayaran as $item)
                        <option value="{{ $item->id_jenis_pembayaran }}" @selected(($filters['id_jenis_pembayaran'] ?? null) == $item->id_jenis_pembayaran)>{{ $item->nama_jenis }}</option>
                    @endforeach
                </select>
            </div>
            <button class="button button-primary" type="submit">Terapkan Filter</button>
            @if (count($filters) > 0)
                <a class="button button-secondary" href="{{ route('pembayaran-non-spp.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIPD</th>
                        <th>Nama Siswa</th>
                        <th>Kelas Aktif</th>
                        <th>Jurusan</th>
                        <th>Jenis Tagihan Non-SPP</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswa as $item)
                        @php($kelasAktif = $item->siswaKelas->first())
                        @php($tagihan = $item->tagihanNonSpp->where('status', '!=', 'lunas')->first())
                        <tr>
                            <td class="text-mono">{{ $item->nipd }}</td>
                            <td>{{ $item->nama_siswa }}</td>
                            <td>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</td>
                            <td class="text-mono">{{ $kelasAktif?->kelas?->jurusan?->kode_jurusan ?? '-' }}</td>
                            <td>{{ $item->tagihanNonSpp->pluck('jenisPembayaran.nama_jenis')->filter()->unique()->implode(', ') ?: '-' }}</td>
                            <td class="text-right">
                                @if ($tagihan)
                                    <a class="button button-primary button-small" href="{{ route('pembayaran-non-spp.show', $item->id_siswa) }}">Bayar</a>
                                @else
                                    <span class="nav-link pending" aria-disabled="true">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Tidak ada siswa dengan tagihan non-SPP yang belum lunas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div>Menampilkan {{ $siswa->count() }} dari {{ $siswa->total() }} siswa</div>
            {{ $siswa->links() }}
        </div>
    </section>
@endsection
