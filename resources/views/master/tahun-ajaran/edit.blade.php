@extends('layouts.app')

@section('title', 'Edit Tahun Ajaran | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Tahun Ajaran</h2>
            <p>Perbarui periode {{ $tahunAjaran->tahun_ajaran }} tanpa menghapus histori terkait.</p>
        </div>
    </div>

    @if (! $tahunAjaran->isPersiapan())
        <section class="form-card">
            @include('master.tahun-ajaran._form_edit')
        </section>
    @else
        <form class="form-card" method="POST" action="{{ route('master.tahun-ajaran.update', $tahunAjaran) }}" data-confirm data-confirm-title="Simpan perubahan tahun ajaran?" data-confirm-message="Tanggal selesai tahun ajaran akan diperbarui." data-confirm-submit="Simpan Perubahan">
            @method('PUT')
            @include('master.tahun-ajaran._form_edit')
        </form>
    @endif
@endsection
