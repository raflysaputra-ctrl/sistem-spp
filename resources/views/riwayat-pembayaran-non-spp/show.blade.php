@extends('layouts.app')

@section('title', 'Detail Penerimaan Non-SPP | Sistem Informasi Keuangan')
@section('page-title', 'Detail Penerimaan Non-SPP')

@section('content')
    <div class="page-header">
        <div>
            <h2>Detail Transaksi Non-SPP</h2>
            <p class="text-mono">{{ $pembayaran->no_kwitansi }}</p>
        </div>
        <div class="action-stack">
            <a class="button button-secondary" href="{{ route('riwayat-pembayaran-non-spp.index') }}">Kembali ke Riwayat</a>
            <a class="button button-primary" href="{{ route('pembayaran-non-spp.kwitansi', $pembayaran) }}">Lihat Kwitansi</a>
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
            <div class="history-detail-item"><span>Tanggal pembayaran</span><strong>{{ $pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</strong></div>
            <div class="history-detail-item"><span>Siswa</span><strong>{{ $pembayaran->siswa->nama_siswa }}</strong></div>
            <div class="history-detail-item"><span>NIPD</span><strong class="text-mono">{{ $pembayaran->siswa->nipd }}</strong></div>
            <div class="history-detail-item"><span>Petugas TU</span><strong>{{ $pembayaran->user->nama }}</strong></div>
            <div class="history-detail-item"><span>Total penerimaan</span><strong class="text-mono">Rp {{ number_format($pembayaran->total_bayar, 0, ',', '.') }}</strong></div>
            <div class="history-detail-item"><span>Status transaksi</span><strong>{{ $pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}</strong></div>
            @if ($pembayaran->status === 'dibatalkan')
                <div class="history-detail-item"><span>Alasan pembatalan</span><strong>{{ $pembayaran->alasan_pembatalan }}</strong></div>
                <div class="history-detail-item"><span>Dibatalkan oleh</span><strong>{{ $pembayaran->dibatalkanOleh?->nama ?? 'Tidak tercatat (legacy)' }}</strong></div>
            @endif
        </div>
    </section>

    @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN && $pembayaran->status === 'aktif')
        <section class="data-card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <div>
                    <h3>Batalkan Seluruh Kwitansi</h3>
                    <p>Pembatalan membatalkan seluruh rincian pada kwitansi ini dan menghitung ulang status tagihan tanpa menghapus histori.</p>
                </div>
            </div>
            <form class="cancellation-form" method="POST" action="{{ route('riwayat-pembayaran-non-spp.batalkan', $pembayaran) }}" data-confirm data-confirm-title="Batalkan seluruh kwitansi?" data-confirm-message="Seluruh rincian pembayaran pada kwitansi ini akan dibatalkan." data-confirm-submit="Batalkan Kwitansi" data-confirm-input-name="password" data-confirm-input-type="password" data-confirm-input-autocomplete="current-password" data-confirm-input-label="Password Anda">
                @csrf
                @method('PATCH')
                <div class="form-field">
                    <label for="alasan_pembatalan">Alasan Pembatalan <span aria-hidden="true">*</span></label>
                    <textarea id="alasan_pembatalan" name="alasan_pembatalan" required maxlength="255">{{ old('alasan_pembatalan') }}</textarea>
                    @error('alasan_pembatalan')<span class="error-message">{{ $message }}</span>@enderror
                </div>
                @error('password')<span class="error-message">{{ $message }}</span>@enderror
                <button class="button button-danger" type="submit">Batalkan Kwitansi</button>
            </form>
        </section>
    @endif

    <section class="data-card">
        <div class="card-header"><div><h3>Rincian Tagihan</h3><p>Nominal dan saldo setelah bayar disimpan sebagai snapshot pada kwitansi.</p></div></div>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Jenis / Periode</th><th>Tahun Ajaran</th><th class="text-right">Dibayar</th><th class="text-right">Sisa Setelah Bayar</th></tr></thead>
                <tbody>
                    @foreach ($pembayaran->detailPembayaranNonSpp as $detail)
                        @php($tagihan = $detail->tagihanPembayaran)
                        <tr>
                            <td>{{ $tagihan->jenisPembayaran->nama_jenis }} - {{ $tagihan->periode_label }}</td>
                            <td>{{ $tagihan->tahunAjaran->tahun_ajaran }}</td>
                            <td class="text-mono text-right">Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}</td>
                            <td class="text-mono text-right">Rp {{ number_format(max(0, (int) $tagihan->total_tagihan - (int) $detail->total_terbayar_setelah), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
