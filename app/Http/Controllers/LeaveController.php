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

        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'heure_debut' => 'required',
            'heure_fin' => 'required',
            'jours_utilises' => 'required|numeric|min:0.01',
            'raison' => 'nullable|string|max:500',
        ]);

        // ✅ Personnel imposé côté backend
        $data['personnel_id'] = $user->personnel_id;
        $data['status'] = 'en_attente';

        // ✅ Passe par toute la logique métier (soldes, règles, etc.)
        $leave = $service->create($data);

        return response()->json([
            'message' => 'Demande envoyée avec succès',
            'leave' => $leave
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

     /**
     * Retourne les soldes d'un employé connecté pour une année donnée (par défaut année courante)
     */
    public function soldesEmploye(Request $request)
    {
        $user = auth()->user();

        // 🔒 Vérification : l'utilisateur doit être lié à un personnel
        if (!$user || !$user->personnel_id) {
            return response()->json([
                'message' => 'Aucun personnel associé à votre compte. Veuillez contacter l’administrateur.'
            ], 403);
        }

        $personnel = $user->personnel;
        $year = $request->input('annee', now()->year);

        // 1️⃣ Tous les types de congés
        $leaveTypes = LeaveType::all();

        // 2️⃣ Solde annuel / global pour l'année de référence
        $balance = LeaveBalance::where('personnel_id', $personnel->id)
            ->where('annee_reference', $year)
            ->first();

        // 3️⃣ Jours utilisés par type_id (optimisation)
        $usedByType = Leave::where('personnel_id', $personnel->id)
            ->where('status', 'approuve_rh')
            ->whereYear('date_debut', $year)
            ->groupBy('leave_type_id')
            ->selectRaw('leave_type_id, SUM(jours_utilises) as total')
            ->pluck('total', 'leave_type_id');

        // 4️⃣ Calcul des soldes
        $soldes = $leaveTypes->map(function ($type) use ($balance, $usedByType) {

            $joursUtilises = $usedByType[$type->id] ?? 0;

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
            'has_balance' => (bool)$balance,
            'soldes' => $soldes
        ]);
    }
    /**
     * 🔐 Politique RH centralisée
     */
    private function getDroitTotal($type, $balance)
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
