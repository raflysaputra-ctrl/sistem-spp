@extends('layouts.app')

@section('title', 'Riwayat Penerimaan Non-SPP | Sistem Informasi Keuangan')
@section('page-title', 'Riwayat Penerimaan Non-SPP')

@section('content')
    <div class="page-header">
        <div>
            <h2>Riwayat Penerimaan Non-SPP</h2>
            <p>Satu baris mewakili satu kwitansi yang dapat memuat beberapa tagihan non-SPP siswa.</p>
        </div>
    </div>

    <section class="data-card" style="margin-bottom: 1.5rem;">
        <form class="filter-bar" method="GET" action="{{ route('riwayat-pembayaran-non-spp.index') }}">
            <div class="filter-field">
                <label for="cari">Cari</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="No. kwitansi, NIPD, atau siswa">
            </div>
            <div class="filter-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                    <option value="dibatalkan" @selected($filters['status'] === 'dibatalkan')>Dibatalkan</option>
                    <option value="semua" @selected($filters['status'] === 'semua')>Semua</option>
                </select>
            </div>
            <button class="button button-primary" type="submit">Terapkan Filter</button>
            <a class="button button-secondary" href="{{ route('riwayat-pembayaran-non-spp.index') }}">Reset</a>
        </form>
    </section>

    <section class="data-card">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Kwitansi</th>
                        <th>Siswa</th>
                        <th>Rincian Tagihan</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatPembayaran as $item)
                        <tr>
                            <td class="text-mono">{{ $item->tanggal_bayar->format('d/m/Y H:i') }}</td>
                            <td class="text-mono"><strong>{{ $item->no_kwitansi }}</strong></td>
                            <td>{{ $item->siswa->nama_siswa }}<br><span class="text-muted text-mono">{{ $item->siswa->nipd }}</span></td>
                            <td>{{ $item->detailPembayaranNonSpp->pluck('tagihanPembayaran.jenisPembayaran.nama_jenis')->filter()->implode(', ') }}</td>
                            <td class="text-mono text-right">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</td>
                            <td><span class="status-badge {{ $item->status === 'aktif' ? 'status-active' : 'status-inactive' }}">{{ $item->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}</span></td>
                            <td class="text-right"><a class="button button-secondary button-small" href="{{ route('riwayat-pembayaran-non-spp.show', $item) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="7">Belum ada transaksi penerimaan non-SPP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($riwayatPembayaran->hasPages())
            <div class="table-footer">{{ $riwayatPembayaran->links() }}</div>
        @endif
    </section>
@endsection
