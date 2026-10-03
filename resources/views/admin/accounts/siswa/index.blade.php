@extends('layouts.app')

@section('title', 'Manajemen Akun Siswa | Sistem Informasi Keuangan')
@section('page-title', 'Manajemen Akun Siswa')

@section('content')
    <div class="page-header">
        <div>
            <h2>Daftar Akun Siswa</h2>
            <p>Kelola akun siswa, buat akun manual, atau impor dari file Excel.</p>
        </div>
        <span class="action-stack">
            <a class="button button-primary" href="{{ route('admin.accounts.siswa.import') }}">Impor dari Excel</a>
        </span>
    </div>

    @if (session('success'))
        <div class="flash-message">{{ session('success') }}</div>
    @endif

    <section class="data-card">
        <div class="filter-bar">
            <form method="GET" action="{{ route('admin.accounts.siswa.index') }}" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end; width: 100%;">
                <div class="filter-field">
                    <label for="search">Cari Nama/NIPD</label>
                    <input id="search" name="search" type="text" placeholder="Nama atau NIPD..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-field">
                    <label for="status_akun">Status Akun</label>
                    <select id="status_akun" name="status_akun">
                        <option value="">Semua</option>
                        <option value="aktif" @if(($filters['status_akun'] ?? '') === 'aktif') selected @endif>Aktif</option>
                        <option value="nonaktif" @if(($filters['status_akun'] ?? '') === 'nonaktif') selected @endif>Nonaktif</option>
                    </select>
                </div>

                <button class="button button-primary" type="submit">Filter</button>
                <a class="button button-secondary" href="{{ route('admin.accounts.siswa.index') }}">Reset</a>
            </form>
        </div>

        @if ($siswaList->isEmpty())
            <div class="empty-state">
                <p>Tidak ada data siswa yang sesuai dengan filter.</p>
            </div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>NIPD</th>
                            <th>Kelas</th>
                            <th>Status Akun</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($siswaList as $siswa)
                            @php
                                $akunSiswa = $siswa->akunSiswa;
                                if (!$akunSiswa) {
                                    $statusAkun = 'Nonaktif';
                                    $statusBadge = 'status-inactive';
                                } else {
                                    $statusAkun = $akunSiswa->is_active ? 'Aktif' : 'Nonaktif';
                                    $statusBadge = $akunSiswa->is_active ? 'status-active' : 'status-inactive';
                                }
                                $kelasAktif = $siswa->siswaKelas->first()?->kelas?->nama_kelas ?? '-';
                            @endphp
                            <tr>
                                <td>{{ $siswa->nama_siswa }}</td>
                                <td>{{ $siswa->nipd }}</td>
                                <td>{{ $kelasAktif }}</td>
                                <td>
                                    <span class="status-badge {{ $statusBadge }}">{{ $statusAkun }}</span>
                                </td>
                                <td>
                                    <div class="action-stack">
                                        @if (!$akunSiswa)
                                            <a class="button button-primary button-small" href="{{ route('admin.accounts.siswa.create', $siswa) }}">Buat Akun</a>
                                        @else
                                            <a class="button button-secondary button-small" href="{{ route('admin.accounts.siswa.edit', $akunSiswa) }}">Edit</a>
                                            <form method="POST" action="{{ route('admin.accounts.siswa.toggle', $akunSiswa) }}" style="display: inline;">
                                                @csrf
                                                <button class="button button-secondary button-small" type="submit">
                                                    {{ $akunSiswa->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.accounts.siswa.destroy', $akunSiswa) }}" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus akun ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button button-danger button-small" type="submit">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.5rem;">
                {{ $siswaList->links() }}
            </div>
        @endif
    </section>
@endsection
