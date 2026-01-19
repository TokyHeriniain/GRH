<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Direction;
use App\Models\Service;
use App\Models\Fonction;
use App\Models\LeaveType;
use App\Models\Leave;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PersonnelImportController extends Controller
{
    public function preview(Request $request)
    {
        $data = $request->input('data', []);
        $valid = [];
        $errors = [];

        foreach ($data as $index => $row) {
            $rowErrors = [];

            // Vérifications de base
            if (empty($row['matricule'])) {
                $rowErrors[] = 'Matricule manquant';
            } elseif (Personnel::where('matricule', $row['matricule'])->exists()) {
                $rowErrors[] = 'Matricule déjà existant';
            }

            if (empty($row['nom'])) {
                $rowErrors[] = 'Nom manquant';
            }
            if (empty($row['prenom'])) {
                $rowErrors[] = 'Prénom manquant';
            }

            if ($rowErrors) {
                $errors[$index + 1] = $rowErrors; // +1 pour coller au tableau React
            } else {
                $valid[] = $row;
            }
        }

        return response()->json([
            'valid' => $valid,
            'errors' => $errors,
        ]);
    }

    

    public function import(Request $request)
    {
        $data = $request->input('data', []);
        $count = 0;

        DB::beginTransaction();
        try {
            foreach ($data as $row) {
                // Création auto des entités si besoin
                $direction = !empty($row['direction']) 
                    ? Direction::firstOrCreate(['nom' => $row['direction']]) 
                    : null;

                $service = !empty($row['service']) 
                    ? Service::firstOrCreate(['nom' => $row['service']]) 
                    : null;

                $fonction = !empty($row['fonction']) 
                    ? Fonction::firstOrCreate(['nom' => $row['fonction']]) 
                    : null;

                // Création du personnel
                $personnel = Personnel::create([
                    'matricule' => $row['matricule'],
                    'nom' => $row['nom'],
                    'prenom' => $row['prenom'],
                    'date_naissance' => $row['date_naissance'] ? Carbon::parse($row['date_naissance'])->format('Y-m-d') : null,
                    'date_entree' => $row['date_entree'] ? Carbon::parse($row['date_entree'])->format('Y-m-d') : null,
                    'direction_id' => $direction?->id,
                    'service_id' => $service?->id,
                    'fonction_id' => $fonction?->id,
                ]);

                // Ajout du congé initial (si droit_total fourni dans le fichier)
                if (!empty($row['droit_total'])) {
                    // Vérifie si un type spécial "Initialisation" existe, sinon le créer
                    $leaveType = LeaveType::firstOrCreate([
                        'nom' => 'Initialisation'
                    ], [
                        'limite_annuelle' => null
                    ]);

                    Leave::create([
                        'personnel_id' => $personnel->id,
                        'leave_type_id' => $leaveType->id,
                        'date_debut' => $personnel->date_entree ?? Carbon::now(),
                        'date_fin' => $personnel->date_entree ?? Carbon::now(),
                        'jours_utilises' => 0,
                        'droit_total' => (float) $row['droit_total'],
                        'solde_restant' => (float) $row['droit_total'],
                        'status' => 'approuve_rh',
                        'raison' => 'Solde initial importé'
                    ]);
                }

                $count++;
            }

            DB::commit();

            return response()->json([
                'message' => 'Import terminé avec succès',
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l’import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
