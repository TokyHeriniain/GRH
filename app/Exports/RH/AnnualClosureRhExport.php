<?php

namespace App\Exports\Rh;

use App\Models\AnnualLeaveClosure;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithProperties
};

class AnnualClosureRhExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithProperties
{
    public function __construct(private int $annee) {}

    public function collection()
    {
        return AnnualLeaveClosure::with(['personnel', 'validator'])
            ->where('annee', $this->annee)
            ->orderBy('personnel_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Solde avant clôture',
            'Report',
            'Perte',
            'Solde N+1',
            'Validé par',
            'Date validation',
        ];
    }

    public function map($row): array
    {
        return [
            $row->personnel->matricule,
            "{$row->personnel->nom} {$row->personnel->prenom}",
            $row->solde_avant,
            $row->report,
            $row->perte,
            $row->solde_n_plus_1,
            $row->validator->name ?? 'RH',
            $row->validated_at->format('d/m/Y H:i'),
        ];
    }

    public function properties(): array
    {
        return [
            'title'       => "Clôture annuelle RH {$this->annee}",
            'description' => "Export officiel RH – soldes figés avant/après clôture",
            'creator'     => 'Système RH',
            'company'     => config('app.name'),
        ];
    }
}
