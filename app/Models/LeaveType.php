<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    //
    protected $fillable = [
        'nom',             // ex: "Congé annuel", "Maladie", "Mariage"
        'description',     // texte explicatif
        'avec_solde',      // booléen (true/false)
        'limite_jours',    // limite de jours si applicable
        'est_exceptionnel',// booléen
        'code',            // code interne si tu en as un
        'autorise_solde_negatif',
    ];
    protected $casts = [
        'avec_solde' => 'boolean',
        'est_exceptionnel' => 'boolean',
    ];

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'leave_type_id');
    }

}
