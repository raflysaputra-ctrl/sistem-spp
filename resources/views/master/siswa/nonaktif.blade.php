@extends('layouts.app')

@section('title', 'Siswa Nonaktif | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Siswa Nonaktif</h2>
            <p>Siswa nonaktif tidak tampil pada daftar aktif. Histori tagihan dan pembayaran tetap dapat dilihat.</p>
        </div>
        <a class="button button-secondary" href="{{ route('master.siswa.index') }}">Daftar Siswa Aktif</a>
    </div>

    <section class="data-card">
        @if (session('status'))
            <div class="flash-message">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <form class="filter-bar" method="GET" action="{{ route('master.siswa.nonaktif') }}">
            <div class="filter-field" style="min-width: min(100%, 24rem); flex: 1;">
                <label for="cari">Cari Siswa Nonaktif</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari NIPD atau nama siswa">
            </div>
            <button class="button button-primary" type="submit">Cari</button>
            @if (($filters['cari'] ?? null) !== null)
                <a class="button button-secondary" href="{{ route('master.siswa.nonaktif') }}">Reset</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIPD</th>
                        <th>Nama Siswa</th>
                        <th>Dinonaktifkan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswa as $item)
                        <tr>
                            <td class="text-mono">{{ $item->nipd }}</td>
                            <td>{{ $item->nama_siswa }}</td>
                            <td>{{ $item->deleted_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-right">
                                <span class="action-stack">
                                    <a class="button button-secondary button-small" href="{{ route('status-spp.show', $item) }}">Status SPP</a>
                                    <a class="button button-secondary button-small" href="{{ route('riwayat-pembayaran.index', ['cari' => $item->nipd]) }}">Riwayat Pembayaran</a>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="4">Tidak ada siswa nonaktif yang sesuai dengan pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($siswa->count() > 0)
            <div class="table-footer">
                <span>Menampilkan {{ $siswa->firstItem() }}-{{ $siswa->lastItem() }} dari {{ $siswa->total() }} siswa nonaktif</span>
                @if ($siswa->hasPages())
                    <nav class="pagination-links" aria-label="Pagination daftar siswa nonaktif">
                        @if ($siswa->onFirstPage())
                            <span>Sebelumnya</span>
                        @else
                            <a href="{{ $siswa->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                        @endif

                        @if ($siswa->hasMorePages())
                            <a href="{{ $siswa->nextPageUrl() }}" rel="next">Selanjutnya</a>
                        @else
                            <span>Selanjutnya</span>
                        @endif
                    </nav>
                @endif
            </div>
        @endif
    </section>
@endsection
