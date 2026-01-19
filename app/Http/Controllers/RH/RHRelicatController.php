<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\Direction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReliquatsParDirectionExport;
use Barryvdh\DomPDF\Facade\Pdf;

class RHRelicatController extends Controller
{
    /**
     * 📊 Liste des reliquats par direction (JSON)
     */
    public function index(int $annee)
    {
        $directions = Direction::with(['personnels.leaveBalance'])
            ->get()
            ->map(function ($direction) {
                $personnels = $direction->personnels
                    ->filter(fn ($p) => $p->leaveBalance)
                    ->map(function ($p) {
                        return [
                            'matricule' => $p->matricule,
                            'nom'       => $p->nom,
                            'prenom'    => $p->prenom,
                            'reliquat'  => round($p->leaveBalance->solde_global_jours, 2),
                        ];
                    });

                return [
                    'direction' => $direction->nom,
                    'total_reliquat' => round(
                        $personnels->sum('reliquat'), 2
                    ),
                    'personnels' => $personnels->values(),
                ];
            });

        return response()->json($directions);
    }

    /**
     * 📊 Export Excel
     */
    public function exportExcel(int $annee)
    {
        return Excel::download(
            new ReliquatsParDirectionExport($annee),
            "reliquats_conges_{$annee}.xlsx"
        );
    }

    /**
     * 📄 Export PDF
     */
    public function exportPdf(int $annee)
    {
        $data = $this->index($annee)->getData();

        $pdf = Pdf::loadView(
            'pdf.reliquats-par-direction',
            [
                'annee' => $annee,
                'directions' => $data,
            ]
        )->setPaper('A4', 'portrait');

        return $pdf->download("reliquats_conges_{$annee}.pdf");
    }
}
