<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\LeaveBalance;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Reliquats\ReliquatsSheetExport;
use App\Exports\Reliquats\ReliquatsMultiSheetExport;

class ReliquatController extends Controller
{
    public function index(Request $request)
    {
        $annee = now()->year;

        $query = LeaveBalance::with([
            'personnel.direction',
            'personnel.service'
        ])->where('annee_reference', $annee);

        // ================= FILTRES =================
        if ($request->filled('direction')) {
            $query->whereHas('personnel.direction', fn ($q) =>
                $q->where('nom', $request->direction)
            );
        }

        if ($request->filled('service')) {
            $query->whereHas('personnel.service', fn ($q) =>
                $q->where('nom', $request->service)
            );
        }

        if ($request->filled('search')) {
            $s = '%' . strtolower($request->search) . '%';
            $query->whereHas('personnel', fn ($q) =>
                $q->whereRaw('LOWER(nom) LIKE ?', [$s])
                ->orWhereRaw('LOWER(prenom) LIKE ?', [$s])
                ->orWhereRaw('LOWER(matricule) LIKE ?', [$s])
            );
        }

        $rows = $query->get();

        // ================= MAPPING =================
        $data = $rows->map(fn ($b) => [
            'matricule' => $b->personnel->matricule,
            'personnel' => "{$b->personnel->nom} {$b->personnel->prenom}",
            'direction' => optional($b->personnel->direction)->nom,
            'service'   => optional($b->personnel->service)->nom,
            'reliquat'  => round($b->solde_annuel_jours, 2),
        ]);

        // ================= TOTAUX =================
        $totaux = [
            'agents'     => $data->count(),
            'reliquats'  => round($data->sum('reliquat'), 2),
            'parDirection' => $data
                ->groupBy('direction')
                ->map(fn ($g) => [
                    'agents' => $g->count(),
                    'reliquat' => round($g->sum('reliquat'), 2),
                ]),
            'parService' => $data
                ->groupBy('service')
                ->map(fn ($g) => [
                    'agents' => $g->count(),
                    'reliquat' => round($g->sum('reliquat'), 2),
                ]),
        ];

        return response()->json([
            'annee'  => $annee,
            'data'   => $data,
            'totaux' => $totaux,
        ]);
    }


    // 📄 EXPORT EXCEL
    // ReliquatController.php
    public function exportExcel(Request $request, LeaveBalanceService $service)
    {
        $annee = now()->year;

        $response = $this->index($request, $service)->getData(true);
        $rows = collect($response['data'] ?? []);

        if ($rows->isEmpty()) {
            abort(404, 'Aucune donnée à exporter');
        }

        return Excel::download(
            new ReliquatsSheetExport(
                $rows,
                $annee,
                'Reliquats'
            ),
            "reliquats_{$annee}.xlsx"
        );
    }


    // 📄 EXPORT PDF
    public function exportPdf(Request $request, LeaveBalanceService $service)
    {
        $annee = now()->year;

        $rows = $this->index($request, $service)->getData()->data;

        if (empty($rows)) {
            abort(404, 'Aucune donnée à exporter');
        }

        $pdf = Pdf::loadView('pdf.reliquats', [
            'annee' => $annee,
            'rows'  => $rows,
        ])->setPaper('a4', 'portrait');

        // 👁️ MODE PREVIEW
        if ($request->boolean('preview')) {
            return $pdf->stream('reliquats_preview.pdf');
        }

        // 📥 MODE DOWNLOAD
        return $pdf->download(
            'reliquats_' . now()->format('Ymd_His') . '.pdf'
        );
    }


    public function exportExcelMultiSheet(Request $request)
    {
        $annee = now()->year;

        $query = LeaveBalance::with([
            'personnel.direction',
            'personnel.service',
        ])->where('annee_reference', $annee);

        // ================= FILTRES =================
        if ($request->filled('direction')) {
            $query->whereHas('personnel.direction', fn ($q) =>
                $q->where('nom', $request->direction)
            );
        }

        if ($request->filled('service')) {
            $query->whereHas('personnel.service', fn ($q) =>
                $q->where('nom', $request->service)
            );
        }

        if ($request->filled('search')) {
            $s = '%' . strtolower($request->search) . '%';
            $query->whereHas('personnel', fn ($q) =>
                $q->whereRaw('LOWER(nom) LIKE ?', [$s])
                ->orWhereRaw('LOWER(prenom) LIKE ?', [$s])
                ->orWhereRaw('LOWER(matricule) LIKE ?', [$s])
            );
        }

        // ================= MAPPING =================
        $rows = $query->get()->map(fn ($b) => [
            'matricule' => $b->personnel->matricule,
            'personnel' => "{$b->personnel->nom} {$b->personnel->prenom}",
            'direction' => optional($b->personnel->direction)->nom ?? '—',
            'service'   => optional($b->personnel->service)->nom ?? '—',
            'reliquat'  => round($b->solde_annuel_jours, 2),
        ]);

        if ($rows->isEmpty()) {
            abort(404, 'Aucune donnée à exporter');
        }

        // ================= GROUP BY DIRECTION =================
        $grouped = $rows->groupBy('direction');

        return Excel::download(
            new ReliquatsMultiSheetExport($grouped, $annee),
            "reliquats_{$annee}_par_direction.xlsx"
        );
    }


}
