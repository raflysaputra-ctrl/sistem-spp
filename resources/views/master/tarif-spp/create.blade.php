@extends('layouts.app')

@section('title', 'Tambah Tarif SPP | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Tambah Tarif SPP</h2>
            <p>Isi nominal untuk seluruh tingkat kelas dalam satu kali penyimpanan.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('master.tarif-spp.store') }}" data-confirm data-confirm-title="Tambahkan tarif SPP?" data-confirm-message="Nominal tarif untuk tingkat X, XI, dan XII akan ditambahkan sekaligus." data-confirm-submit="Tambahkan Semua Tarif">
        @csrf

        <div class="form-grid">
            <div class="form-field">
                <label for="id_tahun_ajaran">Tahun Ajaran</label>
                <select id="id_tahun_ajaran" name="id_tahun_ajaran" required>
                    <option value="">Pilih Tahun Ajaran</option>
                    @foreach ($tahunAjaran as $periode)
                        <option value="{{ $periode->id_tahun_ajaran }}" @selected(old('id_tahun_ajaran') == $periode->id_tahun_ajaran)>{{ $periode->tahun_ajaran }}{{ $periode->aktif ? ' (Aktif)' : '' }}</option>
                    @endforeach
                </select>
                @error('id_tahun_ajaran')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tingkat</th>
                        <th>Kelas</th>
                        <th>Nominal per Bulan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $tingkat => $kelas)
                        <tr>
                            <td>Tingkat {{ $tingkat }}</td>
                            <td>Kelas {{ $kelas }}</td>
                            <td>
                                <input id="tarif_{{ $tingkat }}" class="tarif-input" name="tarif[{{ $tingkat }}]" type="number" value="{{ old("tarif.$tingkat") }}" min="1" step="1" required aria-label="Nominal tarif Tingkat {{ $tingkat }}">
                                @error("tarif.$tingkat")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('master.tarif-spp.index') }}">Batal</a>
            <button class="button button-primary" type="submit">Simpan Semua Tarif</button>
        </div>
    </form>
@endsection
