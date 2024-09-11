<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perusahaan;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use App\Events\AdminUpdated;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
class AdminController extends Controller
{
    public function store(Request $request)
    {
        try {
            $profilePath = null;
            $perusahaan =  DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
            $namaPerusahaan = $perusahaan->nama;
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');  
            if (!$perusahaan) {
                // Handle case when Perusahaan is not found
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
            $nama = $request->input('nama');
            if ($request->hasFile('profile')) {
                $namaPerusahaan = $perusahaan->nama;
                $fileContent = file_get_contents($request->file('profile')->getRealPath());
                $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                $fileName = time() . '_' . $request->file('profile')->getClientOriginalName();
                $profilePath = "perusahaan/{$namaPerusahaan}/Admin/{$nama}/{$fileName}";

                Storage::disk('public')->put($profilePath, $encryptedContent);
            }
            // $encryptedPassword = openssl_encrypt($request->input('password'), 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            $hashedPassword = Hash::make($request->input('password'));
            $admin = Admin::create([
                'id_perusahaan' => $perusahaan->id,
                'email' => $request->input('email'),
                'password' => $hashedPassword,
                'nama' => $nama,
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'profile' => $profilePath,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Admin created successfully', 'admin' => $admin]);
        } catch (\Exception $e) {
            // Get the error message from the exception object
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function editPassword(Request $request)
    {
        $perusahaan = DB::table('perusahaan')
            ->where('nama', $request->input('nama_perusahaan'))
            ->first();
        
        if ($perusahaan) {
            $id_perusahaan = $perusahaan->id;
            $admin = Admin::where('id_perusahaan', $id_perusahaan)
                ->where('email', $request->input('email')) // Fix: Change $email to $request->input('email')
                ->first();
            
            if ($admin) {
                $admin->password = md5($request->input('password')); // Make sure to hash the password
                $admin->save();

                return response()->json(['message' => 'Admin details updated successfully']);
            } else {
                return response()->json(['message' => 'Admin not found'], 404);
            }
        } else {
            return response()->json(['message' => $request->input('nama_perusahaan')], 404);
        }
    }
    public function updateData(Request $request,$id)
    {
        try {
            // Retrieve the admin record
            $adminId = $id;
            $admin = DB::table('admin')->where('id', $adminId)->first();
            
            if (!$admin) {
                return response()->json(['error' => 'Admin not found'], 404);
            }
    
            // Retrieve the associated perusahaan
            $perusahaan = DB::table('perusahaan')->where('id', $admin->id_perusahaan)->first();
            if (!$perusahaan) {
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
    
            // Prepare data to update
            $updateData = [];
            if ($request->filled('nama')) {
                $updateData['nama'] = $request->input('nama');
            }
            if ($request->filled('email')) {
                $updateData['email'] = $request->input('email');
            }
            if ($request->filled('tanggal_lahir')) {
                $updateData['tanggal_lahir'] = $request->input('tanggal_lahir');
            }
    
            // Handle file upload for the profile field
            if ($request->hasFile('profile')) {
                // Delete the previous profile image if it exists
                if (!empty($admin->profile) && Storage::disk('public')->exists($admin->profile)) {
                    Storage::disk('public')->delete($admin->profile);
            
                    // Get the directory of the previous profile image
                    $directory = dirname($admin->profile);
            
                    // Check if the directory is empty
                    $files = Storage::disk('public')->files($directory);
                    if (empty($files)) {
                        Storage::disk('public')->deleteDirectory($directory);
                    }
                }
            
                $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
                $namaPerusahaan = $perusahaan->nama;
                $fileContent = file_get_contents($request->file('profile')->getRealPath());
                $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                $fileName = time() . '_' . $request->file('profile')->getClientOriginalName();
                $profilePath = "perusahaan/{$namaPerusahaan}/Admin/{$admin->nama}/{$fileName}";
                $updateData['profile'] = $profilePath;
            
                Storage::disk('public')->put($profilePath, $encryptedContent);
            }            
    
            // Update the admin record
            DB::table('admin')->where('id', $adminId)->update($updateData);
            $updatedAdmin = DB::table('admin')->where('id', $adminId)->first();

            return response()->json(['status' => 'success', 'message' => 'Admin updated successfully', 'admin' => $updatedAdmin]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

    public function promote(Request $request){
        $pekerjadata = DB::table('pekerja')
        ->join('perusahaan', 'pekerja.id_perusahaan', '=', 'perusahaan.id')
        ->select('pekerja.*')
        ->where('perusahaan.nama',  $request->input('nama_perusahaan'))
        ->where('pekerja.nama',  $request->input('nama'))
        ->first();
        if ($pekerjadata) {
            // Create a new record in the admin table
            DB::table('admin')->insert([
                'id_perusahaan' => $pekerjadata->id_perusahaan,
                'email' => $pekerjadata->email,
                'password' => $pekerjadata->password,
                'nama' => $pekerjadata->nama,
                'tanggal_lahir' => $pekerjadata->tanggal_lahir,
                'profile' => $pekerjadata->profile
            ]);
    
            // Delete the record from the pekerja table
            DB::table('pekerja')->where('id', $pekerjadata->id)->delete();
    
            // Return success response
            return response()->json(['message' => 'Data promoted successfully']);
        } else {
            // Return error response if data not found
            return response()->json(['error' => 'Data not found'], 404);
        }
    }
    public function getDecryptedProfile($AdminId)
    {
        try {
            $Admin = Admin::where('id', $AdminId)->first();

            if (!$Admin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Admin not found'
                ], 404);
            }

            // Read the encrypted file content
            $encryptedContent = Storage::disk('public')->get($Admin->profile);

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
}
