<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\LeaveBalance;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PersonnelCongeController extends Controller
{
    /**
     * Historique des congés d’un personnel
     */
    public function index(Request $request, Personnel $personnel)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // ================= FILTRES =================
        $query = Leave::with(['leaveType', 'validatedBy'])
            ->where('personnel_id', $personnel->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_debut', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_fin', '<=', $request->date_fin);
        }

        // ================= PAGINATION =================
        $total = $query->count();
        $conges = $query
            ->orderBy('date_debut', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function ($leave) {
                $impactedHolidays = [];

                // 🔹 Calcul des jours fériés impactés
                if ($leave->leaveType->avec_solde && !$leave->leaveType->est_exceptionnel) {
                    $impactedHolidays = Holiday::whereBetween(
                        'date',
                        [$leave->date_debut, $leave->date_fin]
                    )
                    ->get()
                    ->map(fn ($h) => [
                        'title' => $h->title,
                        'date' => Carbon::parse($h->date)->format('Y-m-d'),
                    ])
                    ->toArray();
                }

                return [
                    'id' => $leave->id,
                    'leave_type' => [
                        'nom' => $leave->leaveType->nom,
                    ],
                    'date_debut' => $leave->date_debut,
                    'date_fin' => $leave->date_fin,
                    'heure_debut' => $leave->heure_debut,
                    'heure_fin' => $leave->heure_fin,
                    'jours_utilises' => round($leave->jours_utilises, 2),
                    'status' => $leave->status,
                    'created_at' => $leave->created_at,
                    'updated_at' => $leave->updated_at,
                    'validated_by' => $leave->validatedBy
                        ? ['name' => $leave->validatedBy->name]
                        : null,
                    'impacted_holidays' => $impactedHolidays,
                ];
            });

        // ================= SOLDES PAR TYPE =================
        $soldes = Leave::where('personnel_id', $personnel->id)
            ->where('status', 'approuve_rh')
            ->with('leaveType')
            ->get()
            ->groupBy('leave_type_id')
            ->map(function ($leaves, $leaveTypeId) use ($personnel) {
                $leaveType = $leaves->first()->leaveType;
                $joursUtilises = $leaves->sum('jours_utilises');

                // 🔹 Congé annuel
                if ($leaveType->code === 'ANNUEL') {
                    $balance = LeaveBalance::where('personnel_id', $personnel->id)->first();
                    $droitTotal = $balance->solde_global_jours ?? 0;
                    $soldeRestant = $droitTotal - $joursUtilises;
                } else {
                    // 🔹 Congés exceptionnels
                    $droitTotal = $leaveType->limite_jours ?? 0;
                    $soldeRestant = $droitTotal - $joursUtilises;
                }

                return [
                    'leave_type_id' => $leaveTypeId,
                    'leave_type'   => $leaveType->nom,
                    'droit_total'  => round($droitTotal, 2),
                    'jours_utilises' => round($joursUtilises, 2),
                    'solde_restant' => round($soldeRestant, 2),
                ];
            })
            ->values(); // reset keys

        return response()->json([
            'personnel' => [
                'id' => $personnel->id,
                'matricule' => $personnel->matricule,
                'nom' => $personnel->nom,
                'prenom' => $personnel->prenom,
            ],
            'soldes' => $soldes,
            'conges' => $conges,
            'total' => $total,
        ]);
    }
}
