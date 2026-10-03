@extends('layouts.app')

@section('title', 'Edit Akun Siswa | Sistem Informasi Keuangan')
@section('page-title', 'Manajemen Akun Siswa')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Akun Siswa</h2>
            <p>Kelola password dan status akun siswa {{ $user->nama }}.</p>
        </div>
        <span class="action-stack">
            <a class="button button-secondary" href="{{ route('admin.accounts.siswa.index') }}">Kembali</a>
        </span>
    </div>

    @if (session('success'))
        <div class="flash-message">{{ session('success') }}</div>
    @endif

    @if (session('info'))
        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; border: 1px solid #b3d9ff; border-radius: .25rem; background: #d0e1fb; color: #1e40af; font-size: .88rem;">
            {{ session('info') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="error-message">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="form-card">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1rem;">Informasi Akun</h3>

        <div class="form-field">
            <label>Nama Siswa</label>
            <input type="text" value="{{ $user->nama }}" readonly style="background: #f2f4f6; cursor: not-allowed;">
        </div>

        <div class="form-field">
            <label>NIPD / Username</label>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <input type="text" value="{{ $user->username }}" readonly style="background: #f2f4f6; cursor: not-allowed; flex: 1;">
                @if($siswa && $siswa->nipd !== $user->username)
                    <form method="POST" action="{{ route('admin.accounts.siswa.syncUsername', $user) }}" style="display: inline;">
                        @csrf
                        <button class="button button-secondary button-small" type="submit" title="Sinkronkan username dari NIPD di master data">Sync</button>
                    </form>
                @endif
            </div>
            @if($siswa && $siswa->nipd !== $user->username)
                <p style="margin: 0.5rem 0 0; font-size: 0.85rem; color: #d97706;">⚠️ NIPD di master data berbeda ({{ $siswa->nipd }}). Klik "Sync" untuk menyamakannya.</p>
            @endif
        </div>

        <div class="form-field">
            <label>Status Akun</label>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="status-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
                <form method="POST" action="{{ route('admin.accounts.siswa.toggle', $user) }}" style="display: inline;">
                    @csrf
                    <button class="button button-secondary button-small" type="submit">
                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="form-card" style="margin-top: 1.5rem;">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1rem;">Reset Password</h3>

        <form method="POST" action="{{ route('admin.accounts.siswa.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="form-field">
                <label for="password">Password Baru (opsional)</label>
                <input id="password" name="password" type="password" minlength="8" placeholder="Kosongkan jika tidak ingin mengubah">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" placeholder="Ulangi password baru">
                @error('password_confirmation')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-actions">
                <button class="button button-primary" type="submit">Reset Password</button>
                <a class="button button-secondary" href="{{ route('admin.accounts.siswa.index') }}">Batal</a>
            </div>
        </form>
    </section>
@endsection
