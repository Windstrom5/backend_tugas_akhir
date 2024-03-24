<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class lemburUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $lemburdata;
    public $perusahaan_nama;
    public function __construct($lemburdata)
    {
        $this->lemburdata = $lemburdata;
    }

    public function broadcastOn()
    {
        $perusahaanData = DB::table('perusahaan')
            ->where('id', $this->lemburdata->id_perusahaan)
            ->first();
    
        return 'lembur-updates.' . $perusahaanData->nama_perusahaan;
    }

    public function broadcastWith()
    {
        return [
            'lemburdata' => $this->lemburdata,
        ];
    }
}
