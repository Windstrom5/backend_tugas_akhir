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
        $password = $request->input('password');
        $perusahaan = $request->input('perusahaan');
        $admin = DB::table('admin')
        ->join('perusahaan', 'admin.id_perusahaan', '=', 'perusahaan.id')
        ->select('admin.*', 'perusahaan.nama as nama_perusahaan',)
        ->where('admin.email', $email)
        ->where('admin.password', md5($password))
        ->where('perusahaan.nama', $perusahaan)
        ->first();

        if (!$admin) {
            $pekerja = DB::table('pekerja')
            ->join('perusahaan', 'pekerja.id_perusahaan', '=', 'perusahaan.id')
            ->select('pekerja.*', 'perusahaan.nama as nama_perusahaan')
            ->where('pekerja.email', $email)
            ->where('pekerja.password', md5($password))
            ->where('perusahaan.nama', $perusahaan)
            ->first();
            if (!$pekerja){
                return response()->json(['error' => 'email Atau Password Salah'], 500);
            }else{
                $token = $this->generateUniqueToken($pekerja->id,'Pekerja');
                $loginSession = new SessionLoginPekerja;
                $loginSession ->id_pekerja = $pekerja->id;
                $loginSession ->token = $token;
                $loginSession ->created_at = now();
                $loginSession ->save();
                return response()->json(['token' => $token, 'user' => $pekerja,'Role' => 'Pekerja']);
            }
        }else{
            $token = $this->generateUniqueToken($admin->id,'Admin');
            $loginSession = new SessionLoginAdmin;
            $loginSession ->id_admin = $admin->id;
            $loginSession ->token = $token;
            $loginSession ->created_at = now();
            $loginSession ->save();
            return response()->json(['token' => $token,'user' => $admin,'Role' => 'Admin']);
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

            // Retry if the token already exists, up to a maximum number of attempts
        } while ($exists);

        return $token;
    }

    public function validateToken(Request $request)
    {
        $token = $request->input('token');
        $id = $request->input('id');
        $role = $request->input('role');
        if($role == 'Admin'){
            $loginSession = SessionLoginAdmin::where('token', $token)
                ->where('id_admin', $id)
                ->first();
            if ($loginSession && now()->isBefore($loginSession->created_at->addHours(8))) {
                // Token is valid
                $loginSession->update([
                    'created_at' => now(), // Renew the token creation timestamp
                ]);
                return response()->json(['status' => 'valid']);
            } else {
                // Token is invalid or expired
                return response()->json(['status' => 'invalid']);
            }
        }else{
            $loginSession = SessionLoginPekerja::where('token', $token)
                ->where('id_pekerja', $id)
                ->first();
            if ($loginSession && now()->isBefore($loginSession->created_at->addHours(8))) {
                // Token is valid
                $loginSession->update([
                    'created_at' => now(), // Renew the token creation timestamp
                ]);
                return response()->json(['status' => 'valid']);
            } else {
                // Token is invalid or expired
                return response()->json(['status' => 'invalid']);
            }
        }
    }
}
