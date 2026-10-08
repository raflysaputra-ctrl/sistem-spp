@extends('layouts.app')

@section('title', 'Atur & Generate Tagihan Non-SPP | Sistem Informasi Keuangan')
@section('page-title', 'Atur & Generate Tagihan Non-SPP')

@section('content')
    <section class="form-card" style="max-width: none;">
        <div style="margin-bottom: 1.5rem; padding: 1rem; border-radius: .25rem; background: #f2f4f6; color: #444653; font-size: .88rem; line-height: 1.6;">
            <strong>Cara Kerja:</strong> Pilih jenis pembayaran lalu lengkapi semua periode yang berlaku dalam satu proses. PTS/PAS memuat Semester 1 dan 2, sedangkan Biaya Awal Masuk memuat Gelombang 1, 2, dan 3.
        </div>

        <form method="POST" action="{{ route('master.tagihan-non-spp.store') }}" id="form-tagihan">
            @csrf

            <div class="form-grid">
                <div class="form-field">
                    <label for="id_tahun_ajaran">Tahun Ajaran <span aria-hidden="true">*</span></label>
                    <select id="id_tahun_ajaran" name="id_tahun_ajaran" required>
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        @foreach ($tahunAjaran as $ta)
                            <option value="{{ $ta->id_tahun_ajaran }}" @selected(old('id_tahun_ajaran') == $ta->id_tahun_ajaran)>
                                {{ $ta->tahun_ajaran }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_tahun_ajaran')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="id_jenis_pembayaran">Jenis Pembayaran <span aria-hidden="true">*</span></label>
                    <select id="id_jenis_pembayaran" name="id_jenis_pembayaran" required>
                        <option value="">-- Pilih Jenis Pembayaran --</option>
                        @foreach ($jenisPembayaran as $jenis)
                            <option
                                value="{{ $jenis->id_jenis_pembayaran }}"
                                data-cicilan="{{ $jenis->bisaDicicil() ? '1' : '0' }}"
                                data-tipe-periode="{{ $jenis->tipe_periode }}"
                                @selected(old('id_jenis_pembayaran') == $jenis->id_jenis_pembayaran)
                            >
                                {{ $jenis->nama_jenis }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_jenis_pembayaran')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <section id="periode_container" class="data-card" style="display: none; margin-top: 1.5rem;">
                <div class="card-header">
                    <div>
                        <h3>Tagihan per Periode</h3>
                        <p id="aturan_info" aria-live="polite"></p>
                    </div>
                </div>
                <div id="periode_fields" class="form-grid"></div>
            </section>

            @error('tagihan')
                <p class="field-error" style="margin-top: 1rem;">{{ $message }}</p>
            @enderror
            @error('form')
                <p class="field-error" style="margin-top: 1rem;">{{ $message }}</p>
            @enderror

            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('master.tagihan-non-spp.index') }}">Batal</a>
                <button class="button button-primary" type="submit">Generate Tagihan Massal</button>
            </div>
        </form>
    </section>

    <script>
        const periodePilihan = {
            semester: [['semester_1', 'Semester 1'], ['semester_2', 'Semester 2']],
            gelombang: [['gelombang_1', 'Gelombang 1'], ['gelombang_2', 'Gelombang 2'], ['gelombang_3', 'Gelombang 3']],
            tahunan: [['tahunan', 'Satu kali per tahun ajaran']],
        };
        const tagihanLama = @json(old('tagihan', []));

        function nilaiLama(kodePeriode, namaField) {
            return tagihanLama.find((item) => item.kode_periode === kodePeriode)?.[namaField] ?? '';
        }

        function sinkronkanFormTagihan() {
            const jenis = document.getElementById('id_jenis_pembayaran');
            const pilihan = jenis.options[jenis.selectedIndex];
            const tipe = pilihan?.dataset.tipePeriode;
            const bisaCicil = pilihan?.dataset.cicilan === '1';
            const periode = periodePilihan[tipe] || [];
            const container = document.getElementById('periode_container');
            const fields = document.getElementById('periode_fields');
            const info = document.getElementById('aturan_info');

            container.style.display = periode.length ? '' : 'none';
            fields.innerHTML = periode.map(([kode, label], index) => `
                <div class="form-field" style="padding: 1rem; border: 1px solid #c4c5d5; border-radius: .25rem;">
                    <input name="tagihan[${index}][kode_periode]" type="hidden" value="${kode}">
                    <strong>${label}</strong>
                    <label for="total_tagihan_${kode}" style="margin-top: .75rem;">Total Tagihan <span aria-hidden="true">*</span></label>
                    <input id="total_tagihan_${kode}" name="tagihan[${index}][total_tagihan]" type="number" min="1" required value="${nilaiLama(kode, 'total_tagihan')}" placeholder="Contoh: 1300000">
                    ${bisaCicil ? `
                        <label for="minimal_dp_${kode}" style="margin-top: .75rem;">Minimal DP <span aria-hidden="true">*</span></label>
                        <input id="minimal_dp_${kode}" name="tagihan[${index}][minimal_dp]" type="number" min="1" required value="${nilaiLama(kode, 'minimal_dp')}" placeholder="Contoh: 800000">
                    ` : '<input name="tagihan[' + index + '][minimal_dp]" type="hidden" value="0">'}
                </div>
            `).join('');

            info.textContent = jenis.value
                ? (bisaCicil
                    ? 'Setiap periode dapat memiliki total tagihan dan minimal DP berbeda. Pembayaran pertama wajib memenuhi DP periode masing-masing.'
                    : 'Semua periode wajib dibayar lunas dalam satu transaksi sesuai nilai tagihan masing-masing.')
                : '';
        }

        document.getElementById('id_jenis_pembayaran').addEventListener('change', sinkronkanFormTagihan);
        sinkronkanFormTagihan();
    </script>
@endsection
