<?php

// app/Models/LeaveHistory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveHistory extends Model
{
    protected $fillable = ['leave_id', 'user_id', 'action', 'changes'];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
