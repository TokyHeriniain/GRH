<?php

namespace App\Exports;

use App\Models\Leave;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HistoriqueCongesExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = Leave::with(['personnel', 'leaveType', 'personnel.leaveBalance'])
            ->orderBy('date_debut', 'desc');

        if (!empty($this->filters['personnel_id'])) {
            $query->where('personnel_id', $this->filters['personnel_id']);
        }

        if (!empty($this->filters['leave_type_id'])) {
            $query->where('leave_type_id', $this->filters['leave_type_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['date_debut']) && !empty($this->filters['date_fin'])) {
            $query->whereBetween('date_debut', [
                $this->filters['date_debut'],
                $this->filters['date_fin']
            ]);
        }

        return $query->get()->map(function ($c) {
            $balance = $c->personnel?->leaveBalance;

            return [
                $c->personnel?->matricule,
                $c->personnel?->nom . ' ' . $c->personnel?->prenom,
                $c->leaveType?->nom,
                $c->date_debut,
                $c->date_fin,
                round($balance->solde_global_jours ?? 0, 2),
                round($c->jours_utilises ?? 0, 2),
                round($balance->solde_global_restant ?? 0, 2),
                $c->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Personnel',
            'Type de congé',
            'Date début',
            'Date fin',
            'Droit total',
            'Jours utilisés',
            'Solde restant',
            'Statut',
        ];
    }
}
