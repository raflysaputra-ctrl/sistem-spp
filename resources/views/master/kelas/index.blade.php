@extends('layouts.app')

@section('title', 'Data Kelas | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Data Kelas</h2>
        </div>
        <a class="button button-primary" href="{{ route('master.kelas.create') }}">Tambah Kelas</a>
    </div>

    <section class="data-card">
        @if (session('status'))
            <div class="flash-message">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif
        <form class="filter-bar" method="GET" action="{{ route('master.kelas.index') }}">
            <div class="filter-field">
                <label for="id_jurusan">Jurusan</label>
                <select id="id_jurusan" name="id_jurusan">
                    <option value="">Semua Jurusan</option>
                    @foreach ($jurusan as $item)
                        <option value="{{ $item->id_jurusan }}" @selected(($filters['id_jurusan'] ?? null) == $item->id_jurusan)>{{ $item->kode_jurusan }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="tingkat">Tingkat</label>
                <select id="tingkat" name="tingkat">
                    <option value="">Semua Tingkat</option>
                    @foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['tingkat'] ?? null) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="rombel">Rombel</label>
                <input id="rombel" name="rombel" type="number" value="{{ $filters['rombel'] ?? '' }}" min="1" max="255" step="1" placeholder="Semua Rombel">
            </div>

            <button class="button button-secondary" type="submit">Terapkan Filter</button>
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Kelas</th>
                        <th>Jurusan</th>
                        <th>Tingkat</th>
                        <th>Rombel</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelas as $item)
                        <tr>
                            <td>{{ $item->nama_kelas }}</td>
                            <td class="text-mono">{{ $item->jurusan->kode_jurusan }}</td>
                            <td>{{ [1 => 'X', 2 => 'XI', 3 => 'XII'][$item->tingkat] }}</td>
                            <td class="text-muted">Rombel {{ $item->rombel }}</td>
                            <td class="text-right">
                                <span class="action-stack">
                                    @if ($item->siswa_kelas_count === 0)
                                        <form class="inline-form" method="POST" action="{{ route('master.kelas.destroy', $item) }}" data-confirm data-confirm-title="Hapus kelas?" data-confirm-message="Kelas {{ $item->nama_kelas }} akan dihapus permanen karena belum memiliki riwayat siswa." data-confirm-submit="Hapus Kelas">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button button-danger button-small" type="submit">Hapus</button>
                                        </form>
                                    @else
                                        <span class="reference-note">Memiliki riwayat siswa</span>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="5">Tidak ada kelas yang sesuai dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
