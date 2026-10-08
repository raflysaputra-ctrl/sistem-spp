@extends('layouts.app')

@section('title', 'Detail '.$penerimaan->no_kwitansi.' | Sistem Informasi Keuangan')
@section('page-title', 'Detail Pembayaran')

@section('content')
    <section class="receipt-preview">
        <div class="receipt-actions"><a class="button button-secondary" href="{{ route('penerimaan.riwayat') }}">Kembali</a><a class="button button-primary" href="{{ route('penerimaan.kwitansi', $penerimaan) }}">Lihat Kwitansi</a></div>
        @if (session('status'))<div class="flash-message">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="error-message">{{ $errors->first() }}</div>@endif
        @include('penerimaan._receipt')
    </section>

    @if (auth()->user()->role === 'admin' && $penerimaan->status === 'aktif')
        <section class="data-card" style="margin-top: 1rem;">
            <form class="cancellation-form" method="POST" action="{{ route('penerimaan.batalkan', $penerimaan) }}" data-confirm data-confirm-title="Batalkan seluruh kwitansi?" data-confirm-message="Semua pembayaran SPP dan non-SPP dalam kwitansi ini akan dibatalkan." data-confirm-submit="Batalkan Kwitansi">
                @csrf
                @method('PATCH')
                <div class="form-field"><label for="alasan_pembatalan">Alasan Pembatalan</label><textarea id="alasan_pembatalan" name="alasan_pembatalan" required>{{ old('alasan_pembatalan') }}</textarea></div>
                <div class="form-field"><label for="password">Password Admin</label><input id="password" name="password" type="password" required></div>
                <button class="button button-danger" type="submit">Batalkan Seluruh Kwitansi</button>
            </form>
        </section>
    @endif
@endsection
