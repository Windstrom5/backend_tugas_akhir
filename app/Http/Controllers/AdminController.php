<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perusahaan;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
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
            $nama = $request->input('nama');
            $profilePath = $request->file('profile')->storeAs("perusahaan/{$namaPerusahaan}/Admin/{$nama}", 'profile.png', 'public');
            $admin = Admin::create([
                'id_perusahaan' => $perusahaan->id,
                'email' => $request->input('email'),
                'password' => md5($request->input('password')),
                'nama' => $nama,
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'profile' => $profilePath,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Admin created successfully', 'file_path' => $profilePath]);
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
}
