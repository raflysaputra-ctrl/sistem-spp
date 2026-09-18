<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { color: #191c1e; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { margin: 0; color: #00288e; font-size: 18px; }
        p { margin: 4px 0 0; color: #444653; }
        .header { margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #191c1e; }
        .summary { width: 100%; margin-bottom: 16px; border-collapse: separate; border-spacing: 8px 0; }
        .summary td { width: 25%; padding: 10px; border-left: 4px solid #00288e; background: #f2f4f6; }
        .summary td:nth-child(2) { border-left-color: #087443; }
        .summary td:nth-child(3) { border-left-color: #505f76; }
        .summary span { display: block; color: #505f76; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .summary strong { display: block; margin-top: 5px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 8px 6px; background: #eceef0; border: 1px solid #c4c5d5; color: #444653; font-size: 8px; text-align: left; text-transform: uppercase; }
        td { padding: 7px 6px; border: 1px solid #e0e3e5; vertical-align: top; }
        .right { text-align: right; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .note { margin: 0 0 14px; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    @endphp

    <header class="header">
        <h1>Rekap Pembayaran SPP</h1>
        <p>Dicetak pada {{ now()->format('d/m/Y H:i') }}</p>
    </header>

    <p class="note">Periode SPP mengacu pada bulan dan tahun tagihan. Tanggal Transaksi mengacu pada waktu pembayaran diterima.</p>

    <table class="summary">
        <tr>
            <td><span>Total Penerimaan Aktif</span><strong>Rp {{ number_format($ringkasan['total_aktif'], 0, ',', '.') }}</strong></td>
            <td><span>Transaksi Aktif</span><strong>{{ $ringkasan['jumlah_transaksi_aktif'] }}</strong></td>
            <td><span>Total Dibatalkan</span><strong>Rp {{ number_format($ringkasan['total_dibatalkan'], 0, ',', '.') }}</strong></td>
            <td><span>Transaksi Dibatalkan</span><strong>{{ $ringkasan['jumlah_transaksi_dibatalkan'] }}</strong></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Tanggal Transaksi</th>
                <th>No. Kwitansi</th>
                <th>Siswa</th>
                <th>Kelas</th>
                <th>Periode SPP</th>
                <th class="right">Nominal</th>
                <th>Petugas TU</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detailPembayaran as $detail)
                <tr>
                    <td>{{ $detail->pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</td>
                    <td class="mono">{{ $detail->pembayaran->no_kwitansi }}</td>
                    <td>{{ $detail->pembayaran->siswa->nama_siswa }}<br><span class="mono">{{ $detail->pembayaran->siswa->nipd }}</span></td>
                    <td>{{ $detail->tagihanSpp->siswaKelas->kelas->nama_kelas }}</td>
                    <td>{{ $namaBulan[$detail->tagihanSpp->bulan] }} {{ $detail->tagihanSpp->tahun }}</td>
                    <td class="right">Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}</td>
                    <td>{{ $detail->pembayaran->user->nama }}</td>
                    <td>{{ $detail->pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Tidak ada pembayaran yang sesuai dengan filter rekap.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
