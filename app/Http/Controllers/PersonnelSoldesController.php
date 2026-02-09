<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class PersonnelSoldesController extends Controller
{
    public function index(Request $request, Personnel $personnel)
    {
        $annee = (int) $request->input('annee', now()->year);

        $balance = LeaveBalance::where('personnel_id', $personnel->id)
            ->where('annee_reference', $annee)
            ->first();

        if (!$balance) {
            return response()->json([
                'personnel' => [
                    'id'        => $personnel->id,
                    'matricule' => $personnel->matricule,
                    'nom'       => $personnel->nom,
                    'prenom'    => $personnel->prenom,
                ],
                'annee'       => $annee,
                'has_balance' => false,
                'soldes'      => [],
            ]);
        }

        $types = LeaveType::all()->keyBy('id');
        $soldesParType = collect($balance->soldes_par_type ?? []);

        $soldes = $soldesParType
            ->map(function ($data, $typeId) use ($types, $balance) {

                $type = $types->get((int) $typeId);
                if (!$type) return null;

                $joursUtilises = (float) ($data['utilises'] ?? 0);

                // 🟢 CONGÉS EXCEPTIONNELS
                if ($type->est_exceptionnel) {
                    $droitTotal = (float) ($type->limite_jours ?? 0);
                    $soldeRestant = max($droitTotal - $joursUtilises, 0);
                }

                // 🔵 CONGÉS AVEC SOLDE PARTAGÉ (ANNUEL + BILLET)
                elseif ($type->avec_solde && $type->code === 'ANNUEL') {
                    $droitTotal   = (float) $balance->solde_global_jours;     // 🔑 TOUJOURS 9
                    $soldeRestant = (float) $balance->solde_global_restant;  // 🔑 4 après consommation
                }

                // ⚪ AUTRES TYPES
                else {
                    $droitTotal = 0;
                    $soldeRestant = 0;
                }

                return [
                    'leave_type_id'    => (int) $typeId,
                    'leave_type'       => $type->nom,
                    'code'             => $type->code,
                    'avec_solde'       => (bool) $type->avec_solde,
                    'est_exceptionnel' => (bool) $type->est_exceptionnel,
                    'limite_jours'     => $type->limite_jours,
                    'droit_total'      => round($droitTotal, 2),
                    'jours_utilises'   => round($joursUtilises, 2),
                    'solde_restant'    => round($soldeRestant, 2),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'personnel' => [
                'id'        => $personnel->id,
                'matricule' => $personnel->matricule,
                'nom'       => $personnel->nom,
                'prenom'    => $personnel->prenom,
            ],
            'annee'       => $annee,
            'has_balance' => true,
            'soldes'      => $soldes,
        ]);
    }
}
