@extends('layouts.app')

@section('title', 'Detail Riwayat '.$pembayaran->no_kwitansi.' | Sistem Pembayaran SPP')
@section('page-title', 'Detail Riwayat Pembayaran')

@section('content')
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $detailPembayaran = $pembayaran->detailPembayaran->sortBy(
            fn ($detail) => sprintf('%d-%02d', $detail->tagihanSpp->tahun, $detail->tagihanSpp->bulan),
        );
    @endphp

    <div class="page-header">
        <div>
            <h2>Detail Transaksi</h2>
            <p class="text-mono">{{ $pembayaran->no_kwitansi }}</p>
        </div>
        <div class="action-stack">
            <a class="button button-secondary" href="{{ route('riwayat-pembayaran.index') }}">Kembali ke Riwayat</a>
            <a class="button button-primary" href="{{ route('pembayaran.kwitansi.show', $pembayaran) }}">Lihat Kwitansi</a>
        </div>
    </div>

    <section class="data-card" style="margin-bottom: 1.5rem;">
        <div class="history-detail-grid">
            <div class="history-detail-item">
                <span>Tanggal pembayaran</span>
                <strong>{{ $pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</strong>
            </div>
            <div class="history-detail-item">
                <span>Siswa</span>
                <strong>{{ $pembayaran->siswa->nama_siswa }}</strong>
            </div>
            <div class="history-detail-item">
                <span>NIPD</span>
                <strong class="text-mono">{{ $pembayaran->siswa->nipd }}</strong>
            </div>
            <div class="history-detail-item">
                <span>Petugas TU</span>
                <strong>{{ $pembayaran->user->nama }}</strong>
            </div>
        </div>
    </section>

    <section class="data-card">
        <div class="card-header">
            <div>
                <h3>Rincian Periode Dibayar</h3>
                <p>Nominal memakai snapshot pada detail pembayaran transaksi ini.</p>
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Periode SPP</th>
                        <th>Kelas</th>
                        <th class="text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detailPembayaran as $detail)
                        <tr>
                            <td>{{ $namaBulan[$detail->tagihanSpp->bulan] }} {{ $detail->tagihanSpp->tahun }}</td>
                            <td>{{ $detail->tagihanSpp->siswaKelas->kelas->nama_kelas }}</td>
                            <td class="text-mono text-right">Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right"><strong>Total Pembayaran</strong></td>
                        <td class="text-mono text-right"><strong>Rp {{ number_format($pembayaran->total_bayar, 0, ',', '.') }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
@endsection
