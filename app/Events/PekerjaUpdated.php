<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Pekerja;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;

class PekerjaUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $pekerjadata;
    public $perusahaan_nama;
    public $role;

    public function __construct($pekerjadata,$perusahaan_nama,$role)
    {
        $this->pekerjadata = $pekerjadata;
        $this->perusahaan_nama = $perusahaan_nama;
        $this->role = $role;
    }

    public function broadcastOn()
    {
        $perusahaanData = DB::table('perusahaan')
            ->select('perusahaan.*')
            ->where('id', $this->pekerjadata->id_perusahaan)
            ->first();
    
        return 'pekerja-updates.' . $perusahaanData->nama;
    }

    public function broadcastWith()
    {
        return [
            'pekerjadata' => $this->pekerjadata->toArray(),
            'role' => $this->role
        ];
    }
}

