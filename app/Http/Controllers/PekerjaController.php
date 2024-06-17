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
            $pekerja = Pekerja::create([
                'id_perusahaan' => $perusahaan->id,
                'email' => $request->input('email'),
                'password' => md5($request->input('password')),
                'nama' => $nama,
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'profile' => $profilePath
            ]);
            $pekerjaId = $pekerja->getKey();
            broadcast(new PekerjaUpdated($pekerja, $perusahaan->nama, 'Pekerja'));
            return response()->json([
                'status' => 'success',
                'message' => 'pekerja created successfully',
                'profile_path' => $profilePath,
                'perusahaan_id' => $perusahaan->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            // Check if the provided email belongs to a worker (pekerja)
            $pekerja = Pekerja::where("email", $request->input('email'))->first();
            if ($pekerja != null) {
                // Update the password for the worker
                $pekerja->update([
                    'password' => md5($request->input('password'))
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
                $admin->update([
                    'password' => md5($request->input('password'))
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


    public function updateData(Request $request, $id)
    {

        $pekerja = Pekerja::where("id", $id)
            ->first();
        if (!$pekerja) {
            return response()->json(['error' => $request->input('nama')], 404);
        }
        $perusahaan = DB::table('perusahaan')->where('perusahaan.id', $pekerja->id_perusahaan)->first();
        if (!$perusahaan) {
            // Handle case when Perusahaan is not found
            return response()->json(['error' => $request->all()], 404);
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
        broadcast(new PekerjaUpdated($pekerja, $perusahaan->nama, 'Pekerja'));
        return response()->json([
            'status' => 'success',
            'message' => 'Pekerja updated successfully',
            'pekerja' => $pekerja,
        ]);
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
