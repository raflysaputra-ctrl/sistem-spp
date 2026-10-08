@extends('layouts.app')

@section('title', 'Kwitansi '.$penerimaan->no_kwitansi.' | Sistem Informasi Keuangan')
@section('page-title', 'Kwitansi Pembayaran')

@section('content')
    <section class="receipt-preview">
        <div class="receipt-actions">
            <a class="button button-secondary" href="{{ auth()->user()->role === 'tu' ? route('penerimaan.show', $penerimaan->siswa) : route('penerimaan.detail', $penerimaan) }}">Kembali</a>
            <button class="button button-primary" type="button" onclick="window.print()">Cetak Kwitansi</button>
        </div>
        @if (session('status'))<div class="flash-message">{{ session('status') }}</div>@endif
        @include('penerimaan._receipt')
    </section>
@endsection
