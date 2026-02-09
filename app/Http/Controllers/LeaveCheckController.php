<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use LogicException;

class LeaveCheckController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    /**
     * Vérifie le solde d'un congé avant enregistrement.
     */
    public function check(Request $request)
    {
        // 🔒 Récupération de l'utilisateur connecté
        $user = auth()->user();
        if (!$user || !$user->personnel_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Aucun personnel associé à votre compte.'
            ], 403);
        }

        // Validation des champs côté frontend
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'date_debut'    => 'required|date',
            'date_fin'      => 'required|date',
            'heure_debut'   => 'required',
            'heure_fin'     => 'required',
        ]);

        // ⚠️ Ajout automatique du personnel_id
        $data['personnel_id'] = $user->personnel_id;

        try {
            // Création d'un faux congé (non sauvegardé)
            $leave = new Leave($data);

            // 🔹 Calcul des jours utilisés
            $type = LeaveType::findOrFail($data['leave_type_id']);
            $leave->jours_utilises = $this->leaveService->getDaysForLeave($data, $type);

            // ⚡ Associer le type pour les règles
            $leave->setRelation('leaveType', $type);

            // 🔹 Vérification centrale du solde (même règles que RH)
            $this->leaveService->checkSoldeDisponibleForLeave($leave);

            return response()->json([
                'ok' => true,
                'jours_demandes' => $leave->jours_utilises,
                'message' => 'Solde suffisant'
            ]);

        } catch (LogicException $e) {
            return response()->json([
                'ok' => false,
                'jours_demandes' => $leave->jours_utilises ?? 0,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
