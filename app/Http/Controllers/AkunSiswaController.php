<?php

namespace App\Http\Controllers;

use App\Http\Requests\AkunSiswaRequest;
use App\Http\Requests\ResetPasswordSiswaRequest;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AkunSiswaController extends Controller
{
    public function show(Siswa $siswa): View
    {
        return view('akun-siswa.show', [
            'siswa' => $siswa,
            'akunSiswa' => $siswa->akunSiswa,
        ]);
    }

    public function store(AkunSiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        if ($siswa->akunSiswa) {
            return back()->withErrors(['akun' => 'Siswa ini sudah memiliki akun. Gunakan reset password.']);
        }

        $data = $request->validated();
        User::create([
            'nama' => $siswa->nama_siswa,
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => 'siswa',
            'id_siswa' => $siswa->id_siswa,
        ]);

        return to_route('master.siswa.akun.show', $siswa)->with('status', 'Akun siswa berhasil dibuat.');
    }

    public function resetPassword(ResetPasswordSiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $akunSiswa = $siswa->akunSiswa;

        abort_unless($akunSiswa, 404);

        $akunSiswa->update(['password' => $request->validated('password')]);

        return to_route('master.siswa.akun.show', $siswa)->with('status', 'Password akun siswa berhasil direset.');
    }
}
