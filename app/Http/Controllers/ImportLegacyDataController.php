<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Personnel;
use App\Models\Direction;
use App\Models\Service;
use App\Models\Fonction;
use App\Models\LeaveBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportLegacyDataController extends Controller
{
    private const ANNEE_IMPORT = 2025;
    public function import(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Aucun fichier fourni'], 400);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, true);

        $importes = 0;
        $erreurs  = [];

        DB::beginTransaction();

        try {

            foreach ($rows as $index => $row) {

                if ($index === 1) continue; // entête

                try {

                    // ==============================
                    // 1️⃣ Données de base
                    // ==============================
                    $matricule     = trim($row['A'] ?? '');
                    $nom           = trim($row['B'] ?? '');
                    $prenom        = trim($row['C'] ?? '');
                    $fonctionNom   = trim($row['D'] ?? '');
                    $serviceNom    = trim($row['E'] ?? '');
                    $directionNom  = trim($row['F'] ?? '');
                    $dateEntreeRaw = $row['G'] ?? null;
                    $droitExcel    = floatval($row['H'] ?? 0);

                    $billet        = floatval($row['I'] ?? 0);
                    $conge         = floatval($row['J'] ?? 0);
                    $dispensaire   = floatval($row['K'] ?? 0);
                    $convention    = floatval($row['L'] ?? 0);

                    $dateNaissanceRaw = $row['M'] ?? null;

                    if (!$matricule || !$nom) {
                        throw new \Exception("Matricule ou nom manquant");
                    }

                    // ==============================
                    // 2️⃣ Référentiels
                    // ==============================
                    $direction = Direction::firstOrCreate([
                        'nom' => $directionNom ?: 'Non spécifiée'
                    ]);

                    $service = Service::firstOrCreate([
                        'nom' => $serviceNom ?: 'Non spécifié',
                        'direction_id' => $direction->id
                    ]);

                    $fonction = Fonction::firstOrCreate([
                        'nom' => $fonctionNom ?: 'Non spécifiée',
                        'service_id' => $service->id
                    ]);

                    // ==============================
                    // 3️⃣ Personnel
                    // ==============================
                    $dateEntree = $this->convertDate($dateEntreeRaw);

                    $personnel = Personnel::updateOrCreate(
                        ['matricule' => $matricule],
                        [
                            'nom'            => $nom,
                            'prenom'         => $prenom,
                            'date_naissance' => $this->convertDate($dateNaissanceRaw),
                            'date_entree'    => $dateEntree,
                            'fonction_id'    => $fonction->id,
                            'service_id'     => $service->id,
                            'direction_id'   => $direction->id,
                        ]
                    );

                    // ==============================
                    // 4️⃣ Calcul du DROIT
                    // ==============================
                    // 🔒 RÈGLE IMPORT LEGACY : si Excel contient une valeur (même négative), on la respecte
                    if ($row['H'] !== null && $row['H'] !== '') {
                        $droit = round((float) $row['H'], 2);
                    } else {
                        if (!$dateEntree) {
                            throw new \Exception("Date d'entrée invalide pour prorata");
                        }

                        $droit = $this->calculateProrataFromDate(
                            Carbon::parse($dateEntree)
                        );
                    }

                    // ==============================
                    // 5️⃣ Jours utilisés
                    // ==============================
                    $joursUtilises = round(
                        $billet + $conge,
                        2
                    );

                    $restant = round($droit - $joursUtilises, 2);

                    // ==============================
                    // 6️⃣ LeaveBalance (INIT)
                    // ==============================
                    LeaveBalance::updateOrCreate(
                        [
                            'personnel_id'    => $personnel->id,
                            'annee_reference' => self::ANNEE_IMPORT, // 🔒 2025 figé
                        ],
                        [
                            // 🔹 DROIT ANNUEL 2025
                            'solde_annuel_jours'  => round($droit, 2),
                            'solde_annuel_heures' => round($droit * 8, 2),

                            // 🔹 RESTE AU 31/12/2025
                            'solde_global_jours'  => round($restant, 2),
                            'solde_global_heures' => round($restant * 8, 2),
                            'solde_global_restant'=> round($restant, 2),

                            // 🔹 TRACE IMPORT RH
                            //'cloture_at' => Carbon::create(2025, 12, 31, 23, 59, 59),
                            'cloture_at' => null,
                            'soldes_par_type' => [
                                'billet'       => $billet,
                                'conge'        => $conge,
                                'dispensaire'  => $dispensaire,
                                'convention'   => $convention,
                            ],
                        ]
                    );

                    $importes++;

                } catch (\Throwable $e) {

                    Log::error("Erreur import ligne {$index}", [
                        'error' => $e->getMessage(),
                        'ligne' => $row
                    ]);

                    $erreurs[] = [
                        'ligne'     => $index,
                        'matricule' => $matricule ?: 'Inconnu',
                        'erreur'    => $e->getMessage()
                    ];
                }
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'message' => 'Importation terminée',
            'rapport' => [
                'importes' => $importes,
                'erreurs'  => $erreurs
            ]
        ]);
    }

    // ======================================================
    // 🔢 PRORATA RH – 2,5 j / mois
    // ======================================================
    private function calculateProrataFromDate(Carbon $dateEntree): float
    {
        $annee = self::ANNEE_IMPORT;

        if ($dateEntree->year > $annee) {
            return 0;
        }

        if ($dateEntree->year < $annee) {
            return 30;
        }

        // Entrée pendant l'année 2025
        $months = 12 - $dateEntree->month + 1;

        return round($months * 2.5, 2);
    }


    // ======================================================
    // 📅 Conversion date Excel
    // ======================================================
    private function convertDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject($value)
                )->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (is_string($value)) {
            $value = trim($value);

            try {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                try {
                    return Carbon::parse($value)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        return null;
    }
}
