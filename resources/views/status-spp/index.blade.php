@extends('layouts.app')

@section('title', 'Status Pembayaran SPP | Sistem Pembayaran SPP')
@section('page-title', 'Status Pembayaran SPP')

@section('content')
    <div class="page-header">
        <div>
            <h2>Ringkasan Status SPP</h2>
            <p>Monitor tagihan lunas dan belum bayar siswa tanpa mengubah data induk siswa.</p>
        </div>
    </div>

    <section class="data-card">
        <form class="filter-bar" method="GET" action="{{ route('status-spp.index') }}">
            <div class="filter-field" style="min-width: min(100%, 24rem); flex: 1;">
                <label for="cari">Cari Siswa</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari NIPD atau nama siswa">
            </div>
            <button class="button button-secondary" type="submit">Cari</button>
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIPD</th>
                        <th>Nama Siswa</th>
                        <th>Kelas Aktif</th>
                        <th class="text-right">Belum Bayar</th>
                        <th class="text-right">Lunas</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswa as $item)
                        @php($kelasAktif = $item->siswaKelas->first())
                        <tr>
                            <td class="text-mono">{{ $item->nipd }}</td>
                            <td>{{ $item->nama_siswa }}</td>
                            <td>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</td>
                            <td class="text-mono text-right">{{ $item->tagihan_belum_bayar_count }}</td>
                            <td class="text-mono text-right">{{ $item->tagihan_lunas_count }}</td>
                            <td class="text-right">
                                <a class="button button-secondary button-small" href="{{ route('status-spp.show', $item) }}">Lihat Status</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="6">Tidak ada siswa yang sesuai dengan pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
