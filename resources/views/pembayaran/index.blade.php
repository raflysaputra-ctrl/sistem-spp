@extends('layouts.app')

@section('title', 'Transaksi Pembayaran | Sistem Pembayaran SPP')
@section('page-title', 'Transaksi Pembayaran')

@section('content')
    <div class="page-header">
        <div>
            <h2>Pilih Siswa</h2>
            <p>Gunakan pencarian atau filter untuk memilih siswa yang akan melakukan pembayaran.</p>
        </div>
    </div>

    <section class="data-card">
        <form class="filter-bar" method="GET" action="{{ route('pembayaran.index') }}">
            <div class="filter-field" style="min-width: min(100%, 18rem); flex: 1;">
                <label for="cari">NIPD atau Nama Siswa</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Masukkan NIPD atau nama siswa" autofocus>
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
                <label for="status_siswa">Status Siswa</label>
                <select id="status_siswa" name="status_siswa">
                    <option value="aktif" @selected($statusSiswa === 'aktif')>Aktif</option>
                    <option value="lulus" @selected($statusSiswa === 'lulus')>Lulus</option>
                    <option value="pindah" @selected($statusSiswa === 'pindah')>Pindah</option>
                    <option value="semua" @selected($statusSiswa === 'semua')>Semua Status</option>
                </select>
            </div>
            <button class="button button-primary" type="submit">Terapkan Filter</button>
            @if (count($filters) > 0)
                <a class="button button-secondary" href="{{ route('pembayaran.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIPD</th>
                        <th>Nama Siswa</th>
                        <th>Kelas Aktif</th>
                        <th>Jurusan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswa as $item)
                        @php($kelasAktif = $item->siswaKelas->first())
                        <tr>
                            <td class="text-mono">{{ $item->nipd }}</td>
                            <td>{{ $item->nama_siswa }}</td>
                            <td>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</td>
                            <td class="text-mono">{{ $kelasAktif?->kelas?->jurusan?->kode_jurusan ?? '-' }}</td>
                            <td>
                                <span class="status-badge {{ $item->status_siswa === 'aktif' ? 'status-active' : 'status-inactive' }}">{{ $item->status_siswa }}</span>
                            </td>
                            <td class="text-right">
                                <a class="button button-secondary button-small" href="{{ route('pembayaran.show', $item) }}">Pilih Siswa</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="6">Tidak ada siswa yang sesuai dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($siswa->hasPages())
            <div class="table-footer">
                {{ $siswa->links() }}
            </div>
        @endif
    </section>
@endsection
