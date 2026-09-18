@extends('layouts.app')

@section('title', 'Transaksi Pembayaran | Sistem Pembayaran SPP')
@section('page-title', 'Transaksi Pembayaran')

@section('content')
    @php
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $idTagihanDipilih = collect(old('id_tagihan', []))->map(fn ($id) => (int) $id)->all();
    @endphp

    <div class="page-header">
        <div>
            <h2>Tagihan {{ $siswa->nama_siswa }}</h2>
            <p>Periode ditampilkan secara kronologis dari Juli sampai Juni. Pilih tagihan belum bayar secara berurutan; periode berikutnya dapat dipilih setelah semua periode sebelumnya dipilih.</p>
        </div>
        <a class="button button-secondary" href="{{ route('pembayaran.index') }}">Cari Siswa Lain</a>
    </div>

    @if (session('status'))
        <div class="flash-message">{{ session('status') }}</div>
    @endif

    @if ($errors->has('id_tagihan'))
        <div class="error-message">{{ $errors->first('id_tagihan') }}</div>
    @endif

    <section class="form-card" style="max-width: none; margin-bottom: 1.5rem;">
        <div class="form-grid">
            <div class="form-field">
                <label>NIPD</label>
                <strong class="text-mono">{{ $siswa->nipd }}</strong>
            </div>
            <div class="form-field">
                <label>Kelas Aktif</label>
                <span>{{ $kelasAktif?->kelas?->nama_kelas ?? '-' }}</span>
            </div>
        </div>
    </section>

    <form class="payment-layout" method="POST" action="{{ route('pembayaran.store', $siswa) }}" data-confirm data-confirm-title="Proses pembayaran?" data-confirm-message="Tagihan yang dipilih akan dicatat sebagai lunas. Jika perlu, transaksi dapat dibatalkan dari detail riwayat dengan alasan dan password petugas." data-confirm-submit="Proses Pembayaran">
        @csrf

        <section class="data-card">
            <div class="card-header">
                <div>
                    <h3>Rincian Tagihan SPP</h3>
                    <p>Tagihan lunas ditampilkan sebagai referensi dan tidak dapat dipilih. Tagihan belum lunas harus dipilih tanpa melewati periode sebelumnya.</p>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pilih</th>
                            <th>Periode SPP</th>
                            <th>Kelas</th>
                            <th class="text-right">Nominal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagihanSpp as $tagihan)
                            <tr>
                                <td>
                                    <input
                                        data-tagihan-input
                                        data-nominal="{{ $tagihan->nominal }}"
                                        data-status="{{ $tagihan->status }}"
                                        name="id_tagihan[]"
                                        type="checkbox"
                                        value="{{ $tagihan->id_tagihan }}"
                                        @checked(in_array($tagihan->id_tagihan, $idTagihanDipilih, true))
                                        @disabled($tagihan->status === 'lunas')
                                    >
                                </td>
                                <td>{{ $namaBulan[$tagihan->bulan] }} {{ $tagihan->tahun }}</td>
                                <td>{{ $tagihan->siswaKelas->kelas->nama_kelas }}</td>
                                <td class="text-mono text-right">Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}</td>
                                <td>
                                    @if ($tagihan->adalahTunggakan())
                                        <span class="status-badge status-tunggakan">Tunggakan</span>
                                    @endif
                                    <span class="status-badge {{ $tagihan->status === 'lunas' ? 'status-lunas' : 'status-belum-bayar' }}">
                                        {{ $tagihan->status === 'lunas' ? 'Lunas' : 'Belum Bayar' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="empty-state" colspan="5">Belum ada tagihan SPP untuk siswa ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="payment-summary">
            <h3>Ringkasan Pembayaran</h3>
            <div class="payment-summary-row">
                <span>Tagihan dipilih</span>
                <strong id="selected-count">0 bulan</strong>
            </div>
            <div class="payment-total">
                <span>Total bayar</span>
                <strong class="text-mono" id="payment-total">Rp 0</strong>
            </div>
            <button class="button button-primary" type="submit">Proses Pembayaran</button>
            <p class="reference-note">Total dihitung kembali dari data tagihan oleh sistem saat pembayaran diproses.</p>
        </aside>
    </form>

    <script>
        const tagihanInputs = [...document.querySelectorAll('[data-tagihan-input]')];
        const tagihanBelumBayarInputs = tagihanInputs.filter((input) => input.dataset.status === 'belum_bayar');
        const selectedCount = document.getElementById('selected-count');
        const paymentTotal = document.getElementById('payment-total');

        const updatePaymentSummary = () => {
            const selectedInputs = tagihanInputs.filter((input) => input.checked);
            const total = selectedInputs.reduce((sum, input) => sum + Number(input.dataset.nominal), 0);

            selectedCount.textContent = `${selectedInputs.length} bulan`;
            paymentTotal.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(total)}`;
        };

        const updatePaymentAvailability = () => {
            let semuaPeriodeSebelumnyaDipilih = true;

            tagihanBelumBayarInputs.forEach((input) => {
                input.disabled = !semuaPeriodeSebelumnyaDipilih;

                if (input.disabled) {
                    input.checked = false;
                }

                semuaPeriodeSebelumnyaDipilih = input.checked;
            });
        };

        const updatePaymentState = () => {
            updatePaymentAvailability();
            updatePaymentSummary();
        };

        tagihanBelumBayarInputs.forEach((input) => input.addEventListener('change', updatePaymentState));
        updatePaymentState();
    </script>
@endsection
