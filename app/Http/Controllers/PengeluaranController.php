<?php

namespace App\Http\Controllers;

use App\Http\Requests\PembatalanPembayaranRequest;
use App\Http\Requests\PengeluaranRequest;
use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Models\User;
use App\Services\PembatalanPengeluaranService;
use App\Services\PengeluaranService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PengeluaranController extends Controller
{
    public function index(): View
    {
        return view('pengeluaran.index', [
            'kategoriPengeluaran' => KategoriPengeluaran::query()->where('aktif', true)->orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(PengeluaranRequest $request, PengeluaranService $pengeluaranService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $pengeluaran = $pengeluaranService->catat($user, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return to_route('pengeluaran.detail', $pengeluaran)
            ->with('status', 'Pengeluaran berhasil dicatat.');
    }

    public function riwayat(Request $request): View
    {
        $filters = $request->validate([
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'id_kategori_pengeluaran' => ['nullable', 'integer', 'exists:kategori_pengeluaran,id_kategori_pengeluaran'],
            'status' => ['nullable', 'in:aktif,dibatalkan,semua'],
        ]);
        $filters['status'] = $filters['status'] ?? 'aktif';

        $pengeluaran = Pengeluaran::query()
            ->with(['kategori', 'user'])
            ->when($filters['tanggal_mulai'] ?? null, fn (Builder $query, string $tanggal) => $query->whereDate('tanggal_pengeluaran', '>=', $tanggal))
            ->when($filters['tanggal_selesai'] ?? null, fn (Builder $query, string $tanggal) => $query->whereDate('tanggal_pengeluaran', '<=', $tanggal))
            ->when($filters['id_kategori_pengeluaran'] ?? null, fn (Builder $query, int $id) => $query->where('id_kategori_pengeluaran', $id))
            ->when($filters['status'] !== 'semua', fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderByDesc('tanggal_pengeluaran')
            ->orderByDesc('id_pengeluaran')
            ->paginate(15)
            ->withQueryString();

        return view('pengeluaran.riwayat', [
            'pengeluaran' => $pengeluaran,
            'filters' => $filters,
            'kategoriPengeluaran' => KategoriPengeluaran::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function detail(Pengeluaran $pengeluaran): View
    {
        $pengeluaran->load(['kategori', 'user', 'dibatalkanOleh']);

        return view('pengeluaran.detail', compact('pengeluaran'));
    }

    public function batalkan(
        PembatalanPembayaranRequest $request,
        Pengeluaran $pengeluaran,
        PembatalanPengeluaranService $pembatalanPengeluaranService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $pembatalanPengeluaranService->batalkan($user, $pengeluaran, $request->validated('alasan_pembatalan'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return to_route('pengeluaran.detail', $pengeluaran)
            ->with('status', 'Pengeluaran berhasil dibatalkan.');
    }
}
