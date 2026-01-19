<?php

namespace App\Services;

use App\Models\AnnualLeaveClosure;

class AnnualLeaveClosureService
{
    public function isClosed(int $annee): bool
    {
        return AnnualLeaveClosure::where('annee', $annee)->exists();
    }

    public function getStatus(int $annee): array
    {
        $query = AnnualLeaveClosure::where('annee', $annee);

        if (!$query->exists()) {
            return [
                'annee'        => $annee,
                'closed'       => false,
                'closed_at'    => null,
                'closed_by'    => null,
                'total_agents' => 0,
            ];
        }

        // 🔹 Toutes les lignes ont le même validated_at / validated_by
        $first = $query->first();

        return [
            'annee'        => $annee,
            'closed'       => true,
            'closed_at'    => $first->validated_at,
            'closed_by'    => $first->validated_by,
            'total_agents' => $query->count(),
        ];
    }
}
