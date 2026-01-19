<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\Personnel;
use App\Models\Leave;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReliquatsExport;
use App\Services\LeaveBalanceService;
use App\Models\AnnualLeaveClosure;

class ClotureCongeController extends Controller
{
    /* =====================================================
       🟡 AVANT CLÔTURE (calcul dynamique)
    ===================================================== */
    public function reliquatsAvantCloture(Request $request)
    {
        $annee = now()->year;

        $rows = Personnel::with('direction')
            ->when($request->direction_id, fn ($q) =>
                $q->where('direction_id', $request->direction_id)
            )
            ->get()
            ->map(function ($p) use ($annee) {

                // 🔹 CAS 1 : solde importé (vérité RH)
                $imported = LeaveBalance::where('personnel_id', $p->id)
                    ->whereNull('annee_reference')
                    ->first();

                if ($imported) {
                    $solde = $imported->solde_global_jours;
                } else {
                    // 🔹 CAS 2 : nouvel agent (fallback)
                    $droit = 30;

                    $pris = Leave::where('personnel_id', $p->id)
                        ->whereYear('date_debut', $annee)
                        ->where('status', 'approuve_rh')
                        ->sum('jours_utilises');

                    $solde = $droit - $pris;
                }

                return [
                    'direction' => $p->direction->nom,
                    'direction_id' => $p->direction_id,
                    'matricule' => $p->matricule,
                    'nom' => $p->nom,
                    'prenom' => $p->prenom,
                    'solde_global_jours' => round($solde, 2),
                ];
            })
            ->groupBy('direction');

        return response()->json([
            'annee' => $annee,
            'date_export' => now()->format('d/m/Y'),
            'directions' => $rows
        ]);
    }



    /* =====================================================
       🟢 APRÈS CLÔTURE (figé RH)
    ===================================================== */
    private function baseQueryApresCloture(int $annee, ?int $directionId = null)
    {
        $query = AnnualLeaveClosure::query()
            ->join('personnels', 'annual_leave_closures.personnel_id', '=', 'personnels.id')
            ->join('directions', 'personnels.direction_id', '=', 'directions.id')
            ->where('annual_leave_closures.annee', $annee)
            ->select(
                'directions.id as direction_id',
                'directions.nom as direction',
                'personnels.matricule',
                'personnels.nom',
                'personnels.prenom',
                'annual_leave_closures.solde_avant as solde_global_jours'
            )
            ->orderBy('directions.nom')
            ->orderBy('personnels.nom');

        if ($directionId) {
            $query->where('directions.id', $directionId);
        }

        return $query;
    }


    public function reliquatsApresCloture(Request $request)
    {
        $annee = $request->annee ?? now()->year - 1; // 🔴 IMPORTANT

        if (!AnnualLeaveClosure::where('annee', $annee)->exists()) {
            return response()->json([
                'annee' => $annee,
                'date_export' => now()->format('d/m/Y'),
                'directions' => []
            ]);
        }

        $rows = $this->baseQueryApresCloture($annee, $request->direction_id)
            ->get()
            ->groupBy('direction');

        return response()->json([
            'annee' => $annee,
            'date_export' => now()->format('d/m/Y'),
            'directions' => $rows
        ]);
    }




    /* =====================================================
       📄 EXPORTS OFFICIELS
    ===================================================== */
    public function exportPdf(Request $request)
    {
        $annee = $request->annee ?? now()->year;

        $data = $this->baseQueryApresCloture($annee, $request->direction_id)
            ->get()
            ->groupBy('direction');

        $pdf = Pdf::loadView('exports.reliquats_pdf', [
            'date' => now()->format('d/m/Y'),
            'annee' => $annee,
            'directions' => $data
        ])->setPaper('a4', 'portrait');

        return $pdf->download("reliquats_conges_{$annee}.pdf");
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(
            new ReliquatsExport(now()->year, $request->direction_id),
            'reliquats_conges.xlsx'
        );
    }
}
