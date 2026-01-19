<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveAudit extends Model
{
    protected $fillable = [
        'leave_id',
        'personnel_id',
        'actor_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected static function booted()
    {
        static::deleting(function () {
            abort(403, 'Suppression des audits interdite');
        });
    }
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

}
