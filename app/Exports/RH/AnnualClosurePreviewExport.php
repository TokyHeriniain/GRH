<?php

namespace App\Exports\Rh;

use App\Models\Personnel;
use App\Services\LeaveBalanceService;
use Maatwebsite\Excel\Concerns\{
    FromCollection, WithHeadings, ShouldAutoSize
};

class AnnualClosurePreviewExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(private int $annee) {}

    public function collection()
    {
        $service = app(LeaveBalanceService::class);
        $rows = [];

        foreach (Personnel::all() as $p) {
            $solde = $service->getSoldeAnnuelAu31Decembre($p, $this->annee);
            if ($solde === null) continue;

            $report = min($solde, 60);

            $rows[] = [
                $p->matricule,
                "{$p->nom} {$p->prenom}",
                round($solde, 2),
                round($report, 2),
                round(max(0, $solde - 60), 2),
                round($report + 30, 2),
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Solde au 31/12',
            'Report autorisé',
            'Perte',
            'Solde N+1',
        ];
    }
}
