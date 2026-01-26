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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ImportLegacyDataController extends Controller
{
    /**
     * 🔒 Année RH figée pour import legacy
     */
    private const ANNEE_IMPORT = 2025;

    public function import(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Aucun fichier fourni'], 400);
        }

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getSheet(0);

        // ⚠️ On ne fait PLUS confiance à toArray() pour les dates
        $rows = $sheet->toArray(null, true, true, true);

        $importes = 0;
        $erreurs  = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {

                // ⛔ Ignore l’en-tête
                if ($index === 1) {
                    continue;
                }

                try {
                    /* =====================================================
                     * 1️⃣ DONNÉES EXCEL (BRUTES)
                     * ===================================================== */
                    $matricule    = trim($row['A'] ?? '');
                    $nom          = trim($row['B'] ?? '');
                    $prenom       = trim($row['C'] ?? '');
                    $fonctionNom  = trim($row['D'] ?? '');
                    $serviceNom   = trim($row['E'] ?? '');
                    $directionNom = trim($row['F'] ?? '');

                    // 🔐 Lecture sécurisée des dates Excel
                    $dateEntreeExcel     = $this->getCellDateSafe($sheet, "G{$index}");
                    $dateNaissanceExcel = $this->getCellDateSafe($sheet, "M{$index}");

                    $cellDroit = trim((string) ($row['H'] ?? ''));
                    $billet    = round((float) ($row['I'] ?? 0), 2);
                    $conge     = round((float) ($row['J'] ?? 0), 2);

                    $cin     = trim($row['K'] ?? '');
                    $adresse = trim($row['L'] ?? '');

                    if (!$matricule || !$nom) {
                        throw new \Exception("Matricule ou nom manquant");
                    }

                    /* =====================================================
                     * 2️⃣ RÉFÉRENTIELS (AUTO-CRÉATION)
                     * ===================================================== */
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

                    /* =====================================================
                     * 3️⃣ PERSONNEL (DATES PROTÉGÉES)
                     * ===================================================== */
                    $personnel = Personnel::where('matricule', $matricule)->first();

                    if (!$personnel) {
                        // ➕ Création
                        $personnel = Personnel::create([
                            'matricule'       => $matricule,
                            'nom'             => $nom,
                            'prenom'          => $prenom,
                            'date_entree'     => $dateEntreeExcel,
                            'date_naissance'  => $dateNaissanceExcel,
                            'fonction_id'     => $fonction->id,
                            'service_id'      => $service->id,
                            'direction_id'    => $direction->id,
                            'cin'             => $cin,
                            'adresse'         => $adresse,
                        ]);
                    } else {
                        // 🔒 Mise à jour SANS toucher aux dates existantes
                        $updates = [
                            'nom'          => $nom,
                            'prenom'       => $prenom,
                            'fonction_id'  => $fonction->id,
                            'service_id'   => $service->id,
                            'direction_id' => $direction->id,
                            'cin'          => $cin,
                            'adresse'      => $adresse,
                        ];

                        if (!$personnel->date_entree && $dateEntreeExcel) {
                            $updates['date_entree'] = $dateEntreeExcel;
                        }

                        if (!$personnel->date_naissance && $dateNaissanceExcel) {
                            $updates['date_naissance'] = $dateNaissanceExcel;
                        }

                        $personnel->update($updates);
                    }

                    /* =====================================================
                     * 4️⃣ CALCUL DU DROIT RH (RÈGLE FINALE)
                     * ===================================================== */
                    if ($cellDroit === 'X') {

                        if (!$personnel->date_entree) {
                            throw new \Exception("Date d'entrée requise pour prorata (X)");
                        }

                        $droit = $this->calculateProrataFromDate(
                            Carbon::parse($personnel->date_entree)
                        );

                    } elseif (is_numeric($cellDroit)) {

                        $droit = round((float) $cellDroit, 2);

                    } else {
                        throw new \Exception(
                            "Valeur DROIT invalide (attendu : nombre ou 'X')"
                        );
                    }

                    /* =====================================================
                     * 5️⃣ SOLDES
                     * ===================================================== */
                    $joursUtilises = round($billet + $conge, 2);
                    $restant       = round($droit - $joursUtilises, 2);

                    /* =====================================================
                     * 6️⃣ LEAVEBALANCE LEGACY
                     * ===================================================== */
                    LeaveBalance::updateOrCreate(
                        [
                            'personnel_id'    => $personnel->id,
                            'annee_reference' => self::ANNEE_IMPORT,
                        ],
                        [
                            'solde_annuel_jours'   => $droit,
                            'solde_annuel_heures'  => round($droit * 8, 2),

                            'solde_global_jours'   => $restant,
                            'solde_global_heures'  => round($restant * 8, 2),
                            'solde_global_restant' => $restant,

                            'cloture_at' => null,

                            'soldes_par_type' => [
                                'billet' => $billet,
                                'conge'  => $conge,
                            ],
                        ]
                    );

                    Log::info('Import RH OK', [
                        'matricule' => $matricule,
                        'droit' => $droit,
                        'utilises' => $joursUtilises,
                        'restant' => $restant,
                    ]);

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

    /* =====================================================
     * 🔢 PRORATA RH – 2,5 jours / mois
     * ===================================================== */
    private function calculateProrataFromDate(Carbon $dateEntree): float
    {
        $annee = self::ANNEE_IMPORT;

        if ($dateEntree->year > $annee) {
            return 0;
        }

        if ($dateEntree->year < $annee) {
            return 30;
        }

        $months = 12 - $dateEntree->month + 1;
        return round($months * 2.5, 2);
    }

    /* =====================================================
     * 📅 LECTURE DATE EXCEL SAFE (ANTI-BUG)
     * ===================================================== */
    private function getCellDateSafe($sheet, string $cell): ?string
    {
        $excelCell = $sheet->getCell($cell);

        if (!$excelCell || $excelCell->getValue() === null) {
            return null;
        }

        $value = $excelCell->getValue();

        if (ExcelDate::isDateTime($excelCell)) {
            return Carbon::instance(
                ExcelDate::excelToDateTimeObject($value)
            )->format('Y-m-d');
        }

        if (is_string($value)) {
            try {
                return Carbon::createFromFormat('d/m/Y', trim($value))->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }
}
