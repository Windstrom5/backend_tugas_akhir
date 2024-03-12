<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dinas;
use Illuminate\Support\Facades\DB;
use App\Events\DinasUpdated;
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

    public function update(Request $request){
        $Dinas = Dinas::select('Dinas.*', 'perusahaan.nama as nama_perusahaan')
        ->join('perusahaan', 'Dinas.id_perusahaan', '=', 'perusahaan.id')
        ->where('Dinas.id', $request->input('id'))
        ->first();
        if ($Dinas) {
            // Update the status field
            $Dinas->update([
                'status' => $request->input('status')
            ]);
            event(new DinasUpdated($Dinas->nama_perusahaan, $Dinas));
            return response()->json(['message' => 'Dinas record updated successfully']);
        } else {
            // Handle the case where the record with the specified ID is not found
            return response()->json(['error' => 'Dinas record not found'], 404);
        }
    }

    public function getDataPerusahaan($nama_perusahaan)
    {
        $dinasData = DB::table('dinas')
            ->join('perusahaan', 'dinas.id_perusahaan', '=', 'perusahaan.id')
            ->select('dinas.*')
            ->where('perusahaan.nama', $nama_perusahaan)
            ->get();
    
        return response()->json(['data' => $dinasData]);
    }
    
    public function getDataPekerja($nama_perusahaan,$nama_pekerja)
    {
        $dinasData = DB::table('dinas')
            ->join('pekerja', 'dinas.id_pekerja', '=', 'pekerja.id')
            ->join('perusahaan', 'dinas.id_perusahaan', '=', 'perusahaan.id')
            ->select('dinas.*')
            ->where('pekerja.nama', $nama_pekerja)
            ->where('perusahaan.nama', $nama_perusahaan)
            ->get();
    
        return response()->json(['data' => $dinasData]);
    }
    
}
