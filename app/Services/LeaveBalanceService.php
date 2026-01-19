<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\Personnel;
use Carbon\Carbon;
use App\Models\AnnualLeaveClosure;
use LogicException;
use Illuminate\Support\Facades\DB;

class LeaveBalanceService
{   

    private function daysToHours(float $jours): float
    {
        return $jours * 8;
    }

    private function hoursToDays(float $heures): float
    {
        return round($heures / 8, 2);
    }

    /* ===================== EXCEPTIONNELS ===================== */

    public function getUsedExceptionalDays(int $personnelId, int $typeId): float
    {
        return (float) Leave::where('personnel_id', $personnelId)
            ->where('leave_type_id', $typeId)
            ->where('status', 'approuve_rh')
            ->sum('jours_utilises');
    }

    /* ===================== RECONCILIATION RH ===================== */

    public function recalculateForPersonnelAndType(int $personnelId, int $annee): LeaveBalance
    {
        $balance = LeaveBalance::where('personnel_id', $personnelId)
            ->where('annee_reference', $annee)
            ->firstOrFail();

        if ($balance->cloture_at !== null) {
            return $balance; // 🔒 année clôturée → intouchable
        }

        $approvedLeaves = Leave::where('personnel_id', $personnelId)
            ->where('status', 'approuve_rh')
            ->get();

        $leaveTypes = LeaveType::all()->keyBy('id');

        $consommeGlobalHeures = 0;
        $consommeAnnuelHeures = 0;

        $soldesParType = [];

        foreach ($leaveTypes as $type) {
            $droit = 0;

            if ($type->est_exceptionnel) {
                $droit = $type->limite_jours ?? 0;
            } elseif ($type->avec_solde) {
                $droit = $balance->solde_annuel_jours;
            }

            $soldesParType[$type->id] = [
                'droit_total' => round($droit, 2),
                'utilises'    => 0,
                'solde'       => round($droit, 2),
            ];
        }

        foreach ($approvedLeaves as $leave) {
            $type   = $leaveTypes[$leave->leave_type_id];
            $jours  = (float) $leave->jours_utilises;
            $heures = $this->daysToHours($jours);

            $soldesParType[$type->id]['utilises'] += $jours;
            $soldesParType[$type->id]['solde'] =
                round(
                    $soldesParType[$type->id]['droit_total']
                    - $soldesParType[$type->id]['utilises'],
                    2
                );

            if ($type->avec_solde) {
                $consommeGlobalHeures += $heures;
            }

            if ($type->code === 'ANNUEL') {
                $consommeAnnuelHeures += $heures;
            }
        }

        $balance->solde_annuel_jours = round(
            max($balance->solde_annuel_jours - $this->hoursToDays($consommeAnnuelHeures), 0),
            2
        );

        $balance->solde_global_restant = round(
            max($balance->solde_global_jours - $this->hoursToDays($consommeGlobalHeures), 0),
            2
        );

        $balance->soldes_par_type = $soldesParType;
        $balance->save();

        return $balance;
    }

    /* ===================== CLOTURE ANNUELLE ===================== */


    // App\Services\LeaveBalanceService.php

