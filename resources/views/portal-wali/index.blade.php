<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status SPP Wali | Sistem Pembayaran SPP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #191c1e; background: #f7f9fb; } * { box-sizing: border-box; } body { margin: 0; } main { width: min(100% - 2rem, 56rem); margin: 0 auto; padding: 3rem 0; } .eyebrow { margin: 0; color: #505f76; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; } h1 { margin: .4rem 0; color: #00288e; font-size: clamp(1.7rem, 4vw, 2.25rem); } .intro { margin: 0; color: #444653; line-height: 1.6; } .card { margin-top: 1.5rem; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); overflow: hidden; } form { display: flex; gap: .75rem; padding: 1.25rem; } label { display: grid; flex: 1; gap: .4rem; color: #444653; font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; } input { min-height: 2.6rem; padding: .6rem .7rem; border: 1px solid #c4c5d5; border-radius: .25rem; color: #191c1e; font: inherit; } input:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); } button { align-self: end; min-height: 2.6rem; padding: .6rem 1rem; border: 0; border-radius: .25rem; background: #00288e; color: #fff; cursor: pointer; font: inherit; font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; } .student { display: grid; grid-template-columns: 1fr auto; gap: 1rem; padding: 1.25rem; border-bottom: 1px solid #c4c5d5; } .student h2 { margin: 0; font-size: 1.2rem; } .student p { margin: .35rem 0 0; color: #505f76; } .class { align-self: center; color: #00288e; font-size: .9rem; font-weight: 700; } table { width: 100%; border-collapse: collapse; text-align: left; } th, td { padding: .9rem 1.25rem; border-bottom: 1px solid #e0e3e5; } th { color: #505f76; background: #f2f4f6; font-size: .68rem; letter-spacing: .05em; text-transform: uppercase; } tr:last-child td { border-bottom: 0; } .badge { display: inline-flex; padding: .25rem .5rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; } .paid { background: #dff7ed; color: #087443; } .unpaid { background: #ffdad6; color: #93000a; } .empty { padding: 2rem 1.25rem; color: #505f76; text-align: center; } @media (max-width: 600px) { main { width: min(100% - 1.5rem, 56rem); padding: 1.5rem 0; } form { display: grid; } .student { grid-template-columns: 1fr; } .class { align-self: start; } th, td { padding: .75rem; } }
    </style>
</head>
<body>
    @php($namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])
    <main>
        <p class="eyebrow">Sistem Pembayaran SPP</p>
        <h1>Status SPP Siswa</h1>
        <p class="intro">Masukkan NIPD siswa untuk melihat status tagihan SPP yang telah tersedia.</p>
        <section class="card">
            <form method="GET" action="{{ route('wali.portal') }}">
                <label for="nipd">NIPD<input id="nipd" name="nipd" value="{{ $nipd }}" maxlength="30" required autofocus></label>
                <button type="submit">Lihat Status</button>
            </form>
        </section>

        @if ($nipd !== '' && ! $siswa)
            <section class="card"><p class="empty">Data siswa dengan NIPD tersebut tidak ditemukan.</p></section>
        @elseif ($siswa)
            <section class="card">
                <div class="student"><div><h2>{{ $siswa->nama_siswa }}</h2><p>Status tagihan SPP yang telah dibuat sistem.</p></div><div class="class">{{ $kelasAktif?->kelas?->nama_kelas ?? 'Kelas belum tersedia' }}</div></div>
                @if ($tagihanSpp->isEmpty())
                    <p class="empty">Belum ada tagihan SPP untuk siswa ini.</p>
                @else
                    <table>
                        <thead><tr><th>Periode SPP</th><th>Status</th><th>Tanggal Bayar</th></tr></thead>
                        <tbody>
                            @foreach ($tagihanSpp as $tagihan)
                                @php($pembayaranAktif = $tagihan->detailPembayaran->first()?->pembayaran)
                                <tr>
                                    <td>{{ $namaBulan[$tagihan->bulan] }} {{ $tagihan->tahun }}</td>
                                    <td><span class="badge {{ $pembayaranAktif ? 'paid' : 'unpaid' }}">{{ $pembayaranAktif ? 'Sudah dibayar' : 'Belum dibayar' }}</span></td>
                                    <td>{{ $pembayaranAktif?->tanggal_bayar?->format('d/m/Y') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endif
    </main>
</body>
</html>
