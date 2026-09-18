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

        $ringkasan = $this->ringkasan($query);

        return view('rekap-pembayaran.index', [
            'filters' => $filters,
            'jurusan' => Jurusan::query()->orderBy('nama_jurusan')->get(),
            'tahunTersedia' => TagihanSpp::query()->select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun'),
            'rekapPembayaran' => $this->orderedQuery($query)->paginate(20)
                ->withQueryString(),
            'ringkasan' => $ringkasan,
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $detailPembayaran = $this->orderedQuery($this->filteredQuery($filters))->get();

        return response()->streamDownload(function () use ($detailPembayaran) {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Styles>'
                .'<Style ss:ID="border"><Borders>'
                .'<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>'
                .'<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>'
                .'<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>'
                .'<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>'
                .'</Borders></Style>'
                .'<Style ss:ID="header" ss:Parent="border"><Font ss:Bold="1"/></Style>'
                .'</Styles>';
            echo '<Worksheet ss:Name="Rekap Pembayaran"><Table>';
            echo '<Row>'.$this->excelCell('Rekap Pembayaran SPP').'</Row>';
            echo '<Row>'.$this->excelCell('Bulan dan tahun berdasarkan periode SPP; tanggal transaksi ditampilkan terpisah.').'</Row>';
            echo '<Row></Row>';
            echo '<Row>'
                .$this->excelCell('Tanggal Transaksi', 'String', 'header')
                .$this->excelCell('No. Kwitansi', 'String', 'header')
                .$this->excelCell('NIPD', 'String', 'header')
                .$this->excelCell('Siswa', 'String', 'header')
                .$this->excelCell('Kelas', 'String', 'header')
                .$this->excelCell('Periode SPP', 'String', 'header')
                .$this->excelCell('Nominal', 'String', 'header')
                .$this->excelCell('Petugas TU', 'String', 'header')
                .$this->excelCell('Status Transaksi', 'String', 'header')
                .'</Row>';

            foreach ($detailPembayaran as $detail) {
                $tagihan = $detail->tagihanSpp;
                $pembayaran = $detail->pembayaran;

                echo '<Row>'
                    .$this->excelCell($pembayaran->tanggal_bayar->format('d/m/Y H:i'), 'String', 'border')
                    .$this->excelCell($pembayaran->no_kwitansi, 'String', 'border')
                    .$this->excelCell($pembayaran->siswa->nipd, 'String', 'border')
                    .$this->excelCell($pembayaran->siswa->nama_siswa, 'String', 'border')
                    .$this->excelCell($tagihan->siswaKelas->kelas->nama_kelas, 'String', 'border')
                    .$this->excelCell($this->periodeSpp($tagihan->bulan, $tagihan->tahun), 'String', 'border')
                    .$this->excelCell((int) $detail->nominal_bayar, 'Number', 'border')
                    .$this->excelCell($pembayaran->user->nama, 'String', 'border')
                    .$this->excelCell($pembayaran->status === 'aktif' ? 'Aktif' : 'Dibatalkan', 'String', 'border')
                    .'</Row>';
            }

            echo '</Table></Worksheet></Workbook>';
        }, 'rekap-pembayaran-'.now()->format('Ymd-His').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);
        $detailPembayaran = $this->orderedQuery($query)->get();

        return Pdf::loadView('rekap-pembayaran.pdf', [
            'detailPembayaran' => $detailPembayaran,
            'ringkasan' => $this->ringkasan($query),
        ])
            ->setPaper('a4', 'landscape')
            ->download('rekap-pembayaran-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array<string, int|string>
     */
    private function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:1900,9999'],
            'id_jurusan' => ['nullable', 'integer', 'exists:jurusan,id_jurusan'],
            'tingkat' => ['nullable', 'integer', 'in:1,2,3'],
            'rombel' => ['nullable', 'integer', 'min:1', 'max:255'],
            'cari' => ['nullable', 'string', 'max:100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['nullable', 'in:aktif,dibatalkan,semua'],
        ]);

        foreach (['bulan', 'tahun', 'id_jurusan', 'tingkat', 'rombel'] as $filter) {
            if (isset($filters[$filter])) {
                $filters[$filter] = (int) $filters[$filter];
            }
        }

        $filters['status'] = $filters['status'] ?? 'aktif';

        return $filters;
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
            ->when($filters['tanggal_mulai'] ?? null, fn (Builder $query, string $tanggal) => $query->whereHas(
                'pembayaran',
                fn (Builder $query) => $query->whereDate('tanggal_bayar', '>=', $tanggal),
            ))
            ->when($filters['tanggal_selesai'] ?? null, fn (Builder $query, string $tanggal) => $query->whereHas(
                'pembayaran',
                fn (Builder $query) => $query->whereDate('tanggal_bayar', '<=', $tanggal),
            ))
            ->when($filters['status'] !== 'semua', fn (Builder $query) => $query->whereHas(
                'pembayaran',
                fn (Builder $query) => $query->where('status', $filters['status']),
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

    /**
     * @return array{jumlah_transaksi_aktif: int, total_aktif: int|float|string, jumlah_transaksi_dibatalkan: int, total_dibatalkan: int|float|string}
     */
    private function ringkasan(Builder $query): array
    {
        $aktif = (clone $query)->whereHas('pembayaran', fn (Builder $query) => $query->where('status', 'aktif'));
        $dibatalkan = (clone $query)->whereHas('pembayaran', fn (Builder $query) => $query->where('status', 'dibatalkan'));

        return [
            'jumlah_transaksi_aktif' => (clone $aktif)->distinct()->count('id_pembayaran'),
            'total_aktif' => (clone $aktif)->sum('nominal_bayar'),
            'jumlah_transaksi_dibatalkan' => (clone $dibatalkan)->distinct()->count('id_pembayaran'),
            'total_dibatalkan' => (clone $dibatalkan)->sum('nominal_bayar'),
        ];
    }

    private function orderedQuery(Builder $query): Builder
    {
        return $query->orderByDesc(
            Pembayaran::query()
                ->select('tanggal_bayar')
                ->whereColumn('pembayaran.id_pembayaran', 'detail_pembayaran.id_pembayaran'),
        );
    }

    private function excelCell(string|int $value, string $type = 'String', ?string $style = null): string
    {
        $value = (string) $value;

        if ($type === 'String' && preg_match('/^\s*[=+\-@]/', $value)) {
            $value = "'{$value}";
        }

        $styleAttribute = $style === null ? '' : ' ss:StyleID="'.$style.'"';

        return '<Cell'.$styleAttribute.'><Data ss:Type="'.$type.'">'.htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</Data></Cell>';
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
