<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * In-app staff account creation. Who can create whom is a strict matrix:
 *   Super Admin / Admin -> any role
 *   Manager             -> Waiter, Chef, Host
 *   CEO                 -> Manager, Waiter, Chef, Host
 * New staff always join the creator's own tenant (from the token). A Chef needs
 * a kitchen department. This is the ONLY place a role/department is assigned.
 */
class StaffController extends Controller
{
    public function assignableRoles(User $creator): array
    {
        if ($creator->hasAnyRole(['Super Admin', 'Admin'])) {
            return ['Manager', 'CEO', 'Waiter', 'Chef', 'Host', 'Front Desk'];
        }
        if ($creator->hasRole('Manager')) {
            return ['Waiter', 'Chef', 'Host'];
        }
        if ($creator->hasRole('CEO')) {
            return ['Manager', 'Waiter', 'Chef', 'Host'];
        }

        return [];
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $staff = User::query()
            ->where('tenant_id', $tenantId)
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => $this->present($u));

        return response()->json([
            'data' => $staff,
            'assignable_roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $creator = $request->user();
        $allowed = $this->assignableRoles($creator);

        if ($allowed === []) {
            throw ValidationException::withMessages(['role' => 'Your role cannot create staff accounts.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'role' => ['required', 'string', Rule::in($allowed)],
            'department' => ['nullable', 'string', Rule::in(Meal::DEPARTMENTS)],
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            throw ValidationException::withMessages(['email' => 'An email or a phone number is required to sign in.']);
        }
        if ($data['role'] === 'Chef' && empty($data['department'])) {
            throw ValidationException::withMessages(['department' => 'A Chef must be assigned a kitchen station.']);
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'tenant_id' => $creator->tenant_id,
            'department' => $data['role'] === 'Chef' ? $data['department'] : null,
        ]);

        $user->assignRole($data['role']);

        return response()->json(['data' => $this->present($user->load('roles:id,name'))], 201);
    }

    private function present(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'department' => $u->department,
            'roles' => $u->getRoleNames()->values(),
        ];
    }
}
