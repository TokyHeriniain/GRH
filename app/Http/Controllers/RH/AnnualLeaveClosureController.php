<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Services\AnnualLeaveClosureService;
use App\Models\AnnualLeaveClosure;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnnualLeaveClosureExport;
use PDF;


class AnnualLeaveClosureController extends Controller
{
    public function status(int $annee, AnnualLeaveClosureService $service)
    {
        return response()->json(
            $service->getStatus($annee)
        );
    }

    public function showClosed(int $annee)
    {
        $rows = AnnualLeaveClosure::with('personnel.direction')
            ->where('annee', $annee)
            ->orderBy('personnel_id')
            ->get()
            ->map(function ($c) {
                return [
                    'personnel'      => "{$c->personnel->matricule} {$c->personnel->nom} {$c->personnel->prenom}",
                    'direction'       => optional($c->personnel->direction)->nom,
                    'solde_actuel'   => $c->solde_avant,
                    'report'         => $c->report,
                    'perte'          => $c->perte,
                    'nouveau_solde'  => $c->solde_n_plus_1,
                    'validated_at'   => $c->validated_at,
                    'validated_by'   => $c->validated_by,
                ];
            });

        return response()->json($rows);
    }

    // Liste des années clôturées
    public function historique()
    {
        $annees = AnnualLeaveClosure::select('annee')
            ->distinct()
            ->orderByDesc('annee')
            ->pluck('annee');

        return response()->json($annees);
    }

    // Détails d’une année
    public function historiqueDetail(int $annee)
    {
        $data = AnnualLeaveClosure::with('personnel')
            ->where('annee', $annee)
            ->orderBy('personnel_id')
            ->get()
            ->map(function($row) {
                return [
                    'personnel'     => $row->personnel->nom . ' ' . $row->personnel->prenom,
                    'solde_avant'   => round($row->solde_avant, 2),
                    'report'        => round($row->report, 2),
                    'perte'         => round($row->perte, 2),
                    'solde_n_plus_1'=> round($row->solde_n_plus_1, 2),
                    'validated_by'  => $row->validated_by,
                    'validated_at'  => $row->validated_at,
                ];
            });

        return response()->json($data);
    }

    public function exportExcel(int $annee)
    {
        $rows = AnnualLeaveClosure::with('personnel')
            ->where('annee', $annee)
            ->orderBy('personnel_id')
            ->get()
            ->map(function ($c) {
                return [
                    'Personnel'      => "{$c->personnel->matricule} {$c->personnel->nom} {$c->personnel->prenom}",
                    'Solde'          => $c->solde_avant,
                    'Report'         => $c->report,
                    'Perte'          => $c->perte,
                    'Solde_N_plus_1' => $c->solde_n_plus_1,
                    'Valide_le'      => optional($c->validated_at)
                                        ? $c->validated_at->timezone('Indian/Antananarivo')->format('d/m/Y H:i')
                                        : null,
                    'RH'             => $c->validated_by,
                ];
            });

        return Excel::download(new AnnualLeaveClosureExport($rows), "cloture_{$annee}.xlsx");
    }

    public function exportPDF(int $annee)
    {
        $rows = AnnualLeaveClosure::with('personnel')
            ->where('annee', $annee)
            ->orderBy('personnel_id')
            ->get()
            ->map(function ($c) {
                return [
                    'Matricule'      => "{$c->personnel->matricule}",
                    'Personnel'      => "{$c->personnel->nom} {$c->personnel->prenom}",
                    'Solde'          => $c->solde_avant,
                    'Report'         => $c->report,
                    'Perte'          => $c->perte,
                    'Solde_N_plus_1' => $c->solde_n_plus_1,
                    'Valide_le'      => optional($c->validated_at)
                                        ? $c->validated_at->timezone('Indian/Antananarivo')->format('d/m/Y H:i')
                                        : null,
                    'RH'             => $c->validated_by,
                ];
            });

        $pdf = PDF::loadView('pdf.cloture-rh', ['rows' => $rows, 'annee' => $annee]);
        return $pdf->download("cloture_{$annee}.pdf");
    }
}

