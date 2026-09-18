@extends('layouts.app')

@section('title', 'Kwitansi '.$pembayaran->no_kwitansi.' | Sistem Pembayaran SPP')
@section('page-title', 'Preview Kwitansi')

@section('content')
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $detailPembayaran = $pembayaran->detailPembayaran->sortBy(
            fn ($detail) => sprintf('%d-%02d', $detail->tagihanSpp->tahun, $detail->tagihanSpp->bulan),
        );
        $kelas = $detailPembayaran
            ->map(fn ($detail) => $detail->tagihanSpp->siswaKelas->kelas->nama_kelas)
            ->unique()
            ->implode(', ');
    @endphp

    <section class="receipt-preview">
        <div class="receipt-actions">
            <a class="button button-secondary" href="{{ route('pembayaran.show', $pembayaran->siswa) }}">Kembali ke Tagihan</a>
            <div class="receipt-actions-group">
                <button class="button button-primary" type="button" onclick="window.print()">Cetak Kwitansi</button>
            </div>
        </div>

        @if (session('status'))
            <div class="flash-message">{{ session('status') }}</div>
        @endif

        <article class="receipt-paper">
            @if ($pembayaran->status === 'dibatalkan')
                <div class="error-message"><strong>TRANSAKSI DIBATALKAN</strong><br>Alasan: {{ $pembayaran->alasan_pembatalan }}</div>
            @endif
            <header class="receipt-school">
                <img class="receipt-school-logo" src="{{ asset('images/cbi.png') }}" alt="Logo SMK Informatika CBI">
                <div>
                    <h2>Sistem Pembayaran SPP</h2>
                    <p>Bukti pembayaran resmi</p>
                </div>
            </header>

            <div class="receipt-title">
                <h3>Kwitansi Pembayaran</h3>
                <p class="receipt-number text-mono">No. {{ $pembayaran->no_kwitansi }}</p>
            </div>

            <table class="receipt-info">
                <tbody>
                    <tr>
                        <th>Telah terima dari</th>
                        <td class="separator">:</td>
                        <td><strong>{{ $pembayaran->siswa->nama_siswa }}</strong></td>
                    </tr>
                    <tr>
                        <th>NIPD</th>
                        <td class="separator">:</td>
                        <td class="text-mono">{{ $pembayaran->siswa->nipd }}</td>
                    </tr>
                    <tr>
                        <th>Kelas</th>
                        <td class="separator">:</td>
                        <td>{{ $kelas }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal pembayaran</th>
                        <td class="separator">:</td>
                        <td>{{ $pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Petugas TU</th>
                        <td class="separator">:</td>
                        <td>{{ $pembayaran->user->nama }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="receipt-detail">
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
            </table>

            <div class="receipt-total">
                <span>Total pembayaran</span>
                <strong class="text-mono">Rp {{ number_format($pembayaran->total_bayar, 0, ',', '.') }}</strong>
            </div>

            <div class="receipt-signatures">
                <div>
                    <p>Penyetor,</p>
                    <div class="signature-line"></div>
                    <p>{{ $pembayaran->siswa->nama_siswa }}</p>
                </div>
                <div>
                    <p>Petugas Tata Usaha,</p>
                    <div class="signature-line"></div>
                    <p>{{ $pembayaran->user->nama }}</p>
                </div>
            </div>
        </article>
    </section>
@endsection
