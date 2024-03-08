<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absen;

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
        $perusahaan = Perusahaan::where('nama', $request->input('perusahaan'))->first();
        $jamMasuk = Carbon::parse($perusahaan->jam_masuk);
        $jameluar = Carbon::parse($perusahaan->jam_keluar);
        $currentDateTime = now();
        if (!$perusahaan) {
            return response()->json(['status' => 'error', 'message' => 'Perusahaan not found'], 404);
        }
        // Check if current time is within 15 minutes of jam_masuk or jam_keluar
        if ($currentDateTime->diffInMinutes($jamMasuk) <= 15 || $currentDateTime->diffInMinutes($jamKeluar) <= 15) {
            if ($absendata) {
                $absen = Absen::findOrFail($absendata->id);
                $absen->update([
                    'jam_keluar' => $request->input('jam_keluar'),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'updated_at' => now()
                ]);

                return response()->json(['status' => 'success', 'message' => 'Absen Ended']);
            } else {
                // Entry does not exist, create a new one
                Absen::create([
                    'id_pekerja' => $request->id_pekerja,
                    'id_perusahaan' => $request->id_perusahaan,
                    'tanggal' => $request->input('tanggal'),
                    'jam_masuk' => $request->input('jam_masuk'),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'updated_at' => now(),
                ]);
                return response()->json(['status' => 'success', 'message' => 'Absen Started']);
            }
        } else {
            // Return an error if the current time is not within 15 minutes of jam_masuk or jam_keluar
            return response()->json(['status' => 'error', 'message' => 'Cannot start Absen. Current time is not within 15 minutes of jam_masuk or jam_keluar']);
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
            'updated_at' => now(),
        ]);

        if ($affectedRows > 0) {
            // Broadcast the location update event
            broadcast(new LocationUpdated($absendata));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Location updated successfully',
                'current_time' => now()->toDateTimeString(),
            ]);
        } else {
            return response()->json(['status' => 'error', 'message' => 'offline'], 404);
        }
    }

    public function getPekerjaLocation($nama_perusahan){
        $absendata = DB::table('absen')
        ->join('pekerja', 'absen.id_pekerja', '=', 'pekerja.id')
        ->join('perusahaan', 'absen.id_perusahaan', '=', 'perusahaan.id')
        ->select('pekerja.nama', 'absen.latitude', 'absen.longitude', 'absen.updated_at')
        ->where('perusahaan.nama', $request->input('perusahaan'))
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
