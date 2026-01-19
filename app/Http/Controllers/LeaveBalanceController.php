<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Services\LeaveBalanceService;
use App\Services\LeaveAuditService;
use App\Models\Personnel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AnnualLeaveClosure;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class LeaveBalanceController extends Controller
{
    protected $service;

    public function __construct(LeaveBalanceService $service)
    {
        $this->service = $service;
    }

    /**
     * ➤ Retourne le solde d’un personnel
     */
    public function show($personnelId)
    {
        $balance = LeaveBalance::where('personnel_id', $personnelId)->first();

        if (!$balance) {
            // On crée automatiquement le solde si absent
            $balance = $this->service->recalculateForPersonnelAndType($personnelId);
        }

        return response()->json([
            'message' => 'Solde récupéré',
            'data' => $balance
        ]);
    }

    

    public function previewCloture(int $annee)
    {
        // 🔒 INTERDICTION preview si année déjà clôturée
        if (AnnualLeaveClosure::where('annee', $annee)->exists()) {
            return response()->json([
                'message' => "Année {$annee} déjà clôturée"
            ], 409);
        }

        $personnels = Personnel::with('direction')->get();     
        $service = app(LeaveBalanceService::class);

        $data = [];

        foreach ($personnels as $p) {

            $soldeActuel = $service->getSoldeAnnuelAu31Decembre($p, $annee);

            if ($soldeActuel === null) {
                continue;
            }
            $dateDebutAnnee = Carbon::create($annee, 1, 1);
            $report = min($soldeActuel, 60);
            $perte  = max(0, $soldeActuel - 60);

            // ✅ DROIT AUX 30 JOURS UNIQUEMENT SI PRÉSENT AU 01/01/N
            $aDroit30Jours = $p->date_entree
                && Carbon::parse($p->date_entree)->lte($dateDebutAnnee);

            $soldeN1 = $report + ($aDroit30Jours ? 30 : 0);

            $data[] = [
                'personnel_id'   => $p->id,
                'personnel'      => "{$p->matricule} {$p->nom} {$p->prenom}",
                'direction'      => $p->direction->nom,
                'direction_id'   => $p->direction_id,
                'solde_actuel'   => round($soldeActuel, 2),
                'report'         => round($report, 2),
                'perte'          => round($perte, 2),
                'nouveau_solde'  => round($soldeN1, 2),
            ];
        }

        return response()->json([
            'annee' => $annee,
            'total_personnels' => count($data),
            'data' => $data
        ]);
    }


    public function executeCloture(int $annee)
    {
        DB::transaction(function () use ($annee) {

            if (AnnualLeaveClosure::where('annee', $annee)->exists()) {
                abort(409, "L'année {$annee} est déjà clôturée");
            }

            $this->service->closeAnnualLeave(
                $annee,
                auth()->id()
            );
        });

        app(LeaveAuditService::class)->log(
            'CLOTURE_ANNUELLE_GLOBALE',
            1,
            null,
            [],
            [
                'annee' => $annee,
                'validated_by' => auth()->user()->name ?? 'RH',
                'validated_at' => now()->toIso8601String(),
            ]
        );

        return response()->json([
            'message' => "Clôture annuelle {$annee} effectuée avec succès"
        ]);
    }



    public function journal(int $annee)
    {
        return AnnualLeaveClosure::with(['personnel', 'validator'])
            ->where('annee', $annee)
            ->orderBy('personnel_id')
            ->get();
    }


    public function exportPdf(int $annee)
    {
        $rows = AnnualLeaveClosure::with(['personnel', 'validator'])
            ->where('annee', $annee)
            ->orderBy('personnel_id')
            ->get();

        if ($rows->isEmpty()) {
            abort(404, "Aucune clôture RH pour {$annee}");
        }

        $pdf = Pdf::loadView('pdf.cloture-rh', [
            'annee' => $annee,
            'rows'  => $rows,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("cloture_rh_{$annee}.pdf");
    }

}
