<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanTunggakanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);

        return view('laporan-tunggakan.index', [
            'filters' => $filters,
            'jurusan' => Jurusan::query()->orderBy('nama_jurusan')->get(),
            'tahunAjaran' => TahunAjaran::query()->orderByDesc('tahun_ajaran')->get(),
            'tunggakan' => $this->groupedQuery($filters)->paginate(20)->withQueryString(),
            'totalNominal' => (clone $query)->sum('nominal'),
            'jumlahSiswa' => (clone $query)->distinct()->count('id_siswa'),
            'jumlahTagihan' => (clone $query)->count(),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $tunggakan = $this->groupedQuery($this->validatedFilters($request))->get();
        $totalNominal = $tunggakan->sum('total_tunggakan');

        return response()->streamDownload(function () use ($tunggakan, $totalNominal) {
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
            echo '<Worksheet ss:Name="Tunggakan SPP"><Table>';
            echo '<Row>'.$this->excelCell('Laporan Tunggakan SPP').'</Row>';
            echo '<Row></Row>';
            echo '<Row>'
                .$this->excelCell('NIPD', 'String', 'header')
                .$this->excelCell('Nama Siswa', 'String', 'header')
                .$this->excelCell('Kelas', 'String', 'header')
                .$this->excelCell('Jml Tagihan', 'String', 'header')
                .$this->excelCell('Total Tunggakan', 'String', 'header')
                .'</Row>';

            foreach ($tunggakan as $item) {
                echo '<Row>'
                    .$this->excelCell($item->siswa->nipd, 'String', 'border')
                    .$this->excelCell($item->siswa->nama_siswa, 'String', 'border')
                    .$this->excelCell($this->namaKelasAktif($item), 'String', 'border')
                    .$this->excelCell((int) $item->jumlah_tagihan, 'Number', 'border')
                    .$this->excelCell((int) $item->total_tunggakan, 'Number', 'border')
                    .'</Row>';
            }

            echo '<Row></Row>';
            echo '<Row>'.$this->excelCell('Total Nominal', 'String', 'header').$this->excelCell((int) $totalNominal, 'Number', 'border').'</Row>';
            echo '</Table></Worksheet></Workbook>';
        }, 'tunggakan-spp-'.now()->format('Ymd-His').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $tunggakan = $this->groupedQuery($this->validatedFilters($request))->get();

        return Pdf::loadView('laporan-tunggakan.pdf', [
            'tunggakan' => $tunggakan,
            'totalNominal' => $tunggakan->sum('total_tunggakan'),
            'jumlahSiswa' => $tunggakan->count(),
            'jumlahTagihan' => $tunggakan->sum('jumlah_tagihan'),
        ])
            ->setPaper('a4', 'portrait')
            ->download('laporan-tunggakan-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array<string, int|string>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'id_jurusan' => ['nullable', 'integer', 'exists:jurusan,id_jurusan'],
            'tingkat' => ['nullable', 'integer', 'in:1,2,3'],
            'rombel' => ['nullable', 'integer', 'min:1', 'max:255'],
            'id_tahun_ajaran' => ['nullable', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'cari' => ['nullable', 'string', 'max:100'],
        ]);
    }

    /**
     * @param  array<string, int|string>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $sekarang = now();

        return TagihanSpp::query()
            ->where('status', 'belum_bayar')
            ->where(function (Builder $query) use ($sekarang) {
                $query->where('tahun', '<', $sekarang->year)
                    ->orWhere(function (Builder $query) use ($sekarang) {
                        $query->where('tahun', $sekarang->year)
                            ->where('bulan', '<', $sekarang->month);
                    });
            })
            ->whereHas('siswa', function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->where('status_siswa', '!=', 'lulus');
            })
            ->when($filters['cari'] ?? null, function (Builder $query, string $cari) {
                $query->whereHas('siswa', function (Builder $query) use ($cari) {
                    $query->where('nipd', 'like', "%{$cari}%")
                        ->orWhere('nama_siswa', 'like', "%{$cari}%");
                });
            })
            ->when($filters['id_tahun_ajaran'] ?? null, fn (Builder $query, int $idTahunAjaran) => $query->whereHas(
                'siswaKelas',
                fn (Builder $query) => $query->where('id_tahun_ajaran', $idTahunAjaran),
            ))
            ->when(
                ($filters['id_jurusan'] ?? null) || ($filters['tingkat'] ?? null) || ($filters['rombel'] ?? null),
                function (Builder $query) use ($filters) {
                    $query->whereHas('siswaKelas.kelas', function (Builder $query) use ($filters) {
                        $query
                            ->when($filters['id_jurusan'] ?? null, fn (Builder $query, int $idJurusan) => $query->where('id_jurusan', $idJurusan))
                            ->when($filters['tingkat'] ?? null, fn (Builder $query, int $tingkat) => $query->where('tingkat', $tingkat))
                            ->when($filters['rombel'] ?? null, fn (Builder $query, int $rombel) => $query->where('rombel', $rombel));
                    });
                },
            );
    }

    /**
     * @param  array<string, int|string>  $filters
     */
    private function groupedQuery(array $filters): Builder
    {
        return $this->filteredQuery($filters)
            ->join('siswa', 'siswa.id_siswa', '=', 'tagihan_spp.id_siswa')
            ->select('tagihan_spp.id_siswa')
            ->selectRaw('COUNT(*) as jumlah_tagihan')
            ->selectRaw('SUM(tagihan_spp.nominal) as total_tunggakan')
            ->groupBy('tagihan_spp.id_siswa', 'siswa.nama_siswa')
            ->orderBy('siswa.nama_siswa')
            ->with([
                'siswa.siswaKelas' => function ($query) {
                    $query->whereHas('tahunAjaran', fn (Builder $query) => $query->where('aktif', true))
                        ->with('kelas');
                },
            ]);
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

    private function namaKelasAktif(TagihanSpp $item): string
    {
        return $item->siswa->siswaKelas->first()?->kelas?->nama_kelas ?? 'Tidak ada kelas aktif';
    }
}
