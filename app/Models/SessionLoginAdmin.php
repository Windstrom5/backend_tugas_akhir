<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionLoginAdmin extends Model
{
    use HasFactory;
    protected $table = 'login_sessions_admin';
    protected $fillable = [
        'id',
        'id_admin',
        'token',
        'created_at'	
    ];
    public $timestamps = false;
    protected $casts = [
        'created_at' => 'datetime',
    ];
}
