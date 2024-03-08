<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perusahaan;
use App\Models\Pekerja;

class PekerjaController extends Controller
{
    public function store(Request $request)
    {
        try {
            $perusahaan = Perusahaan::where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
            $namaPerusahaan = $perusahaan->nama;
            if (!$perusahaan) {
                // Handle case when Perusahaan is not found
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
            $profilePath = $request->file('profile')->storeAs("perusahaan/{$namaPerusahaan}/Pekerja/{$nama}", 'profile.png', 'public');
            $nama = $request->input('nama');
            $pekerja = Pekerja::create([
                'id_perusahaan' => $perusahaan->id,
                'email' => $request->input('email'),
                'password' => md5($request->input('password')),
                'nama' => $nama,
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'profile' => $profilePath
            ]);
            $pekerjaId = $pekerja->getKey();
            return response()->json(['status' => 'success', 
            'message' => 'pekerja created successfully', 
            'profile_path' => $profilePath,
            'perusahaan_id' => $perusahaanId]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function getPekerja($nama_perusahan){
        $pekerjadata = DB::table('pekerja')
        ->join('perusahaan', 'pekerja.id_perusahaan', '=', 'perusahaan.id')
        ->select('pekerja.*')
        ->where('perusahaan.nama', $request->input('perusahaan'))
        ->get();
        if ($pekerjadata) {
            return response()->json($pekerjadata);
        }else{
            return response()->json(['status' => 'error', 'message' => 'offline'], 404);
        }
    }
    
    private function getLogoUrl($profilePath)
    {
        // Assuming 'public' disk is used for storage
        return Storage::disk('public')->url($profilePath);
    }
}
