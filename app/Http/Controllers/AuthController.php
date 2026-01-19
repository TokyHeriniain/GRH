<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\personnel;
use App\Models\role;

class AuthController extends Controller
{    

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ]);

        // Récupérer rôle Employé
        $roleId = \App\Models\Role::where('name', 'Employe')->first()?->id;
        if (!$roleId) {
            return response()->json(['message' => 'Rôle Employe introuvable.'], 500);
        }

        // Créer l’utilisateur
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $roleId,
        ]);

        // Génération d’un matricule unique : EMP + date + compteur
        $date = now()->format('Ymd');
        $countToday = \App\Models\Personnel::whereDate('created_at', today())->count() + 1;
        $matricule = "EMP{$date}-" . str_pad($countToday, 3, '0', STR_PAD_LEFT);

        // Séparer nom et prénom si possible
        $parts = explode(' ', $validated['name'], 2);
        $nom = $parts[0];
        $prenom = $parts[1] ?? '-';

        // Création du Personnel minimal
        $personnel = \App\Models\Personnel::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'matricule' => $matricule,
            'user_id' => $user->id,
            // Les champs optionnels resteront NULL
        ]);

        // Associer user -> personnel
        $personnel->user_id = $user->id;
        $personnel->save();

        Auth::login($user);

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $user->load('role', 'personnel')
        ]);
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        $request->session()->regenerate(); // Protection contre les attaques de session fixation

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => Auth::user()->load('role'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnecté']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()?->load('role'));
    }
}
