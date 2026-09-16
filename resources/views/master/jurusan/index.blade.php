@extends('layouts.app')

@section('title', 'Data Jurusan | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Data Jurusan</h2>
        </div>
        <a class="button button-primary" href="{{ route('master.jurusan.create') }}">Tambah Jurusan</a>
    </div>

    <section class="data-card">
        @if (session('status'))
            <div class="flash-message">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <div class="card-header">
            <div>
                <h3>Program Keahlian</h3>
                <p>Data awal mengikuti struktur akademik yang ditetapkan.</p>
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Jurusan</th>
                        <th class="text-right">Jumlah Kelas</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jurusan as $item)
                        <tr>
                            <td class="text-mono">{{ $item->kode_jurusan }}</td>
                            <td>{{ $item->nama_jurusan }}</td>
                            <td class="text-right text-muted">{{ $item->kelas_count }}</td>
                            <td class="text-right">
                                <span class="action-stack">
                                    <a class="button button-secondary button-small" href="{{ route('master.jurusan.edit', $item) }}">Edit</a>
                                    @if ($item->kelas_count === 0)
                                        <form class="inline-form" method="POST" action="{{ route('master.jurusan.destroy', $item) }}" data-confirm data-confirm-title="Hapus jurusan?" data-confirm-message="Jurusan {{ $item->kode_jurusan }} akan dihapus permanen karena belum digunakan oleh kelas." data-confirm-submit="Hapus Jurusan">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button button-danger button-small" type="submit">Hapus</button>
                                        </form>
                                    @else
                                        <span class="reference-note">Digunakan kelas</span>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="4">Belum ada data jurusan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