    public function closeAnnualLeave(int $annee, int $rhUserId): void
    {
        $now = now();
        $dateDebutAnnee = Carbon::create($annee, 1, 1);

        foreach (Personnel::all() as $p) {

            $solde = $this->getSoldeAnnuelAu31Decembre($p, $annee);
            if ($solde === null) continue;

            $report = min($solde, 60);
            $perte  = max(0, $solde - 60);

            // ✅ DROIT AUX 30 JOURS UNIQUEMENT SI PRÉSENT AU 01/01/N
            $aDroit30Jours = $p->date_entree
                && Carbon::parse($p->date_entree)->lte($dateDebutAnnee);

            $soldeN1 = $report + ($aDroit30Jours ? 30 : 0);

            /* ===================== 1️⃣ JOURNAL RH ===================== */
            AnnualLeaveClosure::create([
                'annee'          => $annee,
                'personnel_id'   => $p->id,
                'solde_avant'    => round($solde, 2),
                'report'         => round($report, 2),
                'perte'          => round($perte, 2),
                'solde_n_plus_1' => round($soldeN1, 2),
                'validated_by'   => $rhUserId,
                'validated_at'   => $now,
            ]);

            /* ===================== 2️⃣ FIGER ANNÉE N ===================== */
            LeaveBalance::where('personnel_id', $p->id)
                ->where('annee_reference', $annee)
                ->update([
                    'solde_annuel_jours'  => round($solde, 2),
                    'solde_reporte_jours' => round($report, 2),
                    'cloture_at'          => $now,
                    'cloture_by'          => $rhUserId,
                ]);

            /* ===================== 3️⃣ CRÉER ANNÉE N+1 ===================== */
            LeaveBalance::updateOrCreate(
                [
                    'personnel_id'    => $p->id,
                    'annee_reference' => $annee + 1,
                ],
                [
                    'solde_annuel_jours'  => round($soldeN1, 2),
                    'solde_annuel_heures' => round($soldeN1 * 8, 2),
                    'solde_global_jours'  => round($soldeN1, 2),
                    'solde_global_heures' => round($soldeN1 * 8, 2),
                    'solde_global_restant'=> round($soldeN1, 2),
                    'solde_reporte_jours' => round($report, 2),
                    'cloture_at'          => null,
                    'cloture_by'          => null,
                ]
            );
        }
    }



    public function getSoldeAnnuelAu31Decembre(Personnel $p, int $annee): ?float
    {
        /**
         * 🔒 CAS 1 : solde clôturé RH (figé)
         */
        $closedBalance = LeaveBalance::where('personnel_id', $p->id)
            ->where('annee_reference', $annee)
            ->whereNotNull('cloture_at')
            ->first();

        if ($closedBalance) {
            return round($closedBalance->solde_annuel_jours, 2);
        }

        /**
         * 🟢 CAS 2 : IMPORT INITIAL OU ANNÉE EN COURS NON CLÔTURÉE
         * → on utilise le solde importé comme vérité RH
         */
        $importedBalance = LeaveBalance::where('personnel_id', $p->id)
            ->where('annee_reference', $annee)
            ->whereNull('cloture_at')
            ->first();

        if ($importedBalance) {
            return round(
                $importedBalance->solde_global_jours
                    ?? $importedBalance->solde_annuel_jours,
                2
            );
        }

        /**
         * 🔁 CAS 3 : NOUVEAU PERSONNEL (fallback dynamique)
         */
        if (!$p->date_entree) {
            return null;
        }

        $entree = Carbon::parse($p->date_entree);

        if ($entree->year > $annee) {
            return null;
        }

        $start = $entree->year === $annee
            ? $entree
            : Carbon::create($annee, 1, 1);

        $months = $start->diffInMonths(
            Carbon::create($annee, 12, 31)
        ) + 1;

        $droit = round($months * 2.5, 2);

        $pris = Leave::where('personnel_id', $p->id)
            ->whereYear('date_debut', $annee)
            ->where('status', 'approuve_rh')
            ->sum('jours_utilises');

        return round($droit - $pris, 2);
    }

    public function isYearClosedGlobally(int $annee): bool
    {
        return AnnualLeaveClosure::where('annee', $annee)->exists();
    }

    public function getReliquatEnCours(Personnel $personnel, int $annee): float
    {
        $balance = LeaveBalance::where('personnel_id', $personnel->id)
            ->where('annee_reference', $annee)
            ->first();

        if (!$balance) {
            return 0;
        }

        // 🔒 Si année clôturée → solde figé
        if ($balance->cloture_at !== null) {
            return round($balance->solde_annuel_jours, 2);
        }

        $heuresConsommees = Leave::where('personnel_id', $personnel->id)
            ->where('status', 'approuve_rh')
            ->whereYear('date_debut', $annee)
            ->sum(DB::raw('jours_utilises * 8'));

        $joursConsommes = round($heuresConsommees / 8, 2);

        return round(
            max($balance->solde_annuel_jours - $joursConsommes, 0),
            2
        );
    }
}
