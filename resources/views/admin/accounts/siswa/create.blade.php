@extends('layouts.app')

@section('title', 'Buat Akun Siswa | Sistem Informasi Keuangan')
@section('page-title', 'Manajemen Akun Siswa')

@section('content')
    <div class="page-header">
        <div>
            <h2>Buat Akun Siswa</h2>
            <p>Daftarkan akun login untuk siswa {{ $siswa->nama_siswa }}.</p>
        </div>
        <span class="action-stack">
            <a class="button button-secondary" href="{{ route('admin.accounts.siswa.index') }}">Kembali</a>
        </span>
    </div>

    @if ($errors->any())
        <div class="error-message">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="form-card">
        <form method="POST" action="{{ route('admin.accounts.siswa.store', $siswa) }}">
            @csrf

            <div class="form-field">
                <label>Nama Siswa</label>
                <input type="text" value="{{ $siswa->nama_siswa }}" readonly style="background: #f2f4f6; cursor: not-allowed;">
            </div>

            <div class="form-field">
                <label>NIPD (Username)</label>
                <input type="text" value="{{ $siswa->nipd }}" readonly style="background: #f2f4f6; cursor: not-allowed;">
                <p style="margin: 0.5rem 0 0; font-size: 0.85rem; color: #505f76;">NIPD akan digunakan sebagai username untuk login.</p>
            </div>

            <div class="form-field">
                <label for="password">Password *</label>
                <input id="password" name="password" type="password" required minlength="8" placeholder="Minimal 8 karakter">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation">Konfirmasi Password *</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" placeholder="Ulangi password">
                @error('password_confirmation')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-actions">
                <button class="button button-primary" type="submit">Buat Akun</button>
                <a class="button button-secondary" href="{{ route('admin.accounts.siswa.index') }}">Batal</a>
            </div>
        </form>
    </section>
@endsection
