<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnel_id',
        'leave_type_id',
        'date_debut',
        'date_fin',
        'heure_debut',
        'heure_fin',
        'raison',
        'jours_utilises',
        'droit_total',
        'solde_restant',
        'rejection_reason', // motif de rejet 
        'status',
        'validated_by',
    ];

    protected $dates = ['date_debut', 'date_fin'];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Accessors
    protected $appends = ['droit_total_calc', 'solde_restant_calc'];

    public function getDroitTotalCalcAttribute()
    {
        // ton calcul (par mois * 2.5, ou limite_jours du type)
        if ($this->droit_total !== null) {
            return $this->droit_total;
        }

        $personnel = $this->personnel;
        if (!$personnel || !$personnel->date_entree) return null;

        $moisTravailles = \Carbon\Carbon::parse($personnel->date_entree)->diffInMonths(now());
        return round($moisTravailles * 2.5, 2);
    }

    public function getSoldeRestantCalcAttribute()
    {
        if ($this->solde_restant !== null) {
            return $this->solde_restant;
        }

        $personnel = $this->personnel;
        if (!$personnel || !$personnel->date_entree) return null;

        $moisTravailles = \Carbon\Carbon::parse($personnel->date_entree)->diffInMonths(now());
        $droitTotal = round($moisTravailles * 2.5, 2);

        $congesPris = Leave::where('personnel_id', $personnel->id)
            ->whereIn('status', ['approuve_manager', 'approuve_rh'])
            ->sum('jours_utilises');

        return round($droitTotal - $congesPris, 2);
    }

    protected static function booted()
    {
        static::updating(function ($leave) {

            // 🔐 Verrou RH ABSOLU
            if ($leave->getOriginal('status') === 'approuve_rh') {

                // Autorisé UNIQUEMENT : champs techniques
                $allowed = [
                    'updated_at',
                ];

                $dirty = array_keys($leave->getDirty());

                foreach ($dirty as $field) {
                    if (!in_array($field, $allowed)) {
                        throw new \Exception(
                            "Congé RH verrouillé : modification interdite ($field)"
                        );
                    }
                }
            }
        });
    }


}
