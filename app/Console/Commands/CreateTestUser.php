<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    protected $signature = 'test:user {role=Employe} {--email=test@example.com} {--name=TestUser} {--password=password}';

    protected $description = 'Créer un utilisateur de test avec un rôle spécifique';

    public function handle()
    {
        $roleName = ucfirst(strtolower($this->argument('role')));
        $email = $this->option('email');
        $name = $this->option('name');
        $password = $this->option('password');

        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Rôle '$roleName' introuvable.");
            return 1;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("Un utilisateur avec l'email $email existe déjà.");
            return 1;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
        ]);

        $this->info("✅ Utilisateur $name ($roleName) créé avec succès.");
        return 0;
    }
}
