@extends('layouts.app')

@section('title', 'Tambah Kelas | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Tambah Kelas</h2>
            <p>Masukkan jurusan, tingkat, rombel, dan nama kelas.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('master.kelas.store') }}">
        @include('master.kelas._form', ['kelas' => null])
    </form>
@endsection
