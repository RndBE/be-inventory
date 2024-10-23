<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogActivity extends Model
{
    use HasFactory;

    protected $table = 'log_activities';
    protected $guarded = ['id'];

    // Optional: Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
