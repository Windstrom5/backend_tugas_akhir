<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LemburUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $perusahaanNama;
    public $lembur;
    
    public function __construct($perusahaanNama, $lembur)
    {
        $this->perusahaanNama = $perusahaanNama;
        $this->lembur = $lembur;
    }

    public function broadcastOn()
    {
        // Broadcast to a channel named after perusahaan.nama
        return new Channel('lembur-channel.' . $this->perusahaanNama);
    }
}
