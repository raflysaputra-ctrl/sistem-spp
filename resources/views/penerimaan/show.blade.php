@extends('layouts.app')

@section('title', 'Input Pembayaran | Sistem Informasi Keuangan')
@section('page-title', 'Input Pembayaran')

@section('content')
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $tagihanBam = $tagihanNonSpp->filter(fn ($item) => $item['tagihan']->jenisPembayaran->adalahBiayaAwalMasuk());
        $perluPilihGelombangBam = $tagihanBam->count() > 1;
    @endphp

    <div class="page-header">
        <div>
            <h2>Pembayaran: {{ $siswa->nama_siswa }}</h2>
            <p>Tagihan SPP tetap mengikuti urutan periode. Tagihan dari jenis lain dapat ditambahkan ke kwitansi yang sama.</p>
        </div>
        <a class="button button-secondary" href="{{ route('penerimaan.index') }}">Cari Siswa Lain</a>
    </div>

    @if ($errors->any())
        <div class="error-message">{{ $errors->first() }}</div>
    @endif

    <section class="form-card" style="max-width: none; margin-bottom: 1rem;">
        <div class="form-grid">
            <div class="form-field"><label>NIPD</label><strong class="text-mono">{{ $siswa->nipd }}</strong></div>
            <div class="form-field"><label>Kelas Aktif</label><span>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</span></div>
        </div>
    </section>

    <form id="form-pembayaran" method="POST" action="{{ route('penerimaan.store', $siswa) }}" data-confirm data-confirm-title="Simpan satu kwitansi?" data-confirm-message="Semua tagihan terpilih akan dicatat dalam satu kwitansi pembayaran." data-confirm-submit="Simpan Kwitansi">
        @csrf
        <section class="data-card">
            <div class="filter-bar">
                <div class="filter-field">
                    <label for="filter_jenis">Jenis Pembayaran</label>
                    <select id="filter_jenis">
                        <option value="semua">Semua Jenis</option>
                        <option value="spp">SPP</option>
                        @foreach ($jenisPembayaran as $jenis)
                            <option value="jenis_{{ $jenis->id_jenis_pembayaran }}">{{ $jenis->nama_jenis }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($perluPilihGelombangBam)
                    <div class="filter-field">
                        <label for="kode_periode_bam">Gelombang Biaya Awal Masuk</label>
                        <select id="kode_periode_bam" name="kode_periode_bam">
                            <option value="">Pilih Gelombang</option>
                            @foreach (['gelombang_1' => 'Gelombang 1', 'gelombang_2' => 'Gelombang 2', 'gelombang_3' => 'Gelombang 3'] as $kode => $label)
                                <option value="{{ $kode }}" @selected(old('kode_periode_bam') === $kode)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <span class="reference-note">Filter tidak menghapus tagihan yang sudah dipilih.</span>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr><th>Pilih</th><th>Jenis / Periode</th><th>Kelas / Aturan</th><th class="text-right">Sisa Tagihan</th><th>Status</th><th style="min-width: 12rem;">Nominal Dibayar</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($tagihanSpp as $tagihan)
                            <tr class="receipt-item" data-jenis="spp" data-spp="1" data-status="{{ $tagihan->status }}" data-sisa="{{ $tagihan->nominal }}" data-cicilan="0">
                                <td><input class="item-checkbox" name="id_tagihan_spp[]" type="checkbox" value="{{ $tagihan->id_tagihan }}" @checked(in_array($tagihan->id_tagihan, array_map('intval', old('id_tagihan_spp', [])), true)) @disabled($tagihan->status === 'lunas')></td>
                                <td><strong>SPP</strong><br><span class="text-muted">{{ $namaBulan[$tagihan->bulan] }} {{ $tagihan->tahun }}</span></td>
                                <td>{{ $tagihan->siswaKelas->kelas->nama_kelas }}</td>
                                <td class="text-mono text-right">Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}</td>
                                <td>
                                    @if ($tagihan->adalahTunggakan())
                                        <span class="status-badge status-tunggakan">Tunggakan</span>
                                    @endif
                                    <span class="status-badge {{ $tagihan->status === 'lunas' ? 'status-lunas' : 'status-belum-bayar' }}">{{ $tagihan->status === 'lunas' ? 'Lunas' : 'Belum Bayar' }}</span>
                                </td>
                                <td><input class="payment-nominal" type="text" inputmode="numeric" value="{{ number_format($tagihan->nominal, 0, ',', '.') }}" readonly tabindex="-1" aria-label="Nominal SPP" @disabled($tagihan->status === 'lunas')></td>
                            </tr>
                        @endforeach

                        @foreach ($tagihanNonSpp as $index => $item)
                            @php($tagihan = $item['tagihan'])
                            <tr class="receipt-item" data-jenis="jenis_{{ $tagihan->id_jenis_pembayaran }}" data-sisa="{{ $item['sisa_tagihan'] }}" data-cicilan="{{ $tagihan->bisa_cicil ? '1' : '0' }}" data-bam="{{ $tagihan->jenisPembayaran->adalahBiayaAwalMasuk() ? '1' : '0' }}" data-kode-periode="{{ $tagihan->kode_periode }}">
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][id_tagihan_pembayaran]" value="{{ $tagihan->id_tagihan_pembayaran }}">
                                    <input class="item-checkbox" name="items[{{ $index }}][selected]" type="checkbox" value="1" @checked(old("items.{$index}.selected"))>
                                </td>
                                <td><strong>{{ $tagihan->jenisPembayaran->nama_jenis }}</strong><br><span class="text-muted">{{ $tagihan->periode_label }}</span></td>
                                <td>
                                    @if ($tagihan->bisa_cicil)
                                        <span class="status-badge status-warning">Cicilan, min. DP Rp {{ number_format($tagihan->minimal_dp, 0, ',', '.') }}</span>
                                    @else
                                        <span class="status-badge status-active">Wajib Lunas</span>
                                    @endif
                                </td>
                                <td class="text-mono text-right">Rp {{ number_format($item['sisa_tagihan'], 0, ',', '.') }}</td>
                                <td><span class="status-badge {{ $tagihan->status === 'sebagian' ? 'status-warning' : 'status-belum-bayar' }}">{{ $tagihan->status === 'sebagian' ? 'Bayar Sebagian' : 'Belum Bayar' }}</span></td>
                                <td><input class="payment-nominal" name="items[{{ $index }}][nominal_bayar]" type="text" inputmode="numeric" autocomplete="off" value="{{ old("items.{$index}.nominal_bayar") }}" placeholder="Pilih tagihan dahulu"></td>
                            </tr>
                        @endforeach

                        @if ($tagihanSpp->isEmpty() && $tagihanNonSpp->isEmpty())
                            <tr><td class="empty-state" colspan="6">Tidak ada tagihan aktif yang dapat dibayar.</td></tr>
                        @endif
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
        const angkaSaja = (value) => String(value ?? '').replace(/\D/g, '');
        const formatNominalInput = (value) => {
            const angka = angkaSaja(value);
            return angka ? formatRupiah.format(Number(angka)) : '';
        };
        const filterJenis = document.getElementById('filter_jenis');
        const pilihanBam = document.getElementById('kode_periode_bam');
        const barisTagihan = [...document.querySelectorAll('.receipt-item')];
        const inputSppBelumBayar = barisTagihan
            .filter((baris) => baris.dataset.spp === '1' && baris.dataset.status === 'belum_bayar')
            .map((baris) => baris.querySelector('.item-checkbox'));

        function sinkronkanInput(baris) {
            const checkbox = baris.querySelector('.item-checkbox');
            const nominal = baris.querySelector('.payment-nominal');
            nominal.readOnly = !checkbox.checked || baris.dataset.cicilan !== '1';
            if (checkbox.checked && baris.dataset.cicilan !== '1') nominal.value = formatNominalInput(baris.dataset.sisa);
            if (!checkbox.checked && baris.dataset.cicilan === '1') nominal.value = '';
        }

        function sinkronkanTampilan() {
            barisTagihan.forEach((baris) => {
                const sesuaiJenis = filterJenis.value === 'semua' || baris.dataset.jenis === filterJenis.value;
                const sesuaiBam = baris.dataset.bam !== '1' || !pilihanBam || (pilihanBam.value !== '' && baris.dataset.kodePeriode === pilihanBam.value);
                baris.hidden = !sesuaiJenis || !sesuaiBam;
            });
        }

        function sinkronkanUrutanSpp() {
            let seluruhPeriodeSebelumnyaDipilih = true;

            inputSppBelumBayar.forEach((input) => {
                input.disabled = !seluruhPeriodeSebelumnyaDipilih;
                if (input.disabled) input.checked = false;
                seluruhPeriodeSebelumnyaDipilih = input.checked;
            });
        }

        function hitungTotal() {
            const total = barisTagihan.reduce((jumlah, baris) => {
                return jumlah + (baris.querySelector('.item-checkbox').checked ? Number(angkaSaja(baris.querySelector('.payment-nominal').value) || 0) : 0);
            }, 0);
            document.getElementById('total_kwitansi').textContent = `Rp ${formatRupiah.format(total)}`;
        }

        barisTagihan.forEach((baris) => {
            const checkbox = baris.querySelector('.item-checkbox');
            const nominal = baris.querySelector('.payment-nominal');
            sinkronkanInput(baris);
            if (!nominal.readOnly && nominal.value !== '') nominal.value = formatNominalInput(nominal.value);
            checkbox.addEventListener('change', () => {
                sinkronkanUrutanSpp();
                sinkronkanInput(baris);
                hitungTotal();
            });
            nominal.addEventListener('input', () => {
                if (!nominal.readOnly) nominal.value = formatNominalInput(nominal.value);
                hitungTotal();
            });
        });
        filterJenis.addEventListener('change', sinkronkanTampilan);
        pilihanBam?.addEventListener('change', () => {
            barisTagihan.filter((baris) => baris.dataset.bam === '1' && baris.dataset.kodePeriode !== pilihanBam.value).forEach((baris) => {
                baris.querySelector('.item-checkbox').checked = false;
                sinkronkanInput(baris);
            });
            sinkronkanTampilan();
            hitungTotal();
        });
        document.getElementById('form-pembayaran').addEventListener('submit', (event) => {
            if (event.target.dataset.confirmed !== 'true') return;
            document.querySelectorAll('input[name*="[nominal_bayar]"]').forEach((input) => {
                input.value = angkaSaja(input.value);
            });
        });

        sinkronkanUrutanSpp();
        sinkronkanTampilan();
        hitungTotal();
    </script>
@endsection
