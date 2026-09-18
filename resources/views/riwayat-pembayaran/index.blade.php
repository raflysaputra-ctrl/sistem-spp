@extends('layouts.app')

@section('title', 'Riwayat Pembayaran | Sistem Pembayaran SPP')
@section('page-title', 'Riwayat Pembayaran')

@section('content')
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    @endphp

    <div class="page-header">
        <div>
            <h2>Riwayat Pembayaran</h2>
            <p>Daftar transaksi pembayaran yang telah tercatat. Pilih detail untuk melihat periode dan nominal setiap tagihan.</p>
        </div>
        <a class="button button-primary" href="{{ route('pembayaran.index') }}">Input Transaksi Baru</a>
    </div>

    <section class="data-card">
        <div class="card-header">
            <div>
                <h3>Daftar Transaksi</h3>
                <p>Diurutkan dari transaksi pembayaran terbaru.</p>
            </div>
        </div>

        <form class="filter-bar" method="GET" action="{{ route('riwayat-pembayaran.index') }}">
            <div class="filter-field" style="min-width: min(100%, 20rem); flex: 1;">
                <label for="cari">Cari Transaksi</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="No. kwitansi, NIPD, atau nama siswa">
            </div>
            <div class="filter-field">
                <label for="tanggal_mulai">Tanggal Mulai</label>
                <input id="tanggal_mulai" name="tanggal_mulai" type="date" value="{{ $filters['tanggal_mulai'] ?? '' }}">
            </div>
            <div class="filter-field">
                <label for="tanggal_selesai">Tanggal Selesai</label>
                <input id="tanggal_selesai" name="tanggal_selesai" type="date" value="{{ $filters['tanggal_selesai'] ?? '' }}">
            </div>
            <a class="button button-secondary" href="{{ route('riwayat-pembayaran.index') }}">Reset</a>
            <button class="button button-primary" type="submit">Cari</button>
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Kwitansi</th>
                        <th>Tanggal Pembayaran</th>
                        <th>Siswa</th>
                        <th>Periode Dibayar</th>
                        <th>Status</th>
                        <th class="text-right">Total</th>
                        <th>Petugas TU</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatPembayaran as $pembayaran)
                        @php
                            $periode = $pembayaran->detailPembayaran
                                ->sortBy(fn ($detail) => sprintf('%d-%02d', $detail->tagihanSpp->tahun, $detail->tagihanSpp->bulan))
                                ->map(fn ($detail) => $namaBulan[$detail->tagihanSpp->bulan].' '.$detail->tagihanSpp->tahun)
                                ->implode(', ');
                        @endphp
                        <tr>
                            <td class="text-mono"><strong>{{ $pembayaran->no_kwitansi }}</strong></td>
                            <td>{{ $pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>{{ $pembayaran->siswa->nama_siswa }}</strong><br>
                                <span class="text-muted text-mono">{{ $pembayaran->siswa->nipd }}</span>
                            </td>
                            <td>{{ $periode }}</td>
                            <td>
                                <span class="status-badge {{ $pembayaran->status === 'aktif' ? 'status-lunas' : 'status-tunggakan' }}">
                                    {{ $pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}
                                </span>
                            </td>
                            <td class="text-mono text-right"><strong>Rp {{ number_format($pembayaran->total_bayar, 0, ',', '.') }}</strong></td>
                            <td>{{ $pembayaran->user->nama }}</td>
                            <td class="text-right">
                                <a class="button button-secondary button-small" href="{{ route('riwayat-pembayaran.show', $pembayaran) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="8">Belum ada transaksi pembayaran yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($riwayatPembayaran->count() > 0)
            <div class="table-footer">
                <span>Menampilkan {{ $riwayatPembayaran->firstItem() }}-{{ $riwayatPembayaran->lastItem() }} dari {{ $riwayatPembayaran->total() }} transaksi</span>
                @if ($riwayatPembayaran->hasPages())
                    <nav class="pagination-links" aria-label="Pagination riwayat pembayaran">
                        @if ($riwayatPembayaran->onFirstPage())
                            <span>Sebelumnya</span>
                        @else
                            <a href="{{ $riwayatPembayaran->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                        @endif

                        @if ($riwayatPembayaran->hasMorePages())
                            <a href="{{ $riwayatPembayaran->nextPageUrl() }}" rel="next">Selanjutnya</a>
                        @else
                            <span>Selanjutnya</span>
                        @endif
                    </nav>
                @endif
            </div>
        @endif
    </section>
@endsection
