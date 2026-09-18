@extends('layouts.app')

@section('title', 'Rekap Pembayaran | Sistem Pembayaran SPP')
@section('page-title', 'Rekap Pembayaran')

@section('content')
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    @endphp

    <div class="page-header">
        <div>
            <h2>Rekap Pembayaran</h2>
            <p>Rekap periode SPP dan tanggal transaksi pembayaran berdasarkan siswa dan kelas.</p>
        </div>
        <div class="action-stack">
            <a class="button button-secondary" href="{{ route('rekap-pembayaran.export.excel', $filters) }}">Export Excel</a>
            <a class="button button-secondary" href="{{ route('rekap-pembayaran.export.pdf', $filters) }}">Export PDF</a>
        </div>
    </div>

    <section class="data-card" style="margin-bottom: 1.5rem;">
        <form class="filter-bar" method="GET" action="{{ route('rekap-pembayaran.index') }}">
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
            <div class="filter-field">
                <label for="cari">Cari Siswa</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari NIPD atau nama siswa">
            </div>
            <button class="button button-primary" type="submit">Terapkan Filter</button>
            @if (count($filters) > 0)
                <a class="button button-secondary" href="{{ route('rekap-pembayaran.index') }}">Reset</a>
            @endif
        </form>
        <p class="filter-note">Periode SPP mengacu pada bulan dan tahun tagihan. Tanggal Transaksi mengacu pada waktu pembayaran diterima.</p>
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
    </section>

    <section class="data-card">
        <div class="card-header">
            <div>
                <h3>Data Rekap</h3>
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
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekapPembayaran as $detail)
                        <tr>
                            <td>{{ $detail->pembayaran->tanggal_bayar->format('d/m/Y H:i') }}</td>
                            <td class="text-mono"><strong>{{ $detail->pembayaran->no_kwitansi }}</strong></td>
                            <td>
                                <strong>{{ $detail->pembayaran->siswa->nama_siswa }}</strong><br>
                                <span class="text-muted text-mono">{{ $detail->pembayaran->siswa->nipd }}</span>
                            </td>
                            <td>{{ $detail->tagihanSpp->siswaKelas->kelas->nama_kelas }}</td>
                            <td>{{ $namaBulan[$detail->tagihanSpp->bulan] }} {{ $detail->tagihanSpp->tahun }}</td>
                            <td class="text-mono text-right"><strong>Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}</strong></td>
                            <td>{{ $detail->pembayaran->user->nama }}</td>
                            <td>{{ $detail->pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan' }}</td>
                            <td class="text-right">
                                <a class="button button-secondary button-small" href="{{ route('riwayat-pembayaran.show', $detail->pembayaran) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="9">Tidak ada pembayaran yang sesuai dengan filter rekap.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rekapPembayaran->count() > 0)
            <div class="table-footer">
                <span>Menampilkan {{ $rekapPembayaran->firstItem() }}-{{ $rekapPembayaran->lastItem() }} dari {{ $rekapPembayaran->total() }} periode dibayar</span>
                @if ($rekapPembayaran->hasPages())
                    <nav class="pagination-links" aria-label="Pagination rekap pembayaran">
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
@endsection
