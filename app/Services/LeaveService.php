<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class LeaveService
{
    public function __construct(
        protected LeaveBalanceService $balances,
        protected LeaveAuditService $audit,
        protected AnnualLeaveClosureService $closure
    ) {}

    /* =========================================================
     | CREATE — anti-doublon strict
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

        $leave = Leave::create([
            ...$data,
            'jours_utilises' => round($data['jours_utilises'], 2),
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
     | UPDATE — modification avant validation RH
     ========================================================= */
    public function update(Leave $leave, array $data): Leave
    {
        $this->closure->isClosed(Carbon::parse($leave->date_debut)->year);

        $old = $leave->getOriginal();
        $leave->update($data);

        $this->audit->log(
            'update_leave',
            $leave->personnel_id,
            $leave->id,
            $old,
            $leave->fresh()->toArray()
        );

        return $leave;
    }

    /* =========================================================
     | DELETE
     ========================================================= */
    public function delete(Leave $leave): void
    {
        $this->closure->isClosed(Carbon::parse($leave->date_debut)->year);

        $this->audit->log(
            'delete_leave',
            $leave->personnel_id,
            $leave->id,
            $leave->toArray(),
            []
        );

        $leave->delete();
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
                $dejaPris = $this->balances->getUsedExceptionalDays($leave->personnel_id, $type->id);

                if ($type->limite_jours !== null &&
                    ($dejaPris + $leave->jours_utilises) > $type->limite_jours) {
                    throw new LogicException("Quota exceptionnel dépassé");
                }

                $droitTotal = $type->limite_jours;
                $soldeAvant = $type->limite_jours - $dejaPris;
                $soldeApres = $soldeAvant - $leave->jours_utilises;
            }

            /* ================= CONGÉ AVEC SOLDE ================= */
            if ($type->avec_solde && !$type->est_exceptionnel) {
                $balance = LeaveBalance::where('personnel_id', $leave->personnel_id)
                    ->where('annee_reference', $annee)
                    ->firstOrFail();

                $droitTotal = $balance->solde_annuel_jours;
                $soldeAvant = $balance->solde_annuel_jours;
                $soldeApres = $soldeAvant - $leave->jours_utilises;

                // ❌ solde négatif interdit SAUF si type autorisé
                if ($soldeApres < 0 && !$type->autorise_solde_negatif) {
                    throw new LogicException("Solde insuffisant pour ce type de congé");
                }
            }

            $leave->update([
                'status' => 'approuve_rh',
                'validated_by' => $userId,
                'validated_at' => now(),
                'droit_total' => round($droitTotal, 2),
                'solde_restant' => round($soldeApres, 2),
            ]);

            // 🔁 Recalcul global
            $this->balances->recalculateForPersonnelAndType($leave->personnel_id, $annee);

            $this->audit->log('validate_rh', $leave->personnel_id, $leave->id, [], $leave->toArray());

            return $leave->fresh();
        });
    }

    /* =========================================================
     | REJET RH
     ========================================================= */
    public function rejectRH(Leave $leave, int $userId, ?string $reason): Leave
    {
        return DB::transaction(function () use ($leave, $userId, $reason) {
            $annee = Carbon::parse($leave->date_debut)->year;
            $this->closure->isClosed($annee);

            if ($leave->status !== 'approuve_rh') {
                throw new LogicException("Seul un congé validé RH peut être rejeté");
            }

            $old = $leave->getOriginal();

            $leave->forceFill([
                'status' => 'rejete',
                'rejection_reason' => $reason,
                'validated_by' => $userId,
                'validated_at' => null,
                'droit_total' => null,
                'solde_restant' => null,
            ])->save();

            $this->balances->recalculateForPersonnelAndType($leave->personnel_id, $annee);

            $this->audit->log('reject_rh', $leave->personnel_id, $leave->id, $old, $leave->toArray());

            return $leave->fresh();
        });
    }
}
