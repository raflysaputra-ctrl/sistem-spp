@extends('layouts.app')

@section('title', 'Dashboard TU | Sistem Informasi Keuangan')
@section('page-title', 'Dashboard TU')

@section('content')
    <div class="page-header">
        <div>
            <h2>Selamat datang, {{ auth()->user()->nama }}.</h2>
            <p>Ringkasan operasional penerimaan dan pembayaran SPP.</p>
        </div>
        <a class="button button-primary" href="{{ route('penerimaan.index') }}">Input Pembayaran Baru</a>
    </div>

    <section class="dashboard-summary" aria-label="Ringkasan sistem">
        <article class="dashboard-summary-item">
            <span>Siswa Aktif</span>
            <strong>{{ number_format($jumlahSiswaAktif, 0, ',', '.') }}</strong>
        </article>
        <article class="dashboard-summary-item">
            <span>Transaksi Hari Ini</span>
            <strong>{{ number_format($jumlahTransaksiHariIni, 0, ',', '.') }}</strong>
        </article>
        <article class="dashboard-summary-item">
            <span>Penerimaan Bulan Ini</span>
            <strong class="text-mono">Rp {{ number_format($totalPenerimaanBulanIni, 0, ',', '.') }}</strong>
        </article>
    </section>

    @include('dashboard._penerimaan-chart')

    <section class="dashboard-layout">
        <section class="data-card">
            <div class="card-header">
                <div>
                    <h3>Transaksi Terbaru</h3>
                    <p>Lima transaksi terakhir berdasarkan tanggal pembayaran.</p>
                </div>
                <a class="button button-secondary button-small" href="{{ route('penerimaan.riwayat') }}">Lihat Semua</a>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. Kwitansi</th>
                            <th>Tanggal</th>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th class="text-right">Total</th>
                            <th>Petugas TU</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transaksiTerbaru as $pembayaran)
                            @php
                                $detailSpp = $pembayaran->pembayaranSpp?->detailPembayaran ?? collect();
                                $kelas = $detailSpp
                                    ->map(fn ($detail) => $detail->tagihanSpp->siswaKelas->kelas->nama_kelas)
                                    ->unique()
                                    ->implode(', ');
                            @endphp
                            <tr>
                                <td class="text-mono"><strong>{{ $pembayaran->no_kwitansi }}</strong></td>
                                <td>{{ $pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</td>
                                <td>
                                    <strong>{{ $pembayaran->siswa->nama_siswa }}</strong><br>
                                    <span class="text-muted text-mono">{{ $pembayaran->siswa->nipd }}</span>
                                </td>
                                <td>{{ $kelas ?: '-' }}</td>
                                <td class="text-mono text-right"><strong>Rp {{ number_format($pembayaran->total_bayar, 0, ',', '.') }}</strong></td>
                                <td>{{ $pembayaran->user->nama }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="empty-state" colspan="6">Belum ada transaksi pembayaran yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="dashboard-quick-actions">
            <h3>Pencarian Cepat</h3>
            <p>Cari siswa berdasarkan NIPD atau nama untuk melihat tagihan dan mencatat pembayaran.</p>
            <form class="dashboard-search" method="GET" action="{{ route('penerimaan.index') }}">
                <label for="cari">NIPD atau Nama Siswa</label>
                <input id="cari" name="cari" placeholder="Masukkan NIPD atau nama" type="search">
                <button class="button button-secondary" type="submit">Cari Siswa</button>
            </form>

            <nav class="dashboard-links" aria-label="Aksi cepat">
                <a href="{{ route('status-spp.index') }}"><span>Status Pembayaran SPP</span><span aria-hidden="true">›</span></a>
                <a href="{{ route('penerimaan.riwayat') }}"><span>Riwayat Pembayaran</span><span aria-hidden="true">›</span></a>
                <a href="{{ route('rekap-pembayaran.index') }}"><span>Rekap Penerimaan</span><span aria-hidden="true">›</span></a>
            </nav>
        </aside>
    </section>
@endsection
