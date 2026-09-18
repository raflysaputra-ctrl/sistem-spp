@extends('layouts.app')

@section('title', 'Arsip Kwitansi Siswa | Sistem Pembayaran SPP')
@section('page-title', 'Arsip Kwitansi Siswa')

@section('content')
    <div class="page-header"><div><h2>Arsip Foto Kwitansi</h2><p>Foto tersimpan privat dan hanya dapat dibuka oleh Petugas TU.</p></div></div>
    <section class="data-card">
        <form class="filter-bar" method="GET" action="{{ route('arsip-kwitansi.index') }}"><div class="filter-field" style="min-width: min(100%, 24rem); flex: 1;"><label for="cari">Cari Siswa</label><input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari NIPD atau nama siswa"></div><button class="button button-primary" type="submit">Cari</button></form>
        <div class="table-scroll"><table class="data-table"><thead><tr><th>Diunggah</th><th>Siswa</th><th>Transaksi Tertaut</th><th>Jenis File</th><th>Ukuran</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse ($arsipKwitansi as $arsip)
                <tr><td class="text-mono">{{ $arsip->created_at->format('d/m/Y H:i') }}</td><td><strong>{{ $arsip->siswa->nama_siswa }}</strong><br><span class="text-mono text-muted">{{ $arsip->siswa->nipd }}</span></td><td>@if ($arsip->pembayaran)<a href="{{ route('riwayat-pembayaran.show', $arsip->pembayaran) }}">{{ $arsip->pembayaran->tanggal_bayar->format('d/m/Y') }}</a>@else<span class="text-muted">Tidak ditautkan</span>@endif</td><td>{{ $arsip->mime_type }}</td><td>{{ number_format($arsip->ukuran_file / 1024, 1, ',', '.') }} KB</td><td class="text-right"><a class="button button-secondary button-small" href="{{ route('arsip-kwitansi.show', $arsip) }}" target="_blank" rel="noopener">Lihat Foto</a></td></tr>
            @empty
                <tr><td class="empty-state" colspan="6">Belum ada foto kwitansi yang diarsipkan.</td></tr>
            @endforelse
        </tbody></table></div>
        @if ($arsipKwitansi->count() > 0)<div class="table-footer"><span>Menampilkan {{ $arsipKwitansi->firstItem() }}-{{ $arsipKwitansi->lastItem() }} dari {{ $arsipKwitansi->total() }} arsip</span></div>@endif
    </section>
@endsection
