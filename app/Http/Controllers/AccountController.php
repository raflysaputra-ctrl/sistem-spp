<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with(['createdByUser', 'updatedByUser'])
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_TU, User::ROLE_KEPALA_SEKOLAH]);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('nama')->paginate(15)->withQueryString();

        return view('admin.accounts.staff.index', [
            'users' => $users,
            'roles' => [
                User::ROLE_ADMIN => 'Admin',
                User::ROLE_TU => 'Tata Usaha',
                User::ROLE_KEPALA_SEKOLAH => 'Kepala Sekolah',
            ],
            'filters' => $request->only(['role', 'status', 'search']),
        ]);
    }

    public function create(): View
    {
        return view('admin.accounts.staff.create', [
            'roles' => [
                User::ROLE_ADMIN => 'Admin',
                User::ROLE_TU => 'Tata Usaha',
                User::ROLE_KEPALA_SEKOLAH => 'Kepala Sekolah',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_TU, User::ROLE_KEPALA_SEKOLAH])],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.required' => 'Peran akun wajib dipilih.',
            'role.in' => 'Peran akun tidak valid.',
        ]);

        $actor = $request->user();

        $user = User::create([
            'nama' => $validated['nama'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => true,
            'created_by' => $actor->id_user,
        ]);

        $this->logActivity($user, $actor, 'CREATE', "Menambahkan akun staff {$user->username} ({$user->roleLabel()})");

        return redirect()->route('admin.accounts.staff.index')->with('success', "Akun {$user->nama} berhasil dibuat.");
    }

    public function edit(User $account): View
    {
        if (!in_array($account->role, [User::ROLE_ADMIN, User::ROLE_TU, User::ROLE_KEPALA_SEKOLAH], true)) {
            abort(404);
        }

        return view('admin.accounts.staff.edit', [
            'user' => $account,
            'roles' => [
                User::ROLE_ADMIN => 'Admin',
                User::ROLE_TU => 'Tata Usaha',
                User::ROLE_KEPALA_SEKOLAH => 'Kepala Sekolah',
            ],
            'logs' => $account->accountLogs()->with('actor')->orderByDesc('created_at')->limit(10)->get(),
        ]);
    }

    public function update(Request $request, User $account): RedirectResponse
    {
        if (!in_array($account->role, [User::ROLE_ADMIN, User::ROLE_TU, User::ROLE_KEPALA_SEKOLAH], true)) {
            abort(404);
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($account->id_user, 'id_user')],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_TU, User::ROLE_KEPALA_SEKOLAH])],
            'password' => ['nullable', 'string', Password::min(8)],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.required' => 'Peran akun wajib dipilih.',
        ]);

        $actor = $request->user();
        $oldData = $account->only(['nama', 'username', 'role']);

        $updateData = [
            'nama' => $validated['nama'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'updated_by' => $actor->id_user,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
            $this->logActivity($account, $actor, 'RESET_PASSWORD', "Mengubah password akun staff {$account->username}");
        }

        $account->update($updateData);

        $this->logActivity($account, $actor, 'UPDATE', "Mengubah data akun staff {$account->username}: ".json_encode([
            'old' => $oldData,
            'new' => $account->only(['nama', 'username', 'role']),
        ]));

        return redirect()->route('admin.accounts.staff.index')->with('success', "Akun {$account->username} berhasil diperbarui.");
    }

    public function toggleActive(Request $request, User $account): RedirectResponse
    {
        if (!in_array($account->role, [User::ROLE_ADMIN, User::ROLE_TU, User::ROLE_KEPALA_SEKOLAH], true)) {
            abort(404);
        }

        $actor = $request->user();

        if ($account->id_user === $actor->id_user) {
            return back()->withErrors(['form' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $account->is_active = !$account->is_active;
        $account->updated_by = $actor->id_user;
        $account->save();

        $action = $account->is_active ? 'ACTIVATE' : 'DEACTIVATE';
        $statusText = $account->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $this->logActivity($account, $actor, $action, "Status akun staff {$account->username} {$statusText}");

        return back()->with('success', "Akun {$account->username} berhasil {$statusText}.");
    }

    public function destroy(Request $request, User $account): RedirectResponse
    {
        if (!in_array($account->role, [User::ROLE_ADMIN, User::ROLE_TU, User::ROLE_KEPALA_SEKOLAH], true)) {
            abort(404);
        }

        $actor = $request->user();

        if ($account->id_user === $actor->id_user) {
            return back()->withErrors(['form' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        $username = $account->username;
        $this->logActivity($account, $actor, 'DELETE', "Menghapus akun staff {$username}");

        $account->delete();

        return redirect()->route('admin.accounts.staff.index')->with('success', "Akun {$username} berhasil dihapus.");
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
