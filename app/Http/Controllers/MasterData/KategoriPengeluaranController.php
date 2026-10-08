<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KategoriPengeluaranController extends Controller
{
    public function index(): View
    {
        return view('master.kategori-pengeluaran.index', [
            'kategoriPengeluaran' => KategoriPengeluaran::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', 'unique:kategori_pengeluaran,nama_kategori'],
        ]);

        KategoriPengeluaran::create($validated);

        return to_route('master.kategori-pengeluaran.index')->with('status', 'Kategori pengeluaran berhasil ditambahkan.');
    }

    public function edit(KategoriPengeluaran $kategoriPengeluaran): View
    {
        return view('master.kategori-pengeluaran.edit', compact('kategoriPengeluaran'));
    }

    public function update(Request $request, KategoriPengeluaran $kategoriPengeluaran): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori_pengeluaran', 'nama_kategori')
                    ->ignore($kategoriPengeluaran->id_kategori_pengeluaran, 'id_kategori_pengeluaran'),
            ],
        ]);

        $kategoriPengeluaran->update($validated);

        return to_route('master.kategori-pengeluaran.index')->with('status', 'Kategori pengeluaran berhasil diperbarui.');
    }

    public function toggle(KategoriPengeluaran $kategoriPengeluaran): RedirectResponse
    {
        $kategoriPengeluaran->update(['aktif' => ! $kategoriPengeluaran->aktif]);

        return to_route('master.kategori-pengeluaran.index')->with('status', 'Status kategori pengeluaran berhasil diperbarui.');
    }
}
