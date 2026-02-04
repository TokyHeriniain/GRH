<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\Permission;
use App\Models\Personnel;
use Illuminate\Support\Str;


class AdminController extends Controller
{
    public function __construct()
    {
        // 🔐 protection permissions
        $this->middleware('permission:users.manage')->except(['index', 'personnelsDisponibles']);
        $this->middleware('permission:users.view')->only(['index']);
    }
    // 🔹 Liste paginée des utilisateurs avec recherche et filtre rôle
    public function listUsers(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search  = $request->input('search', null);
        $role    = $request->input('role', null);

        $query = User::with('role', 'personnel');

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
            'personnel_id' => [
                'required',
                'exists:personnels,id',
                Rule::unique('users', 'personnel_id'),
            ],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['Admin','Manager','Employe','RH'])],
            'password' => ['required', 'string', 'min:6'],
        ]);

        // 🔒 Récupération sécurisée du personnel
        $personnel = Personnel::findOrFail($validated['personnel_id']);

        $role = Role::where('name', $validated['role'])->firstOrFail();

        $user = User::create([
            'name' => trim($personnel->nom . ' ' . $personnel->prenom),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id,
            'personnel_id' => $personnel->id,
            'must_change_password' => true, // ✅ recommandé
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user->load('personnel', 'role')
        ], 201);
    }

    // 🔹 Modifier un utilisateur existant
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'email'    => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'role'     => ['required', Rule::in(['Admin','Manager','Employe','RH'])],
            'password' => 'nullable|string|min:6',
        ]);

        if (auth()->id() === $user->id && $validated['role'] !== $user->role->name) {
            return response()->json(['error' => 'Impossible de changer votre propre rôle'], 403);
        }

        $role = Role::where('name', $validated['role'])->firstOrFail();

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

    public function personnelsDisponibles()
    {
        $personnels = Personnel::whereDoesntHave('user')
            ->orderBy('nom')
            ->get();

        return response()->json($personnels);
    }
    //creation des comptes manquants pour les personnels sans compte utilisateur
    public function generateMissing()
    {
        set_time_limit(0);

        $role = Role::where('name', 'Employe')->firstOrFail();

        $count = 0;

        Personnel::whereDoesntHave('user')
            ->chunk(100, function ($personnels) use ($role, &$count) {

                foreach ($personnels as $p) {

                    $safeMatricule = preg_replace('/[^a-zA-Z0-9]/', '', $p->matricule);
                    $email = strtolower($safeMatricule) . '@entreprise.local';

                    User::create([
                        'name' => $p->nom . ' ' . $p->prenom,
                        'email' => $email,

                        // ✅ hash Laravel sécurisé
                        'password' => Hash::make($p->matricule),

                        'role_id' => $role->id,
                        'personnel_id' => $p->id,
                        'must_change_password' => true,
                    ]);

                    $count++;
                }
            });

        return response()->json([
            'message' => "$count comptes créés avec succès"
        ]);
    }

}
