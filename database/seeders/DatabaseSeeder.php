<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Créer les rôles
        $roles = ['Admin', 'Manager', 'Employe', 'RH'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Crée un admin s'il n'existe pas déjà
        if (!User::where('email', 'admin@nyhavana.mg')->exists()) {
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@nyhavana.mg',
                'password' => bcrypt('tsyfatako'),
                'role_id' => Role::where('name', 'Admin')->first()->id,
            ]);
        }

        // 👉 Appeler ici tous les autres seeders
        $this->call([
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
