<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perusahaan;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use App\Events\AdminUpdated;
class AdminController extends Controller
{
    public function store(Request $request)
    {
        try {
            $profilePath = null;
            $perusahaan =  DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
            $namaPerusahaan = $perusahaan->nama;
            if (!$perusahaan) {
                // Handle case when Perusahaan is not found
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
            $nama = $request->input('nama');
            if ($request->hasFile('profile')) {
                $profilePath = $request->file('profile')->storeAs(
                    "perusahaan/{$namaPerusahaan}/Admin/{$nama}",
                    time() . '_' . $request->file('profile')->getClientOriginalName(),
                    'public'
                );
            }
    
            $admin = Admin::create([
                'id_perusahaan' => $perusahaan->id,
                'email' => $request->input('email'),
                'password' => md5($request->input('password')),
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
    public function updateData(Request $request)
    {
        try {
            $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
            $namaPerusahaan = $perusahaan->nama;
            if (!$perusahaan) {
                // Handle case when Perusahaan is not found
                return response()->json(['error' => 'Perusahaan not found'], 404);
            }
            $Admin = DB::table('admin')->findOrFail($request->input('id'));
            $Admin->update([
                'email' => $request->input('email'),
                'nama' =>  $request->input('nama'),
                'tanggal_lahir' => $request->input('tanggal_lahir'),
            ]);
            // Handle file upload for the profile field
            if ($request->hasFile('profile')) {
                $profilePath = $request->file('profile')->storeAs(
                    "perusahaan/{$namaPerusahaan}/Admin/{$Admin->nama}",
                    time() . '_' . $request->file('profile')->getClientOriginalName(),
                    'public'
                );
                // Update the profile field in the database
                $Admin->update(['profile' => $profilePath]);
            }
            broadcast(new AdminUpdated($namaPerusahaan,$Admin));
            return response()->json([
                'status' => 'success',
                'message' => 'Admin updated successfully',
                'Admin' => $Admin,
            ]);
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
}
