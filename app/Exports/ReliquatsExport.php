<?php

namespace App\Exports;

use App\Models\LeaveBalance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReliquatsExport implements FromCollection, WithHeadings
{
    private $directionId;

    public function __construct($directionId = null)
    {
        $this->directionId = $directionId;
    }

    public function collection()
    {
        $query = LeaveBalance::query()
            ->join('personnels', 'leave_balances.personnel_id', '=', 'personnels.id')
            ->join('directions', 'personnels.direction_id', '=', 'directions.id')
            ->select(
                'directions.nom as direction',
                'personnels.matricule',
                'personnels.nom',
                'personnels.prenom',
                'leave_balances.solde_annuel_jours as reliquat'
            );

        if ($this->directionId) {
            $query->where('directions.id', $this->directionId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Direction',
            'Matricule',
            'Nom',
            'Prénom',
            'Reliquat (jours)'
        ];
    }
}
