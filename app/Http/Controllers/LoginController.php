<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SessionLoginPekerja;
use App\Models\SessionLoginAdmin;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->input('email');
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
        $encryptedPassword = openssl_encrypt($request->input('password'), 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
        
        // Check in the admin table
        $admin = DB::table('admin')
            ->select('admin.*')
            ->where('admin.email', $email)
            ->first();
    
        if (!$admin) {
            // Check in the pekerja table
            $pekerja = DB::table('pekerja')
                ->select('pekerja.*')
                ->where('pekerja.email', $email)
                ->first();
    
            if (!$pekerja) {
                return response()->json(['error' => 'not found'], 500);
            } else {
                // Decrypt the stored password for pekerja
                $decryptedPassword = openssl_decrypt($pekerja->password, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
    
                // Verify the decrypted password against the input password
                if ($decryptedPassword !== $request->input('password')) {
                    return response()->json(['error' => 'Invalid password: ' . $decryptedPassword], 401);
                }
    
                $perusahaan = DB::table('perusahaan')
                    ->select('perusahaan.*')
                    ->where('perusahaan.id', $pekerja->id_perusahaan)
                    ->first();
                if ($perusahaan) {
                    // Decrypt the secret_key
                    $perusahaan->secret_key = openssl_decrypt($perusahaan->secret_key, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
                }    
                return response()->json(['perusahaan' => $perusahaan, 'user' => $pekerja, 'Role' => 'Pekerja']);
            }
        } else {
            // Decrypt the stored password for admin
            $decryptedPassword = openssl_decrypt($admin->password, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
    
            // Verify the decrypted password against the input password
            if ($decryptedPassword !== $request->input('password')) {
                return response()->json(['error' => 'Invalid password: ' . $decryptedPassword], 401);
            }
    
            $perusahaan = DB::table('perusahaan')
                ->select('perusahaan.*')
                ->where('perusahaan.id', $admin->id_perusahaan)
                ->first();
    
            if ($perusahaan) {
                // Decrypt the secret_key
                $perusahaan->secret_key = openssl_decrypt($perusahaan->secret_key, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            }       
            return response()->json(['perusahaan' => $perusahaan, 'user' => $admin, 'Role' => 'Admin']);
        }
    }
    
    public function getData(Request $request)
    {
        $id = $request->input('id');
        $jenis = $request->input('jenis');
        $presensiId = $request->input('id_presensi');  // Note: $presensiId can be null
    
        if($jenis == "Admin"){
            $admin = DB::table('admin')
                ->select('admin.*')
                ->where('admin.id', $id)
                ->first();
            $perusahaan = DB::table('perusahaan')
                ->select('perusahaan.*')
                ->where('perusahaan.id', $admin->id_perusahaan)
                ->first();
            return response()->json(['perusahaan' => $perusahaan, 'user' => $admin, 'Role' => 'Admin']);
        } else {
            $pekerja = DB::table('pekerja')
                ->select('pekerja.*')
                ->where('pekerja.id', $id)
                ->first();
            
            if (!$pekerja) {
                return response()->json(['error' => 'not found'], 500);
            } else {
                $perusahaan = DB::table('perusahaan')
                    ->select('perusahaan.*')
                    ->where('perusahaan.id', $pekerja->id_perusahaan)
                    ->first();
    
                // Check if presensiId is provided and fetch presensi only if it's not null
                if ($presensiId !== null) {
                    $presensi = DB::table('absen')
                        ->select('absen.*')
                        ->where('absen.id', $presensiId)
                        ->first();
                } else {
                    $presensi = null;
                }
    
                // Prepare the response
                $response = [
                    'perusahaan' => $perusahaan,
                    'user' => $pekerja,
                    'Role' => 'Pekerja'
                ];
    
                // Add presensi data only if it exists
                if ($presensi) {
                    $response['presensi'] = $presensi;
                }
    
                return response()->json($response);
            }
        }
    }
    
    private function generateUniqueToken($Id_User,$role)
    {
        do {
            // Generate a new token
            $token = Str::random(60);
            // Check if the token already exists in the database
            if($role == 'Admin'){
                $exists = DB::table('login_sessions_admin')
                ->where('token', $token)
                ->where('id_admin', $Id_User)
                ->exists();
            }else{
                $exists = DB::table('login_sessions_pekerja')
                ->where('token', $token)
                ->where('id_pekerja', $Id_User)
                ->exists();
            }
        } while ($exists);

        return $token;
    }

    // public function validateToken(Request $request)
    // {
    //     $token = $request->input('token');
    //     $id = $request->input('id');
    //     $role = $request->input('role');
    //     if($role == 'Admin'){
    //         $loginSession = SessionLoginAdmin::where('token', $token)
    //             ->where('id_admin', $id)
    //             ->first();
    //         if ($loginSession && now()->isBefore($loginSession->created_at->addHours(8))) {
    //             // Token is valid
    //             $loginSession->update([
    //                 'created_at' => now(), // Renew the token creation timestamp
    //             ]);
    //             return response()->json(['status' => 'valid']);
    //         } else {
    //             // Token is invalid or expired
    //             return response()->json(['status' => 'invalid']);
    //         }
    //     }else{
    //         $loginSession = SessionLoginPekerja::where('token', $token)
    //             ->where('id_pekerja', $id)
    //             ->first();
    //         if ($loginSession && now()->isBefore($loginSession->created_at->addHours(8))) {
    //             // Token is valid
    //             $loginSession->update([
    //                 'created_at' => now(), // Renew the token creation timestamp
    //             ]);
    //             return response()->json(['status' => 'valid']);
    //         } else {
    //             // Token is invalid or expired
    //             return response()->json(['status' => 'invalid']);
    //         }
    //     }
    // }
}
