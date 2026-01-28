<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\Permission;


class AdminController extends Controller
{
    // 🔹 Liste paginée des utilisateurs avec recherche et filtre rôle
    public function listUsers(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search  = $request->input('search', null);
        $role    = $request->input('role', null);

        $query = User::with('role');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%$search%")
                  ->orWhere('email', 'ilike', "%$search%");
            });
        }

        if ($role) {
            $query->whereHas('role', function($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($users);
    }

    // 🔹 Créer un utilisateur (backend pour modal React)
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => ['required', Rule::in(['Admin','Manager','Employe','RH'])],
            'password' => 'required|string|min:6',
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();

        $user = User::create([
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'role_id' => $role->id,
            'password'=> Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Utilisateur créé', 'user' => $user], 201);
    }

    // 🔹 Modifier un utilisateur existant
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'role'     => ['required', Rule::in(['Admin','Manager','Employe','RH'])],
            'password' => 'nullable|string|min:6',
        ]);

        if (auth()->id() === $user->id && $validated['role'] !== $user->role->name) {
            return response()->json(['error' => 'Impossible de changer votre propre rôle'], 403);
        }

        $role = Role::where('name', $validated['role'])->firstOrFail();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role_id = $role->id;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json(['message' => 'Utilisateur modifié', 'user' => $user]);
    }

    // 🔹 Changement de rôle simple
    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['Admin', 'Manager', 'Employe', 'RH'])],
        ]);

        if (auth()->id() === $user->id) {
            return response()->json(['error' => 'Impossible de changer votre propre rôle'], 403);
        }

        $role = Role::where('name', $validated['role'])->firstOrFail();

        $user->role_id = $role->id;
        $user->save();

        return response()->json(['message' => 'Rôle mis à jour', 'user' => $user]);
    }

    // 🔹 Créer un utilisateur test
    public function createTestUser(Request $request)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['Admin', 'Manager', 'Employe', 'RH'])],
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();

        $user = User::create([
            'name' => 'Test ' . ucfirst($role->name) . ' ' . now()->format('His'),
            'email' => strtolower($role->name) . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        return response()->json(['message' => 'Utilisateur test créé', 'user' => $user], 201);
    }

    // 🔹 Créer le compte RH test
    public function createTestRHUser()
    {
        $existing = User::where('email', 'rh@example.com')->first();
        if ($existing) {
            return response()->json(['message' => 'Le compte RH existe déjà.'], 200);
        }

        $role = Role::where('name', 'RH')->firstOrFail();

        $user = User::create([
            'name' => 'RH Test',
            'email' => 'rh@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        return response()->json(['message' => 'Compte RH créé', 'user' => $user], 201);
    }

    // 🔹 Supprimer un utilisateur
    public function deleteUser(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['error' => 'Impossible de se supprimer soi-même'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    // 🔹 Réinitialiser les utilisateurs test
    public function resetTestUsers()
    {
        $deleted = User::where('email', 'ilike', '%test%')->delete();
        return response()->json(['message' => "$deleted utilisateur(s) test supprimé(s)"]);
    }

    public function listRoles()
    {
        return Role::orderBy('name')->get();
    }

    public function getRolePermissions(Role $role)
    {
        return response()->json([
            'role' => $role,
            'permissions' => Permission::all()->groupBy('module'),
            'assigned' => $role->permissions()->pluck('permissions.id'),
        ]);
    }

    public function syncRolePermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return response()->json(['message' => 'Permissions mises à jour']);
    }
}
