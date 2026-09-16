@if (! $tahunAjaran->isPersiapan())
    <div class="form-grid">
        <div class="form-field full-width">
            <label for="tahun_ajaran">Tahun Ajaran</label>
            <input id="tahun_ajaran" type="text" value="{{ $tahunAjaran->tahun_ajaran }}" disabled>
        </div>

        <div class="form-field">
            <label for="tanggal_mulai">Tanggal Mulai</label>
            <input id="tanggal_mulai" type="date" value="{{ $tahunAjaran->tanggal_mulai->format('Y-m-d') }}" disabled>
        </div>

        <div class="form-field">
            <label for="tanggal_selesai">Tanggal Selesai</label>
            <input id="tanggal_selesai" type="date" value="{{ $tahunAjaran->tanggal_selesai->format('Y-m-d') }}" disabled>
        </div>
    </div>

    <p class="reference-note">
        {{ $tahunAjaran->isAktif()
            ? 'Data periode tidak dapat diubah karena tahun ajaran sedang aktif.'
            : 'Data periode tidak dapat diubah karena tahun ajaran sudah ditutup.' }}
    </p>

    <div class="form-actions">
        <a class="button button-secondary" href="{{ route('master.tahun-ajaran.index') }}">Kembali</a>
    </div>
@else
    @csrf

    <div class="form-grid">
        <div class="form-field full-width">
            <label for="tahun_ajaran">Tahun Ajaran</label>
            <input id="tahun_ajaran" type="text" value="{{ $tahunAjaran->tahun_ajaran }}" disabled>
        </div>

        <div class="form-field">
            <label for="tanggal_mulai">Tanggal Mulai</label>
            <input id="tanggal_mulai" type="date" value="{{ $tahunAjaran->tanggal_mulai->format('Y-m-d') }}" disabled>
        </div>

        <div class="form-field">
            <label for="tanggal_selesai">Tanggal Selesai</label>
            <input id="tanggal_selesai" name="tanggal_selesai" type="date" value="{{ old('tanggal_selesai', $tahunAjaran->tanggal_selesai->format('Y-m-d')) }}" required>
            @error('tanggal_selesai')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <p class="reference-note">Tahun ajaran dan tanggal mulai tidak dapat diubah. Tanggal mulai mengikuti satu hari setelah tanggal selesai tahun ajaran sebelumnya.</p>

    <div class="form-actions">
        <a class="button button-secondary" href="{{ route('master.tahun-ajaran.index') }}">Batal</a>
        <button class="button button-primary" type="submit">Simpan</button>
    </div>

@endif
