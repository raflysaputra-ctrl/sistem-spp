@extends('layouts.app')

@section('title', 'Pembayaran Non-SPP | Sistem Informasi Keuangan')
@section('page-title', 'Pembayaran Non-SPP')

@section('content')
    <div class="page-header">
        <div>
            <h2>Pembayaran Non-SPP: {{ $siswa->nama_siswa }}</h2>
            <p>Pilih satu atau beberapa tagihan non-SPP untuk dicatat dalam satu kwitansi. Setiap tagihan tetap divalidasi sesuai aturan pembayarannya.</p>
        </div>
        <a class="button button-secondary" href="{{ route('pembayaran-non-spp.index') }}">Kembali ke Daftar Siswa</a>
    </div>

    @if ($errors->has('form'))
        <div class="error-message">{{ $errors->first('form') }}</div>
    @endif

    <section class="form-card" style="max-width: none; margin-bottom: 1.5rem;">
        <div class="form-grid">
            <div class="form-field">
                <label>NIPD</label>
                <strong class="text-mono">{{ $siswa->nipd }}</strong>
            </div>
        </div>
    </section>

    @if ($errors->has('items'))
        <div class="error-message">{{ $errors->first('items') }}</div>
    @endif

    @php($tagihanBam = $tagihanBelumLunas->filter(fn ($item) => $item['tagihan']->jenisPembayaran->adalahBiayaAwalMasuk()))
    @php($perluPilihGelombangBam = $tagihanBam->count() > 1)

    <form method="POST" action="{{ route('pembayaran-non-spp.store', $siswa) }}" data-confirm data-confirm-title="Simpan satu kwitansi?" data-confirm-message="Seluruh tagihan yang dipilih akan dicatat dalam satu kwitansi." data-confirm-submit="Simpan Kwitansi">
        @csrf
        <section class="data-card">
            <div class="card-header">
                <div>
                    <h3>Pilih Tagihan untuk Satu Kwitansi</h3>
                    <p>PTS/PAS wajib dibayar penuh. Tagihan cicilan mengikuti minimal DP pada pembayaran pertama.</p>
                </div>
            </div>

            @if ($perluPilihGelombangBam)
                <div class="form-field" style="max-width: 24rem; margin: 0 1.25rem 1rem;">
                    <label for="kode_periode_bam">Gelombang Biaya Awal Masuk</label>
                    <select id="kode_periode_bam" name="kode_periode_bam">
                        <option value="">Pilih gelombang sebelum membayar BAM</option>
                        @foreach (['gelombang_1' => 'Gelombang 1', 'gelombang_2' => 'Gelombang 2', 'gelombang_3' => 'Gelombang 3'] as $kode => $label)
                            <option value="{{ $kode }}" @selected(old('kode_periode_bam') === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">Pilihan ini akan dikunci untuk siswa pada tahun ajaran ini.</span>
                    @error('kode_periode_bam')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            @endif

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pilih</th>
                            <th>Jenis / Periode</th>
                            <th>Tahun Ajaran</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Sudah Dibayar</th>
                            <th class="text-right">Sisa</th>
                            <th>Aturan</th>
                            <th style="min-width: 12rem;">Nominal Dibayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagihanBelumLunas as $index => $item)
                            @php($tagihan = $item['tagihan'])
                            @php($dipilih = old("items.{$index}.selected"))
                            @php($nominalLama = old("items.{$index}.nominal_bayar"))
                            <tr class="payment-item" data-cicilan="{{ $tagihan->bisa_cicil ? '1' : '0' }}" data-sisa="{{ $item['sisa_tagihan'] }}" data-bam="{{ $tagihan->jenisPembayaran->adalahBiayaAwalMasuk() ? '1' : '0' }}" data-kode-periode="{{ $tagihan->kode_periode }}">
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][id_tagihan_pembayaran]" value="{{ $tagihan->id_tagihan_pembayaran }}">
                                    <input
                                        id="pilih_{{ $tagihan->id_tagihan_pembayaran }}"
                                        name="items[{{ $index }}][selected]"
                                        type="checkbox"
                                        value="1"
                                        @checked($dipilih)
                                    >
                                </td>
                                <td>
                                    <label for="pilih_{{ $tagihan->id_tagihan_pembayaran }}" style="cursor: pointer;">
                                        <strong>{{ $tagihan->jenisPembayaran->nama_jenis }}</strong><br>
                                        <span class="text-muted">{{ $tagihan->periode_label }}</span>
                                    </label>
                                </td>
                                <td>{{ $tagihan->tahunAjaran->tahun_ajaran }}</td>
                                <td class="text-mono text-right">Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</td>
                                <td class="text-mono text-right">Rp {{ number_format($item['total_dibayar'], 0, ',', '.') }}</td>
                                <td class="text-mono text-right"><strong>Rp {{ number_format($item['sisa_tagihan'], 0, ',', '.') }}</strong></td>
                                <td>
                                    @if ($tagihan->bisa_cicil)
                                        <span class="status-badge status-warning">Cicilan min. DP Rp {{ number_format($tagihan->minimal_dp, 0, ',', '.') }}</span>
                                    @else
                                        <span class="status-badge status-active">Wajib Lunas</span>
                                    @endif
                                </td>
                                <td>
                                    <input
                                        class="payment-nominal"
                                        id="nominal_{{ $tagihan->id_tagihan_pembayaran }}"
                                        name="items[{{ $index }}][nominal_bayar]"
                                        type="number"
                                        min="1"
                                        value="{{ $nominalLama }}"
                                        placeholder="Pilih tagihan dahulu"
                                        aria-label="Nominal pembayaran {{ $tagihan->jenisPembayaran->nama_jenis }}"
                                    >
                                    @error("items.{$index}.nominal_bayar")
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </td>
                            </tr>
                        @empty
                            <tr><td class="empty-state" colspan="8">Tidak ada tagihan non-SPP yang dapat dibayar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer" style="align-items: center;">
                <strong>Total Kwitansi: <span id="total_kwitansi" class="text-mono">Rp 0</span></strong>
                <button class="button button-primary" type="submit">Proses Pembayaran</button>
            </div>
        </section>
    </form>

    <script>
        const formatRupiah = new Intl.NumberFormat('id-ID');

        function sinkronkanBaris(baris) {
            const dipilih = baris.querySelector('input[type="checkbox"]');
            const nominal = baris.querySelector('.payment-nominal');
            const bisaCicil = baris.dataset.cicilan === '1';

            nominal.readOnly = !dipilih.checked || !bisaCicil;

            if (dipilih.checked && !bisaCicil) {
                nominal.value = baris.dataset.sisa;
            }

            if (!dipilih.checked) {
                nominal.value = '';
            }
        }

        function hitungTotal() {
            let total = 0;
            document.querySelectorAll('.payment-item').forEach((baris) => {
                const dipilih = baris.querySelector('input[type="checkbox"]');
                const nominal = baris.querySelector('.payment-nominal');
                if (dipilih.checked) {
                    total += Number(nominal.value || 0);
                }
            });
            document.getElementById('total_kwitansi').textContent = `Rp ${formatRupiah.format(total)}`;
        }

        const pilihanGelombangBam = document.getElementById('kode_periode_bam');

        function sinkronkanGelombangBam() {
            if (!pilihanGelombangBam) {
                return;
            }

            document.querySelectorAll('.payment-item[data-bam="1"]').forEach((baris) => {
                const ditampilkan = pilihanGelombangBam.value !== '' && baris.dataset.kodePeriode === pilihanGelombangBam.value;
                const dipilih = baris.querySelector('input[type="checkbox"]');
                baris.hidden = !ditampilkan;

                if (!ditampilkan) {
                    dipilih.checked = false;
                    sinkronkanBaris(baris);
                }
            });
        }

        document.querySelectorAll('.payment-item').forEach((baris) => {
            const dipilih = baris.querySelector('input[type="checkbox"]');
            const nominal = baris.querySelector('.payment-nominal');
            sinkronkanBaris(baris);
            dipilih.addEventListener('change', () => {
                sinkronkanBaris(baris);
                hitungTotal();
            });
            nominal.addEventListener('input', hitungTotal);
        });
        if (pilihanGelombangBam) {
            pilihanGelombangBam.addEventListener('change', () => {
                sinkronkanGelombangBam();
                hitungTotal();
            });
            sinkronkanGelombangBam();
        }
        hitungTotal();
    </script>
@endsection
