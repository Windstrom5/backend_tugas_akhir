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

class PekerjaUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $perusahaanNama;
    public $pekerja;
    
    public function __construct($perusahaanNama, $pekerja)
    {
        $this->perusahaanNama = $perusahaanNama;
        $this->pekerja = $pekerja;
    }

    public function broadcastOn()
    {
        // Broadcast to a channel named after perusahaan.nama
        return new Channel('pekerja-channel.' . $this->perusahaanNama);
    }
}

