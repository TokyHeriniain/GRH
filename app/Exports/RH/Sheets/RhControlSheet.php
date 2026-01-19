<?php

namespace App\Exports\Rh\Sheets;

use App\Models\Leave;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithTitle,
    ShouldAutoSize
};

class RhControlSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private int $annee) {}

    public function title(): string
    {
        return 'Contrôle RH';
    }

    public function collection()
    {
        return Leave::with('personnel')
            ->whereYear('date_debut', $this->annee)
            ->where('status', 'approuve_rh')
            ->get()
            ->filter(fn ($l) =>
                $l->solde_restant < 0 ||
                $l->jours_utilises > $l->droit_total
            )
            ->map(fn ($l) => [
                $l->personnel->matricule ?? '',
                $l->personnel->nom ?? '',
                $l->personnel->prenom ?? '',
                round((float)$l->jours_utilises, 2),
                round((float)$l->droit_total, 2),
                round((float)$l->solde_restant, 2),
                $l->solde_restant < 0 ? 'Solde négatif' : 'Utilisé > droit',
            ]);
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prenom',
            'Jours utilisés',
            'Droit total',
            'Solde restant',
            'Anomalie',
        ];
    }
}
