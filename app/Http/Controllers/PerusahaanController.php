<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Perusahaan;
use App\Models\Admin;
use App\Models\Pekerja;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PerusahaanController extends Controller
{
    // Show a list of all perusahaan
    public function index()
    {
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
        $perusahaans = Perusahaan::all();

        $decryptedPerusahaans = $perusahaans->map(function ($perusahaan) use ($encryptionKey) {
            $perusahaan->secret_key = openssl_decrypt($perusahaan->secret_key, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            return $perusahaan;
        });

        return response()->json(['perusahaan' => $decryptedPerusahaans]);
    }

    public function store(Request $request)
    {
        try {
            $nama = $request->input('nama');
            $logoPath = null;
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
            if ($request->hasFile('logo')) {
                $fileContent = file_get_contents($request->file('logo')->getRealPath());
                $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                $fileName = time() . '_' . $request->file('logo')->getClientOriginalName();
                $logoPath = "perusahaan/{$nama}/logo/{$fileName}";

                Storage::disk('public')->put($logoPath, $encryptedContent);
            }
            $secretKey = $request->input('secret_key');
            $encryptedSecretKey = openssl_encrypt($secretKey, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            // $encryptedSecretKey = Hash::make($request->input('secret_key'));
            $perusahaan = Perusahaan::create([
                'nama' => $nama,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'jam_masuk' => $request->input('jam_masuk'),
                'jam_keluar' => $request->input('jam_keluar'),
                'batas_aktif' => $request->input('batas_aktif'),
                'secret_key' => $encryptedSecretKey,
                'logo' =>  $logoPath,
                'holiday' => $request->input('holiday'),
            ]);
            $perusahaanId = $perusahaan->id;
            return response()->json([
                'status' => 'success',
                'message' => 'Perusahaan created successfully',
                'profile_path' => $logoPath,
                'id' => $perusahaanId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating Perusahaan',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function getAllData()
    {
        $data = Perusahaan::all();
        return response()->json($data);
    }

    public function getDecryptedLogo($perusahaanId)
    {
        try {
            $perusahaan = Perusahaan::where('id', $perusahaanId)->first();

            if (!$perusahaan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Perusahaan not found'
                ], 404);
            }

            // Read the encrypted file content
            $encryptedContent = Storage::disk('public')->get($perusahaan->logo);

            // Decrypt the file content
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
            $decryptedContent = openssl_decrypt($encryptedContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));

            // Return the decrypted file content
            return response($decryptedContent, 200)
                ->header('Content-Type', 'image/jpeg'); // Adjust header according to file type
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error decrypting logo',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
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
        } else {
            $admin = Admin::where('id_perusahaan', $perusahaan->id)->get();
            $pekerja = Pekerja::where('id_perusahaan', $perusahaan->id)->get();
            $jumlahadmin = count($admin); // Calculate total number of administrators and workers
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
    public function updateData(Request $request, $id)
    {
        try {
            $perusahaan = DB::table('perusahaan')->where('id', $id)->first();
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

            $jamMasuk = $request->input('jammasuk');
            $jamKeluar = $request->input('jamkeluar');

            if ($jamMasuk) {
                $validatedTime = $this->validateTimeFormat($jamMasuk);
                if ($validatedTime) {
                    $updateData['jam_masuk'] = $validatedTime;
                } else {
                    return response()->json(['error' => $jamMasuk], 400);
                }
            }

            if ($jamKeluar) {
                $validatedTime = $this->validateTimeFormat($jamKeluar);
                if ($validatedTime) {
                    $updateData['jam_keluar'] = $validatedTime;
                } else {
                    return response()->json(['error' => $jamKeluar], 400);
                }
            }
            if ($request->filled('holiday')) {
                $updateData['holiday'] = $request->input('holiday');
            }
            if ($request->hasFile('logo')) {
                if (!empty($perusahaan->logo) && Storage::disk('public')->exists($perusahaan->logo)) {
                    Storage::disk('public')->delete($perusahaan->logo);

                    // Get the directory of the previous profile image
                    $directory = dirname($perusahaan->logo);

                    // Check if the directory is empty
                    $files = Storage::disk('public')->files($directory);
                    if (empty($files)) {
                        Storage::disk('public')->deleteDirectory($directory);
                    }
                }

                $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
                $fileContent = file_get_contents($request->file('logo')->getRealPath());
                $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                $fileName = time() . '_' . $request->file('logo')->getClientOriginalName();
                $logoPath = "perusahaan/{$perusahaan->nama}/logo/{$fileName}";
                $updateData['logo'] = $logoPath;
                Storage::disk('public')->put($logoPath, $encryptedContent);
            }

            // Update the company data in the database
            DB::table('perusahaan')->where('id', $id)->update($updateData);

            // Fetch the updated company data
            $updatedPerusahaan = DB::table('perusahaan')->where('id', $id)->first();

            return response()->json(['status' => 'success', 'perusahaan' => $updatedPerusahaan]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function validateTimeFormat($timeString)
    {
        try {
            $carbonTime = Carbon::createFromFormat('H:i:s', $timeString);
            return $carbonTime->format('H:i:s');
        } catch (\Exception $e) {
            return false;
        }
    }
    public function getPerusahaanData($namaPerusahaan)
    {
        $perusahaan = Perusahaan::where('nama', $namaPerusahaan)->first();

        if (!$perusahaan) {
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }

        // $pekerjaan = $perusahaan->pekerja;
        // $perusahaanData = [
        //     'nama' => $perusahaan->nama,
        //     'latitude' => $perusahaan->latitude,
        //     'longitude' => $perusahaan->longitude,
        //     'jam_masuk' => $perusahaan->jam_masuk,
        //     'jam_keluar' => $perusahaan->jam_keluar,
        //     'batas_aktif' => $perusahaan->batas_aktif,
        //     'logo' => $this->getLogoUrl($pekerjaan->profile),
        //     // Add other data as needed
        // ];

        return response()->json([
            'perusahaan_data' => $perusahaan
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
