<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pekerja extends Model
{
    use HasFactory;
    protected $table = 'pekerja';
    protected $fillable = [
        'id_perusahaan',
        'email',
        'password',
        'nama',
        'tanggal_lahir',
        'profile'
    ];
    public $timestamps = true; // This enables automatic timestamping
    const CREATED_AT = 'created_at'; // Customize the column name if needed
    const UPDATED_AT = 'updated_at'; // Customize the column name if needed
}
