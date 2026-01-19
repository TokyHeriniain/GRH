<?php

// app/Exports/LeavesExport.php
namespace App\Exports;

use App\Models\Leave;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;

class LeavesExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Leave::with(['personnel.direction', 'leaveType'])->orderBy('date_debut', 'desc');

        if (!empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['search'])) {
            $search = strtolower($this->filters['search']);
            $query->whereHas('personnel', function ($q) use ($search) {
                $q->whereRaw('LOWER(nom) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(prenom) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->get()->map(function ($leave) {
            return [
                'Matricule' => $leave->personnel->matricule ?? '',
                'Nom' => $leave->personnel->nom ?? '',
                'Prénom' => $leave->personnel->prenom ?? '',
                'Direction' => $leave->personnel->Direction->nom ?? '',
                'Type' => $leave->leaveType->nom ?? '',
                'Date début' => $leave->date_debut,
                'Heure début' => $leave->heure_debut,
                'Date fin' => $leave->date_fin,
                'Heure fin' => $leave->heure_fin,
                'Jours utilisés' => round($leave->jours_utilises, 2),
                'Droit total' => round($leave->droit_total, 2),
                'Solde restant' => round($leave->solde_restant, 2),
                'Statut' => $leave->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Matricule', 'Nom', 'Prénom', 'Direction', 'Type', 'Date début', 'Heure début',
            'Date fin', 'Heure fin', 'Jours utilisés', 'Droit total',
            'Solde restant', 'Statut'
        ];
    }
}
