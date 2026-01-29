<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            /*
            |----------------------------------------------------------------------
            | AUTH / PROFIL
            |----------------------------------------------------------------------
            */
            ['name' => 'profile.view',    'module' => 'profile', 'action' => 'view'],
            ['name' => 'profile.update',  'module' => 'profile', 'action' => 'update'],

            /*
            |----------------------------------------------------------------------
            | STATS
            |----------------------------------------------------------------------
            */
            ['name' => 'stats.view', 'module' => 'stats', 'action' => 'view'],

            /*
            |----------------------------------------------------------------------
            | PERSONNELS
            |----------------------------------------------------------------------
            */
            ['name' => 'personnels.view',   'module' => 'personnels', 'action' => 'view'],
            ['name' => 'personnels.create', 'module' => 'personnels', 'action' => 'create'],
            ['name' => 'personnels.update', 'module' => 'personnels', 'action' => 'update'],
            ['name' => 'personnels.delete', 'module' => 'personnels', 'action' => 'delete'],
            ['name' => 'personnels.import', 'module' => 'personnels', 'action' => 'import'],
            ['name' => 'personnels.export', 'module' => 'personnels', 'action' => 'export'],

            /*
            |----------------------------------------------------------------------
            | RÉFÉRENTIELS (Directions / Services / Fonctions)
            |----------------------------------------------------------------------
            */
            ['name' => 'referentiels.view',   'module' => 'referentiels', 'action' => 'view'],
            ['name' => 'referentiels.manage', 'module' => 'referentiels', 'action' => 'manage'],

            /*
            |----------------------------------------------------------------------
            | SOLDES & HISTORIQUES
            |----------------------------------------------------------------------
            */
            ['name' => 'soldes.view', 'module' => 'soldes', 'action' => 'view'],

            // Ancienne (conservée)
            ['name' => 'historiques.view', 'module' => 'historiques', 'action' => 'view'],

            // Nouvelle (alignée routes récentes)
            ['name' => 'conges.historique.view', 'module' => 'conges', 'action' => 'historique'],

            /*
            |----------------------------------------------------------------------
            | CONGÉS
            |----------------------------------------------------------------------
            */
            ['name' => 'leaves.view',    'module' => 'leaves', 'action' => 'view'],
            ['name' => 'leaves.create',  'module' => 'leaves', 'action' => 'create'],
            ['name' => 'leaves.update',  'module' => 'leaves', 'action' => 'update'],
            ['name' => 'leaves.delete',  'module' => 'leaves', 'action' => 'delete'],
            ['name' => 'leaves.approve', 'module' => 'leaves', 'action' => 'approve'],
            ['name' => 'leaves.reject',  'module' => 'leaves', 'action' => 'reject'],
            ['name' => 'leaves.export',  'module' => 'leaves', 'action' => 'export'],

            /*
            |----------------------------------------------------------------------
            | RH – RELIQUATS
            |----------------------------------------------------------------------
            */
            ['name' => 'rh.reliquats',        'module' => 'rh', 'action' => 'reliquats'],
            ['name' => 'rh.reliquats.export', 'module' => 'rh', 'action' => 'export_reliquats'],

            /*
            |----------------------------------------------------------------------
            | RH – DASHBOARD
            |----------------------------------------------------------------------
            */
            ['name' => 'rh.dashboard', 'module' => 'rh', 'action' => 'dashboard'],

            /*
            |----------------------------------------------------------------------
            | RH – AUDIT
            |----------------------------------------------------------------------
            */
            ['name' => 'audit.view',   'module' => 'audit', 'action' => 'view'],
            ['name' => 'audit.export', 'module' => 'audit', 'action' => 'export'],

            /*
            |----------------------------------------------------------------------
            | RH – CLÔTURE ANNUELLE
            |----------------------------------------------------------------------
            */
            ['name' => 'rh.closure', 'module' => 'rh', 'action' => 'closure'],

            /*
            |----------------------------------------------------------------------
            | JOURS FÉRIÉS
            |----------------------------------------------------------------------
            */
            ['name' => 'holidays.manage', 'module' => 'holidays', 'action' => 'manage'],

            /*
            |----------------------------------------------------------------------
            | ADMIN – UTILISATEURS / RÔLES / PERMISSIONS
            |----------------------------------------------------------------------
            */
            ['name' => 'users.manage',       'module' => 'users', 'action' => 'manage'],
            ['name' => 'roles.view',         'module' => 'roles', 'action' => 'view'],
            ['name' => 'roles.update',       'module' => 'roles', 'action' => 'update'],
            ['name' => 'permissions.manage', 'module' => 'permissions', 'action' => 'manage'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
