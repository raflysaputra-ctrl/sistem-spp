<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\KelasRequest;
use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'id_jurusan' => ['nullable', 'integer', Rule::exists('jurusan', 'id_jurusan')],
            'tingkat' => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'rombel' => ['nullable', 'integer', 'min:1', 'max:255'],
        ]);

        return view('master.kelas.index', [
            'kelas' => Kelas::query()
                ->with('jurusan')
                ->withCount('siswaKelas')
                ->when($filters['id_jurusan'] ?? null, fn ($query, $idJurusan) => $query->where('id_jurusan', $idJurusan))
                ->when($filters['tingkat'] ?? null, fn ($query, $tingkat) => $query->where('tingkat', $tingkat))
                ->when($filters['rombel'] ?? null, fn ($query, $rombel) => $query->where('rombel', $rombel))
                ->orderBy('tingkat')
                ->orderBy('id_jurusan')
                ->orderBy('rombel')
                ->get(),
            'jurusan' => Jurusan::query()->orderBy('kode_jurusan')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('master.kelas.create', [
            'jurusan' => Jurusan::query()->orderBy('kode_jurusan')->get(),
        ]);
    }

    public function store(KelasRequest $request): RedirectResponse
    {
        Kelas::create($this->kelasAttributes($request->validated()));

        return to_route('master.kelas.index')->with('status', 'Kelas berhasil ditambahkan.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        if ($kelas->siswaKelas()->exists()) {
            return to_route('master.kelas.index')
                ->with('error', 'Kelas tidak dapat dihapus karena sudah memiliki riwayat penempatan siswa.');
        }

        try {
            $kelas->delete();
        } catch (QueryException) {
            return to_route('master.kelas.index')
                ->with('error', 'Kelas tidak dapat dihapus karena sudah direferensikan oleh data lain.');
        }

        return to_route('master.kelas.index')->with('status', 'Kelas berhasil dihapus.');
    }

    /**
     * @param  array{id_jurusan: int, tingkat: int, rombel: int}  $data
     * @return array{id_jurusan: int, tingkat: int, rombel: int, nama_kelas: string}
     */
    private function kelasAttributes(array $data): array
    {
        $kodeJurusan = Jurusan::query()
            ->whereKey($data['id_jurusan'])
            ->value('kode_jurusan');
        $tingkat = [1 => 'X', 2 => 'XI', 3 => 'XII'][$data['tingkat']];

        return [...$data, 'nama_kelas' => "$tingkat $kodeJurusan {$data['rombel']}"];
    }
}
