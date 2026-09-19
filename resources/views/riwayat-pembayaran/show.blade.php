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

    @if (session('status'))
        <div class="flash-message">{{ session('status') }}</div>
    @endif

    @if ($errors->has('pembayaran'))
        <div class="error-message">{{ $errors->first('pembayaran') }}</div>
    @endif

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
            <div class="history-detail-item">
                <span>Status transaksi</span>
                <strong>{{ $pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}</strong>
            </div>
            <div class="history-detail-item">
                <span>Alasan pembatalan</span>
                <strong>{{ $pembayaran->alasan_pembatalan ?? '-' }}</strong>
            </div>
            @if ($pembayaran->status === 'dibatalkan')
                <div class="history-detail-item">
                    <span>Dibatalkan oleh</span>
                    <strong>{{ $pembayaran->dibatalkanOleh->nama }}</strong>
                </div>
                <div class="history-detail-item">
                    <span>Waktu pembatalan</span>
                    <strong>{{ $pembayaran->dibatalkan_pada->format('d/m/Y H:i') }}</strong>
                </div>
            @endif
        </div>
    </section>

    @if ($pembayaran->status !== 'dibatalkan')
        <section class="data-card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <div>
                    <h3>Batalkan Transaksi</h3>
                    <p>Pembatalan mengembalikan seluruh tagihan transaksi menjadi belum bayar dan tidak menghapus histori.</p>
                </div>
            </div>
            <form class="cancellation-form" method="POST" action="{{ route('riwayat-pembayaran.batalkan', $pembayaran) }}" data-confirm data-confirm-title="Batalkan transaksi?" data-confirm-message="Seluruh tagihan dalam transaksi ini akan kembali menjadi belum bayar." data-confirm-submit="Batalkan Transaksi" data-confirm-input-name="password" data-confirm-input-type="password" data-confirm-input-autocomplete="current-password" data-confirm-input-label="Password Anda">
                @csrf
                @method('PATCH')
                <div class="form-field">
                    <label for="alasan_pembatalan">Alasan Pembatalan <span aria-hidden="true">*</span></label>
                    <textarea id="alasan_pembatalan" name="alasan_pembatalan" required maxlength="255" aria-describedby="alasan_pembatalan_bantuan">{{ old('alasan_pembatalan') }}</textarea>
                    <span id="alasan_pembatalan_bantuan" class="reference-note">Jelaskan alasan pembatalan transaksi. Maksimal 255 karakter.</span>
                    @error('alasan_pembatalan')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
                <button class="button button-danger" type="submit">Batalkan Transaksi</button>
            </form>
        </section>
    @endif

    <section class="data-card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <div>
                <h3>Arsip Foto Kwitansi</h3>
                <p>Foto diunggah siswa sebagai arsip dan tidak mengubah status pembayaran.</p>
            </div>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Diunggah</th>
                        <th>Jenis File</th>
                        <th>Ukuran</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($arsip = $pembayaran->arsipKwitansi)
                        <tr>
                            <td class="text-mono">{{ $arsip->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $arsip->mime_type }}</td>
                            <td>{{ number_format($arsip->ukuran_file / 1024, 1, ',', '.') }} KB</td>
                            <td class="text-right"><a class="button button-secondary button-small" href="{{ route('arsip-kwitansi.show', $arsip) }}" target="_blank" rel="noopener">Lihat Foto</a></td>
                        </tr>
                    @else
                        <tr><td class="empty-state" colspan="4">Belum ada foto kwitansi yang diarsipkan untuk transaksi ini.</td></tr>
                    @endif
                </tbody>
            </table>
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
