<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        // 🔒 Sécurité : utilisateur non connecté
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // 🔒 Sécurité : utilisateur sans rôle
        if (!$user->role) {
            return response()->json(['message' => 'Aucun rôle attribué'], 403);
        }

        // 📦 Permissions du rôle
        $userPermissions = $user->role
            ->permissions
            ->pluck('name')
            ->toArray();

        // ✅ Vérifie si au moins une permission correspond
        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return $next($request);
            }
        }

        // ❌ Accès refusé
        return response()->json([
            'message' => 'Permission insuffisante',
            'required' => $permissions,
        ], 403);
    }
}
