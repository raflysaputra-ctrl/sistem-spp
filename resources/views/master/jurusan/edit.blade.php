@extends('layouts.app')

@section('title', 'Edit Jurusan | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Jurusan</h2>
            <p>Perbarui referensi program keahlian.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('master.jurusan.update', $jurusan) }}">
        @method('PUT')
        @include('master.jurusan._form', ['jurusan' => $jurusan])
    </form>
@endsection
