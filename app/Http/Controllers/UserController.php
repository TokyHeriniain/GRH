<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index() {
    return User::with('role')->get();
    }

    public function changeRole(Request $request, $id) {
        $user = User::findOrFail($id);
        $role = Role::where('name', $request->role)->first();
        $user->role()->associate($role);
        $user->save();
        return response()->json(['message' => 'Rôle mis à jour']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required',
            'password' => 'nullable|min:6',
        ]);

        $user->name = $validated['name'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        return response()->json(['message' => 'Profil mis à jour']);
    }
}
