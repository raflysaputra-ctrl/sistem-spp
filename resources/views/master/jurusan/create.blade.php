@extends('layouts.app')

@section('title', 'Tambah Jurusan | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Tambah Jurusan</h2>
            <p>Setelah jurusan dibuat, kelas X, XI, dan XII untuk rombel 1-4 akan dibuat otomatis.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('master.jurusan.store') }}">
        @include('master.jurusan._form', ['jurusan' => null])
    </form>
@endsection
