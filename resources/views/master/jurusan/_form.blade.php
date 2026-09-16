@csrf

<div class="form-grid">
    <div class="form-field">
        <label for="kode_jurusan">Kode Jurusan</label>
        <input id="kode_jurusan" name="kode_jurusan" type="text" value="{{ old('kode_jurusan', $jurusan?->kode_jurusan) }}" maxlength="10" required>
        @error('kode_jurusan')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-field">
        <label for="nama_jurusan">Nama Jurusan</label>
        <input id="nama_jurusan" name="nama_jurusan" type="text" value="{{ old('nama_jurusan', $jurusan?->nama_jurusan) }}" maxlength="50" required>
        @error('nama_jurusan')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="form-actions">
    <a class="button button-secondary" href="{{ route('master.jurusan.index') }}">Batal</a>
    <button class="button button-primary" type="submit">Simpan</button>
</div>
