<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\JenisPembayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JenisPembayaranController extends Controller
{
    public function index(): View
    {
        return view('master.jenis-pembayaran.index', [
            'jenisPembayaran' => JenisPembayaran::query()->orderBy('id_jenis_pembayaran')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.jenis-pembayaran.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_jenis' => ['required', 'string', 'max:50', 'unique:jenis_pembayaran,kode_jenis'],
            'nama_jenis' => ['required', 'string', 'max:100'],
            'target_tingkat' => ['nullable', 'array'],
            'target_tingkat.*' => ['integer', 'in:1,2,3'],
            'aturan_pembayaran' => ['required', Rule::in([
                JenisPembayaran::ATURAN_SEKALI_BAYAR,
                JenisPembayaran::ATURAN_CICILAN,
            ])],
            'tipe_periode' => ['required', Rule::in([
                JenisPembayaran::TIPE_PERIODE_SEMESTER,
                JenisPembayaran::TIPE_PERIODE_GELOMBANG,
                JenisPembayaran::TIPE_PERIODE_TAHUNAN,
            ])],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        JenisPembayaran::create($this->terapkanAturanJenisTetap($validated));

        return redirect()->route('master.jenis-pembayaran.index')
            ->with('status', 'Jenis pembayaran berhasil ditambahkan.');
    }

    public function edit(JenisPembayaran $jenisPembayaran): View
    {
        return view('master.jenis-pembayaran.edit', [
            'jenisPembayaran' => $jenisPembayaran,
        ]);
    }

    public function update(Request $request, JenisPembayaran $jenisPembayaran): RedirectResponse
    {
        $validated = $request->validate([
            'kode_jenis' => [
                'required', 'string', 'max:50',
                Rule::unique('jenis_pembayaran', 'kode_jenis')->ignore($jenisPembayaran->id_jenis_pembayaran, 'id_jenis_pembayaran'),
            ],
            'nama_jenis' => ['required', 'string', 'max:100'],
            'target_tingkat' => ['nullable', 'array'],
            'target_tingkat.*' => ['integer', 'in:1,2,3'],
            'aturan_pembayaran' => ['required', Rule::in([
                JenisPembayaran::ATURAN_SEKALI_BAYAR,
                JenisPembayaran::ATURAN_CICILAN,
            ])],
            'tipe_periode' => ['required', Rule::in([
                JenisPembayaran::TIPE_PERIODE_SEMESTER,
                JenisPembayaran::TIPE_PERIODE_GELOMBANG,
                JenisPembayaran::TIPE_PERIODE_TAHUNAN,
            ])],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $validated['aktif'] = $request->boolean('aktif');

        if (in_array(strtoupper($jenisPembayaran->kode_jenis), JenisPembayaran::KODE_JENIS_SEKALI_BAYAR_TETAP, true)
            && strtoupper($validated['kode_jenis']) !== strtoupper($jenisPembayaran->kode_jenis)) {
            throw ValidationException::withMessages([
                'kode_jenis' => 'Kode jenis pembayaran inti tidak dapat diubah.',
            ]);
        }

        $jenisPembayaran->update($this->terapkanAturanJenisTetap($validated));

        return redirect()->route('master.jenis-pembayaran.index')
            ->with('status', 'Jenis pembayaran berhasil diperbarui.');
    }

    public function destroy(JenisPembayaran $jenisPembayaran): RedirectResponse
    {
        if ($jenisPembayaran->tagihanPembayaran()->exists()) {
            return back()->withErrors(['form' => 'Jenis pembayaran tidak dapat dihapus karena sudah memiliki data tagihan.']);
        }

        $jenisPembayaran->delete();

        return redirect()->route('master.jenis-pembayaran.index')
            ->with('status', 'Jenis pembayaran berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function terapkanAturanJenisTetap(array $validated): array
    {
        if (in_array(strtoupper($validated['kode_jenis']), JenisPembayaran::KODE_JENIS_SEKALI_BAYAR_TETAP, true)) {
            $validated['aturan_pembayaran'] = JenisPembayaran::ATURAN_SEKALI_BAYAR;
            $validated['tipe_periode'] = JenisPembayaran::TIPE_PERIODE_SEMESTER;
        }

        return $validated;
    }
}
