<?php

namespace App\Http\Controllers;

use App\Models\Penerimaan;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        return view('dashboard.admin');
    }

    public function tu(): View
    {
        $sekarang = now();
        $awalGrafik = $sekarang->copy()->subMonths(5)->startOfMonth();
        $penerimaanAktif = $this->penerimaanAktif($awalGrafik, $sekarang);
        $penerimaanPerBulan = $penerimaanAktif
            ->groupBy(fn ($pembayaran): string => $pembayaran->tanggal_bayar->format('Y-m'))
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

        return view('dashboard.tu', [
            'totalPenerimaanBulanIni' => $this->penerimaanAktif($sekarang->copy()->startOfMonth(), $sekarang->copy()->endOfMonth())->sum('total_bayar'),
            'grafikPenerimaan' => $grafikPenerimaan,
            'dataGrafikPenerimaan' => $dataGrafikPenerimaan,
            'totalPenerimaanEnamBulan' => array_sum(array_column($grafikPenerimaan, 'total')),
            'jumlahSiswaAktif' => Siswa::query()->where('status_siswa', 'aktif')->count(),
            'jumlahTransaksiHariIni' => $this->penerimaanAktif($sekarang->copy()->startOfDay(), $sekarang->copy()->endOfDay())->count(),
            'transaksiTerbaru' => $this->penerimaanAktif()->sortByDesc('tanggal_bayar')->take(5),
        ]);
    }

    public function kepsek(): View
    {
        $sekarang = now();
        $awalGrafik = $sekarang->copy()->subMonths(5)->startOfMonth();
        $penerimaanPerBulan = $this->penerimaanAktif($awalGrafik, $sekarang)
            ->groupBy(fn ($pembayaran): string => $pembayaran->tanggal_bayar->format('Y-m'))
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

        return view('dashboard.kepala-sekolah', [
            'totalPenerimaanBulanIni' => $this->penerimaanAktif($sekarang->copy()->startOfMonth(), $sekarang->copy()->endOfMonth())->sum('total_bayar'),
            'grafikPenerimaan' => $grafikPenerimaan,
            'dataGrafikPenerimaan' => $dataGrafikPenerimaan,
            'totalPenerimaanEnamBulan' => array_sum(array_column($grafikPenerimaan, 'total')),
        ]);
    }

    private function penerimaanAktif($tanggalMulai = null, $tanggalSelesai = null): Collection
    {
        $batasiTanggal = function ($query) use ($tanggalMulai, $tanggalSelesai) {
            return $query->when(
                $tanggalMulai && $tanggalSelesai,
                fn ($query) => $query->whereBetween('tanggal_bayar', [$tanggalMulai, $tanggalSelesai]),
            );
        };

        return $batasiTanggal(Penerimaan::query()->where('status', 'aktif'))
            ->with(['siswa', 'user', 'pembayaranSpp.detailPembayaran.tagihanSpp.siswaKelas.kelas'])
            ->get();
    }
}
