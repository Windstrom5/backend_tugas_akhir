<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absen;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\LocationUpdated;
class AbsenController extends Controller
{
    public function absen(Request $request){
        $absendata = DB::table('absen')
        ->join('pekerja', 'absen.id_pekerja', '=', 'pekerja.id')
        ->join('perusahaan', 'absen.id_perusahaan', '=', 'perusahaan.id')
        ->select('absen.*')
        ->where('pekerja.nama', $request->input('nama'))
        ->where('perusahaan.nama', $request->input('perusahaan'))
        ->first();
        $perusahaan = DB::table('perusahaan')->where('nama', $request->input('perusahaan'))->first();
        $pekerja = DB::table('pekerja')->where("nama", $request->input('nama'))
        ->where("id_perusahaan", $perusahaan->id)
        ->first(); 
        $jamMasuk = Carbon::parse($perusahaan->jam_masuk);
        $jamKeluar = Carbon::parse($perusahaan->jam_keluar);
        $requestedJam = Carbon::parse($request->input('jam'));
        if (!$perusahaan) {
            return response()->json(['status' => 'error', 'message' => 'Perusahaan not found'], 404);
        }
        // Check if current time is within 15 minutes of jam_masuk or jam_keluar
            if ($absendata) {
                if($requestedJam->diffInMinutes($jamKeluar) <= 15){
                    $absen = DB::table('absen')->where('id',$absendata->id);
                    $absen->update([
                        'jam_keluar' => $request->input('jam'),
                        'latitude' => $request->input('latitude'),
                        'longitude' => $request->input('longitude'),
                    ]);
    
                    return response()->json(['status' => 'success', 'message' => 'Absen Ended']);
                } else {
                    // Return an error if the current time is not within 15 minutes of jam_masuk or jam_keluar
                    return response()->json(['status' => 'error', 'message' => 'Cannot start Absen. Current time is not within 15 minutes of jam_keluar']);
                }
            } else {
                if ($requestedJam->diffInMinutes($jamMasuk) <= 15){
                    // Entry does not exist, create a new one
                    Absen::create([
                        'id_pekerja' => $pekerja->id,
                        'id_perusahaan' => $perusahaan->id,
                        'tanggal' => $request->input('tanggal'),
                        'jam_masuk' => $request->input('jam'),
                        'latitude' => $request->input('latitude'),
                        'longitude' => $request->input('longitude'),
                    ]);
                    return response()->json(['status' => 'success', 'message' => 'Absen Started']);
                } else {
                    // Return an error if the current time is not within 15 minutes of jam_masuk or jam_keluar
                    return response()->json(['status' => 'error', 'message' => 'Cannot start Absen. Current time is not within 15 minutes of jam_masuk']);
                }
                
            }
    }
    
    public function updateLocation(Request $request){
        $affectedRows = DB::table('absen')
        ->join('pekerja', 'absen.id_pekerja', '=', 'pekerja.id')
        ->join('perusahaan', 'absen.id_perusahaan', '=', 'perusahaan.id')
        ->where('pekerja.nama', $request->input('nama'))
        ->where('perusahaan.nama', $request->input('perusahaan'))
        ->update([
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ]);

        if ($affectedRows > 0) {
            // Broadcast the location update event
            broadcast(new LocationUpdated($affectedRows));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Location updated successfully',
                'current_time' => now()->toDateTimeString(),
            ]);
        } else {
            return response()->json(['status' => 'error', 'message' => 'offline'], 404);
        }
    }

    public function getPekerjaLocation($nama_perusahaan){
        $absendata = DB::table('absen')
        ->join('pekerja', 'absen.id_pekerja', '=', 'pekerja.id')
        ->join('perusahaan', 'absen.id_perusahaan', '=', 'perusahaan.id')
        ->select('pekerja.nama', 'absen.latitude', 'absen.longitude', 'absen.updated_at')
        ->where('perusahaan.nama', $nama_perusahaan)
        ->get();
        if ($absendata) {
            return response()->json($absendata);
        }else{
            return response()->json(['status' => 'error', 'message' => 'offline'], 404);
        }
    }

    public function getData($nama_perusahaan, $nama_pekerja) {
        $absendata = DB::table('absen')
            ->join('pekerja', 'absen.id_pekerja', '=', 'pekerja.id')
            ->join('perusahaan', 'absen.id_perusahaan', '=', 'perusahaan.id')
            ->select('absen.*', 'pekerja.nama')
            ->where('pekerja.nama', $nama_pekerja) // Use the correct parameter name
            ->where('perusahaan.nama', $nama_perusahaan) // Use the correct parameter name
            ->get();
    
        if ($absendata) {
            return response()->json($absendata);
        } else {
            return response()->json(['status' => 'error', 'message' => 'offline'], 404);
        }
    }
    
    // public function start(Request $request){
    //     $absendata = DB::table('absen')
    //     ->join('pekerja', 'absen.id_pekerja', '=', 'pekerja.id')
    //     ->join('perusahaan', 'absen.id_perusahaan', '=', 'perusahaan.id')
    //     ->select('absen.*')
    //     ->where('pekerja.nama', $request->input('nama'))
    //     ->where('perusahaan.nama', $request->input('perusahaan'))
    //     ->first();
    //     $absen = Absen::create([
    //         'id_pekerja'=> $request->id_pekerja, 
    //         'id_perusahaan'=> $request->id_perusahaan, 
    //         'tanggal'=> $request->input('tanggal'), 
    //         'jam_masuk'=> $request->input('jam_masuk'), 
    //         'latitude'=> $request->input('latitude'), 
    //         'longitude'=> $request->input('longitude'),
    //         'updated_at' =>  now()
    //     ]);
    //     return response()->json(['status' => 'success', 'message' => 'Absen Started']);
    // }

    // public function end(Request $request, $absendata){
    //     $absen = Absen::findOrFail($absendata->id);
    //     $absen->update([
    //         'jam_keluar' => $request->input('jam_keluar'),
    //         'latitude' => $request->input('latitude'),
    //         'longitude' => $request->input('longitude'),
    //         'updated_at' =>  now()
    //     ]);
    
    //     return response()->json(['status' => 'success', 'message' => 'Absen Ended']);
    // }
}
