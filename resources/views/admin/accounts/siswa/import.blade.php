@extends('layouts.app')

@section('title', 'Import Akun Siswa | Sistem Informasi Keuangan')
@section('page-title', 'Manajemen Akun Siswa')

@section('content')
    <div class="page-header">
        <div>
            <h2>Import Akun Siswa dari Excel/CSV</h2>
            <p>Unggah file Excel yang berisi data siswa untuk mendaftarkan akun siswa secara massal. NIPD akan digunakan sebagai username.</p>
        </div>
        <span class="action-stack">
            <a class="button button-secondary" href="{{ route('admin.accounts.siswa.index') }}">Kembali ke Daftar Akun</a>
        </span>
    </div>

    <section class="data-card">
        @if ($errors->any())
            <div class="error-message">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('import_errors'))
            <div class="error-message" style="margin-bottom: 1.5rem;">
                <p><strong>Beberapa baris gagal diimpor:</strong></p>
                <ul style="margin: 0.5rem 0 0 1.25rem;">
                    @foreach (session('import_errors') as $importError)
                        <li>{{ $importError }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="info-box" style="margin-bottom: 1.5rem; padding: 1rem; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 0.375rem; color: #1e40af;">
            <h4 style="margin: 0 0 0.5rem 0;">Format File Excel / CSV:</h4>
            <p style="margin: 0 0 0.5rem 0;">Pastikan file memiliki 3 kolom pada baris pertama (header bebas, data mulai baris ke-2):</p>
            <ol style="margin: 0 0 0 1.25rem;">
                <li><strong>Kolom 1</strong>: Nama Siswa (opsional, nama akan diambil dari master data)</li>
                <li><strong>Kolom 2</strong>: NIPD Siswa (harus sudah terdaftar di master data, digunakan sebagai username)</li>
                <li><strong>Kolom 3</strong>: Password (minimal 8 karakter)</li>
            </ol>
            <p style="margin: 0.75rem 0 0 0; font-size: 0.9rem;"><strong>Contoh:</strong></p>
            <table style="margin: 0.5rem 0 0 0; border-collapse: collapse; font-size: 0.85rem;">
                <tr style="background: #fff;">
                    <td style="padding: 0.35rem 0.75rem; border: 1px solid #c7d2fe;">Nama</td>
                    <td style="padding: 0.35rem 0.75rem; border: 1px solid #c7d2fe;">NIPD</td>
                    <td style="padding: 0.35rem 0.75rem; border: 1px solid #c7d2fe;">Password</td>
                </tr>
                <tr style="background: #fff;">
                    <td style="padding: 0.35rem 0.75rem; border: 1px solid #c7d2fe;">Ahmad Fauzi</td>
                    <td style="padding: 0.35rem 0.75rem; border: 1px solid #c7d2fe;">20240001</td>
                    <td style="padding: 0.35rem 0.75rem; border: 1px solid #c7d2fe;">password123</td>
                </tr>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.accounts.siswa.import.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="file">Pilih File (.xlsx, .xls, .csv) *</label>
                <input id="file" name="file" type="file" accept=".xlsx, .xls, .csv" required style="padding: 0.5rem 0; border: 0;">
                @error('file')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-actions">
                <button class="button button-primary" type="submit">Proses Import</button>
                <a class="button button-secondary" href="{{ route('admin.accounts.index') }}">Batal</a>
            </div>
        </form>
    </section>
@endsection

<style>
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
    .form-actions { margin-top: 2rem; display: flex; gap: 1rem; }
    .button { padding: 0.75rem 1rem; border-radius: 0.25rem; border: 0; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
    .button-primary { background: #00288e; color: #fff; }
    .button-primary:hover { background: #1e40af; }
    .button-secondary { background: #e8eaed; color: #191c1e; }
    .button-secondary:hover { background: #d0d5dd; }
    .error-text { margin-top: 0.45rem; color: #ba1a1a; font-size: 0.8rem; }
</style>
