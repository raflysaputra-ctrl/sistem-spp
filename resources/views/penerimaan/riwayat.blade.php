@extends('layouts.app')

@section('title', 'Riwayat Pembayaran | Sistem Informasi Keuangan')
@section('page-title', 'Riwayat Pembayaran')

@section('content')
    <div class="page-header"><div><h2>Riwayat Pembayaran</h2><p>Riwayat transaksi yang dibuat melalui Input Pembayaran.</p></div></div>
    <section class="data-card">
        <form class="filter-bar" method="GET" action="{{ route('penerimaan.riwayat') }}">
            <div class="filter-field" style="flex: 1;"><label for="cari">Nomor Kwitansi / Siswa</label><input id="cari" name="cari" value="{{ $filters['cari'] ?? '' }}"></div>
            <div class="filter-field"><label for="status">Status</label><select id="status" name="status"><option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option><option value="dibatalkan" @selected($filters['status'] === 'dibatalkan')>Dibatalkan</option><option value="semua" @selected($filters['status'] === 'semua')>Semua</option></select></div>
            <button class="button button-primary" type="submit">Filter</button>
        </form>
        <div class="table-scroll"><table class="data-table">
            <thead><tr><th>No. Kwitansi</th><th>Tanggal</th><th>Siswa</th><th>Isi</th><th class="text-right">Total</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($penerimaan as $item)
                    @php($isi = collect([$item->pembayaranSpp ? 'SPP' : null, $item->pembayaranNonSpp ? 'Non-SPP' : null])->filter()->implode(' + '))
                    <tr><td class="text-mono">{{ $item->no_kwitansi }}</td><td>{{ $item->tanggal_bayar->format('d/m/Y H:i') }}</td><td>{{ $item->siswa->nama_siswa }}</td><td>{{ $isi }}</td><td class="text-mono text-right">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</td><td><span class="status-badge {{ $item->status === 'aktif' ? 'status-active' : 'status-inactive' }}">{{ $item->status }}</span></td><td class="text-right"><a class="button button-secondary button-small" href="{{ route('penerimaan.detail', $item) }}">Detail</a></td></tr>
                @empty
                    <tr><td class="empty-state" colspan="7">Belum ada kwitansi pembayaran.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="table-footer">{{ $penerimaan->links() }}</div>
    </section>
@endsection
