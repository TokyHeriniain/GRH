<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use App\Models\LeaveBalance;


class PersonnelSoldesController extends Controller
{
   public function index(Personnel $personnel)
    {
        $leaveTypes = LeaveType::all();

        $balance = LeaveBalance::where('personnel_id', $personnel->id)->first();

        $soldes = $leaveTypes->map(function ($type) use ($personnel, $balance) {

            $joursUtilises = Leave::where('personnel_id', $personnel->id)
                ->where('leave_type_id', $type->id)
                ->where('status', 'approuve_rh')
                ->sum('jours_utilises');

            $droitTotal = $this->getDroitTotal($type, $balance);

            // ✅ SOLDE RESTANT CORRECT
            if ($type->avec_solde) {
                // Congé annuel → déjà déduit à l'import
                $soldeRestant = $balance
                    ? (float) $balance->solde_global_jours
                    : 0;
            } elseif ($type->est_exceptionnel) {
                // Exceptionnel → calcul classique
                $soldeRestant = max(0, $droitTotal - $joursUtilises);
            } else {
                $soldeRestant = 0;
            }

            return [
                'leave_type_id'     => $type->id,
                'leave_type'        => $type->nom,
                'est_exceptionnel'  => $type->est_exceptionnel,
                'droit_total'       => round($droitTotal, 2),
                'jours_utilises'    => round($joursUtilises, 2),
                'solde_restant'     => round($soldeRestant, 2),
            ];
        });
        return response()->json([
            'personnel' => [
                'id'        => $personnel->id,
                'matricule' => $personnel->matricule,
                'nom'       => $personnel->nom,
                'prenom'    => $personnel->prenom,
            ],
            'soldes' => $soldes
        ]);
    }


    /**
     * 🔐 Politique RH centralisée
     */
    private function getDroitTotal(LeaveType $type, ?LeaveBalance $balance)
    {
        if (!$balance) {
            return 0;
        }

        // 🔹 Congés exceptionnels (politique fixe)
        if ($type->est_exceptionnel) {
            /* return match ($type->code ?? null) {
                'MARIAGE_EMPLOYE' => 13,
                default => 15,
            }; */
            $limite = $type->limite_jours;           
            return $limite; 
        }

        // 🔹 Congé annuel → DROIT ANNUEL
        if ($type->avec_solde) {
            return (float) $balance->solde_annuel_jours;
        }

        return 0;
    }

}
