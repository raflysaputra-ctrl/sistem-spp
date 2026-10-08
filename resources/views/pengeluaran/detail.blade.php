@extends('layouts.app')

@section('title', 'Detail Pengeluaran | Sistem Informasi Keuangan')
@section('page-title', 'Detail Pengeluaran')

@section('content')
    <section class="receipt-preview">
        <div class="receipt-actions"><a class="button button-secondary" href="{{ route('pengeluaran.riwayat') }}">Kembali</a></div>
        @if (session('status'))<div class="flash-message">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="error-message">{{ $errors->first() }}</div>@endif
        <div class="receipt-card">
            <div class="receipt-header"><div><h2>Detail Pengeluaran</h2><p>{{ $pengeluaran->tanggal_pengeluaran->format('d/m/Y') }}</p></div><span class="status-badge {{ $pengeluaran->status === 'aktif' ? 'status-active' : 'status-inactive' }}">{{ $pengeluaran->status }}</span></div>
            <dl class="detail-list"><dt>Kategori</dt><dd>{{ $pengeluaran->kategori->nama_kategori }}</dd><dt>Nominal</dt><dd class="text-mono">Rp {{ number_format($pengeluaran->nominal, 0, ',', '.') }}</dd><dt>Keterangan</dt><dd>{{ $pengeluaran->keterangan }}</dd><dt>Dicatat Oleh</dt><dd>{{ $pengeluaran->user->nama }}</dd>@if ($pengeluaran->status === 'dibatalkan')<dt>Dibatalkan Oleh</dt><dd>{{ $pengeluaran->dibatalkanOleh?->nama ?? '-' }}</dd><dt>Waktu Pembatalan</dt><dd>{{ $pengeluaran->dibatalkan_pada?->format('d/m/Y H:i') }}</dd><dt>Alasan Pembatalan</dt><dd>{{ $pengeluaran->alasan_pembatalan }}</dd>@endif</dl>
        </div>
    </section>

    @if (auth()->user()->role === 'admin' && $pengeluaran->status === 'aktif')
        <section class="data-card" style="margin-top: 1rem;">
            <form class="cancellation-form" method="POST" action="{{ route('pengeluaran.batalkan', $pengeluaran) }}" data-confirm data-confirm-title="Batalkan pengeluaran?" data-confirm-message="Pengeluaran tidak akan dihapus dan tetap tersimpan dalam histori." data-confirm-submit="Batalkan Pengeluaran">
                @csrf
                @method('PATCH')
                <div class="form-field"><label for="alasan_pembatalan">Alasan Pembatalan</label><textarea id="alasan_pembatalan" name="alasan_pembatalan" required>{{ old('alasan_pembatalan') }}</textarea></div>
                <div class="form-field"><label for="password">Password Admin</label><input id="password" name="password" type="password" required></div>
                <button class="button button-danger" type="submit">Batalkan Pengeluaran</button>
            </form>
        </section>
    @endif
@endsection
