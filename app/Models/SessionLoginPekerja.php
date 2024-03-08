<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionLoginPekerja extends Model
{
    use HasFactory;
    protected $table = 'login_sessions_pekerja';
    protected $fillable = [
        'id',
        'id_pekerja',
        'token',
        'created_at'	
    ];
    public $timestamps = false;
    protected $casts = [
        'created_at' => 'datetime',
    ];
}
