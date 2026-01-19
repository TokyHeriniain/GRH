<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Services\LeaveBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;

class CloturePdfController extends Controller
{
    public function export(int $annee)
    {
        $service = app(LeaveBalanceService::class);
        $rows = [];

        foreach (Personnel::all() as $p) {
            $solde = $service->getSoldeAnnuelAu31Decembre($p, $annee);
            if ($solde === null) continue;

            $rows[] = [
                'matricule' => $p->matricule,
                'nom'       => "{$p->nom} {$p->prenom}",
                'solde'     => $solde,
                'report'    => min($solde, 60),
                'perte'     => max(0, $solde - 60),
                'n1'        => min($solde, 60) + 30,
            ];
        }

        return Pdf::loadView('pdf.cloture-rh', [
            'annee' => $annee,
            'rows'  => $rows,
        ])->download("Cloture_RH_{$annee}.pdf");
    }
}
