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

class LocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $absendata;
    public $perusahaanNama;
    
    public function __construct($absendata, $perusahaanNama)
    {
        $this->absendata = $absendata;
        $this->perusahaanNama = $perusahaanNama;
    }
    
    public function broadcastOn()
    {
        $perusahaanData = DB::table('perusahaan')
            ->where('id', $this->absendata->id_perusahaan)
            ->first();
        return 'location-updates.' . $perusahaanData->nama_perusahaan;
    }
    
    public function broadcastWith()
    {
        $pekerja = DB::table('pekerja')->where('id', $this->absendata->id_pekerja)->first();
        return [
            'nama' => $pekerja->nama,
            'latitude' => $this->absendata->latitude,
            'longitude' => $this->absendata->longitude,
            'updated_at' => $this->absendata->updated_at,
        ];
    }
}
