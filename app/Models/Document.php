<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnel_id',
        'nom',
        'type',
        'fichier',
    ];

    // Relation inverse vers le personnel
    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }
}
