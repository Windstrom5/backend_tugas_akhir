<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    use HasFactory;
    protected $table = 'perusahaan';
    protected $fillable = [
        'nama',
        'latitude',
        'longitude',
        'jam_masuk',
        'jam_keluar',
        'batas_aktif',
        'logo',
        'secret_key',
    ];
    public $timestamps = true; // This enables automatic timestamping
    const CREATED_AT = 'created_at'; // Customize the column name if needed
    const UPDATED_AT = 'updated_at'; // Customize the column name if needed
}
