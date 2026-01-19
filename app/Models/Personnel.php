<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personnel extends Model
{
    protected $fillable = [
        'nom', 'prenom', 'email', 'matricule', 'date_naissance',
        'adresse', 'cin', 'diplome', 'date_entree', 'photo',
        'direction_id', 'service_id', 'fonction_id',
    ];

    protected $casts = [
        'date_entree' => 'date',
        'date_sortie' => 'date',
        'date_naissance' => 'date',
    ];



    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function direction() {
    return $this->belongsTo(Direction::class);
    }

    public function service() {
        return $this->belongsTo(Service::class);
    }
    
    public function fonction() {
        return $this->belongsTo(Fonction::class);
    }

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset("storage/{$this->photo}") : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveBalance()
    {
        return $this->hasOne(LeaveBalance::class);
    }
}
