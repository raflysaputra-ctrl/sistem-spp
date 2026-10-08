@extends('layouts.app')

@section('title', 'Rekap Penerimaan | Sistem Informasi Keuangan')
@section('page-title', 'Rekap Penerimaan')

@section('content')
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    @endphp

    <div class="page-header">
        <div>
            <h2>Rekap Penerimaan</h2>
            <p>Gabungan penerimaan SPP dan non-SPP dengan sumber transaksi yang ditampilkan terpisah.</p>
        </div>
        @if ($tampilkanSpp)
            <div class="action-stack">
                <a class="button button-secondary" href="{{ route('rekap-pembayaran.export.excel', $filters) }}">Export Rekap SPP Excel</a>
                <a class="button button-secondary" href="{{ route('rekap-pembayaran.export.pdf', $filters) }}">Export Rekap SPP PDF</a>
            </div>
        @endif
    </div>

    <section class="data-card" style="margin-bottom: 1.5rem;">
        <form class="filter-bar" method="GET" action="{{ route('rekap-pembayaran.index') }}">
            <div class="filter-field">
                <label for="sumber">Sumber Penerimaan</label>
                <select id="sumber" name="sumber">
                    <option value="semua" @selected($filters['sumber'] === 'semua')>SPP dan Non-SPP</option>
                    <option value="spp" @selected($filters['sumber'] === 'spp')>SPP</option>
                    <option value="non_spp" @selected($filters['sumber'] === 'non_spp')>Non-SPP</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="tanggal_mulai">Tanggal Transaksi: Mulai</label>
                <input id="tanggal_mulai" name="tanggal_mulai" type="date" value="{{ $filters['tanggal_mulai'] ?? '' }}">
            </div>
            <div class="filter-field">
                <label for="tanggal_selesai">Tanggal Transaksi: Selesai</label>
                <input id="tanggal_selesai" name="tanggal_selesai" type="date" value="{{ $filters['tanggal_selesai'] ?? '' }}">
            </div>
            <div class="filter-field">
                <label for="status">Status Transaksi</label>
                <select id="status" name="status">
                    <option value="aktif" @selected(($filters['status'] ?? 'aktif') === 'aktif')>Aktif</option>
                    <option value="dibatalkan" @selected(($filters['status'] ?? '') === 'dibatalkan')>Dibatalkan</option>
                    <option value="semua" @selected(($filters['status'] ?? '') === 'semua')>Semua</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="cari">Cari Siswa</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari NIPD atau nama siswa">
            </div>
            @if ($tampilkanSpp)
            <div class="filter-field">
                <label for="bulan">Periode SPP: Bulan</label>
                <select id="bulan" name="bulan">
                    <option value="">Semua Bulan</option>
                    @foreach ($namaBulan as $nomorBulan => $bulan)
                        <option value="{{ $nomorBulan }}" @selected(($filters['bulan'] ?? null) === $nomorBulan)>{{ $bulan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="tahun">Periode SPP: Tahun</label>
                <select id="tahun" name="tahun">
                    <option value="">Semua Tahun</option>
                    @foreach ($tahunTersedia as $tahun)
                        <option value="{{ $tahun }}" @selected(($filters['tahun'] ?? null) === $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="id_jurusan">Jurusan</label>
                <select id="id_jurusan" name="id_jurusan">
                    <option value="">Semua Jurusan</option>
                    @foreach ($jurusan as $itemJurusan)
                        <option value="{{ $itemJurusan->id_jurusan }}" @selected(($filters['id_jurusan'] ?? null) === $itemJurusan->id_jurusan)>{{ $itemJurusan->nama_jurusan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="tingkat">Tingkat</label>
                <select id="tingkat" name="tingkat">
                    <option value="">Semua Tingkat</option>
                    @foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $nilaiTingkat => $namaTingkat)
                        <option value="{{ $nilaiTingkat }}" @selected(($filters['tingkat'] ?? null) === $nilaiTingkat)>{{ $namaTingkat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="rombel">Rombel</label>
                <input id="rombel" name="rombel" type="number" value="{{ $filters['rombel'] ?? '' }}" min="1" max="255" step="1" placeholder="Semua Rombel">
            </div>
            @endif
            <button class="button button-primary" type="submit">Terapkan Filter</button>
            <a class="button button-secondary" href="{{ route('rekap-pembayaran.index') }}">Reset</a>
        </form>
        @if ($tampilkanSpp)
            <p class="filter-note">Periode SPP mengacu pada bulan dan tahun tagihan. Filter periode, jurusan, tingkat, dan rombel hanya berlaku untuk penerimaan SPP. Tanggal Transaksi mengacu pada waktu pembayaran diterima.</p>
        @else
            <p class="filter-note">Tanggal Transaksi mengacu pada waktu pembayaran diterima. Rekap non-SPP dicatat per kwitansi.</p>
        @endif
    </section>

    <section class="report-summary" aria-label="Ringkasan rekap pembayaran">
        <div class="report-summary-item">
            <span>Total Penerimaan Aktif</span>
            <strong class="text-mono">Rp {{ number_format($ringkasan['total_aktif'], 0, ',', '.') }}</strong>
        </div>
        <div class="report-summary-item">
            <span>Transaksi Aktif</span>
            <strong>{{ $ringkasan['jumlah_transaksi_aktif'] }}</strong>
        </div>
        <div class="report-summary-item">
            <span>Total Dibatalkan</span>
            <strong class="text-mono">Rp {{ number_format($ringkasan['total_dibatalkan'], 0, ',', '.') }}</strong>
        </div>
        <div class="report-summary-item">
            <span>Transaksi Dibatalkan</span>
            <strong>{{ $ringkasan['jumlah_transaksi_dibatalkan'] }}</strong>
        </div>
        @if ($tampilkanSpp)
            <div class="report-summary-item">
                <span>Penerimaan SPP Aktif</span>
                <strong class="text-mono">Rp {{ number_format($ringkasanSpp['total_aktif'], 0, ',', '.') }}</strong>
            </div>
        @endif
        @if ($tampilkanNonSpp)
            <div class="report-summary-item">
                <span>Penerimaan Non-SPP Aktif</span>
                <strong class="text-mono">Rp {{ number_format($ringkasanNonSpp['total_aktif'], 0, ',', '.') }}</strong>
            </div>
        @endif
    </section>

    @if ($tampilkanSpp)
        <section class="data-card" @if ($tampilkanNonSpp) style="margin-bottom: 1.5rem;" @endif>
            <div class="card-header">
                <div>
                    <h3>Penerimaan SPP</h3>
                    <p>Setiap baris menunjukkan satu periode SPP yang telah dibayar.</p>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal Transaksi</th>
                            <th>No. Kwitansi</th>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Periode SPP</th>
                            <th class="text-right">Nominal</th>
                            <th>Petugas TU</th>
                            <th>Status</th>
                            @if (auth()->user()->role !== 'kepala_sekolah')
                                <th class="text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapPembayaran as $detail)
                            <tr>
                                <td>{{ $detail->pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</td>
                                <td class="text-mono"><strong>{{ $detail->pembayaran->penerimaan?->no_kwitansi ?? $detail->pembayaran->no_kwitansi }}</strong></td>
                                <td>
                                    <strong>{{ $detail->pembayaran->siswa->nama_siswa }}</strong><br>
                                    <span class="text-muted text-mono">{{ $detail->pembayaran->siswa->nipd }}</span>
                                </td>
                                <td>{{ $detail->tagihanSpp->siswaKelas->kelas->nama_kelas }}</td>
                                <td>{{ $namaBulan[$detail->tagihanSpp->bulan] }} {{ $detail->tagihanSpp->tahun }}</td>
                                <td class="text-mono text-right"><strong>Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}</strong></td>
                                <td>{{ $detail->pembayaran->user->nama }}</td>
                                <td>{{ $detail->pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}</td>
                                @if (auth()->user()->role !== 'kepala_sekolah')
                                    <td class="text-right">
                                        <a class="button button-secondary button-small" href="{{ $detail->pembayaran->penerimaan ? route('penerimaan.detail', $detail->pembayaran->penerimaan) : route('riwayat-pembayaran.show', $detail->pembayaran) }}">Detail</a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td class="empty-state" colspan="{{ auth()->user()->role !== 'kepala_sekolah' ? '9' : '8' }}">Tidak ada pembayaran yang sesuai dengan filter rekap.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rekapPembayaran->count() > 0)
                <div class="table-footer">
                    <span>Menampilkan {{ $rekapPembayaran->firstItem() }}-{{ $rekapPembayaran->lastItem() }} dari {{ $rekapPembayaran->total() }} periode dibayar</span>
                    @if ($rekapPembayaran->hasPages())
                        <nav class="pagination-links" aria-label="Pagination rekap SPP">
                            @if ($rekapPembayaran->onFirstPage())
                                <span>Sebelumnya</span>
                            @else
                                <a href="{{ $rekapPembayaran->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                            @endif

                            @if ($rekapPembayaran->hasMorePages())
                                <a href="{{ $rekapPembayaran->nextPageUrl() }}" rel="next">Selanjutnya</a>
                            @else
                                <span>Selanjutnya</span>
                            @endif
                        </nav>
                    @endif
                </div>
            @endif
        </section>
    @endif

    @if ($tampilkanNonSpp)
        <section class="data-card">
            <div class="card-header">
                <div>
                    <h3>Penerimaan Non-SPP</h3>
                    <p>Setiap baris menunjukkan satu kwitansi yang dapat memuat beberapa tagihan non-SPP.</p>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal Transaksi</th>
                            <th>No. Kwitansi</th>
                            <th>Siswa</th>
                            <th>Rincian Tagihan</th>
                            <th class="text-right">Total</th>
                            <th>Petugas TU</th>
                            <th>Status</th>
                            @if (auth()->user()->role !== 'kepala_sekolah')
                                <th class="text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapPembayaranNonSpp as $pembayaran)
                            <tr>
                                <td>{{ $pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</td>
                                <td class="text-mono"><strong>{{ $pembayaran->penerimaan?->no_kwitansi ?? $pembayaran->no_kwitansi }}</strong></td>
                                <td>
                                    <strong>{{ $pembayaran->siswa->nama_siswa }}</strong><br>
                                    <span class="text-muted text-mono">{{ $pembayaran->siswa->nipd }}</span>
                                </td>
                                <td>
                                    @forelse ($pembayaran->detailPembayaranNonSpp as $detail)
                                        <div>
                                            {{ $detail->tagihanPembayaran?->jenisPembayaran?->nama_jenis ?? 'Jenis pembayaran legacy' }}
                                            @if ($detail->tagihanPembayaran)
                                                <span class="text-muted">- {{ $detail->tagihanPembayaran->periode_label }} - {{ $detail->tagihanPembayaran->tahunAjaran?->tahun_ajaran ?? 'Tahun ajaran tidak tersedia' }}</span>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-muted">Rincian tagihan legacy tidak tersedia.</span>
                                    @endforelse
                                </td>
                                <td class="text-mono text-right"><strong>Rp {{ number_format($pembayaran->total_bayar, 0, ',', '.') }}</strong></td>
                                <td>{{ $pembayaran->user->nama }}</td>
                                <td>{{ $pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}</td>
                                @if (auth()->user()->role !== 'kepala_sekolah')
                                    <td class="text-right">
                                        <a class="button button-secondary button-small" href="{{ $pembayaran->penerimaan ? route('penerimaan.detail', $pembayaran->penerimaan) : route('riwayat-pembayaran-non-spp.show', $pembayaran) }}">Detail</a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td class="empty-state" colspan="{{ auth()->user()->role !== 'kepala_sekolah' ? '8' : '7' }}">Tidak ada penerimaan non-SPP yang sesuai dengan filter rekap.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rekapPembayaranNonSpp->count() > 0)
                <div class="table-footer">
                    <span>Menampilkan {{ $rekapPembayaranNonSpp->firstItem() }}-{{ $rekapPembayaranNonSpp->lastItem() }} dari {{ $rekapPembayaranNonSpp->total() }} kwitansi</span>
                    @if ($rekapPembayaranNonSpp->hasPages())
                        <nav class="pagination-links" aria-label="Pagination rekap non-SPP">
                            @if ($rekapPembayaranNonSpp->onFirstPage())
                                <span>Sebelumnya</span>
                            @else
                                <a href="{{ $rekapPembayaranNonSpp->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                            @endif

                            @if ($rekapPembayaranNonSpp->hasMorePages())
                                <a href="{{ $rekapPembayaranNonSpp->nextPageUrl() }}" rel="next">Selanjutnya</a>
                            @else
                                <span>Selanjutnya</span>
                            @endif
                        </nav>
                    @endif
                </div>
            @endif
        </section>
    @endif
@endsection
