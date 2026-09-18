<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Siswa;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $sekarang = now();
        $awalGrafik = $sekarang->copy()->subMonths(5)->startOfMonth();
        $penerimaanPerBulan = Pembayaran::query()
            ->where('status', 'aktif')
            ->whereBetween('tanggal_bayar', [$awalGrafik, $sekarang])
            ->get(['tanggal_bayar', 'total_bayar'])
            ->groupBy(fn (Pembayaran $pembayaran): string => $pembayaran->tanggal_bayar->format('Y-m'))
            ->map(fn ($pembayaran): int => (int) $pembayaran->sum('total_bayar'));

        $namaBulanSingkat = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $grafikPenerimaan = [];

        for ($urutanBulan = 0; $urutanBulan < 6; $urutanBulan++) {
            $bulan = $awalGrafik->copy()->addMonths($urutanBulan);
            $total = $penerimaanPerBulan->get($bulan->format('Y-m'), 0);

            $grafikPenerimaan[] = [
                'label' => $namaBulanSingkat[$bulan->month].' '.$bulan->year,
                'total' => $total,
            ];
        }

        $dataGrafikPenerimaan = [
            'labels' => array_column($grafikPenerimaan, 'label'),
            'values' => array_column($grafikPenerimaan, 'total'),
        ];

        return view('home', [
            'jumlahSiswaAktif' => Siswa::query()->where('status_siswa', 'aktif')->count(),
            'jumlahTransaksiHariIni' => Pembayaran::query()
                ->where('status', 'aktif')
                ->whereDate('tanggal_bayar', $sekarang->toDateString())
                ->count(),
            'totalPenerimaanBulanIni' => Pembayaran::query()
                ->where('status', 'aktif')
                ->whereBetween('tanggal_bayar', [$sekarang->copy()->startOfMonth(), $sekarang->copy()->endOfMonth()])
                ->sum('total_bayar'),
            'grafikPenerimaan' => $grafikPenerimaan,
            'dataGrafikPenerimaan' => $dataGrafikPenerimaan,
            'totalPenerimaanEnamBulan' => array_sum(array_column($grafikPenerimaan, 'total')),
            'transaksiTerbaru' => Pembayaran::query()
                ->where('status', 'aktif')
                ->with(['siswa', 'user', 'detailPembayaran.tagihanSpp.siswaKelas.kelas'])
                ->orderByDesc('tanggal_bayar')
                ->limit(5)
                ->get(),
        ]);
    }
}
