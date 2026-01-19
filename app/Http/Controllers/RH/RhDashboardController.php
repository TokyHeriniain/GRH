<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\AnnualLeaveClosure;
use App\Models\Leave;
use App\Models\Personnel;
use Carbon\Carbon;

class RhDashboardController extends Controller
{
    public function index()
    {
        $annee = now()->year;

        return response()->json([
            'personnels' => [
                'total' => Personnel::count(),
            ],
            'conges' => [
                'total' => Leave::count(),
                'approuves' => Leave::where('status', 'approuve')->count(),
                'refuses' => Leave::where('status', 'refuse')->count(),
                'en_attente'=> Leave::where('status', 'en_attente')->count(),
            ],
            'cloture' => [
                'annees_cloturees' => AnnualLeaveClosure::distinct('annee')->count('annee'),
            ],
            'pertes' => [
                'total_jours_perdus' =>
                    AnnualLeaveClosure::where('annee', now()->year - 1)->sum('perte')
            ],
        ]);
    }
}
