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
        :root { font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #191c1e; background: #f7f9fb; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { width: min(100% - 2rem, 64rem); margin: 0 auto; padding: 2rem 0 3rem; }
        header { display: flex; align-items: start; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; }
        .eyebrow { margin: 0; color: #505f76; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: .35rem 0; color: #00288e; font-size: 1.6rem; }
        h2, h3 { margin: 0; }
        h2 { font-size: 1.05rem; }
        h3 { font-size: 1rem; }
        p { color: #505f76; line-height: 1.55; }
        .logout { padding: .6rem .8rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; color: #444653; cursor: pointer; font: inherit; font-size: .75rem; font-weight: 700; }
        .card { margin-top: 1.5rem; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); overflow: hidden; }
        .identity { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 1.25rem; }
        .identity span, .meta-label { display: block; margin-bottom: .3rem; color: #505f76; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .section-heading { padding: 1.25rem 1.25rem 0; }
        .period-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; padding: 1.25rem; }
        .period-card { padding: 1rem; border: 1px solid #e0e3e5; border-radius: .4rem; background: #fff; }
        .period-header { display: flex; align-items: start; justify-content: space-between; gap: .75rem; }
        .period-meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; margin-top: 1rem; }
        .badge { display: inline-flex; padding: .25rem .5rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
        .paid, .uploaded { background: #dff7ed; color: #087443; }
        .unpaid { background: #ffdad6; color: #93000a; }
        .uploaded { margin-top: 1rem; }
        .empty { margin: 0; padding: 1.5rem 1.25rem; text-align: center; }
        .notice { padding: .8rem 1rem; border: 1px solid #a9c7ff; border-radius: .35rem; background: #eaf1ff; color: #003ea8; }
        .error { margin: 0; color: #ba1a1a; font-size: .78rem; }
        .upload-panel { margin-top: 1rem; border-top: 1px solid #e0e3e5; padding-top: 1rem; }
        summary { color: #003ea8; cursor: pointer; font-size: .78rem; font-weight: 700; }
        .transaction { margin-top: .9rem; padding: .85rem; border-radius: .35rem; background: #f2f4f6; }
        .transaction dl { display: grid; gap: .65rem; margin: 0; }
        .transaction dt { color: #505f76; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
        .transaction dd { margin: .15rem 0 0; font-size: .82rem; line-height: 1.45; }
        .form { display: grid; gap: .75rem; margin-top: 1rem; }
        label { display: grid; gap: .4rem; color: #444653; font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        input { min-height: 2.6rem; width: 100%; padding: .6rem .7rem; border: 1px solid #74777f; border-radius: .25rem; background: #fff; font: inherit; }
        .primary { min-height: 2.6rem; border: 0; border-radius: .25rem; background: #003ea8; color: #fff; cursor: pointer; font: inherit; font-weight: 700; }
        .help { margin: .35rem 0 0; font-size: .78rem; }
        @media (max-width: 680px) { .identity, .period-grid { grid-template-columns: 1fr; } .period-meta { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    @endphp
    <main>
        <header>
            <div><p class="eyebrow">Sistem Pembayaran SPP</p><h1>Portal Siswa</h1></div>
            <form method="POST" action="{{ route('siswa.logout') }}">@csrf<button class="logout" type="submit">Logout</button></form>
        </header>

        @if (session('status'))<p class="notice">{{ session('status') }}</p>@endif

        <section class="card">
            <div class="identity">
                <div><span>Nama Siswa</span><strong>{{ $siswa->nama_siswa }}</strong></div>
                <div><span>NIPD</span><strong>{{ $siswa->nipd }}</strong></div>
                <div><span>Kelas Saat Ini</span><strong>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</strong></div>
            </div>
        </section>

        <section class="card">
            <div class="section-heading">
                <h2>Status SPP</h2>
                <p>Setiap periode SPP ditampilkan terpisah. Foto hanya menjadi arsip dan tidak mengubah pembayaran atau tagihan.</p>
                @error('id_pembayaran')<p class="error">{{ $message }}</p>@enderror
                @error('foto')<p class="error">{{ $message }}</p>@enderror
            </div>

            @if ($tagihanSpp->isEmpty())
                <p class="empty">Belum ada tagihan SPP untuk Anda.</p>
            @else
                <div class="period-grid">
                    @foreach ($tagihanSpp as $tagihan)
                        @php
                            $pembayaranTagihan = $tagihan->detailPembayaran->first()?->pembayaran;
                            $periodeTransaksi = $pembayaranTagihan?->detailPembayaran
                                ->sortBy(fn ($detail) => sprintf('%04d%02d', $detail->tagihanSpp->tahun, $detail->tagihanSpp->bulan))
                                ->map(fn ($detail) => $namaBulan[$detail->tagihanSpp->bulan].' '.$detail->tagihanSpp->tahun)
                                ->implode(', ');
                            $formId = 'foto-kwitansi-'.$tagihan->id_tagihan;
                        @endphp
                        <article class="period-card">
                            <div class="period-header">
                                <h3>{{ $namaBulan[$tagihan->bulan] }} {{ $tagihan->tahun }}</h3>
                                <span class="badge {{ $pembayaranTagihan ? 'paid' : 'unpaid' }}">{{ $pembayaranTagihan ? 'Sudah dibayar' : 'Belum dibayar' }}</span>
                            </div>
                            <div class="period-meta">
                                <div><span class="meta-label">Tanggal Bayar</span><strong>{{ $pembayaranTagihan?->tanggal_bayar?->format('d/m/Y') ?? '-' }}</strong></div>
                                <div><span class="meta-label">Foto Kwitansi</span><strong>{{ $pembayaranTagihan?->arsipKwitansi ? 'Sudah diunggah' : '-' }}</strong></div>
                            </div>

                            @if ($pembayaranTagihan)
                                @if ($pembayaranTagihan->arsipKwitansi)
                                    <span class="badge uploaded">Foto kwitansi sudah diunggah</span>
                                @endif
                                <details class="upload-panel">
                                    <summary>{{ $pembayaranTagihan->arsipKwitansi ? 'Ganti foto kwitansi' : 'Unggah foto kwitansi' }}</summary>
                                    <div class="transaction">
                                        <dl>
                                            <div><dt>Nomor Kwitansi</dt><dd>{{ $pembayaranTagihan->no_kwitansi }}</dd></div>
                                            <div><dt>Tanggal Pembayaran</dt><dd>{{ $pembayaranTagihan->tanggal_bayar->format('d/m/Y') }}</dd></div>
                                            <div><dt>Seluruh Periode SPP</dt><dd>{{ $periodeTransaksi }}</dd></div>
                                        </dl>
                                    </div>
                                    <form class="form" method="POST" action="{{ $pembayaranTagihan->arsipKwitansi ? route('siswa.kwitansi.update') : route('siswa.kwitansi.store') }}" enctype="multipart/form-data">
                                        @csrf
                                        @if ($pembayaranTagihan->arsipKwitansi) @method('PATCH') @endif
                                        <input name="id_pembayaran" type="hidden" value="{{ $pembayaranTagihan->id_pembayaran }}">
                                        <label for="{{ $formId }}">{{ $pembayaranTagihan->arsipKwitansi ? 'Foto kwitansi pengganti' : 'Foto kwitansi' }} (JPEG atau PNG, maksimal 2 MB)<input id="{{ $formId }}" name="foto" type="file" accept="image/jpeg,image/png" required></label>
                                        <button class="primary" type="submit">{{ $pembayaranTagihan->arsipKwitansi ? 'Ganti Foto' : 'Simpan Foto' }}</button>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif

            @unless ($adaTransaksiDapatDiunggah)
                <p class="empty">Belum ada transaksi pembayaran yang dapat dihubungkan dengan foto kwitansi. Jika Anda sudah menerima kwitansi fisik tetapi transaksinya belum tampil, silakan hubungi petugas TU dan tunjukkan kwitansi tersebut.</p>
            @endunless
        </section>
    </main>
</body>
</html>
