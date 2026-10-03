@extends('layouts.app')

@section('title', 'Dashboard Keuangan | Sistem Informasi Keuangan')
@section('page-title', 'Dashboard Keuangan')

@section('content')
    <div class="page-header">
        <div>
            <h2>Selamat datang, {{ auth()->user()->nama }}.</h2>
            <p>Monitoring penerimaan sekolah berdasarkan transaksi SPP yang tersedia saat ini.</p>
        </div>
        <a class="button button-secondary" href="{{ route('rekap-pembayaran.index') }}">Lihat Rekap Penerimaan</a>
    </div>

    <section class="dashboard-summary two-columns" aria-label="Ringkasan penerimaan">
        <article class="dashboard-summary-item">
            <span>Penerimaan Bulan Ini</span>
            <strong class="text-mono">Rp {{ number_format($totalPenerimaanBulanIni, 0, ',', '.') }}</strong>
        </article>
        <article class="dashboard-summary-item">
            <span>Penerimaan 6 Bulan</span>
            <strong class="text-mono">Rp {{ number_format($totalPenerimaanEnamBulan, 0, ',', '.') }}</strong>
        </article>
    </section>

    @include('dashboard._penerimaan-chart')

    <p class="reference-note">Data Pengeluaran dan selisih keuangan belum ditampilkan karena modul tersebut belum tersedia.</p>
@endsection
