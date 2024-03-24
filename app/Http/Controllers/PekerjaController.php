<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perusahaan;
use App\Models\Pekerja;
use Illuminate\Support\Facades\DB;
use App\Events\PekerjaUpdated;
use Illuminate\Support\Facades\Storage;

class PekerjaController extends Controller
{
    public function store(Request $request)
    {
        try {
            $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
            $namaPerusahaan = $perusahaan->nama;
            if (!$perusahaan) {
                // Handle case when Perusahaan is not found
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
            $nama = $request->input('nama');
            $profilePath = $request->file('profile')->storeAs("perusahaan/{$namaPerusahaan}/Pekerja/{$nama}", 
            time() . '_' . $request->file('profile')->getClientOriginalName(), 'public');
            $pekerja = Pekerja::create([
                'id_perusahaan' => $perusahaan->id,
                'email' => $request->input('email'),
                'password' => md5($request->input('password')),
                'nama' => $nama,
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'profile' => $profilePath
            ]);
            $pekerjaId = $pekerja->getKey();
            broadcast(new PekerjaUpdated($pekerja,$perusahaan->nama,'Pekerja'));
            return response()->json(['status' => 'success', 
            'message' => 'pekerja created successfully', 
            'profile_path' => $profilePath,
            'perusahaan_id' => $perusahaan->id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request){
        try {
            $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
            $namaPerusahaan = $perusahaan->nama;
            if (!$perusahaan) {
                // Handle case when Perusahaan is not found
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
            $pekerja = Pekerja::where("email", $request->input('email'))
            ->where("id_perusahaan", $perusahaan->id)
            ->first();
            $pekerja->update([
                'password' => md5($request->input('password'))
            ]);
            broadcast(new PekerjaUpdated($pekerja,$perusahaan->nama,'Pekerja'));
            return response()->json([
                'status' => 'success',
                'message' => 'Pekerja updated successfully',
                'pekerja' => $pekerja,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateData(Request $request){
        $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
        if (!$perusahaan) {
            // Handle case when Perusahaan is not found
             return response()->json(['error' => $request->all()], 404);
         }
        $pekerja = Pekerja::where("nama", $request->input('old_nama'))
        ->where("id_perusahaan", $perusahaan->id)
        ->first(); 
        if (!$pekerja) {
             return response()->json(['error' => $request->input('nama')], 404);
         }
        $pekerja->update([
            'email' => $request->input('email'),
            'nama' =>  $request->input('nama'),
            'tanggal_lahir' => $request->input('tanggal_lahir'),
        ]);
        // Handle file upload for the profile field
        if ($request->hasFile('profile')) {
            $profilePath = $request->file('profile')->storeAs(
                "perusahaan/{$perusahaan->nama}/Pekerja/{$pekerja->nama}",
                time() . '_' . $request->file('profile')->getClientOriginalName(),
                'public'
            );
            // Update the profile field in the database
            $pekerja->update(['profile' => $profilePath]);
        }
        broadcast(new PekerjaUpdated($pekerja,$perusahaan->nama,'Pekerja'));
        return response()->json([
            'status' => 'success',
            'message' => 'Pekerja updated successfully',
            'pekerja' => $pekerja,
        ]);
    }

    public function getPekerja($nama_perusahaan){
        $pekerjadata = DB::table('pekerja')
        ->join('perusahaan', 'pekerja.id_perusahaan', '=', 'perusahaan.id')
        ->select('pekerja.*')
        ->where('perusahaan.nama', $nama_perusahaan)
        ->get();
        if ($pekerjadata) {
            return response()->json($pekerjadata);
        }else{
            return response()->json(['status' => 'error', 'message' => 'offline'], 404);
        }
    }
}
