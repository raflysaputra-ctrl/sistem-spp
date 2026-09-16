@csrf

<div class="form-grid">
    <div class="form-field">
        <label for="id_tahun_ajaran">Tahun Ajaran</label>
        <select id="id_tahun_ajaran" name="id_tahun_ajaran" required>
            <option value="">Pilih Tahun Ajaran</option>
            @foreach ($tahunAjaran as $periode)
                <option value="{{ $periode->id_tahun_ajaran }}" @selected(old('id_tahun_ajaran', $tarifSpp?->id_tahun_ajaran) == $periode->id_tahun_ajaran)>{{ $periode->tahun_ajaran }}{{ $periode->aktif ? ' (Aktif)' : '' }}</option>
            @endforeach
        </select>
        @error('id_tahun_ajaran')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-field">
        <label for="tingkat">Tingkat</label>
        <select id="tingkat" name="tingkat" required>
            <option value="">Pilih Tingkat</option>
            @foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $value => $label)
                <option value="{{ $value }}" @selected(old('tingkat', $tarifSpp?->tingkat) == $value)>Tingkat {{ $value }} / Kelas {{ $label }}</option>
            @endforeach
        </select>
        @error('tingkat')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-field full-width">
        <label for="nominal">Nominal per Bulan</label>
        <input id="nominal" name="nominal" type="number" value="{{ old('nominal', $tarifSpp?->nominal) }}" min="1" step="1" required>
        @error('nominal')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="form-actions">
    <a class="button button-secondary" href="{{ route('master.tarif-spp.index') }}">Batal</a>
    <button class="button button-primary" type="submit">Simpan</button>
</div>
