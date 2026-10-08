@extends('layouts.app')

@section('title', 'Edit Kategori Pengeluaran | Sistem Informasi Keuangan')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header"><div><h2>Edit Kategori Pengeluaran</h2><p>Perbarui nama kategori tanpa menghapus histori pengeluaran.</p></div></div>
    <form class="form-card" method="POST" action="{{ route('master.kategori-pengeluaran.update', $kategoriPengeluaran) }}">
        @csrf
        @method('PUT')
        <div class="form-field"><label for="nama_kategori">Nama Kategori</label><input id="nama_kategori" name="nama_kategori" value="{{ old('nama_kategori', $kategoriPengeluaran->nama_kategori) }}" required maxlength="100">@error('nama_kategori')<p class="error-message">{{ $message }}</p>@enderror</div>
        <div class="form-actions"><a class="button button-secondary" href="{{ route('master.kategori-pengeluaran.index') }}">Batal</a><button class="button button-primary" type="submit">Simpan Perubahan</button></div>
    </form>
@endsection
