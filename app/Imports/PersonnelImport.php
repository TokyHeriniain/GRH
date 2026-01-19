<?php

namespace App\Imports;

use App\Models\Personnel;
use App\Models\Direction;
use App\Models\Service;
use App\Models\Fonction;
use App\Models\LeaveType;
use App\Models\LeaveBalance; // ton modèle pivot pour les soldes
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PersonnelImport implements ToCollection
{
    public $rapport = [
        'ajoutes' => 0,
        'existant' => 0,
        'erreurs' => [],
        'creations' => [
            'directions' => [],
            'services' => [],
            'fonctions' => [],
        ]
    ];

    /**
     * Convertir un champ Excel en date Y-m-d
     */
    private function excelDateToYmd($value)
    {
        if (empty($value)) {
            return null;
        }

        // Si c’est déjà une date valide
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // Si c’est un nombre (numéro de série Excel)
        if (is_numeric($value)) {
            try {
                return date('Y-m-d', Date::excelToTimestamp($value));
            } catch (\Exception $e) {
                return null;
            }
        }

        // Dernier recours
        try {
            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return null;
        }
    }



    public function collection(Collection $rows)
    {
        $rows->shift();

        foreach ($rows as $index => $row) {
            try {
                $matricule = trim($row[0]);

                if (empty($matricule)) {
                    $this->rapport['erreurs'][] = "Ligne " . ($index + 2) . " : matricule vide.";
                    continue;
                }

                if (Personnel::where('matricule', $matricule)->exists()) {
                    $this->rapport['existant']++;
                    continue;
                }

                // Direction
                $directionName = !empty($row[4]) ? trim($row[4]) : 'NON DEFINI';
                $direction = Direction::firstOrCreate(['nom' => $directionName]);
                if ($direction->wasRecentlyCreated) {
                    $this->rapport['creations']['directions'][] = $direction->nom;
                }

                // Service
                $service = null;
                if (!empty($row[5])) {
                    $service = Service::firstOrCreate(
                        [
                            'nom' => trim($row[5]),
                            'direction_id' => $direction->id
                        ],
                        [
                            'direction_id' => $direction->id
                        ]
                    );
                    if ($service->wasRecentlyCreated) {
                        $this->rapport['creations']['services'][] = $service->nom;
                    }
                }

                // Fonction
                $fonction = null;
                if (!empty($row[6])) {
                    $fonction = Fonction::firstOrCreate(['nom' => trim($row[6])]);
                    if ($fonction->wasRecentlyCreated) {
                        $this->rapport['creations']['fonctions'][] = $fonction->nom;
                    }
                }

                // Création du personnel
                $personnel = Personnel::create([
                    'matricule'      => $matricule,
                    'nom'            => $row[1],
                    'prenom'         => $row[2],
                    'date_entree'    => $this->excelDateToYmd($row[3]),
                    'direction_id'   => $direction?->id,
                    'service_id'     => $service?->id,
                    'fonction_id'    => $fonction?->id,
                    'diplome'        => $row[7],
                    'date_naissance' => $this->excelDateToYmd($row[8]),
                    'adresse'        => $row[9],
                    'cin'            => $row[10],
                ]);

                // ⇩⇩⇩ Import des soldes/droits
                if (!empty($row[11])) {
                    $leaveType = LeaveType::firstOrCreate(
                        ['code' => trim($row[11])],
                        ['nom' => trim($row[11]), 'avec_solde' => true]
                    );

                    LeaveBalance::updateOrCreate(
                        [
                            'personnel_id'  => $personnel->id,
                            'leave_type_id' => $leaveType->id,
                        ],
                        [
                            'droit_total'   => (float)$row[12],
                            'jours_utilises'=> (float)$row[13],
                            'solde_restant' => (float)$row[14],
                        ]
                    );
                }
                // ⇧⇧⇧ Fin bloc solde

                $this->rapport['ajoutes']++;

            } catch (\Exception $e) {
                $this->rapport['erreurs'][] = "Ligne " . ($index + 2) . " : " . $e->getMessage();
            }
        }
}

}
