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
    ];
    public $timestamps = true; // This enables automatic timestamping
    const CREATED_AT = 'created_at'; // Customize the column name if needed
    const UPDATED_AT = 'updated_at'; // Customize the column name if needed
}
