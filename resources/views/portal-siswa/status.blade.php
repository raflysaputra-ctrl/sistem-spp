<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Siswa | Sistem Pembayaran SPP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #191c1e; background: #f7f9fb; } * { box-sizing: border-box; } body { margin: 0; } main { width: min(100% - 2rem, 64rem); margin: 0 auto; padding: 2rem 0 3rem; } header { display: flex; align-items: start; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; } .eyebrow { margin: 0; color: #505f76; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; } h1 { margin: .35rem 0; color: #00288e; font-size: 1.6rem; } h2 { margin: 0; font-size: 1.05rem; } p { color: #505f76; line-height: 1.55; } .logout { padding: .6rem .8rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; color: #444653; cursor: pointer; font: inherit; font-size: .75rem; font-weight: 700; } .card { margin-top: 1.5rem; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); overflow: hidden; } .identity { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 1.25rem; } .identity span { display: block; margin-bottom: .3rem; color: #505f76; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; } table { width: 100%; border-collapse: collapse; text-align: left; } th, td { padding: .85rem 1.25rem; border-top: 1px solid #e0e3e5; } th { color: #505f76; background: #f2f4f6; font-size: .68rem; letter-spacing: .05em; text-transform: uppercase; } .badge { display: inline-flex; padding: .25rem .5rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; } .paid { background: #dff7ed; color: #087443; } .unpaid { background: #ffdad6; color: #93000a; } .empty { padding: 1.5rem 1.25rem; text-align: center; } .form { display: grid; gap: 1rem; padding: 1.25rem; } label { display: grid; gap: .4rem; color: #444653; font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; } input, select { min-height: 2.6rem; width: 100%; padding: .6rem .7rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; color: #191c1e; font: inherit; } button.primary { justify-self: start; padding: .7rem .9rem; border: 0; border-radius: .25rem; background: #00288e; color: #fff; cursor: pointer; font: inherit; font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; } .notice { margin: 0 0 1rem; padding: .75rem 1rem; border: 1px solid #9bdfbe; border-radius: .25rem; background: #eafaf1; color: #087443; } .error { margin: 0; color: #ba1a1a; font-size: .8rem; } .help { margin: 0; font-size: .82rem; } @media (max-width: 600px) { main { width: min(100% - 1.5rem, 64rem); padding-top: 1.5rem; } header { flex-direction: column; } .identity { grid-template-columns: 1fr; } th, td { padding: .7rem; } }
    </style>
</head>
<body>
    @php($namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])
    <main>
        <header><div><p class="eyebrow">Sistem Pembayaran SPP</p><h1>Portal Siswa</h1></div><form method="POST" action="{{ route('siswa.logout') }}">@csrf<button class="logout" type="submit">Logout</button></form></header>
        @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif
        <section class="card"><div class="identity"><div><span>Nama Siswa</span><strong>{{ $siswa->nama_siswa }}</strong></div><div><span>NIPD</span><strong>{{ $siswa->nipd }}</strong></div><div><span>Kelas Saat Ini</span><strong>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</strong></div></div></section>
        <section class="card">
            <div style="padding: 1.25rem 1.25rem 0;"><h2>Status SPP</h2><p>Hanya tagihan yang telah dibuat sistem yang ditampilkan.</p></div>
            @if ($tagihanSpp->isEmpty())<p class="empty">Belum ada tagihan SPP untuk Anda.</p>@else
                <table><thead><tr><th>Periode SPP</th><th>Status</th><th>Tanggal Bayar</th></tr></thead><tbody>@foreach ($tagihanSpp as $tagihan) @php($pembayaranTagihan = $tagihan->detailPembayaran->first()?->pembayaran)<tr><td>{{ $namaBulan[$tagihan->bulan] }} {{ $tagihan->tahun }}</td><td><span class="badge {{ $pembayaranTagihan ? 'paid' : 'unpaid' }}">{{ $pembayaranTagihan ? 'Sudah dibayar' : 'Belum dibayar' }}</span></td><td>{{ $pembayaranTagihan?->tanggal_bayar?->format('d/m/Y') ?? '-' }}</td></tr>@endforeach</tbody></table>
            @endif
        </section>
        <section class="card">
            <div style="padding: 1.25rem 1.25rem 0;"><h2>Unggah Foto Kwitansi</h2><p class="help">Unggah foto kwitansi fisik yang telah dicap dan ditandatangani. Foto hanya menjadi arsip dan tidak mengubah pembayaran atau tagihan.</p></div>
            <form class="form" method="POST" action="{{ route('siswa.kwitansi.store') }}" enctype="multipart/form-data">
                @csrf
                <label for="id_pembayaran">Hubungkan ke transaksi (opsional)<select id="id_pembayaran" name="id_pembayaran"><option value="">Simpan tanpa transaksi</option>@foreach ($pembayaranAktif as $pembayaran) @php($periode = $pembayaran->detailPembayaran->sortBy(fn ($detail) => sprintf('%04d%02d', $detail->tagihanSpp->tahun, $detail->tagihanSpp->bulan))->map(fn ($detail) => $namaBulan[$detail->tagihanSpp->bulan].' '.$detail->tagihanSpp->tahun)->implode(', '))<option value="{{ $pembayaran->id_pembayaran }}" @selected(old('id_pembayaran') == $pembayaran->id_pembayaran)>{{ $pembayaran->tanggal_bayar->format('d/m/Y') }}: {{ $periode }}</option>@endforeach</select></label>
                @error('id_pembayaran')<p class="error">{{ $message }}</p>@enderror
                <label for="foto">Foto kwitansi (JPEG atau PNG, maksimal 2 MB)<input id="foto" name="foto" type="file" accept="image/jpeg,image/png" required></label>
                @error('foto')<p class="error">{{ $message }}</p>@enderror
                <button class="primary" type="submit">Simpan Foto</button>
            </form>
        </section>
    </main>
</body>
</html>
