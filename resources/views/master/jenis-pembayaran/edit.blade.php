@extends('layouts.app')

@section('title', 'Edit Jenis Pembayaran | Sistem Informasi Keuangan')
@section('page-title', 'Edit Jenis Pembayaran')

@section('content')
    <section class="form-card">
        <form method="POST" action="{{ route('master.jenis-pembayaran.update', $jenisPembayaran) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-field">
                    <label for="kode_jenis">Kode Jenis <span aria-hidden="true">*</span></label>
                    <input id="kode_jenis" name="kode_jenis" type="text" value="{{ old('kode_jenis', $jenisPembayaran->kode_jenis) }}" required maxlength="50">
                    @error('kode_jenis')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-field">
                    <label for="nama_jenis">Nama Jenis Pembayaran <span aria-hidden="true">*</span></label>
                    <input id="nama_jenis" name="nama_jenis" type="text" value="{{ old('nama_jenis', $jenisPembayaran->nama_jenis) }}" required maxlength="100">
                    @error('nama_jenis')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-field full-width">
                    <label>Target Tingkat / Kelas <span aria-hidden="true">*</span></label>
                    <div style="display: flex; gap: 1.5rem; margin-top: .5rem;">
                        <div class="checkbox-field" style="margin-top: 0;">
                            <input id="target_tingkat_1" name="target_tingkat[]" type="checkbox" value="1" @checked(in_array(1, old('target_tingkat', $jenisPembayaran->target_tingkat ?? [])))>
                            <label for="target_tingkat_1" style="margin: 0; font-size: .9rem;">Kelas 1</label>
                        </div>
                        <div class="checkbox-field" style="margin-top: 0;">
                            <input id="target_tingkat_2" name="target_tingkat[]" type="checkbox" value="2" @checked(in_array(2, old('target_tingkat', $jenisPembayaran->target_tingkat ?? [])))>
                            <label for="target_tingkat_2" style="margin: 0; font-size: .9rem;">Kelas 2</label>
                        </div>
                        <div class="checkbox-field" style="margin-top: 0;">
                            <input id="target_tingkat_3" name="target_tingkat[]" type="checkbox" value="3" @checked(in_array(3, old('target_tingkat', $jenisPembayaran->target_tingkat ?? [])))>
                            <label for="target_tingkat_3" style="margin: 0; font-size: .9rem;">Kelas 3</label>
                        </div>
                    </div>
                    @error('target_tingkat')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-field">
                    <label for="aturan_pembayaran">Aturan Pembayaran <span aria-hidden="true">*</span></label>
                    <select id="aturan_pembayaran" name="aturan_pembayaran" required>
                        <option value="sekali_bayar" @selected(old('aturan_pembayaran', $jenisPembayaran->aturan_pembayaran) === 'sekali_bayar')>Wajib Lunas Sekali Bayar</option>
                        <option value="cicilan" @selected(old('aturan_pembayaran', $jenisPembayaran->aturan_pembayaran) === 'cicilan')>Dapat Dicicil</option>
                    </select>
                    @error('aturan_pembayaran')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-field">
                    <label for="tipe_periode">Tipe Periode <span aria-hidden="true">*</span></label>
                    <select id="tipe_periode" name="tipe_periode" required>
                        <option value="semester" @selected(old('tipe_periode', $jenisPembayaran->tipe_periode) === 'semester')>Semester</option>
                        <option value="gelombang" @selected(old('tipe_periode', $jenisPembayaran->tipe_periode) === 'gelombang')>Gelombang</option>
                        <option value="tahunan" @selected(old('tipe_periode', $jenisPembayaran->tipe_periode) === 'tahunan')>Satu Kali per Tahun Ajaran</option>
                    </select>
                    @error('tipe_periode')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-field full-width">
                    <label for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" maxlength="255">{{ old('keterangan', $jenisPembayaran->keterangan) }}</textarea>
                    @error('keterangan')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="checkbox-field">
                    <input id="aktif" name="aktif" type="checkbox" value="1" @checked(old('aktif', $jenisPembayaran->aktif))>
                    <label for="aktif">Jenis Pembayaran Aktif</label>
                </div>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('master.jenis-pembayaran.index') }}">Batal</a>
                <button class="button button-primary" type="submit">Perbarui Jenis Pembayaran</button>
            </div>
        </form>
    </section>
@endsection
