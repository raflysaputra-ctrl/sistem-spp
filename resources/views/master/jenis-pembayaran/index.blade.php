@extends('layouts.app')

@section('title', 'Master Data Jenis Pembayaran | Sistem Informasi Keuangan')
@section('page-title', 'Master Data Jenis Pembayaran')

@section('content')
    <div class="page-header">
        <div>
            <h2>Jenis Pembayaran Non-SPP</h2>
            <p>Kelola master jenis penerimaan sekolah (PTS, PAS, PKL, UJIKOM, Biaya Awal Masuk, dll).</p>
        </div>
        <a class="button button-primary" href="{{ route('master.jenis-pembayaran.create') }}">Tambah Jenis</a>
    </div>

    @if (session('status'))
        <div class="flash-message">{{ session('status') }}</div>
    @endif

    <section class="data-card">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Jenis Pembayaran</th>
                        <th>Target Kelas</th>
                        <th>Aturan</th>
                        <th>Periode</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jenisPembayaran as $item)
                        <tr>
                            <td class="text-mono"><strong>{{ $item->kode_jenis }}</strong></td>
                            <td>{{ $item->nama_jenis }}</td>
                            <td>
                                @if (empty($item->target_tingkat))
                                    <span class="text-muted">Semua Kelas</span>
                                @else
                                    {{ implode(', ', array_map(fn($t) => "Kelas $t", $item->target_tingkat)) }}
                                @endif
                            </td>
                            <td>{{ $item->bisaDicicil() ? 'Dapat dicicil' : 'Lunas sekali bayar' }}</td>
                            <td>{{ ucfirst($item->tipe_periode) }}</td>
                            <td>{{ $item->keterangan ?? '-' }}</td>
                            <td>
                                <span class="status-badge {{ $item->aktif ? 'status-active' : 'status-inactive' }}">
                                    {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="action-stack">
                                    <a class="button button-secondary button-small" href="{{ route('master.jenis-pembayaran.edit', $item) }}">Edit</a>
                                    <form class="inline-form" method="POST" action="{{ route('master.jenis-pembayaran.destroy', $item) }}" data-confirm data-confirm-title="Hapus Jenis Pembayaran?" data-confirm-message="Jenis pembayaran {{ $item->nama_jenis }} akan dihapus secara permanen." data-confirm-submit="Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button class="button button-danger button-small" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="8">Belum ada data jenis pembayaran non-SPP.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
