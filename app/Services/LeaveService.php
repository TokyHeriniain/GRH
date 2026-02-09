<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use LogicException;

class LeaveService
{
    public function __construct(
        protected LeaveBalanceService $balances,
        protected LeaveAuditService $audit,
        protected AnnualLeaveClosureService $closure,
        protected HolidayService $holidays
    ) {}

    /* =========================================================
     | CREATE
     ========================================================= */
    public function create(array $data): Leave
    {
        $annee = Carbon::parse($data['date_debut'])->year;
        $this->closure->isClosed($annee);

        // 🔒 Anti-doublon strict
        $exists = Leave::where('personnel_id', $data['personnel_id'])
            ->where('date_debut', $data['date_debut'])
            ->where('date_fin', $data['date_fin'])
            ->where('heure_debut', $data['heure_debut'])
            ->where('heure_fin', $data['heure_fin'])
            ->whereIn('status', ['en_attente', 'approuve_manager', 'approuve_rh'])
            ->exists();

        if ($exists) {
            throw new LogicException("Un congé identique existe déjà pour cette période");
        }

        $type = LeaveType::findOrFail($data['leave_type_id']);

        $jours = $this->calculateDays(
            $data['date_debut'],
            $data['date_fin'],
            $data['heure_debut'],
            $data['heure_fin'],
            $type
        );

        $leave = Leave::create([
            ...$data,
            'jours_utilises' => $jours,
            'status' => 'en_attente',
        ]);

        $this->audit->log(
            'create_leave',
            $leave->personnel_id,
            $leave->id,
            [],
            $leave->toArray()
        );

        return $leave;
    }

    /* =========================================================
     | UPDATE
     ========================================================= */
    public function update(Leave $leave, array $data): Leave
    {
        $this->closure->isClosed(Carbon::parse($leave->date_debut)->year);

        $old = $leave->getOriginal();

        if (
            isset($data['date_debut'], $data['date_fin'], $data['heure_debut'], $data['heure_fin'])
        ) {
            $type = LeaveType::findOrFail(
                $data['leave_type_id'] ?? $leave->leave_type_id
            );

            $data['jours_utilises'] = $this->calculateDays(
                $data['date_debut'],
                $data['date_fin'],
                $data['heure_debut'],
                $data['heure_fin'],
                $type
            );
        }

        $leave->update($data);

        $this->audit->log(
            'update_leave',
            $leave->personnel_id,
            $leave->id,
            $old,
            $leave->fresh()->toArray()
        );

        return $leave->fresh();
    }

    /* =========================================================
     | DELETE
     ========================================================= */
    public function delete(Leave $leave): void
    {
        $annee = Carbon::parse($leave->date_debut)->year;

        // 🔒 Année clôturée → interdiction
        $this->closure->isClosed($annee);

        $wasApprovedRH = $leave->status === 'approuve_rh';
        $personnelId  = $leave->personnel_id;

        $this->audit->log(
            'delete_leave',
            $personnelId,
            $leave->id,
            $leave->toArray(),
            []
        );

        $leave->delete();

        // 🔁 IMPORTANT : restituer les soldes uniquement si validé RH
        if ($wasApprovedRH) {
            $this->balances->recalculateForPersonnelAndType(
                $personnelId,
                $annee
            );
        }
    }


    /* =========================================================
     | VALIDATION RH
     ========================================================= */
    public function validateRH(Leave $leave, int $userId): Leave
    {
        return DB::transaction(function () use ($leave, $userId) {

            $annee = Carbon::parse($leave->date_debut)->year;
            $this->closure->isClosed($annee);

            if ($leave->status === 'approuve_rh') {
                throw new LogicException("Congé déjà validé RH");
            }

            $type = $leave->leaveType;

            $droitTotal = 0;
            $soldeAvant = 0;
            $soldeApres = 0;

                        /* ================= CONGÉ EXCEPTIONNEL ================= */
            if ($type->est_exceptionnel) {

                $dejaPris = $this->balances
                    ->getUsedExceptionalDays($leave->personnel_id, $type->id);

                if (
                    $type->limite_jours !== null &&
                    ($dejaPris + $leave->jours_utilises) > $type->limite_jours
                ) {
                    throw new LogicException("Quota exceptionnel dépassé");
                }

                $droitTotal = $type->limite_jours;
                $soldeAvant = $type->limite_jours - $dejaPris;
                $soldeApres = $soldeAvant - $leave->jours_utilises;
            }

            /* ================= CONGÉ AVEC SOLDE (ANNUEL + BILLET) ================= */
            elseif ($type->avec_solde) {

                $balance = LeaveBalance::where('personnel_id', $leave->personnel_id)
                    ->where('annee_reference', $annee)
                    ->firstOrFail();

                // 🔒 Quota billet / permission
                if ($type->limite_jours !== null) {

                    $dejaPris = Leave::where('personnel_id', $leave->personnel_id)
                        ->where('leave_type_id', $type->id)
                        ->where('status', 'approuve_rh')
                        ->sum('jours_utilises');

                    if (($dejaPris + $leave->jours_utilises) > $type->limite_jours) {
                        throw new LogicException("Quota billet dépassé");
                    }
                }

                // 🔵 Solde annuel partagé
                $droitTotal = $balance->solde_global_jours;
                $soldeAvant = $balance->solde_global_restant;
                $soldeApres = $soldeAvant - $leave->jours_utilises;

                if ($soldeApres < 0 && !$type->autorise_solde_negatif) {
                    throw new LogicException("Solde insuffisant");
                }
            }

            /* ================= AUTRES TYPES (SANS SOLDE) ================= */
            else {
                $droitTotal = 0;
                $soldeAvant = 0;
                $soldeApres = 0;
            }
            $leave->update([
                'status' => 'approuve_rh',
                'validated_by' => $userId,
                'validated_at' => now(),
                'droit_total' => round($droitTotal, 2),
                'solde_restant' => round($soldeApres, 2),
            ]);

            $this->balances->recalculateForPersonnelAndType(
                $leave->personnel_id,
                $annee
            );

            $this->audit->log(
                'validate_rh',
                $leave->personnel_id,
                $leave->id,
                [],
                $leave->toArray()
            );

            return $leave->fresh();
        });
    }

