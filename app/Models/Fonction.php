<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fonction extends Model
{
    protected $fillable = ['nom', 'service_id'];
    public function service() {
        return $this->belongsTo(Service::class);
    }
}
