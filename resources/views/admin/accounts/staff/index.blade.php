@extends('layouts.app')

@section('title', 'Manajemen Akun | Sistem Informasi Keuangan')
@section('page-title', 'Manajemen Akun')

@section('content')
    <div class="page-header">
        <div>
            <h2>Daftar Akun Staff</h2>
            <p>Kelola akun internal: Admin, Tata Usaha, dan Kepala Sekolah.</p>
        </div>
        <span class="action-stack">
            <a class="button button-primary" href="{{ route('admin.accounts.staff.create') }}">Tambah Akun Staff</a>
        </span>
    </div>

    <section class="data-card">
        @if (session('success'))
            <div class="flash-message">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="error-message">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="filter-bar" method="GET" action="{{ route('admin.accounts.staff.index') }}">
            <div class="filter-field" style="min-width: min(100%, 24rem); flex: 1;">
                <label for="search">Cari Akun</label>
                <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama, username, atau NIPD siswa">
            </div>
            <div class="filter-field">
                <label for="role">Peran Akun</label>
                <select id="role" name="role">
                    <option value="">Semua Peran</option>
                    @foreach ($roles as $roleValue => $roleLabel)
                        <option value="{{ $roleValue }}" @selected(($filters['role'] ?? null) === $roleValue)>{{ $roleLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>Aktif</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <button class="button button-primary" type="submit">Terapkan Filter</button>
            @if (count($filters) > 0 && (array_filter($filters)))
                <a class="button button-secondary" href="{{ route('admin.accounts.staff.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th>Dibuat Oleh</th>
                        <th>Dibuat Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->nama }}</td>
                            <td><code>{{ $user->username }}</code></td>
                            <td><span class="badge">{{ $user->roleLabel() }}</span></td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>{{ $user->createdByUser?->nama ?? '-' }}</td>
                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a class="button button-sm button-secondary" href="{{ route('admin.accounts.staff.edit', $user) }}">Edit</a>
                                    @if ($user->id_user !== auth()->id())
                                        <form method="POST" action="{{ route('admin.accounts.staff.toggle', $user) }}" style="display: inline;">
                                            @csrf
                                            <button class="button button-sm {{ $user->is_active ? 'button-warning' : 'button-success' }}" type="submit">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.accounts.staff.destroy', $user) }}" style="display: inline;" onsubmit="return confirm('Hapus akun {{ $user->username }}? Aksi ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button button-sm button-danger" type="submit">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">Tidak ada akun yang sesuai dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div style="margin-top: 2rem; display: flex; justify-content: center;">
                {{ $users->links() }}
            </div>
        @endif
    </section>
@endsection
