@csrf

<div class="form-grid">
    <div class="form-field">
        <label for="nipd">NIPD</label>
        <input id="nipd" name="nipd" type="text" value="{{ old('nipd', $siswa?->nipd) }}" maxlength="30" required>
        @error('nipd')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-field">
        <label for="nama_siswa">Nama Siswa</label>
        <input id="nama_siswa" name="nama_siswa" type="text" value="{{ old('nama_siswa', $siswa?->nama_siswa) }}" maxlength="100" required>
        @error('nama_siswa')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-field">
        <label for="jenis_kelamin">Jenis Kelamin</label>
        <select id="jenis_kelamin" name="jenis_kelamin" required>
            <option value="">Pilih Jenis Kelamin</option>
            <option value="L" @selected(old('jenis_kelamin', $siswa?->jenis_kelamin) === 'L')>Laki-laki</option>
            <option value="P" @selected(old('jenis_kelamin', $siswa?->jenis_kelamin) === 'P')>Perempuan</option>
        </select>
        @error('jenis_kelamin')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <input id="angkatan" name="angkatan" type="hidden" value="{{ old('angkatan', $siswa?->angkatan) }}">
    @error('angkatan')
        <p class="field-error">{{ $message }}</p>
    @enderror

    <div class="form-field">
        <label for="status_siswa">Status Siswa</label>
        <select id="status_siswa" name="status_siswa" required>
            @foreach (['aktif' => 'Aktif', 'lulus' => 'Lulus', 'pindah' => 'Pindah'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status_siswa', $siswa?->status_siswa ?? 'aktif') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status_siswa')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    @if (! $siswa || $idKelasAktif)
        <div class="form-field">
            <label for="id_kelas">Kelas Tahun Ajaran Aktif</label>
            <select id="id_kelas" name="id_kelas" @required(! $siswa)>
                <option value="">Pilih Kelas</option>
                @foreach ($kelas as $item)
                    @if (! $siswa || $item->tingkat === $tingkatKelasAktif)
                        <option value="{{ $item->id_kelas }}" data-tingkat="{{ $item->tingkat }}" @selected(old('id_kelas', $idKelasAktif) == $item->id_kelas)>{{ $item->nama_kelas }} - {{ $item->jurusan->kode_jurusan }}</option>
                    @endif
                @endforeach
            </select>
            @if ($tahunAjaranAktif)
                <span class="reference-note">Tahun ajaran aktif: {{ $tahunAjaranAktif->tahun_ajaran }}</span>
            @else
                <span class="reference-note">Belum ada tahun ajaran yang aktif.</span>
            @endif
            @if ($siswa)
                <span class="reference-note">Kelas hanya dapat diubah ke kelas lain pada tingkat yang sama.</span>
            @endif
            @error('id_kelas')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>

<div class="form-actions">
    <a class="button button-secondary" href="{{ route('master.siswa.index') }}">Batal</a>
    <button class="button button-primary" type="submit">Simpan</button>
</div>

@if (! $siswa && $tahunAjaranAktif)
    <script>
        const kelasInput = document.getElementById('id_kelas');
        const angkatanInput = document.getElementById('angkatan');
        const tahunMulai = {{ $tahunAjaranAktif->tanggal_mulai->year }};

        kelasInput.addEventListener('change', () => {
            const tingkat = Number(kelasInput.selectedOptions[0]?.dataset.tingkat);

            if (tingkat) {
                angkatanInput.value = String(tahunMulai - (tingkat - 1));
            }
        });

        if (kelasInput.value) kelasInput.dispatchEvent(new Event('change'));
    </script>
@endif
