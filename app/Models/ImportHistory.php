<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    protected $fillable = ['filename','rows_total','rows_imported','errors','type','user_id'];
    protected $casts = ['errors' => 'array'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
