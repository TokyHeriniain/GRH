<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |----------------------------------------------------------------------
        | ADMIN = TOUT
        |----------------------------------------------------------------------
        */
        if ($admin = Role::where('name', 'Admin')->first()) {
            $admin->permissions()->sync(
                Permission::pluck('id')
            );
        }

        /*
        |----------------------------------------------------------------------
        | RH
        |----------------------------------------------------------------------
        */
        if ($rh = Role::where('name', 'RH')->first()) {
            $rh->permissions()->sync(
                Permission::whereIn('name', [

                    // Profil
                    'profile.view',
                    'profile.update',

                    // Stats
                    'stats.view',

                    // Personnels
                    'personnels.view',
                    'personnels.create',
                    'personnels.update',
                    'personnels.import',
                    'personnels.export',

                    // Soldes & historiques
                    'soldes.view',
                    'historiques.view',
                    'conges.historique.view',

                    // Congés
                    'leaves.view',
                    'leaves.approve',
                    'leaves.reject',
                    'leaves.export',

                    // RH
                    'rh.dashboard',
                    'rh.closure',
                    'rh.reliquats',
                    'rh.reliquats.export',

                    // Audit
                    'audit.view',
                    'audit.export',

                ])->pluck('id')
            );
        }

        /*
        |----------------------------------------------------------------------
        | MANAGER
        |----------------------------------------------------------------------
        */
        if ($manager = Role::where('name', 'Manager')->first()) {
            $manager->permissions()->sync(
                Permission::whereIn('name', [

                    // Profil
                    'profile.view',

                    // Personnels
                    'personnels.view',

                    // Congés
                    'leaves.view',
                    'leaves.approve',

                    // Soldes & historiques
                    'soldes.view',
                    'conges.historique.view',
                    // espace employé
                    'employee.dashboard',
                    'employee.leaves.view',
                    'employee.soldes.view',


                ])->pluck('id')
            );
        }

        /*
        |----------------------------------------------------------------------
        | EMPLOYÉ
        |----------------------------------------------------------------------
        */
        if ($employe = Role::where('name', 'Employe')->first()) {
            $employe->permissions()->sync(
                Permission::whereIn('name', [

                    'profile.view',
                    'profile.update',

                    // espace employé
                    'employee.dashboard',
                    'employee.leaves.view',
                    'employee.leaves.create',
                    'employee.leaves.cancel',
                    'employee.soldes.view',

                ])->pluck('id')
            );
        }
    }
}
