<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesAdminDeletes;
use App\Http\Controllers\Controller;
use App\Models\PasswordResetAudit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    use HandlesAdminDeletes;

    public function create(): Response
    {
        return Inertia::render('Admin/Accounts/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'in:admin,teacher,student'],
            'is_active' => ['boolean'],
        ]);

        $plainPassword = $validated['password'];
        $validated['password'] = bcrypt($plainPassword);
        $validated['plain_password'] = $plainPassword;
        $validated['is_active'] = $request->input('is_active', true);

        User::create($validated);

        return redirect()->route('admin.accounts.index')->with('flash', [
            'type' => 'success',
            'message' => 'Akun berhasil ditambahkan.',
        ]);
    }

    public function show(User $user): Response
    {
        $user->load([
            'teacherProfile.subjects',
            'studentProfile.classes',
            'passwordResetAudits' => fn ($q) => $q->with('resetByAdmin:id,name')->latest()->limit(10),
        ]);

        return Inertia::render('Admin/Accounts/Show', [
            'user' => $user->makeVisible('plain_password'),
        ]);
    }

    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->query('role'), fn ($q, $role) => $q->whereJsonContains('roles', $role))
            ->when(in_array($request->query('status'), ['active', 'inactive'], true), function ($query) use ($request): void {
                $query->where('is_active', $request->query('status') === 'active');
            })
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Accounts/Index', [
            'users' => $users,
            'filters' => $request->only('role', 'search', 'status'),
        ]);
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'new_password' => ['nullable', 'string', 'min:6'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $newPassword = $request->input('new_password', Str::random(8));

        $user->update([
            'password' => $newPassword,
            'plain_password' => $newPassword,
            'must_change_password' => true,
        ]);

        // Revoke all API tokens
        $user->tokens()->delete();

        // Audit log
        PasswordResetAudit::create([
            'user_id' => $user->id,
            'reset_by_admin_id' => $request->user()->id,
            'reason' => $request->input('reason'),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => "Password berhasil direset. Password baru: {$newPassword}",
        ]);
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Tidak bisa menonaktifkan akun Anda sendiri.',
            ]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        // Revoke tokens if deactivating
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('flash', [
            'type' => 'success',
            'message' => "Akun {$user->name} berhasil {$status}.",
        ]);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ids = collect($validated['ids'])->reject(fn ($id) => $id === $request->user()->id)->values();

        if ($ids->isEmpty()) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Tidak bisa menghapus akun Anda sendiri.',
            ]);
        }

        return $this->tryDelete(
            function () use ($ids): void {
                $idList = $ids->all();
                User::purgePasswordResetAuditsForUserIds($idList);
                User::query()->whereIn('id', $idList)->delete();
            },
            'admin.accounts.index',
            ['type' => 'success', 'message' => $ids->count().' akun berhasil dihapus.'],
            'Gagal menghapus akun. Silakan coba lagi.',
        );
    }
}
