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
        .summary td { width: 33.33%; padding: 10px; border-left: 4px solid #00288e; background: #f2f4f6; }
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
    <header class="header">
        <h1>Laporan Tunggakan SPP</h1>
        <p>Dicetak pada {{ now()->format('d/m/Y H:i') }}</p>
    </header>

    <p class="note">Mencakup tagihan belum bayar dari periode SPP sebelum bulan berjalan.</p>

    <table class="summary">
        <tr>
            <td><span>Total Tunggakan</span><strong>Rp {{ number_format($totalNominal, 0, ',', '.') }}</strong></td>
            <td><span>Jumlah Siswa Menunggak</span><strong>{{ $jumlahSiswa }}</strong></td>
            <td><span>Jumlah Tagihan Menunggak</span><strong>{{ $jumlahTagihan }}</strong></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>NIPD</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th class="right">Jml Tagihan</th>
                <th class="right">Total Tunggakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tunggakan as $item)
                @php($kelasAktif = $item->siswa->siswaKelas->first())
                <tr>
                    <td class="mono">{{ $item->siswa->nipd }}</td>
                    <td>{{ $item->siswa->nama_siswa }}</td>
                    <td>{{ $kelasAktif?->kelas?->nama_kelas ?? 'Tidak ada kelas aktif' }}</td>
                    <td class="right">{{ $item->jumlah_tagihan }}</td>
                    <td class="right">Rp {{ number_format($item->total_tunggakan, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Tidak ada tunggakan SPP yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
