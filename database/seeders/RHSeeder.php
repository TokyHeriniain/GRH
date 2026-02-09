<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RHSeeder extends Seeder
{
    public function run()
    {
        $role = Role::firstOrCreate(['name' => 'Admin']);

        User::firstOrCreate(
            ['email' => 'toky@nyhavana.mg'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role_id' => $role->id,
            ]
        );
    }
}
