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
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->storeAs("perusahaan/{$nama}/logo",
                time() . '_' . $request->file('logo')->getClientOriginalName(), 'public');
            }
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
            $perusahaanId = $perusahaan->id;
            return response()->json(['status' => 'success', 
            'message' => 'Perusahaan created successfully', 
            'profile_path' => $logoPath,
            'id' => $perusahaanId]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating Perusahaan',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
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
            $jumlahadmin= count($admin); // Calculate total number of administrators and workers
            $jumlahPekerja = count($pekerja);
        }
        return response()->json([
            'perusahaan' => $perusahaan,
            'admin' => $admin->toArray(),
            'pekerja' => $pekerja->toArray(),
            'jumlahadmin' => $jumlahadmin,
            'jumlahpekerja' => $jumlahPekerja // Include the total count in the response
        ]);
    }

    // Update a specific perusahaan
    public function update(Request $request, $id)
    {
        try {
            $perusahaan = Perusahaan::find($id);
            if (!$perusahaan) {
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
    
            $updateData = [];
    
            if ($request->filled('nama')) {
                $updateData['nama'] = $request->input('nama');
            }
    
            if ($request->filled('latitude')) {
                $updateData['latitude'] = $request->input('latitude');
            }
    
            if ($request->filled('longitude')) {
                $updateData['longitude'] = $request->input('longitude');
            }
    
            if ($request->filled('batas_aktif')) {
                $updateData['batas_aktif'] = $request->input('batas_aktif');
            }
    
            if ($request->hasFile('logo')) {
                // Upload new logo
                $logoPath = $request->file('logo')->storeAs("perusahaan/{$perusahaan->nama}/logo",
                    time() . '_' . $request->file('logo')->getClientOriginalName(), 'public');
                $updateData['logo'] = $logoPath;
            }
    
            $perusahaan->update($updateData);
    
            return response()->json(['status' => 'success', 'message' => 'Perusahaan updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
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

        return response()->json([
            'perusahaan_data' => $perusahaanData
        ]);
    }

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
}
