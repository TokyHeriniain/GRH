<?php

namespace App\Exports;

use App\Models\Personnel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PersonnelsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Personnel::with(['direction', 'service', 'fonction'])->get()->map(function ($p) {
            return [
                'Nom' => $p->nom,
                'Prénom' => $p->prenom,
                'Matricule' => $p->matricule,
                'CIN' => $p->cin,
                'Adresse' => $p->adresse,
                'Diplôme' => $p->diplome,
                'Date de naissance' => $p->date_naissance,
                'Date d\'entrée' => $p->date_entree,
                'Direction' => $p->direction->nom ?? '',
                'Service' => $p->service->nom ?? '',
                'Fonction' => $p->fonction->nom ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nom', 'Prénom', 'Matricule', 'CIN', 'Adresse',
            'Diplôme', 'Date de naissance', 'Date d\'entrée',
            'Direction', 'Service', 'Fonction',
        ];
    }
}
