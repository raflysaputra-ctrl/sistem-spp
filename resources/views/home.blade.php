@extends('layouts.app')

@section('title', 'Dashboard | Sistem Pembayaran SPP')
@section('page-title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h2>Selamat datang, {{ auth()->user()->nama }}.</h2>
            <p>Ringkasan operasional Sistem Pembayaran SPP.</p>
        </div>
        <a class="button button-primary" href="{{ route('pembayaran.index') }}">Input Transaksi Baru</a>
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

    <section class="data-card finance-chart-card" aria-labelledby="finance-chart-title">
        <div class="card-header">
            <div>
                <h3 id="finance-chart-title">Penerimaan 6 Bulan Terakhir</h3>
                <p>Berdasarkan tanggal transaksi pembayaran.</p>
            </div>
            <strong class="finance-chart-total text-mono">Rp {{ number_format($totalPenerimaanEnamBulan, 0, ',', '.') }}</strong>
        </div>

        @if ($totalPenerimaanEnamBulan === 0)
            <p class="finance-chart-empty">Belum ada penerimaan yang tercatat dalam enam bulan terakhir.</p>
        @endif

        <div class="finance-line-chart">
            <canvas id="finance-chart" data-finance-chart aria-label="Grafik garis penerimaan enam bulan terakhir berdasarkan tanggal transaksi" role="img"></canvas>
        </div>
        <script id="finance-chart-data" type="application/json">@json($dataGrafikPenerimaan)</script>

        <div class="finance-line-details" aria-label="Rincian penerimaan per bulan">
            @foreach ($grafikPenerimaan as $penerimaan)
                <span><strong>{{ $penerimaan['label'] }}</strong> Rp {{ number_format($penerimaan['total'], 0, ',', '.') }}</span>
            @endforeach
        </div>
    </section>

    <section class="dashboard-layout">
        <section class="data-card">
            <div class="card-header">
                <div>
                    <h3>Transaksi Terbaru</h3>
                    <p>Lima transaksi terakhir berdasarkan tanggal pembayaran.</p>
                </div>
                <a class="button button-secondary button-small" href="{{ route('riwayat-pembayaran.index') }}">Lihat Semua</a>
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
                                $kelas = $pembayaran->detailPembayaran
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
            <form class="dashboard-search" method="GET" action="{{ route('pembayaran.index') }}">
                <label for="cari">NIPD atau Nama Siswa</label>
                <input id="cari" name="cari" placeholder="Masukkan NIPD atau nama" type="search">
                <button class="button button-secondary" type="submit">Cari Siswa</button>
            </form>

            <nav class="dashboard-links" aria-label="Aksi cepat">
                <a href="{{ route('status-spp.index') }}"><span>Status Pembayaran SPP</span><span aria-hidden="true">›</span></a>
                <a href="{{ route('riwayat-pembayaran.index') }}"><span>Riwayat Pembayaran</span><span aria-hidden="true">›</span></a>
                <a href="{{ route('rekap-pembayaran.index') }}"><span>Rekap Pembayaran</span><span aria-hidden="true">›</span></a>
            </nav>
        </aside>
    </section>
@endsection
