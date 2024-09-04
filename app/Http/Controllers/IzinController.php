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
        $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');
        if (!$perusahaan) {
            // Handle case when Perusahaan is not found
            return response()->json(['error' => 'Perusahaan not found'], 404);
        }
        $pekerja = DB::table('pekerja')->where('pekerja.nama', $request->input('nama'))->first();
        $fileContent = file_get_contents($request->file('logo')->getRealPath());
        $encryptedContent = openssl_encrypt($fileContent, 'aes-256-cbc', $encryptionKey, 0, substr($encryptionKey, 0, 16));
        $fileName = time() . '_' . $request->file('bukti')->getClientOriginalName();
        $buktiPath = "perusahaan/{$perusahaan->nama}/Pekerja/{$pekerja->nama}/Izin/Bukti/{$fileName}";

        Storage::disk('public')->put($buktiPath, $encryptedContent);
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

    public function getDecryptedBukti($IzinId)
    {
        try {
            // Retrieve the Izin record from the database
            $Izin = Izin::where('id', $IzinId)->first();
    
            // Check if the Izin record exists
            if (!$Izin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Izin record not found'
                ], 404);
            }
    
            // Read the encrypted file content from storage
            $encryptedContent = Storage::disk('public')->get($Izin->bukti);
    
            // Decrypt the file content
            $encryptionKey = env('OPENSSL_ENCRYPTION_KEY');  
            $iv = substr($encryptionKey, 0, 16); // Initialization vector (IV) should be 16 bytes long
            $decryptedContent = openssl_decrypt($encryptedContent, 'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
    
            // Check if decryption was successful
            if ($decryptedContent === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error decrypting the file content'
                ], 500);
            }
    
            // Determine the file type based on the file extension or other metadata
            $fileExtension = pathinfo($Izin->bukti, PATHINFO_EXTENSION);
            $contentType = '';
    
            switch (strtolower($fileExtension)) {
                case 'pdf':
                    $contentType = 'application/pdf';
                    break;
                case 'jpg':
                case 'jpeg':
                    $contentType = 'image/jpeg';
                    break;
                case 'png':
                    $contentType = 'image/png';
                    break;
                case 'gif':
                    $contentType = 'image/gif';
                    break;
                default:
                    $contentType = 'application/octet-stream'; // Fallback for unknown file types
            }
    
            // Return the decrypted content with the correct content type
            return response($decryptedContent, 200)
                ->header('Content-Type', $contentType);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error processing the request',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }    
}
