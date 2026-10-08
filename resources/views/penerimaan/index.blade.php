@extends('layouts.app')

@section('title', 'Input Pembayaran | Sistem Informasi Keuangan')
@section('page-title', 'Input Pembayaran')

@section('content')
    <div class="page-header">
        <div>
            <h2>Pilih Siswa</h2>
            <p>Pilih siswa untuk memproses tagihan SPP dan non-SPP dalam satu kwitansi pembayaran.</p>
        </div>
    </div>

    <section class="data-card">
        <form class="filter-bar" method="GET" action="{{ route('penerimaan.index') }}">
            <div class="filter-field" style="min-width: min(100%, 18rem); flex: 1;">
                <label for="cari">NIPD atau Nama Siswa</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Masukkan NIPD atau nama siswa">
            </div>
            <div class="filter-field">
                <label for="id_jurusan">Jurusan</label>
                <select id="id_jurusan" name="id_jurusan">
                    <option value="">Semua Jurusan</option>
                    @foreach ($jurusan as $item)
                        <option value="{{ $item->id_jurusan }}" @selected(($filters['id_jurusan'] ?? null) == $item->id_jurusan)>{{ $item->nama_jurusan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="tingkat">Tingkat</label>
                <select id="tingkat" name="tingkat">
                    <option value="">Semua Tingkat</option>
                    @foreach ([1, 2, 3] as $tingkat)
                        <option value="{{ $tingkat }}" @selected(($filters['tingkat'] ?? null) == $tingkat)>Kelas {{ $tingkat }}</option>
                    @endforeach
                </select>
            </div>
            <button class="button button-primary" type="submit">Cari</button>
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr><th>NIPD</th><th>Nama Siswa</th><th>Kelas</th><th>Jurusan</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($siswa as $item)
                        @php($kelasAktif = $item->siswaKelas->first())
                        <tr>
                            <td class="text-mono">{{ $item->nipd }}</td>
                            <td>{{ $item->nama_siswa }}</td>
                            <td>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</td>
                            <td>{{ $kelasAktif?->kelas?->jurusan?->kode_jurusan ?? '-' }}</td>
                            <td class="text-right"><a class="button button-primary button-small" href="{{ route('penerimaan.show', $item) }}">Pilih Tagihan</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="5">Tidak ada siswa dengan tagihan aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <div>Menampilkan {{ $siswa->count() }} dari {{ $siswa->total() }} siswa</div>
            {{ $siswa->links() }}
        </div>
    </section>
@endsection
