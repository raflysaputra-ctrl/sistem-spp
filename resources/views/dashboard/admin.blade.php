@extends('layouts.app')

@section('title', 'Dashboard Admin | Sistem Informasi Keuangan')
@section('page-title', 'Dashboard Admin')

@section('content')
    <div class="page-header">
        <div>
            <h2>Selamat datang, {{ auth()->user()->nama }}.</h2>
            <p>Kelola data sekolah, kontrol transaksi, dan rekap keuangan dari satu tempat.</p>
        </div>
    </div>

    <section class="intro-card">
        <h2>Kontrol Sistem</h2>
        <p>Gunakan akses Admin untuk memelihara master data, meninjau transaksi sebelum pembatalan, dan melihat rekap pembayaran.</p>

        <nav class="dashboard-links" aria-label="Aksi cepat Admin">
            <a href="{{ route('master.siswa.index') }}"><span>Kelola Data Siswa</span><span aria-hidden="true">›</span></a>
            <a href="{{ route('master.tahun-ajaran.index') }}"><span>Kelola Tahun Ajaran</span><span aria-hidden="true">›</span></a>
            <a href="{{ route('riwayat-pembayaran.index') }}"><span>Tinjau Pembatalan Transaksi</span><span aria-hidden="true">›</span></a>
            <a href="{{ route('rekap-pembayaran.index') }}"><span>Lihat Rekap Penerimaan</span><span aria-hidden="true">›</span></a>
        </nav>
    </section>
@endsection
