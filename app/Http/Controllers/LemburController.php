<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lembur;
use App\Events\LemburUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class LemburController extends Controller
{
    public function getDataPerusahaan($nama_perusahaan)
    {
        $lemburData = DB::table('lembur')
        ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
        ->join('pekerja', 'lembur.id_pekerja', '=', 'pekerja.id')
        ->select('lembur.*', 'perusahaan.nama as nama_perusahaan', 'pekerja.nama as nama_pekerja')
        ->where('perusahaan.nama', $nama_perusahaan)
        ->get();
    
        return response()->json(['data' => $lemburData]);
    }
    
    public function getDataPekerja($nama_perusahaan,$nama_pekerja)
    {
        $lemburData = DB::table('lembur')
            ->join('pekerja', 'lembur.id_pekerja', '=', 'pekerja.id')
            ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
            ->select('lembur.*,perusahaan.nama as nama_perusahaan,pekerja.nama as nama_pekerja')
            ->where('pekerja.nama', $nama_pekerja)
            ->where('perusahaan.nama', $nama_perusahaan)
            ->get();
    
        return response()->json(['data' => $lemburData]);
    }

    public function store(Request $request){
        $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
        if (!$perusahaan) {
            // Handle case when Perusahaan is not found
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }
         // Convert time strings to DateTime objects
        $waktuMasuk = \DateTime::createFromFormat('H:i', $request->input('waktu_masuk'));
        $waktuPulang = \DateTime::createFromFormat('H:i', $request->input('waktu_pulang'));

        if ($waktuMasuk < $perusahaan->jam_keluar || $waktuPulang < $perusahaan->jam_masuk) {
            // Handle the case where the times are not valid
            return response()->json(['error' => 'Invalid time'], 400);
        }else{
            $pekerja = DB::table('pekerja')->where('pekerja.nama', $request->input('nama'))->first();
            $buktiPath = $request->file('bukti')->storeAs("perusahaan/{$perusahaan->nama}/Pekerja/{$pekerja->nama}/Lembur/Bukti",
            time() . '_' . $request->file('bukti')->getClientOriginalName(), 'public');
            $lembur = Lembur::create([
                'id_perusahaan' => $perusahaan->id,
                'id_pekerja' => $pekerja->id,
                'tanggal' => $request->input('tanggal'),
                'waktu_masuk' => $request->input('waktu_masuk'),
                'waktu_pulang' => $request->input('waktu_pulang'),
                'pekerjaan' =>  $request->input('pekerjaan'),
                'bukti' => $buktiPath
            ]);
            event(new LemburUpdated($lembur));
            return response()->json(['status' => 'success', 
            'message' => 'Absen created successfully']);
        }
    }

    public function update(Request $request){
        $lembur = Lembur::select('lembur.*', 'perusahaan.nama as nama_perusahaan')
        ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
        ->where('lembur.id', $request->input('id'))
        ->first();
        if ($lembur) {
            // Update the status field
            $lembur->update([
                'status' => $request->input('status')
            ]);
            event(new LemburUpdated($lembur->nama_perusahaan, $lembur));
            return response()->json(['message' => 'Lembur record updated successfully']);
        } else {
            // Handle the case where the record with the specified ID is not found
            return response()->json(['error' => 'Lembur record not found'], 404);
        }
    }
}
