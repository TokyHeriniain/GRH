<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Congés
            ['name' => 'leaves.create',  'module' => 'leaves', 'action' => 'create'],
            ['name' => 'leaves.view',    'module' => 'leaves', 'action' => 'view'],
            ['name' => 'leaves.approve', 'module' => 'leaves', 'action' => 'approve'],
            ['name' => 'leaves.reject',  'module' => 'leaves', 'action' => 'reject'],

            // Utilisateurs
            ['name' => 'users.manage',   'module' => 'users', 'action' => 'manage'],

            // Audit
            ['name' => 'audit.view',     'module' => 'audit', 'action' => 'view'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']], // clé unique
                $permission
            );
        }
    }
}
