<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Perusahaan;
use App\Models\Admin;
use App\Models\Pekerja;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PerusahaanController extends Controller
{
    // Show a list of all perusahaan
    public function index()
    {
        $perusahaan = Perusahaan::all();
        return response()->json(['perusahaan' => $perusahaan]);
    }

    public function store(Request $request)
    {
        try {
            $nama = $request->input('nama');
            $logoPath = $request->file('logo')->storeAs("perusahaan/{$nama}/logo",
             time() . '_' . $request->file('logo')->getClientOriginalName(), 'public');
            $perusahaan = Perusahaan::create([
                'nama' => $nama,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'jam_masuk' => $request->input('jam_masuk'),
                'jam_keluar' => $request->input('jam_keluar'),
                'batas_aktif' => $request->input('batas_aktif'),
                'secret_key' => $request->input('secret_key'),
                'logo' =>  $logoPath, 
            ]);
            $perusahaanId = $perusahaan->getKey();
            return response()->json(['status' => 'success', 
            'message' => 'Perusahaan created successfully', 
            'profile_path' => $logoPath,
            'perusahaan_id' => $perusahaanId]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function getAllData() {
        $data = Perusahaan::all();
        return response()->json($data);
    }
    

    private function generateSecretKey()
    {
        do {
            // Generate a new token
            $token = Str::random(60);
            $encryptedToken = Crypt::encryptString($token);
            $exists = DB::table('perusahaan')->where('secret_key', $encryptedToken)->exists();
        } while ($exists);
    
        return $encryptedToken;
    }

    // Show the details of a specific perusahaan
    public function show($nama_perusahaan)
    {
        $perusahaan = Perusahaan::where('nama', $nama_perusahaan)->first();
        if (!$perusahaan) {
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }
        return response()->json(['perusahaan' => $perusahaan]);
    }
    public function showAnggota($nama_perusahaan)
    {
        $perusahaan = Perusahaan::where('nama', $nama_perusahaan)->first();
        if (!$perusahaan) {
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }else{
            $admin = Admin::where('id_perusahaan', $perusahaan->id)->get();
            $pekerja = Pekerja::where('id_perusahaan', $perusahaan->id)->get();
        }
        return response()->json([
            'perusahaan' => $perusahaan,
            'admin' => $admin->toArray(),
            'pekerja' => $pekerja->toArray(),
        ]);
    }

    // Update a specific perusahaan
    public function update(Request $request, $id)
    {
        $perusahaan = Perusahaan::find($id);

        if (!$perusahaan) {
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }

        // Validate request data
        $request->validate([
            'nama' => 'required|string|max:255',
            'latitude' => 'required|string|max:255',
            'longitude' => 'required|string|max:255',
            'batas_aktif' => 'required|date',
        ]);

        // Update the perusahaan
        $perusahaan->update([
            'nama' => $request->input('nama'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'batas_aktif' => $request->input('batas_aktif'),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Perusahaan updated successfully']);
    }

    // Delete a specific perusahaan
    public function destroy($id)
    {
        $perusahaan = Perusahaan::find($id);

        if (!$perusahaan) {
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }

        // Delete the perusahaan
        $perusahaan->delete();

        return response()->json(['status' => 'success', 'message' => 'Perusahaan deleted successfully']);
    }
    
    public function getPerusahaanData($namaPerusahaan)
    {
        $perusahaan = Perusahaan::where('nama', $namaPerusahaan)->first();

        if (!$perusahaan) {
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }

        $pekerjaan = $perusahaan->pekerja;

        $perusahaanData = [
            'nama' => $perusahaan->nama,
            'latitude' => $perusahaan->latitude,
            'longitude' => $perusahaan->longitude,
            'jam_masuk' => $perusahaan->jam_masuk,
            'jam_keluar' => $perusahaan->jam_keluar,
            'batas_aktif' => $perusahaan->batas_aktif,
            'logo' => $this->getLogoUrl($pekerjaan->profile),
            // Add other data as needed
        ];

        return response()->json($perusahaanData);
    }
}
