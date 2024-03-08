<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dinas extends Model
{
    use HasFactory;
    protected $table = 'dinas';
    protected $fillable = [
        'id_pekerja', 
        'id_perusahaan', 
        'tujuan', 
        'tanggal_berangkat', 
        'tanggal_pulang', 
        'kegiatan', 
        'bukti', 
        'update_at'
    ];
    public $timestamps = true; // This enables automatic timestamping
    const CREATED_AT = 'created_at'; // Customize the column name if needed
    const UPDATED_AT = 'updated_at'; // Customize the column name if needed
}
