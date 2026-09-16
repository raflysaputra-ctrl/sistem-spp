@extends('layouts.app')

@section('title', 'Import Siswa | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Import Siswa</h2>
            <p>Masukkan data siswa dari file Excel ke tahun ajaran aktif.</p>
        </div>
    </div>

    @if (session('error'))
        <div class="error-message">{{ session('error') }}</div>
    @endif

    @if (session('import_result'))
        @php($result = session('import_result'))
        <section class="data-card">
            <div class="card-header">
                <div>
                    <h3>Hasil Import</h3>
                    <p>{{ $result['berhasil'] }} siswa berhasil diimport, {{ $result['duplikat'] }} baris duplikat dilewati.</p>
                </div>
            </div>

            @if ($result['errors'])
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Baris</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['errors'] as $error)
                                <tr>
                                    <td class="text-mono">{{ $error['baris'] }}</td>
                                    <td>{{ $error['alasan'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    <form class="form-card" method="POST" action="{{ route('master.siswa.import') }}" enctype="multipart/form-data" data-confirm data-confirm-title="Import data siswa?" data-confirm-message="Siswa valid akan ditambahkan ke tahun ajaran aktif beserta tagihan SPP-nya." data-confirm-submit="Import Siswa">
        @csrf

        <div class="form-grid">
            <div class="form-field full-width">
                <label for="file">File Excel</label>
                <div class="file-picker">
                    <input id="file" class="file-picker-input" name="file" type="file" accept=".xlsx,.xls" required>
                    <label class="file-picker-button" for="file">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M12 3v12m0-12 4 4m-4-4-4 4M5 15v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
                        </svg>
                        Pilih File
                    </label>
                    <span id="selected-file-name" class="file-picker-name" aria-live="polite">Belum ada file dipilih</span>
                </div>
                <span class="reference-note">Format yang diterima: .xlsx atau .xls, maksimal 5 MB.</span>
                @error('file')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field full-width">
                <label>Tahun Ajaran Aktif</label>
                <span class="reference-note">{{ $tahunAjaranAktif?->tahun_ajaran ?? 'Belum ada tahun ajaran aktif' }}</span>
            </div>
        </div>

        <section class="data-card">
            <div class="card-header">
                <div>
                    <h3>Format File</h3>
                    <p>Baris header dideteksi otomatis. Data dibaca mulai baris setelah header ditemukan.</p>
                </div>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kolom Excel</th>
                            <th>Digunakan Sebagai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-mono">Rombel</td>
                            <td>Nama kelas, contoh: X DKV 1</td>
                        </tr>
                        <tr>
                            <td class="text-mono">NIPD</td>
                            <td>NIPD siswa</td>
                        </tr>
                        <tr>
                            <td class="text-mono">Nama</td>
                            <td>Nama siswa</td>
                        </tr>
                        <tr>
                            <td class="text-mono">JK</td>
                            <td>L atau P</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('master.siswa.index') }}">Batal</a>
            <button class="button button-primary" type="submit">Import</button>
        </div>
    </form>

    <script>
        const fileInput = document.getElementById('file');
        const selectedFileName = document.getElementById('selected-file-name');

        fileInput.addEventListener('change', () => {
            selectedFileName.textContent = fileInput.files[0]?.name ?? 'Belum ada file dipilih';
        });
    </script>
@endsection
