<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\TarifSppBatchRequest;
use App\Http\Requests\MasterData\TarifSppRequest;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TarifSppController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'id_tahun_ajaran' => ['nullable', 'integer', Rule::exists('tahun_ajaran', 'id_tahun_ajaran')],
        ]);
        $idTahunAjaran = $filters['id_tahun_ajaran']
            ?? TahunAjaran::query()->where('aktif', true)->value('id_tahun_ajaran');

        return view('master.tarif-spp.index', [
            'tarifSpp' => TarifSpp::query()
                ->with('tahunAjaran')
                ->withCount('tagihanSpp')
                ->when($idTahunAjaran, fn ($query, $id) => $query->where('id_tahun_ajaran', $id))
                ->orderBy('tingkat')
                ->get(),
            'tahunAjaran' => TahunAjaran::query()
                ->orderByDesc('aktif')
                ->orderByDesc('tanggal_mulai')
                ->get(),
            'idTahunAjaran' => $idTahunAjaran,
        ]);
    }

    public function create(): View
    {
        return view('master.tarif-spp.create', [
            'tahunAjaran' => TahunAjaran::query()
                ->orderByDesc('aktif')
                ->orderByDesc('tanggal_mulai')
                ->get(),
        ]);
    }

    public function store(TarifSppBatchRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            foreach ([1, 2, 3] as $tingkat) {
                TarifSpp::create([
                    'id_tahun_ajaran' => $data['id_tahun_ajaran'],
                    'tingkat' => $tingkat,
                    'nominal' => $data['tarif'][$tingkat],
                ]);
            }
        });

        return to_route('master.tarif-spp.index', ['id_tahun_ajaran' => $request->integer('id_tahun_ajaran')])
            ->with('status', 'Tarif SPP untuk semua tingkat berhasil ditambahkan.');
    }

    public function edit(TarifSpp $tarifSpp): View|RedirectResponse
    {
        if ($tarifSpp->tagihanSpp()->exists()) {
            return to_route('master.tarif-spp.index', ['id_tahun_ajaran' => $tarifSpp->id_tahun_ajaran])
                ->with('error', 'Tarif tidak dapat diubah karena sudah digunakan oleh tagihan.');
        }

        return view('master.tarif-spp.edit', [
            'tarifSpp' => $tarifSpp,
            'tahunAjaran' => TahunAjaran::query()
                ->orderByDesc('aktif')
                ->orderByDesc('tanggal_mulai')
                ->get(),
        ]);
    }

    public function update(TarifSppRequest $request, TarifSpp $tarifSpp): RedirectResponse
    {
        if ($tarifSpp->tagihanSpp()->exists()) {
            return to_route('master.tarif-spp.index', ['id_tahun_ajaran' => $tarifSpp->id_tahun_ajaran])
                ->with('error', 'Tarif tidak dapat diubah karena sudah digunakan oleh tagihan.');
        }

        $tarifSpp->update($request->validated());

        return to_route('master.tarif-spp.index', ['id_tahun_ajaran' => $tarifSpp->id_tahun_ajaran])
            ->with('status', 'Tarif SPP berhasil diperbarui.');
    }
}
