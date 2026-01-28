<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN = TOUT
        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->permissions()->sync(
                Permission::pluck('id')
            );
        }

        // RH
        $rh = Role::where('name', 'RH')->first();
        if ($rh) {
            $rh->permissions()->sync(
                Permission::whereIn('name', [
                    'leaves.view',
                    'leaves.approve',
                    'leaves.reject',
                    'audit.view',
                ])->pluck('id')
            );
        }

        // MANAGER
        $manager = Role::where('name', 'Manager')->first();
        if ($manager) {
            $manager->permissions()->sync(
                Permission::whereIn('name', [
                    'leaves.view',
                    'leaves.approve',
                ])->pluck('id')
            );
        }

        // EMPLOYÉ
        $employe = Role::where('name', 'Employe')->first();
        if ($employe) {
            $employe->permissions()->sync(
                Permission::whereIn('name', [
                    'leaves.create',
                    'leaves.view',
                ])->pluck('id')
            );
        }
    }
}
