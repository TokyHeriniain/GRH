<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use App\Services\LeaveBalanceService;
use App\Exports\HistoriqueCongesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\LeaveBalance;

class LeaveController extends Controller
{
    public function __construct(protected LeaveService $service) {}

    // Liste congés
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $query = Leave::with(['personnel', 'leaveType'])->orderBy('date_debut', 'desc');

        if ($request->search) {
            $query->whereHas('personnel', fn($q) =>
                $q->where('matricule', 'like', "%{$request->search}%")
                  ->orWhere('nom', 'like', "%{$request->search}%")
                  ->orWhere('prenom', 'like', "%{$request->search}%")
            );
        }

        $leaves = $query->paginate($perPage);

        return response()->json($leaves);
    }

    // Création congé
    public function store(Request $request)
    {
        $data = $request->validate([
            'personnel_id' => 'required|exists:personnels,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'heure_debut' => 'required',
            'heure_fin' => 'required',
            'raison' => 'nullable|string',
            'jours_utilises' => 'required|numeric',
        ]);

        $leave = $this->service->create($data);

        return response()->json(['message' => 'Congé créé', 'data' => $leave]);
    }

    // Update congé
    public function update(Request $request, Leave $leave)
    {
        $data = $request->validate([
            'personnel_id' => 'sometimes|exists:personnels,id',
            'leave_type_id' => 'sometimes|exists:leave_types,id',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
            'heure_debut' => 'sometimes',
            'heure_fin' => 'sometimes',
            'raison' => 'nullable|string',
            'jours_utilises' => 'sometimes|numeric',
        ]);

        $updated = $this->service->update($leave, $data);

        return response()->json(['message' => 'Congé mis à jour', 'data' => $updated]);
    }

    // Suppression
    public function destroy(Leave $leave)
    {
        $this->service->delete($leave);
        return response()->json(['message' => 'Congé supprimé']);
    }

    // Validation RH
    public function approveRH(Leave $leave)
    {
        $updated = $this->service->validateRH($leave, auth()->id());
        return response()->json(['message' => 'Congé validé RH', 'data' => $updated]);
    }

    // Rejet RH
    public function rejectRH(Request $request, Leave $leave)
    {
        $updated = $this->service->rejectRH($leave, auth()->id(), $request->rejection_reason ?? null);
        return response()->json(['message' => 'Congé rejeté RH', 'data' => $updated]);
    }    

    /**
    * HISTORIQUE
     */
    private function historiqueQuery(Request $request)
    {
        return Leave::with(['personnel', 'leaveType'])
            ->when($request->personnel_id, fn($q) =>
                $q->where('personnel_id', $request->personnel_id)
            )
            ->when($request->leave_type_id, fn($q) =>
                $q->where('leave_type_id', $request->leave_type_id)
            )
            ->when($request->status, fn($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->date_debut, fn($q) =>
                $q->whereDate('date_debut', '>=', $request->date_debut)
            )
            ->when($request->date_fin, fn($q) =>
                $q->whereDate('date_fin', '<=', $request->date_fin)
            )
            ->orderBy('created_at', 'desc');
    }

    public function historique(Request $request)
    {
        return response()->json(
            $this->historiqueQuery($request)
                ->paginate($request->get('per_page', 10))
        );
    }

    public function exportHistoriqueExcel(Request $request)
    {
        $data = $this->historiqueQuery($request)->get();

        return \Excel::download(
            new HistoriqueCongesExport($request->all()),
            'historique_conges.xlsx'
        );
    }       
    
    public function exportHistoriquePdf(Request $request)
    {
        $data = $this->historiqueQuery($request)->get();

        $pdf = Pdf::loadView('pdf.historique_conges', [
            'leaves' => $data,
            'dateExport' => now()->format('d/m/Y H:i'),
        ])->setPaper('A4', 'landscape');

        return $request->preview
            ? $pdf->stream('apercu_historique_conges.pdf')
            : $pdf->download('historique_conges_' . now()->format('Ymd_His') . '.pdf');
    }   
    
    // Employé – Mes congés
    public function mesConges(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->personnel_id) {
            return response()->json([
                'message' => 'Aucun personnel associé à votre compte.'
            ], 403);
        }

        return Leave::with(['leaveType'])
            ->where('personnel_id', $user->personnel_id)
            ->orderBy('date_debut', 'desc')
            ->paginate(10);
    }

    public function storeEmploye(Request $request, LeaveService $service)
    {
        $user = auth()->user();

        // 🔒 Sécurité : l'utilisateur doit être lié à un personnel
        if (!$user || !$user->personnel_id) {
            return response()->json([
                'message' => 'Aucun personnel associé à votre compte. Veuillez contacter l’administrateur.'
            ], 403);
        }

        // ✅ Validation basique (SANS jours_utilises)
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'date_debut'    => 'required|date',
            'date_fin'      => 'required|date|after_or_equal:date_debut',
            'heure_debut'   => 'required',
            'heure_fin'     => 'required',
            'raison'        => 'nullable|string|max:500',
        ]);

        // 🔒 Personnel imposé côté backend
        $data['personnel_id'] = $user->personnel_id;
        $data['status'] = 'en_attente';

        $type = LeaveType::findOrFail($data['leave_type_id']);

        // 🔢 Calcul OFFICIEL des jours (backend)
        $jours = $service->getDaysForLeave($data, $type);

        if ($jours <= 0) {
            return response()->json([
                'message' => 'La durée du congé est invalide.'
            ], 422);
        }

        /* =========================================================
        | CONTRÔLES MÉTIER AVANT CRÉATION (UX + SÉCURITÉ)
        ========================================================= */

        // 🟠 CONGÉS EXCEPTIONNELS (ex: Déménagement, Mariage, Décès…)
        if ($type->est_exceptionnel && $type->limite_jours !== null) {

            $dejaPris = Leave::where('personnel_id', $user->personnel_id)
                ->where('leave_type_id', $type->id)
                ->where('status', 'approuve_rh')
                ->sum('jours_utilises');

            if (($dejaPris + $jours) > $type->limite_jours) {
                return response()->json([
                    'message' => "Quota dépassé pour le congé « {$type->nom} »"
                ], 422);
            }
        }

        // 🟡 BILLET / PERMISSION (limite annuelle stricte, ex: 3 jours)
        if ($type->code === 'ANNUEL' && $type->limite_jours !== null) {

            $dejaPris = Leave::where('personnel_id', $user->personnel_id)
                ->where('leave_type_id', $type->id)
                ->where('status', 'approuve_rh')
                ->sum('jours_utilises');

            if (($dejaPris + $jours) > $type->limite_jours) {
                return response()->json([
                    'message' => "Quota annuel dépassé pour le congé « {$type->nom} »"
                ], 422);
            }
        }

        /* =========================================================
        | CRÉATION VIA LE SERVICE MÉTIER
        ========================================================= */

        $leave = $service->create([
            ...$data,
            'jours_utilises' => $jours, // 🔒 valeur calculée backend
        ]);

        return response()->json([
            'message' => 'Demande de congé envoyée avec succès',
            'leave'   => $leave
        ], 201);
    }


    public function annulerEmploye(Leave $leave)
    {
        $user = auth()->user();

        // 🔐 sécurité
        if ($leave->personnel_id !== $user->personnel_id) {
            abort(403, "Action non autorisée");
        }

        if ($leave->status !== 'en_attente') {
            return response()->json([
                'message' => 'Impossible d’annuler une demande déjà traitée'
            ], 422);
        }

        $leave->update([
            'status' => 'annule',
        ]);                     

        return response()->json([
            'message' => 'Demande annulée avec succès'
        ]);
    }

    public function soldesEmploye(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->personnel_id) {
            return response()->json([
                'message' => 'Aucun personnel associé à votre compte.'
            ], 403);
        }

        $personnel = $user->personnel;
        $annee = (int) $request->input('annee', now()->year);

        $balance = LeaveBalance::where('personnel_id', $personnel->id)
            ->where('annee_reference', $annee)
            ->first();

        if (!$balance) {
            return response()->json([
                'personnel' => $personnel,
                'annee' => $annee,
                'has_balance' => false,
                'soldes' => [],
            ]);
        }

        $leaveTypes = LeaveType::all()->keyBy('id');

        $soldes = collect($balance->soldes_par_type ?? [])
            ->map(function ($data, $typeId) use ($leaveTypes, $balance) {

                $type = $leaveTypes[$typeId] ?? null;
                if (!$type) return null;

                /* ================= EXCEPTIONNELS ================= */
                if ($type->est_exceptionnel) {

                    $droitTotal = $type->limite_jours ?? 0;
                    $utilises   = $data['utilises'] ?? 0;

                    return [
                        'leave_type_id'    => $type->id,
                        'leave_type'       => $type->nom,
                        'code'             => $type->code,
                        'avec_solde'       => false,
                        'est_exceptionnel' => true,
                        'limite_jours'     => $type->limite_jours,
                        'droit_total'      => round($droitTotal, 2),
                        'jours_utilises'   => round($utilises, 2),
                        'solde_restant'    => round(max($droitTotal - $utilises, 0), 2),
                    ];
                }

                /* ================= AVEC SOLDE (ANNUEL + BILLET) ================= */
                if ($type->avec_solde) {

                    return [
                        'leave_type_id'    => $type->id,
                        'leave_type'       => $type->nom,
                        'code'             => $type->code,
                        'avec_solde'       => true,
                        'est_exceptionnel' => false,
                        'limite_jours'     => $type->limite_jours,
                        'droit_total'      => round($balance->solde_global_jours, 2),
                        'jours_utilises'   => round($data['utilises'] ?? 0, 2),
                        'solde_restant'    => round($balance->solde_global_restant, 2),
                    ];
                }

                /* ================= SANS SOLDE ================= */
                return [
                    'leave_type_id'    => $type->id,
                    'leave_type'       => $type->nom,
                    'code'             => $type->code,
                    'avec_solde'       => false,
                    'est_exceptionnel' => false,
                    'limite_jours'     => null,
                    'droit_total'      => 0,
                    'jours_utilises'   => 0,
                    'solde_restant'    => 0,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'personnel' => [
                'id' => $personnel->id,
                'matricule' => $personnel->matricule,
                'nom' => $personnel->nom,
                'prenom' => $personnel->prenom,
            ],
            'annee' => $annee,
            'has_balance' => true,
            'soldes' => $soldes,
        ]);
    }
    public function soldeGlobal(Request $request)
    {
        $balance = LeaveBalance::where('personnel_id', auth()->user()->personnel_id)
            ->where('annee_reference', (int) $request->input('annee', now()->year))
            ->firstOrFail();

        return response()->json([
            'solde_global_restant' => round($balance->solde_global_restant, 2)
        ]);
    }

}
