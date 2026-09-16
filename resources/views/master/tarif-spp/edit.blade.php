@extends('layouts.app')

@section('title', 'Edit Tarif SPP | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Tarif SPP</h2>
            <p>Perbarui tarif yang belum digunakan oleh tagihan.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('master.tarif-spp.update', $tarifSpp) }}" data-confirm data-confirm-title="Simpan perubahan tarif?" data-confirm-message="Perubahan tarif hanya berlaku untuk tagihan yang belum dibuat." data-confirm-submit="Simpan Tarif">
        @method('PUT')
        @include('master.tarif-spp._form', ['tarifSpp' => $tarifSpp])
    </form>
@endsection
