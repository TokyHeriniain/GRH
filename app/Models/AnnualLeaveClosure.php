<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualLeaveClosure extends Model
{
    protected $fillable = [
        'annee',
        'personnel_id',
        'solde_avant',
        'report',
        'perte',
        'solde_n_plus_1',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }
    
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}

