<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perusahaan;
use App\Models\Pekerja;
use Illuminate\Support\Facades\DB;
use App\Events\PekerjaUpdated;
use App\Models\Admin;
use Illuminate\Support\Facades\Storage;

class PekerjaController extends Controller
{
    public function checkEmail($email)
    {
        // Check if email exists in the pekerja table
        $pekerjaData = DB::table('pekerja')
            ->select('nama')
            ->where('email', $email)
            ->first();

        if ($pekerjaData) {
            // Email found in the pekerja table
            return response()->json(['data' => $pekerjaData, 'type' => 'pekerja']);
        } else {
            // Email not found in the pekerja table, search in the admin table
            $adminData = DB::table('admin')
                ->select('nama')
                ->where('email', $email)
                ->first();

            if ($adminData) {
                // Email found in the admin table
                return response()->json(['data' => $adminData, 'type' => 'admin']);
            } else {
                // Email not found in both tables
                return response()->json(['status' => 'error', 'message' => 'offline'], 404);
            }
        }
    }


    public function store(Request $request)
    {
        try {
            $profilePath = null;
            $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
            $namaPerusahaan = $perusahaan->nama;
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
            if (!$perusahaan) {
                // Handle case when Perusahaan is not found
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
            $nama = $request->input('nama');
            if ($request->hasFile('profile')) {
                $fileContent = file_get_contents($request->file('profile')->getRealPath());
                $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                $fileName = time() . '_' . $request->file('profile')->getClientOriginalName();
                $profilePath = "perusahaan/{$namaPerusahaan}/Pekerja/{$nama}/{$fileName}";
    
                Storage::disk('public')->put($profilePath, $encryptedContent);
            }
            $encryptedPassword = openssl_encrypt($request->input('password'), 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            $pekerja = Pekerja::create([
                'id_perusahaan' => $perusahaan->id,
                'email' => $request->input('email'),
                'password' => $encryptedPassword,
                'nama' => $request->input('nama'),
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'profile' => $profilePath
            ]);
            $pekerjaId = $pekerja->getKey();
            broadcast(new PekerjaUpdated($pekerja, $perusahaan->nama, 'Pekerja'));
            return response()->json([
                'status' => 'success',
                'message' => 'pekerja created successfully',
                'profile_path' => $profilePath,
                '$pekerja_Id ' => $pekerjaId
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');  
            // Check if the provided email belongs to a worker (pekerja)
            $pekerja = Pekerja::where("email", $request->input('email'))->first();
            if ($pekerja != null) {
                $encryptedPassword = openssl_encrypt($request->input('password'), 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                $pekerja->update([
                    'password' => $encryptedPassword
                ]);
                // Return success response
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pekerja updated successfully',
                    'pekerja' => $pekerja,
                ]);
            }
            // Check if the provided email belongs to an admin
            $admin = Admin::where("email", $request->input('email'))->first();
            if ($admin != null) {
                // Update the password for the admin
                $encryptedPassword = openssl_encrypt($request->input('password'), 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                $admin->update([
                    'password' => $encryptedPassword
                ]);
                // Return success response
                return response()->json([
                    'status' => 'success',
                    'message' => 'Admin updated successfully',
                    'admin' => $admin,
                ]);
            }
            // If neither a worker nor an admin was found with the provided email
            return response()->json([
                'status' => 'error',
                'message' => 'User not found for the provided email',
            ], 404);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDecryptedProfile($pekerjaId)
    {
        try {
            $pekerja = Pekerja::where('id', $pekerjaId)->first();

            if (!$pekerja) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pekerja not found'
                ], 404);
            }

            // Read the encrypted file content
            $encryptedContent = Storage::disk('public')->get($pekerja->profile);

            // Decrypt the file content
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');  
            $decryptedContent = openssl_decrypt($encryptedContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));

            // Return the decrypted file content
            return response($decryptedContent, 200)
                ->header('Content-Type', 'image/jpeg'); // Adjust header according to file type
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error decrypting profile',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function updateData(Request $request, $id)
    {
        try {
            $pekerja = Pekerja::where("id", $id)
                ->first();
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');  
            $nama = $pekerja->nama;
            if (!$pekerja) {
                return response()->json(['error' => $request->input('nama')], 404);
            }
            $perusahaan = DB::table('perusahaan')->where('perusahaan.id', $pekerja->id_perusahaan)->first();
            if (!$perusahaan) {
                // Handle case when Perusahaan is not found
                return response()->json(['error' => $request->all()], 404);
            }
            // $pekerja->update([
            //     'email' => $request->input('email'),
            //     'nama' =>  $request->input('nama'),
            //     'tanggal_lahir' => $request->input('tanggal_lahir'),
            // ]);
            if ($request->filled('nama')) {
                $updateData['nama'] = $request->input('nama');
                $nama = $request->input('nama');
            }

            if ($request->filled('email')) {
                $updateData['email'] = $request->input('email');
            }

            if ($request->filled('tanggal_lahir')) {
                $updateData['tanggal_lahir'] = $request->input('tanggal_lahir');
            }

            if ($request->hasFile('profile')) {
                $namaPerusahaan = $perusahaan->nama;
                $fileContent = file_get_contents($request->file('profile')->getRealPath());
                $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                $fileName = time() . '_' . $request->file('profile')->getClientOriginalName();
                $profilePath = "perusahaan/{$namaPerusahaan}/Pekerja/{$nama}/{$fileName}";

                Storage::disk('public')->put($profilePath, $encryptedContent);
            }
            // broadcast(new PekerjaUpdated($pekerja, $perusahaan->nama, 'Pekerja'));
            $perusahaan->update($updateData);
            return response()->json(['status' => 'success', 'message' => 'Pekerja updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function getPekerja($nama_perusahaan)
    {
        $pekerjadata = DB::table('pekerja')
            ->join('perusahaan', 'pekerja.id_perusahaan', '=', 'perusahaan.id')
            ->select('pekerja.*')
            ->where('perusahaan.nama', $nama_perusahaan)
            ->get();
        if ($pekerjadata) {
            return response()->json($pekerjadata);
        } else {
            return response()->json(['status' => 'error', 'message' => 'offline'], 404);
        }
    }
}
