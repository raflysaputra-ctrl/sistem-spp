<section class="data-card finance-chart-card" aria-labelledby="finance-chart-title">
    <div class="card-header">
        <div>
            <h3 id="finance-chart-title">Penerimaan 6 Bulan Terakhir</h3>
            <p>Berdasarkan tanggal transaksi pembayaran.</p>
        </div>
        <strong class="finance-chart-total text-mono">Rp {{ number_format($totalPenerimaanEnamBulan, 0, ',', '.') }}</strong>
    </div>

    @if ($totalPenerimaanEnamBulan === 0)
        <p class="finance-chart-empty">Belum ada penerimaan yang tercatat dalam enam bulan terakhir.</p>
    @endif

    <div class="finance-line-chart">
        <canvas id="finance-chart" data-finance-chart aria-label="Grafik garis penerimaan enam bulan terakhir berdasarkan tanggal transaksi" role="img"></canvas>
    </div>
    <script id="finance-chart-data" type="application/json">@json($dataGrafikPenerimaan)</script>

    <div class="finance-line-details" aria-label="Rincian penerimaan per bulan">
        @foreach ($grafikPenerimaan as $penerimaan)
            <span><strong>{{ $penerimaan['label'] }}</strong> Rp {{ number_format($penerimaan['total'], 0, ',', '.') }}</span>
        @endforeach
    </div>
</section>
