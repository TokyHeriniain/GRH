<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class AdminController extends Controller
{
    // Liste des utilisateurs avec leur rôle
    public function listUsers(Request $request)
    {
        return User::with('role')->paginate(5); // ou 10
    }

    // Changement de rôle d'un utilisateur
    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:Admin,Manager,Employe',
        ]);

        $role = Role::where('name', $validated['role'])->first();

        if (!$role) {
            return response()->json(['error' => 'Rôle invalide'], 422);
        }

        $user->role_id = $role->id;
        $user->save();

        return response()->json(['message' => 'Rôle mis à jour']);
    }

        // ✅ Créer un utilisateur test avec rôle au choix
    public function createTestUser(Request $request)
    {
        $request->validate([
            'role' => 'required|in:Admin,Manager,Employe',
        ]);

        $role = Role::where('name', $request->role)->firstOrFail();

        $user = User::create([
            'name' => 'Test ' . ucfirst($role->name) . ' ' . now()->format('His'),
            'email' => strtolower($role->name) . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        return response()->json(['message' => 'Utilisateur test créé', 'user' => $user], 201);
    }

    public function createTestRHUser()
    {
        $existing = \App\Models\User::where('email', 'rh@example.com')->first();
        if ($existing) {
            return response()->json(['message' => 'Le compte RH existe déjà.'], 200);
        }

        $role = \App\Models\Role::where('name', 'RH')->first();
        if (!$role) {
            return response()->json(['error' => 'Le rôle RH n\'existe pas'], 422);
        }

        $user = \App\Models\User::create([
            'name' => 'RH Test',
            'email' => 'rh@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        return response()->json(['message' => 'Compte RH créé avec succès', 'user' => $user], 201);
    }

    // 🗑 Supprimer un utilisateur
    public function deleteUser(User $user)
    {
        // Protection : empêcher suppression de soi-même
        if (auth()->id() === $user->id) {
            return response()->json(['error' => 'Impossible de se supprimer soi-même'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    // ♻️ Réinitialiser les utilisateurs test (email contenant "test")
    public function resetTestUsers()
    {
        $deleted = User::where('email', 'ilike', '%test%')->delete();
        return response()->json(['message' => "$deleted utilisateur(s) test supprimé(s)"]);
    }

}
