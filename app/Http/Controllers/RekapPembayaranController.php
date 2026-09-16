<?php

namespace App\Http\Controllers;

use App\Models\DetailPembayaran;
use App\Models\Jurusan;
use App\Models\Pembayaran;
use App\Models\TagihanSpp;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapPembayaranController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);

        $totalNominal = (clone $query)->sum('nominal_bayar');
        $jumlahTagihan = (clone $query)->count();
        $jumlahTransaksi = (clone $query)->distinct()->count('id_pembayaran');

        return view('rekap-pembayaran.index', [
            'filters' => $filters,
            'jurusan' => Jurusan::query()->orderBy('nama_jurusan')->get(),
            'tahunTersedia' => TagihanSpp::query()->select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun'),
            'rekapPembayaran' => $this->orderedQuery($query)->paginate(20)
                ->withQueryString(),
            'totalNominal' => $totalNominal,
            'jumlahTagihan' => $jumlahTagihan,
            'jumlahTransaksi' => $jumlahTransaksi,
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $detailPembayaran = $this->orderedQuery($this->filteredQuery($this->validatedFilters($request)))->get();

        return response()->streamDownload(function () use ($detailPembayaran) {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Worksheet ss:Name="Rekap Pembayaran"><Table>';
            echo '<Row>'.$this->excelCell('Rekap Pembayaran SPP').'</Row>';
            echo '<Row>'.$this->excelCell('Bulan dan tahun berdasarkan periode SPP; tanggal transaksi ditampilkan terpisah.').'</Row>';
            echo '<Row></Row>';
            echo '<Row>'
                .$this->excelCell('Tanggal Transaksi')
                .$this->excelCell('No. Kwitansi')
                .$this->excelCell('NIPD')
                .$this->excelCell('Siswa')
                .$this->excelCell('Kelas')
                .$this->excelCell('Periode SPP')
                .$this->excelCell('Nominal')
                .$this->excelCell('Petugas TU')
                .'</Row>';

            foreach ($detailPembayaran as $detail) {
                $tagihan = $detail->tagihanSpp;
                $pembayaran = $detail->pembayaran;

                echo '<Row>'
                    .$this->excelCell($pembayaran->tanggal_bayar->format('d/m/Y H:i'))
                    .$this->excelCell($pembayaran->no_kwitansi)
                    .$this->excelCell($pembayaran->siswa->nipd)
                    .$this->excelCell($pembayaran->siswa->nama_siswa)
                    .$this->excelCell($tagihan->siswaKelas->kelas->nama_kelas)
                    .$this->excelCell($this->periodeSpp($tagihan->bulan, $tagihan->tahun))
                    .$this->excelCell((int) $detail->nominal_bayar, 'Number')
                    .$this->excelCell($pembayaran->user->nama)
                    .'</Row>';
            }

            echo '</Table></Worksheet></Workbook>';
        }, 'rekap-pembayaran-'.now()->format('Ymd-His').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $detailPembayaran = $this->orderedQuery($this->filteredQuery($this->validatedFilters($request)))->get();

        return Pdf::loadView('rekap-pembayaran.pdf', [
            'detailPembayaran' => $detailPembayaran,
            'totalNominal' => $detailPembayaran->sum('nominal_bayar'),
            'jumlahTagihan' => $detailPembayaran->count(),
            'jumlahTransaksi' => $detailPembayaran->pluck('id_pembayaran')->unique()->count(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('rekap-pembayaran-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array<string, int|string>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:1900,9999'],
            'id_jurusan' => ['nullable', 'integer', 'exists:jurusan,id_jurusan'],
            'tingkat' => ['nullable', 'integer', 'in:1,2,3'],
            'rombel' => ['nullable', 'integer', 'min:1', 'max:255'],
            'cari' => ['nullable', 'string', 'max:100'],
        ]);
    }

    /**
     * @param  array<string, int|string>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return DetailPembayaran::query()
            ->with([
                'pembayaran.siswa',
                'pembayaran.user',
                'tagihanSpp.siswaKelas.kelas.jurusan',
            ])
            ->when($filters['bulan'] ?? null, fn (Builder $query, int $bulan) => $query->whereHas(
                'tagihanSpp',
                fn (Builder $query) => $query->where('bulan', $bulan),
            ))
            ->when($filters['tahun'] ?? null, fn (Builder $query, int $tahun) => $query->whereHas(
                'tagihanSpp',
                fn (Builder $query) => $query->where('tahun', $tahun),
            ))
            ->when($filters['cari'] ?? null, function (Builder $query, string $cari) {
                $query->whereHas('pembayaran.siswa', function (Builder $query) use ($cari) {
                    $query->where('nipd', 'like', "%{$cari}%")
                        ->orWhere('nama_siswa', 'like', "%{$cari}%");
                });
            })
            ->when(
                ($filters['id_jurusan'] ?? null) || ($filters['tingkat'] ?? null) || ($filters['rombel'] ?? null),
                function (Builder $query) use ($filters) {
                    $query->whereHas('tagihanSpp.siswaKelas.kelas', function (Builder $query) use ($filters) {
                        $query
                            ->when($filters['id_jurusan'] ?? null, fn (Builder $query, int $idJurusan) => $query->where('id_jurusan', $idJurusan))
                            ->when($filters['tingkat'] ?? null, fn (Builder $query, int $tingkat) => $query->where('tingkat', $tingkat))
                            ->when($filters['rombel'] ?? null, fn (Builder $query, int $rombel) => $query->where('rombel', $rombel));
                    });
                },
            );
    }

    private function orderedQuery(Builder $query): Builder
    {
        return $query->orderByDesc(
            Pembayaran::query()
                ->select('tanggal_bayar')
                ->whereColumn('pembayaran.id_pembayaran', 'detail_pembayaran.id_pembayaran'),
        );
    }

    private function excelCell(string|int $value, string $type = 'String'): string
    {
        $value = (string) $value;

        if ($type === 'String' && preg_match('/^\s*[=+\-@]/', $value)) {
            $value = "'{$value}";
        }

        return '<Cell><Data ss:Type="'.$type.'">'.htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</Data></Cell>';
    }

    private function periodeSpp(int $bulan, int $tahun): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$bulan].' '.$tahun;
    }
}
