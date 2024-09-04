<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class session_lembur extends Model
{
    use HasFactory;
    protected $table = 'session_lembur';
    protected $fillable = [
        'id_lembur',
        'jam',
        'keterangan',
        'bukti',
        'status'
    ];
    public $timestamps = true; // This enables automatic timestamping
    const CREATED_AT = 'created_at'; // Customize the column name if needed
    const UPDATED_AT = 'updated_at'; // Customize the column name if needed
}
