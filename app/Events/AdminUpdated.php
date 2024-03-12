<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $perusahaanNama;
    public $admin;
    
    public function __construct($perusahaanNama, $admin)
    {
        $this->perusahaanNama = $perusahaanNama;
        $this->admin = $admin;
    }

    public function broadcastOn()
    {
        // Broadcast to a channel named after perusahaan.nama
        return new Channel('admin-channel.' . $this->perusahaanNama);
    }
}
