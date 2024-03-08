<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dinas;
class DinasController extends Controller
{
    public function index()
    {
        $dinasList = Dinas::all();
        return view('dinas.index', compact('dinasList'));
    }

    public function create(Request $request){
        $absendata = DB::table('dinas')
        ->join('pekerja', 'dinas.id_pekerja', '=', 'pekerja.id')
        ->join('perusahaan', 'dinas.id_perusahaan', '=', 'perusahaan.id')
        ->select('dinas.*')
        ->where('pekerja.nama', $request->input('nama'))
        ->where('perusahaan.nama', $request->input('perusahaan'))
        ->first();
        $perusahaan = Perusahaan::where('nama', $request->input('perusahaan'))->first();
        $jamMasuk = Carbon::parse($perusahaan->jam_masuk);
        $jameluar = Carbon::parse($perusahaan->jam_keluar);
        $currentDateTime = now();
        if (!$perusahaan) {
            return response()->json(['status' => 'error', 'message' => 'Perusahaan not found'], 404);
        }
        // Check if current time is within 15 minutes of jam_masuk or jam_keluar
        if ($currentDateTime->diffInMinutes($jamMasuk) <= 15 || $currentDateTime->diffInMinutes($jamKeluar) <= 15) {
            if ($absendata) {
                $absen = Absen::findOrFail($absendata->id);
                $absen->update([
                    'jam_keluar' => $request->input('jam_keluar'),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'updated_at' => now()
                ]);

                return response()->json(['status' => 'success', 'message' => 'Absen Ended']);
            } else {
                // Entry does not exist, create a new one
                Absen::create([
                    'id_pekerja' => $request->id_pekerja,
                    'id_perusahaan' => $request->id_perusahaan,
                    'tanggal' => $request->input('tanggal'),
                    'jam_masuk' => $request->input('jam_masuk'),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'updated_at' => now(),
                ]);
                return response()->json(['status' => 'success', 'message' => 'Absen Started']);
            }
        } else {
            // Return an error if the current time is not within 15 minutes of jam_masuk or jam_keluar
            return response()->json(['status' => 'error', 'message' => 'Cannot start Absen. Current time is not within 15 minutes of jam_masuk or jam_keluar']);
        }
    }

    public function store(Request $request)
    {
        Dinas::create($request->all());
        return redirect()->route('dinas.index')->with('success', 'Dinas created successfully');
    }

    public function show(Dinas $dinas)
    {
        return view('dinas.show', compact('dinas'));
    }

    public function edit(Dinas $dinas)
    {
        return view('dinas.edit', compact('dinas'));
    }

    public function update(Request $request, Dinas $dinas)
    {
        $dinas->update($request->all());
        return redirect()->route('dinas.index')->with('success', 'Dinas updated successfully');
    }

    public function destroy(Dinas $dinas)
    {
        $dinas->delete();
        return redirect()->route('dinas.index')->with('success', 'Dinas deleted successfully');
    }
}
