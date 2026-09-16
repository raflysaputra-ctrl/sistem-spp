@extends('layouts.app')

@section('title', 'Tambah Siswa | Sistem Pembayaran SPP')
@section('page-title', 'Data Siswa')

@section('content')
    <div class="page-header">
        <div>
            <h2>Tambah Siswa</h2>
            <p>Masukkan data induk dan kelas siswa pada tahun ajaran aktif.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('master.siswa.store') }}" data-confirm data-confirm-title="Tambahkan siswa?" data-confirm-message="Periksa kembali identitas dan kelas siswa sebelum menyimpan." data-confirm-submit="Tambahkan Siswa">
        @include('master.siswa._form', ['siswa' => null])
    </form>
@endsection
