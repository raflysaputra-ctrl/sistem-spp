@extends('layouts.app')

@section('title', 'Edit Akun | Sistem Informasi Keuangan')
@section('page-title', 'Manajemen Akun')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Akun: {{ $user->username }}</h2>
            <p>Ubah data akun atau reset password.</p>
        </div>
    </div>

    <section class="data-card">
        @if ($errors->any())
            <div class="error-message">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.accounts.staff.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="nama">Nama Lengkap *</label>
                <input id="nama" name="nama" type="text" value="{{ old('nama', $user->nama) }}" required>
                @error('nama')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="username">Username *</label>
                <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required>
                @error('username')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="role">Peran Akun *</label>
                <select id="role" name="role" required>
                    <option value="">-- Pilih Peran --</option>
                    @foreach ($roles as $roleValue => $roleLabel)
                        <option value="{{ $roleValue }}" @selected(old('role', $user->role) === $roleValue)>{{ $roleLabel }}</option>
                    @endforeach
                </select>
                @error('role')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="password">Password Baru (Minimal 8 Karakter, kosongkan jika tidak ingin diubah)</label>
                <input id="password" name="password" type="password">
                @error('password')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-actions">
                <button class="button button-primary" type="submit">Simpan Perubahan</button>
                <a class="button button-secondary" href="{{ route('admin.accounts.staff.index') }}">Kembali</a>
            </div>
        </form>

        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #e0e0e0;">

        <div style="margin-top: 2rem;">
            <h3>Riwayat Aktivitas (10 Aktivitas Terbaru)</h3>
            @if ($logs->count() > 0)
                <div class="table-scroll">
                    <table class="data-table" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>Aksi</th>
                                <th>Dilakukan Oleh</th>
                                <th>Waktu</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td><span class="badge">{{ $log->action }}</span></td>
                                    <td>{{ $log->actor->nama }}</td>
                                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td style="word-break: break-word; max-width: 30rem;">{{ $log->details ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="color: #666;">Belum ada aktivitas untuk akun ini.</p>
            @endif
        </div>
    </section>
@endsection

<style>
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
    .form-group input,
    .form-group select { width: 100%; max-width: 30rem; padding: 0.7rem 0.75rem; border: 1px solid #c4c5d5; border-radius: 0.25rem; font-family: inherit; }
    .form-group input:focus,
    .form-group select:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
    .error-text { margin-top: 0.45rem; color: #ba1a1a; font-size: 0.8rem; }
    .form-actions { margin-top: 2rem; display: flex; gap: 1rem; }
    .button { padding: 0.75rem 1rem; border-radius: 0.25rem; border: 0; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
    .button-primary { background: #00288e; color: #fff; }
    .button-primary:hover { background: #1e40af; }
    .button-secondary { background: #e8eaed; color: #191c1e; }
    .button-secondary:hover { background: #d0d5dd; }
    .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #e3f2fd; color: #00288e; }
    hr { border: 0; border-top: 1px solid #e0e0e0; }
</style>
