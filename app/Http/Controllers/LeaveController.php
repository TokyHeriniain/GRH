<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use App\Services\LeaveBalanceService;
use App\Exports\HistoriqueCongesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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

}
