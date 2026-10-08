@php
    $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
@endphp
<article class="receipt-paper">
    @if ($penerimaan->status === 'dibatalkan')
        <div class="error-message"><strong>KWITANSI DIBATALKAN</strong><br>Alasan: {{ $penerimaan->alasan_pembatalan }}</div>
    @endif
    <header class="receipt-school">
        <img class="receipt-school-logo" src="{{ asset('images/cbi.png') }}" alt="Logo SMK Informatika CBI">
        <div><h2>SMK Informatika CBI</h2><p>Sistem Informasi Keuangan Sekolah</p></div>
    </header>
    <div class="receipt-title"><h3>Kwitansi Pembayaran</h3><p class="receipt-number text-mono">No. {{ $penerimaan->no_kwitansi }}</p></div>
    <table class="receipt-info"><tbody>
        <tr><th>Telah terima dari</th><td class="separator">:</td><td><strong>{{ $penerimaan->siswa->nama_siswa }}</strong></td></tr>
        <tr><th>NIPD</th><td class="separator">:</td><td class="text-mono">{{ $penerimaan->siswa->nipd }}</td></tr>
        <tr><th>Tanggal</th><td class="separator">:</td><td>{{ $penerimaan->tanggal_bayar->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Petugas TU</th><td class="separator">:</td><td>{{ $penerimaan->user->nama }}</td></tr>
    </tbody></table>
    <table class="receipt-detail">
        <thead><tr><th>Jenis</th><th>Periode / Keterangan</th><th class="text-right">Nominal</th></tr></thead>
        <tbody>
            @foreach ($penerimaan->pembayaranSpp?->detailPembayaran ?? [] as $detail)
                <tr><td>SPP</td><td>{{ $namaBulan[$detail->tagihanSpp->bulan] }} {{ $detail->tagihanSpp->tahun }}</td><td class="text-mono text-right">Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}</td></tr>
            @endforeach
            @foreach ($penerimaan->pembayaranNonSpp?->detailPembayaranNonSpp ?? [] as $detail)
                <tr><td>{{ $detail->tagihanPembayaran->jenisPembayaran->nama_jenis }}</td><td>{{ $detail->tagihanPembayaran->periode_label }}</td><td class="text-mono text-right">Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <div class="receipt-total"><span>Total pembayaran</span><strong class="text-mono">Rp {{ number_format($penerimaan->total_bayar, 0, ',', '.') }}</strong></div>
    <div class="receipt-signatures">
        <div><p>Penyetor,</p><div class="signature-line"></div><p>{{ $penerimaan->siswa->nama_siswa }}</p></div>
        <div><p>Petugas Tata Usaha,</p><div class="signature-line"></div><p>{{ $penerimaan->user->nama }}</p></div>
    </div>
</article>
