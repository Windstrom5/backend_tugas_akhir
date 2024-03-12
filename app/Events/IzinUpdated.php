<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IzinUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $perusahaanNama;
    public $izin;
    
    public function __construct($perusahaanNama, $izin)
    {
        $this->perusahaanNama = $perusahaanNama;
        $this->izin = $izin;
    }

    public function broadcastOn()
    {
        // Broadcast to a channel named after perusahaan.nama
        return new Channel('izin-channel.' . $this->perusahaanNama);
    }
}
