@extends('layouts.app')

@section('title', 'Edit Siswa | Sistem Pembayaran SPP')
@section('page-title', 'Data Siswa')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Siswa</h2>
            <p>Perbarui data induk dan kelas aktif {{ $siswa->nama_siswa }}.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('master.siswa.update', $siswa) }}" data-confirm data-confirm-title="Simpan perubahan siswa?" data-confirm-message="Pastikan perubahan data induk siswa sudah benar." data-confirm-submit="Simpan Perubahan">
        @method('PUT')
        @include('master.siswa._form')
    </form>
@endsection
