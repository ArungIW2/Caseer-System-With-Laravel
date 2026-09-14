<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('store')->when($request->filled('search'), function ($query) use ($request) {
            $term = '%' . $request->string('search')->toString() . '%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term));
        })->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
          ->when($request->has('active') && $request->active !== '', fn ($q) => $q->where('is_active', (bool) $request->active))
          ->orderBy('name')->paginate(15)->withQueryString();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User(), 'stores' => Store::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request, AuditLogService $audit)
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $audit->record($request->user(), 'user.created', $user, null, ['name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'store_id' => $user->store_id, 'is_active' => $user->is_active]);
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        return view('users.form', ['user' => $user, 'stores' => Store::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user, AuditLogService $audit)
    {
        abort_if($request->user()->id === $user->id && $request->input('role') !== $user->role, 422, 'Perubahan role akun sendiri tidak diizinkan.');
        $old = ['name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'store_id' => $user->store_id, 'is_active' => $user->is_active];
        $data = $this->validated($request, $user);
        if (blank($data['password'] ?? null)) unset($data['password']); else $data['password'] = Hash::make($data['password']);
        $user->update($data);
        $audit->record($request->user(), 'user.updated', $user, $old, ['name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'store_id' => $user->store_id, 'is_active' => $user->is_active]);
        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user, AuditLogService $audit)
    {
        abort_if($request->user()->id === $user->id, 422, 'Anda tidak dapat menonaktifkan akun sendiri.');
        $old = ['is_active' => $user->is_active];
        $user->update(['is_active' => false]);
        $audit->record($request->user(), 'user.deactivated', $user, $old, ['is_active' => false]);
        return back()->with('success', 'User dinonaktifkan.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $rolesRequiringStore = ['manager', 'cashier', 'inventory_staff'];
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super_admin', 'owner', 'manager', 'cashier', 'inventory_staff'])],
            'store_id' => [Rule::requiredIf(fn () => in_array($request->input('role'), $rolesRequiringStore, true)), 'nullable', 'exists:stores,id'],
            'is_active' => ['boolean'],
        ]);
    }
}
