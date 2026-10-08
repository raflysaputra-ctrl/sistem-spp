@extends('layouts.app')

@section('title', 'Kategori Pengeluaran | Sistem Informasi Keuangan')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Kategori Pengeluaran</h2>
            <p>Kelola kategori pengeluaran operasional sekolah.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="flash-message">{{ session('status') }}</div>
    @endif

    <section class="data-card">
        <div class="card-header"><div><h3>Tambah Kategori</h3><p>Gunakan nama kategori yang lengkap dan mudah dipahami.</p></div></div>
        <form class="filter-bar" method="POST" action="{{ route('master.kategori-pengeluaran.store') }}">
            @csrf
            <div class="filter-field" style="flex: 1;"><label for="nama_kategori">Nama Kategori</label><input id="nama_kategori" name="nama_kategori" value="{{ old('nama_kategori') }}" required maxlength="100"></div>
            <button class="button button-primary" type="submit">Tambah Kategori</button>
        </form>
        @error('nama_kategori')<p class="error-message">{{ $message }}</p>@enderror
    </section>

    <section class="data-card">
        <div class="table-scroll"><table class="data-table">
            <thead><tr><th>Nama Kategori</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($kategoriPengeluaran as $item)
                    <tr>
                        <td>{{ $item->nama_kategori }}</td>
                        <td><span class="status-badge {{ $item->aktif ? 'status-active' : 'status-inactive' }}">{{ $item->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right"><div class="action-stack">
                            <a class="button button-secondary button-small" href="{{ route('master.kategori-pengeluaran.edit', $item) }}">Edit</a>
                            <form class="inline-form" method="POST" action="{{ route('master.kategori-pengeluaran.toggle', $item) }}" data-confirm data-confirm-title="{{ $item->aktif ? 'Nonaktifkan kategori?' : 'Aktifkan kategori?' }}" data-confirm-message="{{ $item->aktif ? 'Kategori tidak akan tersedia untuk input baru, tetapi histori pengeluaran tetap tersimpan.' : 'Kategori akan kembali tersedia untuk input pengeluaran.' }}" data-confirm-submit="{{ $item->aktif ? 'Nonaktifkan' : 'Aktifkan' }}">
                                @csrf
                                @method('PATCH')
                                <button class="button {{ $item->aktif ? 'button-danger' : 'button-primary' }} button-small" type="submit">{{ $item->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="3">Belum ada kategori pengeluaran.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
@endsection
