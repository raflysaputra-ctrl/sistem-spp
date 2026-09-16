@csrf

<div class="form-grid">
    <div class="form-field">
        <label for="id_jurusan">Jurusan</label>
        <select id="id_jurusan" name="id_jurusan" required>
            <option value="">Pilih Jurusan</option>
            @foreach ($jurusan as $item)
                <option value="{{ $item->id_jurusan }}" data-kode="{{ $item->kode_jurusan }}" @selected(old('id_jurusan', $kelas?->id_jurusan) == $item->id_jurusan)>{{ $item->kode_jurusan }} - {{ $item->nama_jurusan }}</option>
            @endforeach
        </select>
        @error('id_jurusan')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-field">
        <label for="tingkat">Tingkat</label>
        <select id="tingkat" name="tingkat" required>
            <option value="">Pilih Tingkat</option>
            @foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $value => $label)
                <option value="{{ $value }}" @selected(old('tingkat', $kelas?->tingkat) == $value)>Tingkat {{ $value }} / Kelas {{ $label }}</option>
            @endforeach
        </select>
        @error('tingkat')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-field">
        <label for="rombel">Rombel</label>
        <input id="rombel" name="rombel" type="number" value="{{ old('rombel', $kelas?->rombel) }}" min="1" max="255" step="1" required>
        <span class="reference-note">Masukkan nomor rombel, misalnya 5.</span>
        @error('rombel')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

</div>

<div class="form-actions">
    <a class="button button-secondary" href="{{ route('master.kelas.index') }}">Batal</a>
    <button class="button button-primary" type="submit">Simpan</button>
</div>
