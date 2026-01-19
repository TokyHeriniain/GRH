<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class LeaveBalance extends Model
{
    protected $fillable = [
        'personnel_id',
        'annee_reference',
        'solde_global_heures',
        'solde_global_jours',
        'solde_annuel_heures',
        'solde_annuel_jours',
        'solde_reporte_jours',
        'solde_global_restant',
        'soldes_par_type',
        'cloture_at',
    ];

    protected $casts = [
        'soldes_par_type' => 'array',
        'solde_global_heures' => 'decimal:2',
        'solde_global_jours' => 'decimal:2',
        'solde_annuel_heures' => 'decimal:2',
        'solde_annuel_jours' => 'decimal:2',
        'solde_reporte_jours' => 'decimal:2',
        'solde_global_restant' => 'decimal:2',
        'cloture_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::updating(function ($balance) {
            if ($balance->getOriginal('cloture_at')) {
                throw new \Exception("Solde clôturé RH – modification interdite");
            }
        });
    }
    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }
}


    