    /* =========================================================
     | CALCUL CENTRALISÉ – VERSION RH CORRECTE
     | - Week-ends inclus
     | - Jours fériés exclus selon règle
     | - Multi-jours = 8h par jour
     | - Heures utilisées uniquement si 1 seul jour
     ========================================================= */
    protected function calculateDays(
        string $dateDebut,
        string $dateFin,
        string $heureDebut,
        string $heureFin,
        LeaveType $type
    ): float {

        $startDate = Carbon::parse($dateDebut);
        $endDate   = Carbon::parse($dateFin);

        if ($endDate->lessThan($startDate)) {
            throw new LogicException("Période invalide");
        }

        $excludeHolidays = $this->holidays->shouldExclude($type);
        $holidays = $excludeHolidays
            ? $this->holidays->datesBetween($dateDebut, $dateFin)
            : [];

        $totalHours = 0;
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {

            // ⛔ Jour férié exclu
            if ($excludeHolidays && in_array($date->toDateString(), $holidays)) {
                continue;
            }

            // 🔹 Congé sur une seule journée → calcul horaire
            if ($startDate->isSameDay($endDate)) {
                $dayStart = Carbon::parse("$dateDebut $heureDebut");
                $dayEnd   = Carbon::parse("$dateFin $heureFin");
                $hours = $this->calculateWorkedHours($dayStart, $dayEnd);
            }
            // 🔹 Congé multi-jours → TOUS les jours = 8h
            else {
                $hours = 8;
            }

            $totalHours += max($hours, 0);
        }

        return round($totalHours / 8, 2);
    }

    /* =========================================================
     | CALCUL HEURES TRAVAILLÉES (1 JOUR)
     ========================================================= */
    protected function calculateWorkedHours(Carbon $start, Carbon $end): float
    {
        if ($end <= $start) return 0;

        $minutes = $end->diffInMinutes($start);

        // Pause déjeuner 12h00–13h30
        $pauseStart = $start->copy()->setTime(12, 0);
        $pauseEnd   = $start->copy()->setTime(13, 30);

        if ($start < $pauseEnd && $end > $pauseStart) {
            $overlapStart = $start->greaterThan($pauseStart) ? $start : $pauseStart;
            $overlapEnd   = $end->lessThan($pauseEnd) ? $end : $pauseEnd;
            $minutes -= $overlapStart->diffInMinutes($overlapEnd);
        }

        return max($minutes / 60, 0);
    }

    public function getDaysForLeave(array $data, LeaveType $type): float
    {
        return $this->calculateDays(
            $data['date_debut'],
            $data['date_fin'],
            $data['heure_debut'],
            $data['heure_fin'],
            $type
        );
    }
    /**
     * Vérifie si le solde est suffisant pour un congé fictif
     */
    public function checkSoldeDisponibleForLeave(Leave $leave): void
    {
        $type = $leave->leaveType;

        $annee = \Carbon\Carbon::parse($leave->date_debut)->year;

        if ($type->est_exceptionnel) {
            $dejaPris = $this->balances->getUsedExceptionalDays($leave->personnel_id, $type->id);

            if ($type->limite_jours !== null && ($dejaPris + $leave->jours_utilises) > $type->limite_jours) {
                throw new \LogicException("Quota exceptionnel dépassé");
            }
        }

        if ($type->avec_solde && !$type->est_exceptionnel) {
            $balance = \App\Models\LeaveBalance::where('personnel_id', $leave->personnel_id)
                ->where('annee_reference', $annee)
                ->firstOrFail();

            $soldeApres = $balance->solde_global_restant - $leave->jours_utilises;

            if ($soldeApres < 0 && !$type->autorise_solde_negatif) {
                throw new \LogicException("Solde insuffisant");
            }
        }
    }

}
