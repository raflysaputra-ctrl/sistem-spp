<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\JurusanRequest;
use App\Models\Jurusan;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JurusanController extends Controller
{
    public function index(): View
    {
        return view('master.jurusan.index', [
            'jurusan' => Jurusan::query()
                ->withCount('kelas')
                ->orderBy('kode_jurusan')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.jurusan.create');
    }

    public function store(JurusanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $jurusan = Jurusan::create($request->validated());

            foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $tingkat => $namaTingkat) {
                foreach (range(1, 4) as $rombel) {
                    $jurusan->kelas()->create([
                        'tingkat' => $tingkat,
                        'rombel' => $rombel,
                        'nama_kelas' => "$namaTingkat {$jurusan->kode_jurusan} $rombel",
                    ]);
                }
            }
        });

        return to_route('master.jurusan.index')->with('status', 'Jurusan berhasil ditambahkan beserta 12 kelas.');
    }

    public function edit(Jurusan $jurusan): View
    {
        return view('master.jurusan.edit', compact('jurusan'));
    }

    public function update(JurusanRequest $request, Jurusan $jurusan): RedirectResponse
    {
        $jurusan->update($request->validated());

        return to_route('master.jurusan.index')->with('status', 'Jurusan berhasil diperbarui.');
    }

    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        if ($jurusan->kelas()->exists()) {
            return to_route('master.jurusan.index')
                ->with('error', 'Jurusan tidak dapat dihapus karena sudah digunakan oleh kelas.');
        }

        try {
            $jurusan->delete();
        } catch (QueryException) {
            return to_route('master.jurusan.index')
                ->with('error', 'Jurusan tidak dapat dihapus karena sudah direferensikan oleh data lain.');
        }

        return to_route('master.jurusan.index')->with('status', 'Jurusan berhasil dihapus.');
    }
}
