@extends('layouts.app')

@section('title', 'Kwitansi Pembayaran Non-SPP | Sistem Informasi Keuangan')
@section('page-title', 'Kwitansi Pembayaran Non-SPP')

@section('content')
    <div class="receipt-preview">
        <div class="receipt-actions">
            <div class="receipt-actions-group">
                <a class="button button-secondary" href="{{ route('pembayaran-non-spp.index') }}">Kembali ke Daftar</a>
            </div>
            <button class="button button-primary" type="button" onclick="window.print()">Cetak Kwitansi</button>
        </div>

        <section class="receipt-paper">
            <div class="receipt-school">
                <img class="receipt-school-logo" src="{{ asset('images/cbi.png') }}" alt="Logo">
                <div>
                    <h2>SMK Informatika CBI</h2>
                    <p>Sistem Informasi Keuangan Sekolah</p>
                </div>
            </div>

            <div class="receipt-title">
                <h3>Kwitansi Pembayaran Non-SPP</h3>
                <p class="receipt-number">No: {{ $pembayaran->no_kwitansi }}</p>
            </div>

            <table class="receipt-info">
                <tr>
                    <th>Nama Siswa</th>
                    <td class="separator">:</td>
                    <td><strong>{{ $pembayaran->siswa->nama_siswa }}</strong> ({{ $pembayaran->siswa->nipd }})</td>
                </tr>
                <tr>
                    <th>Tanggal Transaksi</th>
                    <td class="separator">:</td>
                    <td>{{ $pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            <table class="data-table receipt-detail">
                <thead>
                    <tr>
                        <th>Jenis / Periode</th>
                        <th>Tahun Ajaran</th>
                        <th class="text-right">Nominal Dibayar</th>
                        <th class="text-right">Sisa Setelah Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pembayaran->detailPembayaranNonSpp as $detail)
                        @php($tagihan = $detail->tagihanPembayaran)
                        <tr>
                            <td>{{ $tagihan->jenisPembayaran->nama_jenis }} - {{ $tagihan->periode_label }}</td>
                            <td>{{ $tagihan->tahunAjaran->tahun_ajaran }}</td>
                            <td class="text-right">Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format(max(0, (int) $tagihan->total_tagihan - (int) $detail->total_terbayar_setelah), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="receipt-total">
                <div><span>Total Pembayaran: <strong>Rp {{ number_format($pembayaran->total_bayar, 0, ',', '.') }}</strong></span></div>
                <div><span>Status Transaksi: <strong>{{ $pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}</strong></span></div>
            </div>

            <div class="receipt-signatures">
                <div>
                    <p>Penyetor</p>
                    <div class="signature-line"></div>
                    <p>({{ $pembayaran->siswa->nama_siswa }})</p>
                </div>
                <div>
                    <p>Petugas TU</p>
                    <div class="signature-line"></div>
                    <p>({{ $pembayaran->user->nama }})</p>
                </div>
            </div>
        </section>
    </div>
@endsection
