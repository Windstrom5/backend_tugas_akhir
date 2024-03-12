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

    public function __construct($absendata)
    {
        $this->absendata = $absendata;
    }
    
    public function broadcastOn()
    {
        // Return a string channel name using the 'nama' of the Pekerja
        return 'location-updates.' . $this->absendata->nama;
    }
    
    public function broadcastWith()
    {
        // Assuming you have a 'pekerjas' table with a 'nama' column
        $pekerjaData = DB::table('pekerja')
            ->where('id', $this->absendata->id_pekerja)
            ->first();
    
        return [
            'nama' => $pekerjaData->nama,
            'latitude' => $this->absendata->latitude,
            'longitude' => $this->absendata->longitude,
            'updated_at' => $this->absendata->updated_at,
        ];
    }
}
