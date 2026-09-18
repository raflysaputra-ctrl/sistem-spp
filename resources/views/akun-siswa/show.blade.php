@extends('layouts.app')

@section('title', 'Akun Siswa | Sistem Pembayaran SPP')
@section('page-title', 'Akun Siswa')

@section('content')
    <div class="page-header">
        <div>
            <h2>Akun Portal Siswa</h2>
            <p>{{ $siswa->nama_siswa }} <span class="text-mono">({{ $siswa->nipd }})</span></p>
        </div>
        <a class="button button-secondary" href="{{ route('master.siswa.index') }}">Kembali ke Data Siswa</a>
    </div>

    @if (session('status'))<div class="flash-message">{{ session('status') }}</div>@endif
    @if ($errors->has('akun'))<div class="error-message">{{ $errors->first('akun') }}</div>@endif

    @if (! $akunSiswa)
        <section class="form-card">
            <h3>Buat Akun Siswa</h3>
            <p class="text-muted">Tidak ada pendaftaran publik. Bagikan username dan password awal ini langsung kepada siswa.</p>
            <form method="POST" action="{{ route('master.siswa.akun.store', $siswa) }}">
                @csrf
                <div class="form-grid">
                    <div class="form-field full-width"><label for="username">Username</label><input id="username" name="username" value="{{ old('username') }}" maxlength="50" required>@error('username')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="password">Password Awal</label><input id="password" name="password" type="password" minlength="8" required>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="password_confirmation">Konfirmasi Password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required></div>
                </div>
                <div class="form-actions"><button class="button button-primary" type="submit">Buat Akun</button></div>
            </form>
        </section>
    @else
        <section class="data-card" style="margin-bottom: 1.5rem;"><div class="history-detail-grid"><div class="history-detail-item"><span>Username</span><strong class="text-mono">{{ $akunSiswa->username }}</strong></div><div class="history-detail-item"><span>Status Akses</span><strong>Portal siswa aktif</strong></div></div></section>
        <section class="form-card">
            <h3>Reset Password</h3>
            <p class="text-muted">Password baru akan langsung menggantikan password sebelumnya.</p>
            <form method="POST" action="{{ route('master.siswa.akun.password', $siswa) }}">
                @csrf @method('PATCH')
                <div class="form-grid"><div class="form-field"><label for="password">Password Baru</label><input id="password" name="password" type="password" minlength="8" required>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div><div class="form-field"><label for="password_confirmation">Konfirmasi Password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required></div></div>
                <div class="form-actions"><button class="button button-primary" type="submit">Reset Password</button></div>
            </form>
        </section>
    @endif
@endsection
