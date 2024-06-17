<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Izin;
use Illuminate\Support\Facades\DB;
use App\Events\IzinUpdated;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
class IzinController extends Controller
{
    //
    public function getDataPerusahaan($nama_perusahaan)
    {
        $izinData = DB::table('izin')
        ->join('perusahaan', 'izin.id_perusahaan', '=', 'perusahaan.id')
        ->join('pekerja', 'izin.id_pekerja', '=', 'pekerja.id')
        ->select('izin.*', 'perusahaan.nama as nama_perusahaan', 'pekerja.nama as nama_pekerja')
        ->where('perusahaan.nama', $nama_perusahaan)
        ->get();
    
        return response()->json(['data' => $izinData]);
    }
    
    public function getDataPekerja($nama_perusahaan,$nama_pekerja)
    {
        $izinData = DB::table('izin')
        ->join('pekerja', 'izin.id_pekerja', '=', 'pekerja.id')
        ->join('perusahaan', 'izin.id_perusahaan', '=', 'perusahaan.id')
        ->select('izin.*', 'perusahaan.nama as nama_perusahaan', 'pekerja.nama as nama_pekerja')
        ->where('pekerja.nama', $nama_pekerja)
        ->where('perusahaan.nama', $nama_perusahaan)
        ->get();
    
        return response()->json(['data' => $izinData]);
    }
    
    public function store(Request $request){
        $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
        if (!$perusahaan) {
            // Handle case when Perusahaan is not found
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }
        $pekerja = DB::table('pekerja')->where('pekerja.nama', $request->input('nama'))->first();
        $buktiPath = $request->file('bukti')->storeAs("perusahaan/{$perusahaan->nama}/Pekerja/{$pekerja->nama}/Izin/Bukti",
        time() . '_' . $request->file('bukti')->getClientOriginalName(), 'public');
        $Izin = Izin::create([
            'id_perusahaan' => $perusahaan->id,
            'id_pekerja' => $pekerja->id,
            'tanggal' => $request->input('tanggal'),
            'kategori' => $request->input('kategori'),
            'alasan' => $request->input('alasan'),
            'bukti' => $buktiPath
        ]);
        event(new IzinUpdated($perusahaan->nama, $Izin));
        return response()->json(['status' => 'success', 
        'message' => 'pekerja created successfully']);
    }
    
    public function update(Request $request, $id){
        // Find the existing Izin record by ID
        $izin = DB::table('izin')
            ->join('perusahaan', 'izin.id_perusahaan', '=', 'perusahaan.id')
            ->join('pekerja', 'izin.id_pekerja', '=', 'pekerja.id')
            ->select('izin.*', 'perusahaan.nama as perusahaan_nama', 'pekerja.nama as pekerja_nama')
            ->where('izin.id', $id)
            ->first();
        // Check if the Izin record exists
        if (!$izin) {
            // Handle case when Izin is not found
            return response()->json(['error' => 'Izin not found'], 404);
        }
        $perusahaanNama = $izin->perusahaan_nama;
        $pekerjaNama = $izin->pekerja_nama;
        $izin->tanggal = $request->input('tanggal');
        $izin->kategori = $request->input('kategori');
        $izin->alasan = $request->input('alasan');
        if ($request->hasFile('bukti')) {
            $buktiPath = public_path("storage/{$izin->bukti}");
            File::delete($buktiPath);    
            $buktiPath = $request->file('bukti')->storeAs(
                "perusahaan/{$perusahaanNama}/Pekerja/{$pekerjaNama}/Izin/Bukti",
                time() . '_' . $request->file('bukti')->getClientOriginalName(),
                'public'
            );
            $izin->bukti = $buktiPath;
            DB::table('izin')
            ->where('id', $id)
            ->update(['bukti' => $buktiPath]);
        }
        DB::table('izin')
        ->where('id', $id)
        ->update([
            'tanggal' => $request->input('tanggal'),
            'kategori' => $request->input('kategori'),
            'alasan' => $request->input('alasan'),
        ]);
        return response()->json(['status' => 'success', 'message' => 'Izin updated successfully']);
    }
    
    public function updatestatus(Request $request){
        $Izin = Izin::select('Izin.*', 'perusahaan.nama as nama_perusahaan')
        ->join('perusahaan', 'Izin.id_perusahaan', '=', 'perusahaan.id')
        ->where('Izin.id', $request->input('id'))
        ->first();
        if ($Izin) {
            // Update the status field
            $Izin->update([
                'status' => $request->input('status')
            ]);
                // event(new IzinUpdated($Izin->nama_perusahaan, $Izin));
            return response()->json(['message' => 'Izin record updated successfully']);
        } else {
            // Handle the case where the record with the specified ID is not found
            return response()->json(['error' => 'Izin record not found'], 404);
        }
    }
}
