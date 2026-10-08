@extends('layouts.app')

@section('title', 'Input Pengeluaran | Sistem Informasi Keuangan')
@section('page-title', 'Input Pengeluaran')

@section('content')
    <div class="page-header expense-page-header">
        <div>
            <p class="eyebrow">Operasional sekolah</p>
            <h2>Input Pengeluaran</h2>
            <p>Catat uang keluar dengan informasi yang jelas agar riwayat keuangan mudah ditelusuri.</p>
        </div>
        <a class="button button-secondary" href="{{ route('pengeluaran.riwayat') }}">Lihat Riwayat</a>
    </div>

    <div class="expense-entry-layout">
        <form class="form-card expense-form-card" method="POST" action="{{ route('pengeluaran.store') }}">
            @csrf
            <div class="expense-form-heading">
                <div>
                    <h3>Rincian pengeluaran</h3>
                    <p>Lengkapi seluruh informasi sebelum menyimpan transaksi.</p>
                </div>
            </div>
            <div class="expense-form-body">
                @if ($errors->any())<div class="error-message">{{ $errors->first() }}</div>@endif
                <div class="form-grid">
                    <div class="form-field"><label for="tanggal_pengeluaran">Tanggal Pengeluaran</label><input id="tanggal_pengeluaran" name="tanggal_pengeluaran" type="date" value="{{ old('tanggal_pengeluaran', now()->format('Y-m-d')) }}" required data-expense-date></div>
                    <div class="form-field"><label for="id_kategori_pengeluaran">Kategori</label><select id="id_kategori_pengeluaran" name="id_kategori_pengeluaran" required data-expense-category><option value="">Pilih kategori</option>@foreach ($kategoriPengeluaran as $kategori)<option value="{{ $kategori->id_kategori_pengeluaran }}" @selected(old('id_kategori_pengeluaran') == $kategori->id_kategori_pengeluaran)>{{ $kategori->nama_kategori }}</option>@endforeach</select></div>
                    <div class="form-field full-width"><label for="nominal">Nominal Pengeluaran</label><div class="currency-input-shell"><span aria-hidden="true">Rp</span><input id="nominal" name="nominal" type="text" inputmode="numeric" autocomplete="off" value="{{ old('nominal') }}" placeholder="Contoh: 100.000" required data-currency-input data-expense-nominal></div></div>
                    <div class="form-field full-width"><label for="keterangan">Keterangan</label><textarea id="keterangan" name="keterangan" maxlength="1000" placeholder="Contoh: Pembelian perlengkapan kelas untuk kegiatan praktik." required data-expense-description>{{ old('keterangan') }}</textarea><p class="input-help">Jelaskan kebutuhan pengeluaran agar mudah dikenali pada riwayat.</p></div>
                </div>
                <div class="form-actions"><button class="button button-primary" type="submit">Simpan Pengeluaran</button></div>
            </div>
        </form>

        <aside class="expense-side-panel">
            <section class="expense-summary-card" aria-live="polite">
                <span class="expense-step">Ringkasan entri</span>
                <dl>
                    <div><dt>Tanggal</dt><dd data-expense-summary-date>Belum dipilih</dd></div>
                    <div><dt>Kategori</dt><dd data-expense-summary-category>Belum dipilih</dd></div>
                    <div><dt>Nominal</dt><dd class="text-mono" data-expense-summary-nominal>Rp 0</dd></div>
                </dl>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('.expense-form-card');

            if (! form) {
                return;
            }

            const nominalInput = form.querySelector('[data-currency-input]');
            const dateInput = form.querySelector('[data-expense-date]');
            const categoryInput = form.querySelector('[data-expense-category]');
            const summaryDate = document.querySelector('[data-expense-summary-date]');
            const summaryCategory = document.querySelector('[data-expense-summary-category]');
            const summaryNominal = document.querySelector('[data-expense-summary-nominal]');

            const angkaSaja = (value) => value.replace(/\D/g, '');
            const formatNominal = (value) => {
                const angka = angkaSaja(value);

                return angka ? new Intl.NumberFormat('id-ID').format(Number(angka)) : '';
            };

            const formatTanggal = (value) => {
                if (! value) {
                    return 'Belum dipilih';
                }

                const [tahun, bulan, tanggal] = value.split('-').map(Number);

                return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
                    .format(new Date(tahun, bulan - 1, tanggal));
            };

            const perbaruiRingkasan = () => {
                const angka = angkaSaja(nominalInput.value);
                summaryDate.textContent = formatTanggal(dateInput.value);
                summaryCategory.textContent = categoryInput.value
                    ? categoryInput.selectedOptions[0].textContent
                    : 'Belum dipilih';
                summaryNominal.textContent = `Rp ${angka ? new Intl.NumberFormat('id-ID').format(Number(angka)) : '0'}`;
            };

            nominalInput.value = formatNominal(nominalInput.value);
            nominalInput.addEventListener('input', () => {
                nominalInput.value = formatNominal(nominalInput.value);
                perbaruiRingkasan();
            });
            dateInput.addEventListener('change', perbaruiRingkasan);
            categoryInput.addEventListener('change', perbaruiRingkasan);
            form.addEventListener('submit', () => {
                nominalInput.value = angkaSaja(nominalInput.value);
            });
            perbaruiRingkasan();
        })();
    </script>
@endpush
