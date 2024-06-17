<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dinas;
use Illuminate\Support\Facades\DB;
use App\Events\DinasUpdated;
use Illuminate\Support\Facades\File;
class DinasController extends Controller
{
    public function index()
    {
        $dinasList = Dinas::all();
        return view('dinas.index', compact('dinasList'));
    }

    public function store(Request $request){
        $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
        if (!$perusahaan) {
            // Handle case when Perusahaan is not found
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }
        $pekerja = DB::table('pekerja')->where('pekerja.nama', $request->input('nama'))->first();
        $buktiPath = $request->file('bukti')->storeAs("perusahaan/{$perusahaan->nama}/Pekerja/{$pekerja->nama}/Dinas/Bukti",
        time() . '_' . $request->file('bukti')->getClientOriginalName(), 'public');
        $Dinas = Dinas::create([
            'id_perusahaan' => $perusahaan->id,
            'id_pekerja' => $pekerja->id,
            'tujuan' => $request->input('tujuan'),
            'tanggal_berangkat' => $request->input('tanggal_berangkat'),
            'tanggal_pulang' => $request->input('tanggal_pulang'),
            'kegiatan' => $request->input('kegiatan'),
            'bukti' => $buktiPath
        ]);
        event(new DinasUpdated($perusahaan->nama, $Dinas));
        return response()->json(['status' => 'success', 
        'message' => 'pekerja created successfully']);
    }
    public function update(Request $request, $id){
        // Find the existing dinas record by ID
        $dinas = DB::table('dinas')
            ->join('perusahaan', 'dinas.id_perusahaan', '=', 'perusahaan.id')
            ->join('pekerja', 'dinas.id_pekerja', '=', 'pekerja.id')
            ->select('dinas.*', 'perusahaan.nama as perusahaan_nama', 'pekerja.nama as pekerja_nama')
            ->where('dinas.id', $id)
            ->first();
        // Check if the dinas record exists
        if (!$dinas) {
            // Handle case when dinas is not found
            return response()->json(['error' => 'dinas not found'], 404);
        }
        $perusahaanNama = $dinas->perusahaan_nama;
        $pekerjaNama = $dinas->pekerja_nama;
        if ($request->hasFile('bukti')) {
            $buktiPath = public_path("storage/{$dinas->bukti}");
            File::delete($buktiPath);    
            $buktiPath = $request->file('bukti')->storeAs(
                "perusahaan/{$perusahaanNama}/Pekerja/{$pekerjaNama}/dinas/Bukti",
                time() . '_' . $request->file('bukti')->getClientOriginalName(),
                'public'
            );
            $dinas->bukti = $buktiPath;
            DB::table('dinas')
            ->where('id', $id)
            ->update(['bukti' => $buktiPath]);
        }
        DB::table('dinas')
        ->where('id', $id)
        ->update([
            'tanggal_berangkat' => $request->input('berangkat'),
            'tanggal_pulang' => $request->input('pulang'),
            'tujuan' => $request->input('tujuan'),
            'kegiatan' => $request->input('kegiatan'),
        ]);
        return response()->json(['status' => 'success', 'message' => 'dinas updated successfully']);
    }
    
    public function updatestatus(Request $request){
        $Dinas = Dinas::select('dinas.*', 'perusahaan.nama as nama_perusahaan')
        ->join('perusahaan', 'dinas.id_perusahaan', '=', 'perusahaan.id')
        ->where('dinas.id', $request->input('id'))
        ->first();
        if ($Dinas) {
            // Update the status field
            $Dinas->update([
                'status' => $request->input('status')
            ]);
            // event(new DinasUpdated($Dinas->nama_perusahaan, $Dinas));
            return response()->json(['message' => 'Dinas record updated successfully']);
        } else {
            // Handle the case where the record with the specified ID is not found
            return response()->json(['error' => $request->input('id')], 404);
        }
    }

    public function getDataPerusahaan($nama_perusahaan)
    {
        $dinasData = DB::table('dinas')
        ->join('perusahaan', 'dinas.id_perusahaan', '=', 'perusahaan.id')
        ->join('pekerja', 'dinas.id_pekerja', '=', 'pekerja.id')
        ->select('dinas.*', 'perusahaan.nama as nama_perusahaan', 'pekerja.nama as nama_pekerja')
        ->where('perusahaan.nama', $nama_perusahaan)
        ->get();
    
        return response()->json(['data' => $dinasData]);
    }
    
    public function getDataPekerja($nama_perusahaan, $nama_pekerja)
    {
        $dinasData = DB::table('dinas')
            ->join('perusahaan', 'dinas.id_perusahaan', '=', 'perusahaan.id')
            ->join('pekerja', 'dinas.id_pekerja', '=', 'pekerja.id')
            ->select('dinas.*', 'perusahaan.nama as nama_perusahaan', 'pekerja.nama as nama_pekerja')
            ->where('pekerja.nama', $nama_pekerja)
            ->where('perusahaan.nama', $nama_perusahaan)
            ->get();
    
        return response()->json(['data' => $dinasData]);
    }
    
    
    // public function getDataPekerjaDinas($id_perusahaan,$id_pekerja){
    //     $pekerja = DB::table('pekerja')
    //         ->join('perusahaan', 'pekerja.id_perusahaan', '=', 'perusahaan.id')
    //         ->select('pekerja')
    //         ->where('pekerja.id', $id_pekerja)
    //         ->where('perusahaan.nama', $id_perusahaan)
    //         ->first();
    //     return response()->json(['data' => $pekerja]);    
    // }
}
