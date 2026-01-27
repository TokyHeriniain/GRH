<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\LeaveType;
use Carbon\Carbon;

class HolidayService
{
    /**
     * Nombre de jours fériés entre deux dates (inclus)
     */
    public function countBetween(string $dateDebut, string $dateFin): int
    {
        return Holiday::whereBetween('date', [$dateDebut, $dateFin])->count();
    }

    /**
     * Liste des jours fériés entre deux dates
     * @return array ['2026-01-01', '2026-05-01', ...]
     */
    public function datesBetween(string $dateDebut, string $dateFin): array
    {
        return Holiday::whereBetween('date', [$dateDebut, $dateFin])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();
    }

    /**
     * Le type de congé exclut-il les jours fériés ?
     */
    public function shouldExclude(LeaveType $type): bool
    {
        // Exemple RH : exclut les jours fériés pour congés avec solde mais pas exceptionnel
        return $type->avec_solde && !$type->est_exceptionnel;
    }
}
