@extends('layouts.app')

@section('title', 'Laporan Tunggakan | Sistem Pembayaran SPP')
@section('page-title', 'Laporan Tunggakan')

@section('content')
    <div class="page-header">
        <div>
            <h2>Laporan Tunggakan SPP</h2>
            <p>Daftar tagihan belum bayar dari periode SPP sebelum bulan berjalan.</p>
        </div>
        <div class="action-stack">
            <a class="button button-secondary" href="{{ route('laporan-tunggakan.export.excel', $filters) }}">Export Excel</a>
            <a class="button button-secondary" href="{{ route('laporan-tunggakan.export.pdf', $filters) }}">Export PDF</a>
        </div>
    </div>

    <section class="data-card" style="margin-bottom: 1.5rem;">
        <form class="filter-bar" method="GET" action="{{ route('laporan-tunggakan.index') }}">
            <div class="filter-field">
                <label for="id_jurusan">Jurusan</label>
                <select id="id_jurusan" name="id_jurusan">
                    <option value="">Semua Jurusan</option>
                    @foreach ($jurusan as $itemJurusan)
                        <option value="{{ $itemJurusan->id_jurusan }}" @selected((string) ($filters['id_jurusan'] ?? '') === (string) $itemJurusan->id_jurusan)>{{ $itemJurusan->nama_jurusan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="tingkat">Tingkat</label>
                <select id="tingkat" name="tingkat">
                    <option value="">Semua Tingkat</option>
                    @foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $nilaiTingkat => $namaTingkat)
                        <option value="{{ $nilaiTingkat }}" @selected((string) ($filters['tingkat'] ?? '') === (string) $nilaiTingkat)>{{ $namaTingkat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="rombel">Rombel</label>
                <input id="rombel" name="rombel" type="number" value="{{ $filters['rombel'] ?? '' }}" min="1" max="255" step="1" placeholder="Semua Rombel">
            </div>
            <div class="filter-field">
                <label for="id_tahun_ajaran">Tahun Ajaran</label>
                <select id="id_tahun_ajaran" name="id_tahun_ajaran">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach ($tahunAjaran as $itemTahunAjaran)
                        <option value="{{ $itemTahunAjaran->id_tahun_ajaran }}" @selected((string) ($filters['id_tahun_ajaran'] ?? '') === (string) $itemTahunAjaran->id_tahun_ajaran)>{{ $itemTahunAjaran->tahun_ajaran }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="cari">Cari Siswa</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari NIPD atau nama siswa">
            </div>
            <button class="button button-primary" type="submit">Terapkan Filter</button>
            @if (count($filters) > 0)
                <a class="button button-secondary" href="{{ route('laporan-tunggakan.index') }}">Reset</a>
            @endif
        </form>
        <p class="filter-note">Tunggakan mencakup tagihan belum bayar dari periode sebelum bulan berjalan.</p>
    </section>

    <section class="report-summary" aria-label="Ringkasan laporan tunggakan">
        <div class="report-summary-item">
            <span>Total Tunggakan</span>
            <strong class="text-mono">Rp {{ number_format($totalNominal, 0, ',', '.') }}</strong>
        </div>
        <div class="report-summary-item">
            <span>Jumlah Siswa Menunggak</span>
            <strong>{{ $jumlahSiswa }}</strong>
        </div>
        <div class="report-summary-item">
            <span>Jumlah Tagihan Menunggak</span>
            <strong>{{ $jumlahTagihan }}</strong>
        </div>
    </section>

    <section class="data-card">
        <div class="card-header">
            <div>
                <h3>Data Tunggakan</h3>
                <p>Setiap baris menunjukkan total tagihan belum bayar dari satu siswa.</p>
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIPD</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th class="text-right">Jml Tagihan</th>
                        <th class="text-right">Total Tunggakan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tunggakan as $item)
                        @php($kelasAktif = $item->siswa->siswaKelas->first())
                        <tr>
                            <td class="text-mono">{{ $item->siswa->nipd }}</td>
                            <td><strong>{{ $item->siswa->nama_siswa }}</strong></td>
                            <td>{{ $kelasAktif?->kelas?->nama_kelas ?? 'Tidak ada kelas aktif' }}</td>
                            <td class="text-mono text-right">{{ $item->jumlah_tagihan }}</td>
                            <td class="text-mono text-right"><strong>Rp {{ number_format($item->total_tunggakan, 0, ',', '.') }}</strong></td>
                            <td class="text-right">
                                <a class="button button-primary button-small" href="{{ route('pembayaran.show', $item->siswa) }}">Bayar SPP</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="6">Tidak ada tunggakan SPP yang sesuai dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tunggakan->count() > 0)
            <div class="table-footer">
                <span>Menampilkan {{ $tunggakan->firstItem() }}-{{ $tunggakan->lastItem() }} dari {{ $tunggakan->total() }} siswa menunggak</span>
                @if ($tunggakan->hasPages())
                    <nav class="pagination-links" aria-label="Pagination laporan tunggakan">
                        @if ($tunggakan->onFirstPage())
                            <span>Sebelumnya</span>
                        @else
                            <a href="{{ $tunggakan->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                        @endif

                        @if ($tunggakan->hasMorePages())
                            <a href="{{ $tunggakan->nextPageUrl() }}" rel="next">Selanjutnya</a>
                        @else
                            <span>Selanjutnya</span>
                        @endif
                    </nav>
                @endif
            </div>
        @endif
    </section>
@endsection
