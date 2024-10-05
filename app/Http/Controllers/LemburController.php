<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lembur;
use App\Models\session_lembur;
use App\Events\LemburUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

    public function getDataPekerja($nama_perusahaan, $nama_pekerja)
    {
        $lemburData = DB::table('lembur')
            ->join('pekerja', 'lembur.id_pekerja', '=', 'pekerja.id')
            ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
            ->select('lembur.*', 'perusahaan.nama as nama_perusahaan', 'pekerja.nama as nama_pekerja') // Corrected line
            ->where('pekerja.nama', $nama_pekerja)
            ->where('perusahaan.nama', $nama_perusahaan)
            ->get();
    
        return response()->json(['data' => $lemburData]);
    }
    

    public function getDataSession($LemburId)
    {
        $lemburData = DB::table('session_lembur')
            ->join('lembur', 'session_lembur.id_lembur', '=', 'lembur.id')
            ->join('pekerja', 'lembur.id_pekerja', '=', 'pekerja.id')
            ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
            ->select(
                'session_lembur.*',
                'lembur.id as id_lembur',
                'perusahaan.nama as nama_perusahaan',
                'pekerja.nama as nama_pekerja'
            )
            ->where('lembur.id', $LemburId)
            ->get();


        return response()->json(['data' => $lemburData]);
    }
    public function getDataSessionperusahaan($perusahaanId)
    {
        $lemburData = DB::table('session_lembur')
            ->join('lembur', 'session_lembur.id_lembur', '=', 'lembur.id')
            ->join('pekerja', 'lembur.id_pekerja', '=', 'pekerja.id')
            ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
            ->select(
                'session_lembur.*',
                'lembur.id as id_lembur',
                'perusahaan.nama as nama_perusahaan',
                'pekerja.nama as nama_pekerja'
            )
            ->where('perusahaan.id', $perusahaanId)
            ->get();


        return response()->json(['data' => $lemburData]);
    }
    public function addSession(Request $request)
    {
        $lembur = DB::table('lembur')->where('lembur.id', $request->input('id_lembur'))->first();
        $pekerja = DB::table('pekerja')->where('pekerja.nama', $request->input('nama'))->first();
        $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
        $fileContent = file_get_contents($request->file('bukti')->getRealPath());
        $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
        $fileName = time() . '_' . $request->file('bukti')->getClientOriginalName();
        $date = date('Y-m-d');
        $buktiPath = "perusahaan/{$perusahaan->nama}/Pekerja/{$pekerja->nama}/Lembur/{$date}/Bukti/Sesi/{$request->input('sesi')}/{$fileName}";
        Storage::disk('public')->put($buktiPath, $encryptedContent);
        $session = session_lembur::create([
            'id_lembur' => $lembur->id,
            'jam' => $request->input('jam'),
            'keterangan' => $request->input('keterangan'),
            'bukti' => $request->input('bukti'),
            'status'
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Session successfully'
        ]);
    }

    public function updateSession(Request $request, $id)
    {   
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
        $lembur = DB::table('lembur')->where('lembur.id', $request->input('id_lembur'))->first();
        $sessionlembur = DB::table('session_lembur')
            ->join('lembur', 'session_lembur.id_lembur', '=', 'lembur.id')
            ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
            ->join('pekerja', 'lembur.id_pekerja', '=', 'pekerja.id')
            ->select('session_lembur.*',
                'lembur.id as id_lembur',
                'perusahaan.nama as nama_perusahaan',
                'pekerja.nama as nama_pekerja')
            ->where('session_lembur.id', $id)
            ->first();
        // Check if the lembur record exists
        if (!$sessionlembur) {
            // Handle case when lembur is not found
            return response()->json(['error' => 'lembur not found'], 404);
        }
        $updateData = [];
        if ($request->input('keterangan')){
            $updateData ['keterangan'] = $request->input('keterangan');
        }
        if($request->input($request->input('jam'))){
            $updateData ['jam'] = $request->input('jam');
        }
        $perusahaanNama = $sessionlembur->perusahaan_nama;
        $pekerjaNama = $sessionlembur->pekerja_nama;
        $date = date('Y-m-d');
        if ($request->hasFile('bukti')) {
            $buktiPath = public_path("storage/{$sessionlembur->bukti}");
            $fileContent = file_get_contents($request->file('bukti')->getRealPath());
            $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            $fileName = time() . '_' . $request->file('bukti')->getClientOriginalName();
            $buktiPath = "perusahaan/{$perusahaanNama}/Pekerja/{$pekerjaNama}/Lembur/{$date}/Bukti/Sesi/{$request->input('sesi')}/{$fileName}";
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
            $fileContent = file_get_contents($request->file('bukti')->getRealPath());
            $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            Storage::disk('public')->put($buktiPath, $encryptedContent);
            File::delete($buktiPath);    
            $updateData['bukti'] = $buktiPath;
        }
        DB::table('session_lembur')
            ->where('id', $id)
            ->update([

            ]);
        return response()->json(['status' => 'success', 'message' => 'session updated successfully']);
    }

    public function updatestatusSession(Request $request)
    {
        $sessionlembur = session_lembur::select('session_lembur.*', 'perusahaan.nama as nama_perusahaan')
            ->join('lembur as lemburTable', 'session_lembur.id_lembur', '=', 'lemburTable.id')
            ->join('perusahaan', 'lemburTable.id_perusahaan', '=', 'perusahaan.id')
            ->where('session_lembur.id', $request->input('id'))
            ->first();    
        if ($sessionlembur) {
            // Update the status field
            $sessionlembur->update([
                'status' => $request->input('status')
            ]);
            // event(new LemburUpdated($lembur->nama_perusahaan, $lembur));
            return response()->json(['message' => 'Lembur session updated successfully']);
        } else {
            // Handle the case where the record with the specified ID is not found
            return response()->json(['error' => 'Lembur record not found'], 404);
        }
    }


    public function store(Request $request)
    {
        $perusahaan = DB::table('perusahaan')->where('perusahaan.nama', $request->input('nama_perusahaan'))->first();
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
        if (!$perusahaan) {
            // Handle case when Perusahaan is not found
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }
        // Convert time strings to DateTime objects
        // Attempt to parse the input times
        $waktuMasuk = \DateTime::createFromFormat('H:i', trim($request->input('waktu_masuk')));
        $waktuPulang = \DateTime::createFromFormat('H:i', trim($request->input('waktu_pulang')));

        // Check if parsing was successful
        if (!$waktuMasuk || !$waktuPulang) {

            return response()->json([
                'error' => 'Failed to parse time. waktu_masuk: ' . $request->input('waktu_masuk') .
                    ', waktu_pulang: ' . $request->input('waktu_pulang')
            ], 400);
        } else {
            $pekerja = DB::table('pekerja')->where('pekerja.nama', $request->input('nama'))->first();
            $fileContent = file_get_contents($request->file('bukti')->getRealPath());
            $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            $fileName = time() . '_' . $request->file('bukti')->getClientOriginalName();
            $date = date('Y-m-d');
            $buktiPath = "perusahaan/{$perusahaan->nama}/Pekerja/{$pekerja->nama}/Lembur/Request/{$date}/{$fileName}";
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

            // event(new LemburUpdated($lembur));
            return response()->json([
                'status' => 'success',
                'message' => 'Lembur created successfully'
            ]);
        }
    }

    public function updatestatus(Request $request)
    {
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
    public function update(Request $request, $id)
    {
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
        $lembur = DB::table('lembur')
            ->join('perusahaan', 'lembur.id_perusahaan', '=', 'perusahaan.id')
            ->join('pekerja', 'lembur.id_pekerja', '=', 'pekerja.id')
            ->select('lembur.*', 'perusahaan.nama as perusahaan_nama', 'pekerja.nama as pekerja_nama')
            ->where('lembur.id', $id)
            ->first();
        $lemburId = $id;
        if (!$lembur) {
            // Handle case when lembur is not found
            return response()->json(['error' => 'lembur not found'], 404);
        }
        $updateData = [];
        if ($request->input('tanggal')){
            $updateData ['tanggal'] = $request->input('tanggal');
        }
        if($request->input('masuk')){
            $updateData ['waktu_masuk'] = $request->input('masuk');
        }
        if($request->input('pulang')){
            $updateData ['waktu_pulang'] = $request->input('pulang');
        }
        if($request->input('pekerjaan')){
            $updateData ['pekerjaan'] = $request->input('pekerjaan');
        }
        $perusahaanNama = $lembur->perusahaan_nama;
        $pekerjaNama = $lembur->pekerja_nama;
        if ($request->hasFile('bukti')) {
            $fileName = time() . '_' . $request->file('bukti')->getClientOriginalName();
            $date = date('Y-m-d');
            $buktiPath = public_path("storage/{$lembur->bukti}");
            File::delete($buktiPath);    
            $buktiPath = "perusahaan/{$perusahaanNama}/Pekerja/{$pekerjaNama}/Lembur/Request/{$date}/{$fileName}";
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
            $fileContent = file_get_contents($request->file('bukti')->getRealPath());
            $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
            Storage::disk('public')->put($buktiPath, $encryptedContent);
            $updateData['bukti'] = $buktiPath;
        }
        DB::table('lembur')->where('id', $lemburId)->update($updateData);

        return response()->json(['status' => 'success', 'message' => 'lembur updated successfully']);
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
    public function getDecryptedSessionBukti($SessionId)
    {
        try {
            $Lembur = session_lembur::where('id', $SessionId)->first();

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
