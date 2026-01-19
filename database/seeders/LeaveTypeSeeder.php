<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // --- Congé annuel classique ---
            [
                'nom' => 'Congé annuel',
                'avec_solde' => true,
                'limite_jours' => null,
                'est_exceptionnel' => false,
                'description' => 'Congé payé réglementaire (2.5 jours/mois).'
            ],

            // --- Congés exceptionnels ---
            ['nom' => 'Mariage de l’employé', 'avec_solde' => true, 'limite_jours' => 13, 'est_exceptionnel' => true, 'description' => 'Congé exceptionnel pour le mariage du salarié.'],
            ['nom' => 'Mariage des descendants', 'avec_solde' => true, 'limite_jours' => 2, 'est_exceptionnel' => true, 'description' => 'Mariage d’un enfant du salarié.'],
            ['nom' => 'Mariage des frères et sœurs', 'avec_solde' => true, 'limite_jours' => 1, 'est_exceptionnel' => true, 'description' => 'Mariage des frères ou sœurs du salarié ou du conjoint.'],
            ['nom' => 'Naissance d’un enfant', 'avec_solde' => true, 'limite_jours' => 2, 'est_exceptionnel' => true, 'description' => 'Congé de paternité.'],
            ['nom' => 'Circoncision d’un enfant', 'avec_solde' => true, 'limite_jours' => 2, 'est_exceptionnel' => true, 'description' => 'Congé pour circoncision.'],
            ['nom' => 'Décès conjoint ou enfant', 'avec_solde' => true, 'limite_jours' => 5, 'est_exceptionnel' => true, 'description' => 'Décès du conjoint ou d’un enfant.'],
            ['nom' => 'Décès parent du salarié ou du conjoint', 'avec_solde' => true, 'limite_jours' => 3, 'est_exceptionnel' => true, 'description' => 'Décès père ou mère.'],
            ['nom' => 'Décès frère ou sœur', 'avec_solde' => true, 'limite_jours' => 2, 'est_exceptionnel' => true, 'description' => 'Décès frère ou sœur.'],
            ['nom' => 'Exhumation', 'avec_solde' => true, 'limite_jours' => 2, 'est_exceptionnel' => true, 'description' => 'Exhumation du conjoint, parent ou enfant.'],
            ['nom' => 'Déménagement', 'avec_solde' => true, 'limite_jours' => 1, 'est_exceptionnel' => true, 'description' => 'Congé pour déménagement.'],
            ['nom' => 'Chirurgie conjoint/enfant', 'avec_solde' => true, 'limite_jours' => 2, 'est_exceptionnel' => true, 'description' => 'Intervention chirurgicale du conjoint ou de l’enfant.'],
            ['nom' => 'Hospitalisation enfant/conjoint', 'avec_solde' => true, 'limite_jours' => 2, 'est_exceptionnel' => true, 'description' => 'Hospitalisation.'],
            ['nom' => 'Examens professionnels', 'avec_solde' => true, 'limite_jours' => 1, 'est_exceptionnel' => true, 'description' => 'Veille et jour d’examen.'],

            // --- Congés sans solde ---
            ['nom' => 'Maladie', 'avec_solde' => false, 'limite_jours' => null, 'est_exceptionnel' => false, 'description' => 'Repos maladie sans solde.'],
            ['nom' => 'Congé de maternité', 'avec_solde' => false, 'limite_jours' => null, 'est_exceptionnel' => false, 'description' => 'Congé maternité.'],
            ['nom' => 'Assistance maternelle', 'avec_solde' => false, 'limite_jours' => null, 'est_exceptionnel' => false, 'description' => 'Assistance à l’enfant.'],
            ['nom' => 'Billet / Petite permission', 'avec_solde' => false, 'limite_jours' => 3, 'est_exceptionnel' => true, 'description' => 'Absence courte (max. 3 jours).'],

            // --- Congé générique sans solde ---
            ['nom' => 'Sans solde', 'avec_solde' => false, 'limite_jours' => null, 'est_exceptionnel' => false, 'description' => 'Congé exceptionnel sans solde.'],
        ];

        foreach ($types as $type) {
            LeaveType::create($type);
        }
    }
}
