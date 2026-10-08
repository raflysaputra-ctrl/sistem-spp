@extends('layouts.app')

@section('title', 'Riwayat Pengeluaran | Sistem Informasi Keuangan')
@section('page-title', 'Riwayat Pengeluaran')

@section('content')
    <div class="page-header"><div><h2>Riwayat Pengeluaran</h2><p>Riwayat pengeluaran yang dicatat oleh Tata Usaha.</p></div></div>
    <section class="data-card">
        <form class="filter-bar" method="GET" action="{{ route('pengeluaran.riwayat') }}">
            <div class="filter-field"><label for="tanggal_mulai">Tanggal Mulai</label><input id="tanggal_mulai" name="tanggal_mulai" type="date" value="{{ $filters['tanggal_mulai'] ?? '' }}"></div>
            <div class="filter-field"><label for="tanggal_selesai">Tanggal Selesai</label><input id="tanggal_selesai" name="tanggal_selesai" type="date" value="{{ $filters['tanggal_selesai'] ?? '' }}"></div>
            <div class="filter-field"><label for="id_kategori_pengeluaran">Kategori</label><select id="id_kategori_pengeluaran" name="id_kategori_pengeluaran"><option value="">Semua kategori</option>@foreach ($kategoriPengeluaran as $kategori)<option value="{{ $kategori->id_kategori_pengeluaran }}" @selected(($filters['id_kategori_pengeluaran'] ?? null) == $kategori->id_kategori_pengeluaran)>{{ $kategori->nama_kategori }}</option>@endforeach</select></div>
            <div class="filter-field"><label for="status">Status</label><select id="status" name="status"><option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option><option value="dibatalkan" @selected($filters['status'] === 'dibatalkan')>Dibatalkan</option><option value="semua" @selected($filters['status'] === 'semua')>Semua</option></select></div>
            <button class="button button-primary" type="submit">Filter</button>
        </form>
        <div class="table-scroll"><table class="data-table">
            <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Pencatat</th><th class="text-right">Nominal</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($pengeluaran as $item)
                    <tr><td>{{ $item->tanggal_pengeluaran->format('d/m/Y') }}</td><td>{{ $item->kategori->nama_kategori }}</td><td>{{ $item->keterangan }}</td><td>{{ $item->user->nama }}</td><td class="text-mono text-right">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td><td><span class="status-badge {{ $item->status === 'aktif' ? 'status-active' : 'status-inactive' }}">{{ $item->status }}</span></td><td class="text-right"><a class="button button-secondary button-small" href="{{ route('pengeluaran.detail', $item) }}">Detail</a></td></tr>
                @empty
                    <tr><td class="empty-state" colspan="7">Belum ada pengeluaran.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="table-footer">{{ $pengeluaran->links() }}</div>
    </section>
@endsection
