<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use App\Models\LeaveBalance;

class PersonnelSoldesController extends Controller
{
    /**
     * Retourne les soldes d'un employé pour une année donnée (par défaut année courante)
     */
    public function index(Request $request, Personnel $personnel)
    {
        $year = $request->input('annee', now()->year);

        // 1️⃣ Tous les types de congés
        $leaveTypes = LeaveType::all();

        // 2️⃣ Solde annuel / global pour l'année de référence
        $balance = LeaveBalance::where('personnel_id', $personnel->id)
            ->where('annee_reference', $year)
            ->first();

        // 3️⃣ Jours utilisés par code (pour gérer Congé annuel + Billet/Permission)
        $usedLeaves = Leave::where('personnel_id', $personnel->id)
            ->where('status', 'approuve_rh')
            ->whereYear('date_debut', $year)
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->groupBy('leave_types.code')
            ->selectRaw('leave_types.code, SUM(jours_utilises) as total')
            ->pluck('total', 'code');

        // 4️⃣ Calcul des soldes
        $soldes = $leaveTypes->map(function ($type) use ($balance, $usedLeaves) {

            // Pour les types avec solde partagé (annuel, billet/permission), on prend le total par code
            if ($type->avec_solde) {
                $joursUtilises = isset($usedLeaves[$type->code]) ? (float)$usedLeaves[$type->code] : 0;
            } else {
                // Pour les types exceptionnels, on prend le total par type_id
                $joursUtilises = Leave::where('personnel_id', $balance->personnel_id ?? 0)
                    ->where('leave_type_id', $type->id)
                    ->where('status', 'approuve_rh')
                    ->whereYear('date_debut', $balance->annee_reference ?? $year)
                    ->sum('jours_utilises');
            }

            // Droit total selon le type
            $droitTotal = $this->getDroitTotal($type, $balance);

            // Solde restant (peut être négatif)
            $soldeRestant = ($type->avec_solde || $type->est_exceptionnel)
                ? ($droitTotal - $joursUtilises)
                : 0;

            // Limite pour Billet/Permission seulement
            $limite = $type->limite_jours ?? 0;

            return [
                'leave_type_id'     => $type->id,
                'leave_type'        => $type->nom,
                'code'              => $type->code,
                'est_exceptionnel'  => $type->est_exceptionnel,
                'droit_total'       => round($droitTotal, 2),
                'jours_utilises'    => round($joursUtilises, 2),
                'solde_restant'     => round($soldeRestant, 2),
                'limite_jours'      => $limite,
            ];
        });

        return response()->json([
            'personnel' => [
                'id'        => $personnel->id,
                'matricule' => $personnel->matricule,
                'nom'       => $personnel->nom,
                'prenom'    => $personnel->prenom,
            ],
            'annee' => (int)$year,
            'soldes' => $soldes
        ]);
    }

    /**
     * 🔐 Politique RH centralisée
     */
    private function getDroitTotal(LeaveType $type, ?LeaveBalance $balance)
    {
        if (!$balance) return 0;

        // Congés exceptionnels → limite fixe
        if ($type->est_exceptionnel) {
            return $type->limite_jours;           
        }

        // Congé annuel ou Billet/Permission → droit annuel partagé
        if ($type->avec_solde) {
            return (float) $balance->solde_annuel_jours;
        }

        return 0;
    }
}
