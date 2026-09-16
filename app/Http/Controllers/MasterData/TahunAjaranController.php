<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\ActivateTahunAjaranRequest;
use App\Http\Requests\MasterData\TahunAjaranRequest;
use App\Models\TahunAjaran;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TahunAjaranController extends Controller
{
    public function index(): View
    {
        $tahunAjaranAktif = TahunAjaran::aktif()->first();
        $tahunAjaranBerikutnya = null;
        $dapatMenyiapkanTahunAjaran = false;

        if ($tahunAjaranAktif) {
            $tanggalMulaiBerikutnya = $tahunAjaranAktif->tanggal_selesai->copy()->addDay();
            $tahunAjaranBerikutnya = $tanggalMulaiBerikutnya->year.'/'.($tanggalMulaiBerikutnya->year + 1);
            $dapatMenyiapkanTahunAjaran = ! TahunAjaran::query()
                ->whereDate('tanggal_mulai', '>=', $tanggalMulaiBerikutnya)
                ->exists();
        }

        return view('master.tahun-ajaran.index', [
            'tahunAjaran' => TahunAjaran::query()
                ->withCount([
                    'siswaKelas',
                    'tarifSpp',
                    'tarifSpp as tarif_spp_digunakan_count' => fn ($query) => $query->whereHas('tagihanSpp'),
                ])
                ->orderByDesc('aktif')
                ->orderByDesc('tanggal_mulai')
                ->get(),
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'tahunAjaranBerikutnya' => $tahunAjaranBerikutnya,
            'dapatMenyiapkanTahunAjaran' => $dapatMenyiapkanTahunAjaran,
        ]);
    }

    public function store(): RedirectResponse
    {
        $tahunAjaranAktif = TahunAjaran::aktif()->first();

        if (! $tahunAjaranAktif) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Tidak dapat menyiapkan tahun ajaran baru karena belum ada tahun ajaran aktif.');
        }

        $tanggalMulaiBerikutnya = $tahunAjaranAktif->tanggal_selesai->copy()->addDay();

        if (TahunAjaran::query()->whereDate('tanggal_mulai', '>=', $tanggalMulaiBerikutnya)->exists()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', "Tahun ajaran berikutnya {$tanggalMulaiBerikutnya->year}/".($tanggalMulaiBerikutnya->year + 1).' sudah disiapkan.');
        }

        $tahunAjaran = $tanggalMulaiBerikutnya->year.'/'.($tanggalMulaiBerikutnya->year + 1);
        $this->persist(new TahunAjaran([
            'aktif' => false,
            'status' => 'persiapan',
        ]), [
            'tahun_ajaran' => $tahunAjaran,
            'tanggal_mulai' => $tanggalMulaiBerikutnya->toDateString(),
        ]);

        return to_route('master.tahun-ajaran.index')
            ->with('status', "Tahun ajaran {$tahunAjaran} berhasil disiapkan.");
    }

    public function edit(TahunAjaran $tahunAjaran): View
    {
        return view('master.tahun-ajaran.edit', compact('tahunAjaran'));
    }

    public function update(TahunAjaranRequest $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        if (! $tahunAjaran->isPersiapan()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Hanya tahun ajaran berstatus persiapan yang dapat diubah.');
        }

        $this->persist($tahunAjaran, $request->validated());

        return to_route('master.tahun-ajaran.index')
            ->with('status', 'Tahun ajaran berhasil diperbarui.');
    }

    public function activate(ActivateTahunAjaranRequest $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        if (! Hash::check($request->validated('password'), $request->user()->password)) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Password petugas tidak sesuai. Tahun ajaran tidak diaktifkan.');
        }

        if ($tahunAjaran->isTutup()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Tahun ajaran yang sudah ditutup tidak dapat diaktifkan kembali.');
        }

        if (! $tahunAjaran->isPersiapan()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Hanya tahun ajaran berstatus persiapan yang dapat diaktifkan.');
        }

        $tahunAjaranAktif = TahunAjaran::aktif()->first();

        if ($tahunAjaranAktif) {
            $tanggalMulaiBerikutnya = $tahunAjaranAktif->tanggal_selesai->copy()->addDay();

            if (! $tahunAjaran->tanggal_mulai->isSameDay($tanggalMulaiBerikutnya)) {
                return to_route('master.tahun-ajaran.index')
                    ->with('error', "Hanya tahun ajaran {$tanggalMulaiBerikutnya->year}/".($tanggalMulaiBerikutnya->year + 1).' yang dapat diaktifkan berikutnya.');
            }
        } else {
            $tahunAjaranPersiapanPertama = TahunAjaran::query()
                ->where('status', 'persiapan')
                ->orderBy('tanggal_mulai')
                ->first();

            if ($tahunAjaran->id_tahun_ajaran !== $tahunAjaranPersiapanPertama->id_tahun_ajaran) {
                return to_route('master.tahun-ajaran.index')
                    ->with('error', "Aktifkan tahun ajaran {$tahunAjaranPersiapanPertama->tahun_ajaran} terlebih dahulu.");
            }
        }

        $tingkatWajib = [1, 2, 3];
        $tingkatTersedia = $tahunAjaran->tarifSpp()
            ->whereIn('tingkat', $tingkatWajib)
            ->pluck('tingkat')
            ->map(fn (int $tingkat): int => $tingkat)
            ->all();
        $tingkatBelumAda = array_values(array_diff($tingkatWajib, $tingkatTersedia));

        if ($tingkatBelumAda) {
            $tingkat = implode(', ', $tingkatBelumAda);

            return to_route('master.tahun-ajaran.index')
                ->with('error', "Tahun ajaran tidak dapat diaktifkan karena tarif SPP tingkat {$tingkat} belum tersedia.");
        }

        DB::transaction(function () use ($tahunAjaran): void {
            TahunAjaran::aktif()->update([
                'aktif' => false,
                'status' => 'ditutup',
            ]);

            $tahunAjaran->update([
                'aktif' => true,
                'status' => 'aktif',
            ]);
        });

        return to_route('master.tahun-ajaran.index')
            ->with('status', "Tahun ajaran {$tahunAjaran->tahun_ajaran} aktif.");
    }

    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if (! $tahunAjaran->isPersiapan()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Hanya tahun ajaran berstatus persiapan yang dapat dihapus.');
        }

        if ($tahunAjaran->siswaKelas()->exists() || $tahunAjaran->tarifSpp()->exists()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Tahun ajaran tidak dapat dihapus karena sudah digunakan oleh data kelas siswa atau tarif SPP.');
        }

        try {
            $tahunAjaran->delete();
        } catch (QueryException) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Tahun ajaran tidak dapat dihapus karena sudah direferensikan oleh data lain.');
        }

        return to_route('master.tahun-ajaran.index')->with('status', 'Tahun ajaran berhasil dihapus.');
    }

    public function deactivate(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if (! $tahunAjaran->isAktif()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Hanya tahun ajaran aktif yang dapat dibatalkan aktivasinya.');
        }

        if ($tahunAjaran->siswaKelas()->exists()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Aktivasi tidak dapat dibatalkan karena tahun ajaran sudah memiliki riwayat kelas siswa.');
        }

        if ($tahunAjaran->tarifSpp()->whereHas('tagihanSpp')->exists()) {
            return to_route('master.tahun-ajaran.index')
                ->with('error', 'Aktivasi tidak dapat dibatalkan karena tarif SPP tahun ajaran sudah digunakan oleh tagihan.');
        }

        DB::transaction(function () use ($tahunAjaran): void {
            $tahunAjaran->update([
                'aktif' => false,
                'status' => 'persiapan',
            ]);
        });

        return to_route('master.tahun-ajaran.index')
            ->with('status', "Aktivasi tahun ajaran {$tahunAjaran->tahun_ajaran} dibatalkan dan dikembalikan ke persiapan.");
    }

    /**
     * @param  array{tahun_ajaran?: string, tanggal_mulai?: string, tanggal_selesai?: string}  $data
     */
    private function persist(TahunAjaran $tahunAjaran, array $data): void
    {
        if (isset($data['tahun_ajaran'])) {
            [$tanggalMulai, $tanggalSelesai] = $this->tanggalUntuk($data['tahun_ajaran']);

            $tahunAjaran->fill([
                'tahun_ajaran' => $data['tahun_ajaran'],
                'tanggal_mulai' => $data['tanggal_mulai'] ?? $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
            ]);
        } else {
            $tahunAjaran->fill([
                'tanggal_selesai' => $data['tanggal_selesai'],
            ]);
        }

        $tahunAjaran->save();
    }

    /**
     * @return array{string, string}
     */
    private function tanggalUntuk(string $tahunAjaran): array
    {
        [$tahunMulai] = explode('/', $tahunAjaran);
        $tahunSelesai = (int) $tahunMulai + 1;

        return ["{$tahunMulai}-07-01", "{$tahunSelesai}-06-30"];
    }
}
