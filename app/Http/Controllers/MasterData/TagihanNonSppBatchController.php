<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\GenerateTagihanNonSppRequest;
use App\Models\JenisPembayaran;
use App\Models\PenetapanGelombangBam;
use App\Models\Siswa;
use App\Models\TagihanPembayaran;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PenetapanGelombangBamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TagihanNonSppBatchController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'id_tahun_ajaran' => ['nullable', 'integer'],
        ]);
        $idTahunAjaran = $filters['id_tahun_ajaran']
            ?? TahunAjaran::query()->where('aktif', true)->value('id_tahun_ajaran');

        $tagihanSummary = TagihanPembayaran::query()
            ->with(['jenisPembayaran', 'tahunAjaran'])
            ->when($idTahunAjaran, fn ($query, $id) => $query->where('id_tahun_ajaran', $id))
            ->selectRaw('id_jenis_pembayaran, id_tahun_ajaran, kode_periode, COUNT(*) as jumlah_tagihan, MIN(total_tagihan) as biaya_tagihan')
            ->groupBy('id_jenis_pembayaran', 'id_tahun_ajaran', 'kode_periode')
            ->orderByDesc('id_tahun_ajaran')
            ->orderBy('id_jenis_pembayaran')
            ->orderBy('kode_periode')
            ->get();

        return view('master.tagihan-non-spp.index', [
            'tagihanSummary' => $tagihanSummary,
            'jenisPembayaran' => JenisPembayaran::query()->where('aktif', true)->orderBy('nama_jenis')->get(),
            'tahunAjaran' => TahunAjaran::query()->orderByDesc('aktif')->orderByDesc('tanggal_mulai')->get(),
            'idTahunAjaran' => $idTahunAjaran,
        ]);
    }

    public function create(): View
    {
        return view('master.tagihan-non-spp.create', [
            'jenisPembayaran' => JenisPembayaran::query()->where('aktif', true)->orderBy('nama_jenis')->get(),
            'tahunAjaran' => TahunAjaran::query()->where('aktif', true)->get(),
        ]);
    }

    public function detail(Request $request): View
    {
        $filters = $request->validate([
            'id_jenis_pembayaran' => ['required', 'integer', 'exists:jenis_pembayaran,id_jenis_pembayaran'],
            'id_tahun_ajaran' => ['required', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'kode_periode' => ['required', 'string', 'max:20'],
        ]);

        $tagihanPembayaran = TagihanPembayaran::query()
            ->with(['siswa', 'jenisPembayaran'])
            ->withSum([
                'detailPembayaranNonSpp as total_dibayar_aktif' => fn (Builder $query) => $query
                    ->whereHas('pembayaranNonSpp', fn (Builder $pembayaran) => $pembayaran->where('status', 'aktif')),
            ], 'nominal_bayar')
            ->where($filters)
            ->orderBy('id_tagihan_pembayaran')
            ->paginate(20)
            ->withQueryString();

        $jenisPembayaran = JenisPembayaran::findOrFail($filters['id_jenis_pembayaran']);
        $penetapanBamBySiswa = $jenisPembayaran->adalahBiayaAwalMasuk()
            ? PenetapanGelombangBam::query()
                ->where('id_tahun_ajaran', $filters['id_tahun_ajaran'])
                ->whereIn('id_siswa', $tagihanPembayaran->getCollection()->pluck('id_siswa'))
                ->get()
                ->keyBy('id_siswa')
            : collect();

        return view('master.tagihan-non-spp.detail', [
            'filters' => $filters,
            'jenisPembayaran' => $jenisPembayaran,
            'tahunAjaran' => TahunAjaran::findOrFail($filters['id_tahun_ajaran']),
            'tagihanPembayaran' => $tagihanPembayaran,
            'penetapanBamBySiswa' => $penetapanBamBySiswa,
        ]);
    }

    public function koreksiGelombangBam(
        Request $request,
        Siswa $siswa,
        PenetapanGelombangBamService $penetapanGelombangBamService,
    ): RedirectResponse {
        $validated = $request->validate([
            'id_tahun_ajaran' => ['required', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'kode_periode' => ['required', 'in:gelombang_1,gelombang_2,gelombang_3'],
            'alasan_koreksi' => ['required', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        try {
            $penetapanGelombangBamService->koreksi(
                $user,
                $siswa,
                TahunAjaran::findOrFail($validated['id_tahun_ajaran']),
                $validated['kode_periode'],
                $validated['alasan_koreksi'],
            );
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        } catch (QueryException) {
            return back()->withInput()->withErrors([
                'form' => 'Gelombang BAM tidak dapat dikoreksi karena data baru saja berubah. Muat ulang halaman dan coba lagi.',
            ]);
        }

        return back()->with('status', 'Gelombang BAM siswa berhasil dikoreksi.');
    }

    public function store(GenerateTagihanNonSppRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $jenis = JenisPembayaran::query()
            ->where('aktif', true)
            ->findOrFail($validated['id_jenis_pembayaran']);

        $kodePeriodeDiharapkan = array_keys($jenis->pilihanPeriode());
        $tagihanPerPeriode = collect($validated['tagihan'])->keyBy('kode_periode');

        if (
            $tagihanPerPeriode->count() !== count($kodePeriodeDiharapkan)
            || $tagihanPerPeriode->keys()->sort()->values()->all() !== collect($kodePeriodeDiharapkan)->sort()->values()->all()
        ) {
            throw ValidationException::withMessages([
                'tagihan' => 'Lengkapi seluruh periode yang berlaku untuk jenis pembayaran yang dipilih.',
            ]);
        }

        foreach ($tagihanPerPeriode as $item) {
            $minimalDp = $jenis->bisaDicicil() ? (int) ($item['minimal_dp'] ?? 0) : 0;

            if ($jenis->bisaDicicil() && $minimalDp < 1) {
                throw ValidationException::withMessages([
                    'tagihan' => 'Minimal DP wajib diisi untuk setiap periode tagihan yang dapat dicicil.',
                ]);
            }

            if ($minimalDp > (int) $item['total_tagihan']) {
                throw ValidationException::withMessages([
                    'tagihan' => 'Minimal DP tidak boleh melebihi total tagihan pada setiap periode.',
                ]);
            }
        }

        $targetTingkat = $jenis->target_tingkat ?: [1, 2, 3];
        $siswa = Siswa::query()
            ->where('status_siswa', 'aktif')
            ->whereHas('siswaKelas', function (Builder $query) use ($validated, $targetTingkat) {
                $query->where('id_tahun_ajaran', $validated['id_tahun_ajaran'])
                    ->whereHas('kelas', fn (Builder $kelas) => $kelas->whereIn('tingkat', $targetTingkat));
            })
            ->orderBy('id_siswa')
            ->get();

        if ($siswa->isEmpty()) {
            return back()->withInput()->withErrors([
                'id_tahun_ajaran' => 'Tidak ada siswa aktif sesuai target kelas untuk tahun ajaran ini.',
            ]);
        }

        $idSiswa = $siswa->pluck('id_siswa');
        $dibuat = 0;
        $dilewati = 0;

        try {
            DB::transaction(function () use ($validated, $jenis, $tagihanPerPeriode, $siswa, $idSiswa, &$dibuat, &$dilewati): void {
                $sudahAda = TagihanPembayaran::query()
                    ->whereIn('id_siswa', $idSiswa)
                    ->where('id_jenis_pembayaran', $jenis->id_jenis_pembayaran)
                    ->where('id_tahun_ajaran', $validated['id_tahun_ajaran'])
                    ->whereIn('kode_periode', $tagihanPerPeriode->keys())
                    ->get(['id_siswa', 'kode_periode'])
                    ->mapWithKeys(fn (TagihanPembayaran $tagihan): array => ["{$tagihan->id_siswa}|{$tagihan->kode_periode}" => true])
                    ->all();

                foreach ($tagihanPerPeriode as $item) {
                    $minimalDp = $jenis->bisaDicicil() ? (int) ($item['minimal_dp'] ?? 0) : 0;

                    foreach ($siswa as $siswaItem) {
                        $key = "{$siswaItem->id_siswa}|{$item['kode_periode']}";

                        if (isset($sudahAda[$key])) {
                            $dilewati++;

                            continue;
                        }

                        TagihanPembayaran::create([
                            'id_siswa' => $siswaItem->id_siswa,
                            'id_jenis_pembayaran' => $jenis->id_jenis_pembayaran,
                            'id_tahun_ajaran' => $validated['id_tahun_ajaran'],
                            'total_tagihan' => $item['total_tagihan'],
                            'minimal_dp' => $minimalDp,
                            'bisa_cicil' => $jenis->bisaDicicil(),
                            'kode_periode' => $item['kode_periode'],
                            'periode_keterangan' => JenisPembayaran::labelPeriode($item['kode_periode']),
                            'status' => 'belum_bayar',
                            'created_by' => auth()->id(),
                        ]);
                        $dibuat++;
                    }
                }
            });
        } catch (QueryException) {
            return back()->withInput()->withErrors([
                'form' => 'Tagihan tidak dapat dibuat karena data berubah. Muat ulang halaman dan coba lagi.',
            ]);
        }

        $jumlahPeriode = $tagihanPerPeriode->count();
        $pesan = "Berhasil membuat {$dibuat} tagihan {$jenis->nama_jenis} untuk {$jumlahPeriode} periode.";
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} siswa dilewati karena tagihan periode ini sudah ada.";
        }

        return to_route('master.tagihan-non-spp.index')->with('status', $pesan);
    }
}
