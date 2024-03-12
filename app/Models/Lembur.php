<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lembur extends Model
{
    use HasFactory;
    protected $table = 'lembur';
    protected $fillable = [
        'id_perusahaan',
        'id_pekerja',
        'tanggal',
        'waktu_masuk',
        'waktu_pulang',
        'pekerjaan',
        'bukti',
        'status'
    ];
    public $timestamps = true; // This enables automatic timestamping
    const CREATED_AT = 'created_at'; // Customize the column name if needed
    const UPDATED_AT = 'updated_at'; // Customize the column name if needed
}
