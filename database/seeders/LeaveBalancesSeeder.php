<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Personnel;
use App\Models\LeaveType;
use App\Models\LeaveBalance;

class LeaveBalancesSeeder extends Seeder
{
    public function run()
    {
        $types = LeaveType::all();
        Personnel::chunk(200, function($personnels) use ($types) {
            foreach ($personnels as $p) {
                foreach ($types as $t) {
                    // logique par défaut : exemples
                    $default = 0;
                    if ($t->code === 'ANNUEL') {
                        $default = 30.00; // exemple 30 jours par an (adapter)
                    } elseif ($t->is_exceptionnel ?? false) {
                        $default = 10.00; // ou selon type
                    } elseif ($t->code === 'MARIAGE') {
                        $default = 13.00;
                    }
                    LeaveBalance::updateOrCreate(
                        ['personnel_id' => $p->id, 'leave_type_id' => $t->id],
                        [
                            'droit_total' => number_format($default, 2, '.', ''),
                            'jours_utilises' => 0.00,
                            'solde_restant' => number_format($default, 2, '.', ''),
                        ]
                    );
                }
            }
        });
    }
}
