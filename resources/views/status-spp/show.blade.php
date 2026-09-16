@extends('layouts.app')

@section('title', 'Status SPP | Sistem Pembayaran SPP')
@section('page-title', 'Status Pembayaran SPP')

@section('content')
    @php($namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])

    <div class="page-header">
        <div>
            <h2>Status SPP Siswa</h2>
            <p>Lihat periode SPP yang sudah lunas dan yang masih belum dibayar.</p>
        </div>
        <a class="button button-secondary" href="{{ route('status-spp.index') }}">Kembali ke Status SPP</a>
    </div>

    <section class="form-card" style="max-width: none; margin-bottom: 1.5rem;">
        <div class="form-grid">
            <div class="form-field">
                <label>Nama Siswa</label>
                <strong>{{ $siswa->nama_siswa }}</strong>
            </div>
            <div class="form-field">
                <label>NIPD</label>
                <span class="text-mono">{{ $siswa->nipd }}</span>
            </div>
            <div class="form-field">
                <label>Kelas Saat Ini</label>
                <span>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</span>
            </div>
            <div class="form-field">
                <label>Tahun Ajaran Aktif</label>
                <span>{{ $kelasAktif?->tahunAjaran?->tahun_ajaran ?? '-' }}</span>
            </div>
        </div>
    </section>

    <section class="data-card">
        <div class="card-header">
            <div>
                <h3>Daftar Tagihan SPP</h3>
                <p>Kelas pada setiap baris mengikuti riwayat kelas saat tagihan dibuat.</p>
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Periode SPP</th>
                        <th>Tahun Ajaran</th>
                        <th>Kelas</th>
                        <th class="text-right">Nominal</th>
                        <th>Status</th>
                        <th>Tanggal Lunas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tagihanSpp as $tagihan)
                        <tr>
                            <td>{{ $namaBulan[$tagihan->bulan] }} {{ $tagihan->tahun }}</td>
                            <td>{{ $tagihan->siswaKelas->tahunAjaran->tahun_ajaran }}</td>
                            <td>{{ $tagihan->siswaKelas->kelas->nama_kelas }}</td>
                            <td class="text-mono text-right">Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}</td>
                            <td>
                                @if ($tagihan->adalahTunggakan())
                                    <span class="status-badge status-tunggakan">Tunggakan</span>
                                @endif
                                <span class="status-badge {{ $tagihan->status === 'lunas' ? 'status-lunas' : 'status-belum-bayar' }}">
                                    {{ $tagihan->status === 'lunas' ? 'Lunas' : 'Belum Bayar' }}
                                </span>
                            </td>
                            <td class="text-mono">{{ $tagihan->tanggal_lunas?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="6">Belum ada tagihan SPP untuk siswa ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
