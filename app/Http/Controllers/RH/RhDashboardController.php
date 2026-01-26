<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\AnnualLeaveClosure;
use App\Models\Leave;
use App\Models\Personnel;
use Illuminate\Support\Facades\DB;

class RhDashboardController extends Controller
{
    public function index()
    {
        $annee = now()->year;

        return response()->json([
            'indicateurs' => [
                'total_conges' => Leave::count(),
                'approuves' => Leave::where('status', 'approuve_rh')->count(),
                'refuses' => Leave::where('status', 'rejete')->count(),
                'en_attente' => Leave::where('status', 'en_attente')->count(),
                'taux_approbation' => Leave::count() > 0
                    ? round(
                        Leave::where('status', 'approuve_rh')->count()
                        / Leave::count() * 100,
                        2
                    )
                    : 0,
            ],

            'repartition_mensuelle' => Leave::selectRaw('
                    EXTRACT(MONTH FROM date_debut) as mois,
                    COUNT(*) as total
                ')
                ->whereYear('date_debut', $annee)
                ->groupBy(DB::raw('EXTRACT(MONTH FROM date_debut)'))
                ->orderBy('mois')
                ->get(),

            'repartition_par_type' => Leave::join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
                ->selectRaw('leave_types.nom as type, COUNT(*) as total')
                ->groupBy('leave_types.nom')
                ->get(),
        ]);
    }


    /* ================= MÉTHODES RH ================= */

    private function tauxApprobation($annee)
    {
        $total = Leave::whereYear('date_debut', $annee)->count();

        if ($total === 0) {
            return 0;
        }

        $approuves = Leave::where('status', 'approuve')
            ->whereYear('date_debut', $annee)
            ->count();

        return round(($approuves / $total) * 100, 2);
    }

    private function repartitionParType($annee)
    {
        return Leave::select('leave_type_id', DB::raw('count(*) as total'))
            ->whereYear('date_debut', $annee)
            ->groupBy('leave_type_id')
            ->with('leaveType:id,nom')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->leaveType->nom ?? 'Inconnu',
                'total' => $row->total,
            ]);
    }

    private function repartitionMensuelle($annee)
    {
        return Leave::select(
                DB::raw('EXTRACT(MONTH FROM date_debut) as mois'),
                DB::raw('count(*) as total')
            )
            ->whereYear('date_debut', $annee)
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();
    }

    

    public function comparatif()
    {
        $anneeN = now()->year;
        $anneeN1 = $anneeN - 1;

        $stats = function ($annee) {
            return [
                'total_conges' => Leave::whereYear('date_debut', $annee)->count(),

                'jours_utilises' => Leave::whereYear('date_debut', $annee)
                    ->where('status', 'approuve_rh')
                    ->sum('jours_utilises'),

                'pertes' => AnnualLeaveClosure::where('annee', $annee)
                    ->sum('perte'),
            ];
        };

        $n = $stats($anneeN);
        $n1 = $stats($anneeN1);

        return response()->json([
            'annee_n' => $anneeN,
            'annee_n1' => $anneeN1,

            'data' => [
                'conges' => [
                    'n' => $n['total_conges'],
                    'n1' => $n1['total_conges'],
                    'evolution' => $this->evolution($n1['total_conges'], $n['total_conges']),
                ],
                'jours' => [
                    'n' => round($n['jours_utilises'], 2),
                    'n1' => round($n1['jours_utilises'], 2),
                    'evolution' => $this->evolution($n1['jours_utilises'], $n['jours_utilises']),
                ],
                'pertes' => [
                    'n' => round($n['pertes'], 2),
                    'n1' => round($n1['pertes'], 2),
                    'evolution' => $this->evolution($n1['pertes'], $n['pertes']),
                ],
            ],
        ]);
    }

    /* ======================
    CALCUL EVOLUTION %
    ====================== */
    private function evolution($ancien, $nouveau)
    {
        if ($ancien == 0) {
            return $nouveau > 0 ? 100 : 0;
        }

        return round((($nouveau - $ancien) / $ancien) * 100, 2);
    }

}
