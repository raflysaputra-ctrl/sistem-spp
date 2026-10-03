<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class SiswaAccountController extends Controller
{
    public function index(Request $request): View
    {
        $query = Siswa::with(['akunSiswa', 'siswaKelas.kelas'])
            ->whereNull('deleted_at')
            ->where('status_siswa', 'aktif');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_siswa', 'like', "%{$search}%")
                  ->orWhere('nipd', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status_akun')) {
            if ($request->status_akun === 'aktif') {
                $query->whereHas('akunSiswa', function ($q) {
                    $q->where('is_active', true);
                });
            } elseif ($request->status_akun === 'nonaktif') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('akunSiswa')
                      ->orWhereHas('akunSiswa', function ($sq) {
                          $sq->where('is_active', false);
                      });
                });
            }
        }

        $siswaList = $query->orderBy('nama_siswa')->paginate(20)->withQueryString();

        return view('admin.accounts.siswa.index', [
            'siswaList' => $siswaList,
            'filters' => $request->only(['search', 'status_akun']),
        ]);
    }

    public function create(Siswa $siswa): View
    {
        if ($siswa->akunSiswa) {
            abort(403, 'Siswa sudah memiliki akun.');
        }

        return view('admin.accounts.siswa.create', ['siswa' => $siswa]);
    }

    public function store(Request $request, Siswa $siswa): RedirectResponse
    {
        if ($siswa->akunSiswa) {
            return back()->withErrors(['form' => 'Siswa ini sudah memiliki akun.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $actor = $request->user();

        $user = User::create([
            'nama' => $siswa->nama_siswa,
            'username' => $siswa->nipd,
            'password' => $validated['password'],
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
            'is_active' => true,
            'created_by' => $actor->id_user,
        ]);

        $this->logActivity($user, $actor, 'CREATE', "Membuat akun siswa {$user->username} untuk {$siswa->nama_siswa}");

        return redirect()->route('admin.accounts.siswa.index')->with('success', "Akun siswa {$siswa->nama_siswa} berhasil dibuat.");
    }

    public function edit(User $user): View
    {
        if ($user->role !== User::ROLE_SISWA) {
            abort(404);
        }

        return view('admin.accounts.siswa.edit', [
            'user' => $user,
            'siswa' => $user->siswa,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== User::ROLE_SISWA) {
            abort(404);
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string', Password::min(8), 'confirmed'],
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $actor = $request->user();

        if (!empty($validated['password'])) {
            $user->update([
                'password' => $validated['password'],
                'updated_by' => $actor->id_user,
            ]);

            $this->logActivity($user, $actor, 'RESET_PASSWORD', "Reset password akun siswa {$user->username}");

            return redirect()->route('admin.accounts.siswa.index')->with('success', "Password akun siswa {$user->nama} berhasil diperbarui.");
        }

        return back()->with('info', 'Tidak ada perubahan.');
    }

    public function syncUsername(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== User::ROLE_SISWA || !$user->siswa) {
            abort(404);
        }

        $actor = $request->user();
        $oldUsername = $user->username;
        $newUsername = $user->siswa->nipd;

        if ($oldUsername === $newUsername) {
            return back()->with('info', 'Username sudah sesuai dengan NIPD.');
        }

        $user->update([
            'username' => $newUsername,
            'updated_by' => $actor->id_user,
        ]);

        $this->logActivity($user, $actor, 'UPDATE', "Sync username dari {$oldUsername} ke {$newUsername}");

        return back()->with('success', "Username berhasil disinkronkan ke NIPD: {$newUsername}");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== User::ROLE_SISWA) {
            abort(404);
        }

        $actor = $request->user();

        $user->is_active = !$user->is_active;
        $user->updated_by = $actor->id_user;
        $user->save();

        $action = $user->is_active ? 'ACTIVATE' : 'DEACTIVATE';
        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $this->logActivity($user, $actor, $action, "Status akun siswa {$user->username} {$statusText}");

        return back()->with('success', "Akun siswa {$user->nama} berhasil {$statusText}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== User::ROLE_SISWA) {
            abort(404);
        }

        $actor = $request->user();

        if ($user->pembayaran()->exists()) {
            $user->is_active = false;
            $user->updated_by = $actor->id_user;
            $user->save();

            $this->logActivity($user, $actor, 'DEACTIVATE', "Akun siswa {$user->username} dinonaktifkan (memiliki riwayat transaksi)");

            return back()->with('success', "Akun siswa {$user->nama} dinonaktifkan karena memiliki riwayat transaksi.");
        } else {
            $username = $user->username;
            $this->logActivity($user, $actor, 'DELETE', "Menghapus akun siswa {$username}");

            $user->delete();

            return back()->with('success', "Akun siswa {$user->nama} berhasil dihapus.");
        }
    }

    public function importForm(): View
    {
        return view('admin.accounts.siswa.import');
    }

    public function importSiswa(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'Pilih file Excel/CSV terlebih dahulu.',
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $file = $request->file('file');
        $data = Excel::toArray([], $file)[0] ?? [];

        if (empty($data) || count($data) < 2) {
            return back()->withErrors(['file' => 'File kosong atau format tidak sesuai.']);
        }

        $actor = $request->user();
        $successCount = 0;
        $errors = [];

        foreach (array_slice($data, 1) as $index => $row) {
            $rowNum = $index + 2;
            $nama = trim((string) ($row[0] ?? ''));
            $nipd = trim((string) ($row[1] ?? ''));
            $password = trim((string) ($row[2] ?? ''));

            if (empty($nama) && empty($nipd) && empty($password)) {
                continue;
            }

            if (empty($nipd) || empty($password)) {
                $errors[] = "Baris {$rowNum}: Data NIPD atau Password tidak lengkap.";
                continue;
            }

            if (strlen($password) < 8) {
                $errors[] = "Baris {$rowNum}: Password kurang dari 8 karakter.";
                continue;
            }

            $siswa = Siswa::where('nipd', $nipd)->first();
            if (!$siswa) {
                $errors[] = "Baris {$rowNum}: Siswa dengan NIPD '{$nipd}' tidak ditemukan di sistem.";
                continue;
            }

            if (User::where('username', $nipd)->exists()) {
                $errors[] = "Baris {$rowNum}: NIPD '{$nipd}' sudah memiliki akun.";
                continue;
            }

            if (User::where('id_siswa', $siswa->id_siswa)->exists()) {
                $errors[] = "Baris {$rowNum}: Siswa {$siswa->nama_siswa} ({$nipd}) sudah memiliki akun.";
                continue;
            }

            $user = User::create([
                'nama' => $siswa->nama_siswa,
                'username' => $nipd,
                'password' => $password,
                'role' => User::ROLE_SISWA,
                'id_siswa' => $siswa->id_siswa,
                'is_active' => true,
                'created_by' => $actor->id_user,
            ]);

            $this->logActivity($user, $actor, 'CREATE', "Import akun siswa {$nipd}");
            $successCount++;
        }

        $message = "Berhasil mengimpor {$successCount} akun siswa.";
        if (!empty($errors)) {
            return back()->with('success', $message)->with('import_errors', $errors);
        }

        return redirect()->route('admin.accounts.siswa.index')->with('success', $message);
    }

    private function logActivity(User $targetUser, User $actor, string $action, ?string $details = null): void
    {
        AccountLog::create([
            'user_id' => $targetUser->id_user,
            'actor_id' => $actor->id_user,
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
