<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DinasUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $perusahaanNama;
    public $dinas;
    
    public function __construct($perusahaanNama, $dinas)
    {
        $this->perusahaanNama = $perusahaanNama;
        $this->dinas = $dinas;
    }

    public function broadcastOn()
    {
        // Broadcast to a channel named after perusahaan.nama
        return new Channel('dinas-channel.' . $this->perusahaanNama);
    }
}
