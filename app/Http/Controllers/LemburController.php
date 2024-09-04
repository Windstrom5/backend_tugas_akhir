<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lembur;
use App\Models\session_lembur;
use App\Events\LemburUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
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
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
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
            $fileContent = file_get_contents($request->file('logo')->getRealPath());
            $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            $fileName = time() . '_' . $request->file('bukti')->getClientOriginalName();
            $date = date('Y-m-d');
            $buktiPath = "perusahaan/{$perusahaan->nama}/Pekerja/{$pekerja->nama}/Lembur/{$date}/Bukti/Sesi/1/{$fileName}";
            Storage::disk('public')->put($buktiPath, $encryptedContent);
            $lembur = Lembur::create([
                'id_perusahaan' => $perusahaan->id,
                'id_pekerja' => $pekerja->id,
                'tanggal' => $request->input('tanggal'),
                'waktu_masuk' => $request->input('waktu_masuk'),
                'waktu_pulang' => $request->input('waktu_pulang'),
                'pekerjaan' =>  $request->input('pekerjaan'),
                'bukti' => $buktiPath
            ]);
            $session = session_lembur::create([
                'id_lembur' => $request->input('id_lembur'),
                'jam'=> $request->input('jam'),
                'keterangan'=> $request->input('keterangan'),
                'bukti'=> $request->input('bukti'),
            ]);

            // event(new LemburUpdated($lembur));
            return response()->json(['status' => 'success', 
            'message' => 'Absen created successfully']);
        }
    }

    public function updatestatus(Request $request){
        $lembur = Lembur::select('lembur.*', 'perusahaan.nama as nama_perusahaan')
        ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
        ->where('lembur.id', $request->input('id'))
        ->first();
        if ($lembur) {
            // Update the status field
            $lembur->update([
                'status' => $request->input('status')
            ]);
            // event(new LemburUpdated($lembur->nama_perusahaan, $lembur));
            return response()->json(['message' => 'Lembur record updated successfully']);
        } else {
            // Handle the case where the record with the specified ID is not found
            return response()->json(['error' => 'Lembur record not found'], 404);
        }
    }
    public function update(Request $request, $id){
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
        $lembur = DB::table('lembur')
            ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
            ->join('pekerja', 'lembur.id_pekerja', '=', 'pekerja.id')
            ->select('lembur.*', 'perusahaan.nama as perusahaan_nama', 'pekerja.nama as pekerja_nama')
            ->where('lembur.id', $id)
            ->first();
        // Check if the lembur record exists
        if (!$lembur) {
            // Handle case when lembur is not found
            return response()->json(['error' => 'lembur not found'], 404);
        }
        $perusahaanNama = $lembur->perusahaan_nama;
        $pekerjaNama = $lembur->pekerja_nama;
        if ($request->hasFile('bukti')) {
            $fileContent = file_get_contents($request->file('logo')->getRealPath());
            $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            $fileName = time() . '_' . $request->file('bukti')->getClientOriginalName();
            $buktiPath = "perusahaan/{$perusahaanNama}/Pekerja/{$pekerjaNama}/Lembur/Bukti/{$fileName}";
            Storage::disk('public')->put($buktiPath, $encryptedContent);
            $lembur->bukti = $buktiPath;
            DB::table('lembur')
            ->where('id', $id)
            ->update(['bukti' => $buktiPath]);
        }
        DB::table('lembur')
        ->where('id', $id)
        ->update([
            'tanggal' => $request->input('tanggal'),
            'waktu_masuk' => $request->input('masuk'), 
            'waktu_pulang' => $request->input('pulang'),
            'pekerjaan' => $request->input('pekerjaan'),
        ]);
        return response()->json(['status' => 'success', 'message' => 'lembur updated successfully']);
    }

    public function updatesession(Request $request){
        $session = session_lembur::create([
            'id_lembur' => $request->input('id_lembur'),
            'jam'=> $request->input('jam'),
            'keterangan'=> $request->input('keterangan'),
            'bukti'=> $request->input('bukti'),
            'status'
        ]);
    }

    public function getDecryptedBukti($LemburId)
    {
        try {
            $Lembur = Lembur::where('id', $LemburId)->first();

            if (!$Lembur) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Admin not found'
                ], 404);
            }

            // Read the encrypted file content
            $encryptedContent = Storage::disk('public')->get($Lembur->bukti);

            // Decrypt the file content
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');  
            $decryptedContent = openssl_decrypt($encryptedContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));

            // Return the decrypted file content
            return response($decryptedContent, 200)
                ->header('Content-Type', 'image/jpeg'); // Adjust header according to file type
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error decrypting profile',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}