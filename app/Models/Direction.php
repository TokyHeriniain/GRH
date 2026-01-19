<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Direction extends Model
{
    protected $fillable = ['nom'];
    public function services()
    {
        return $this->hasMany(Service::class);
    }

}
