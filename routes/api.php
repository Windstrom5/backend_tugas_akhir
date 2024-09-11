<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PerusahaanController;
use App\Http\Controllers\PekerjaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AbsenController;
use App\Http\Controllers\IzinController;
use App\Http\Controllers\DinasController;
use App\Http\Controllers\LemburController;
use App\Models\Admin;
use App\Models\Perusahaan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [LoginController::class, 'login']);
Route::post('/session', [LoginController::class, 'validateToken']);
Route::get('/GetPerusahaan', [PerusahaanController::class, 'index']);
Route::post('/DaftarPerusahaan', [PerusahaanController::class, 'store']);
Route::post('/Perusahaan/DaftarPekerja', [PekerjaController::class, 'store']);
Route::post('/Perusahaan/DaftarAdmin', [AdminController::class, 'store']);
Route::get('/Perusahaan/getPerusahaan/{nama_perusahaan}', [PerusahaanController::class, 'show']);
Route::get('/Presensi/getLocation/{nama_perusahaan}', [AbsenController::class, 'getPekerjaLocation']);
Route::put('/Presensi/UpdateLocation', [AbsenController::class, 'updateLocation']);
Route::post('/Presensi/Absensi', [AbsenController::class, 'absen']);
Route::get('/Perusahaan/getPekerja/{nama_perusahaan}', [PekerjaController::class, 'getPekerja']);
Route::put('/Perusahaan/editPasswordAdmin', [AdminController::class, 'editPassword']);
Route::get('/Perusahaan/getAnggota/{nama_perusahaan}', [PerusahaanController::class, 'showAnggota']);
Route::put('/Perusahaan/UpdateDataPekerja/{id}', [PekerjaController::class, 'updateData']);
Route::put('/Perusahaan/UpdateDataAdmin/{id}', [AdminController::class, 'updateData']);
Route::put('/Perusahaan/UpdateDataPerusahaan/{id}', [PerusahaanController::class, 'updateData']);
Route::get('/getAllSecretKeys', [PerusahaanController::class, 'index']);
Route::get('/Dinas/getDataDinasPerusahaan/{nama_perusahaan}', [DinasController::class, 'getDataPerusahaan']);
Route::get('/Dinas/getDataDinasPekerja/{nama_perusahaan}/{nama_pekerja}', [DinasController::class, 'getDataPekerja']);
Route::get('/Izin/getDataIzinPerusahaan/{nama_perusahaan}', [IzinController::class, 'getDataPerusahaan']);
Route::get('/Izin/getDataIzinPekerja/{nama_perusahaan}/{nama_pekerja}', [IzinController::class, 'getDataPekerja']);
Route::get('/Lembur/getDataLemburPerusahaan/{nama_perusahaan}', [LemburController::class, 'getDataPerusahaan']);
Route::get('/Lembur/getDataLemburPekerja/{nama_perusahaan}/{nama_pekerja}', [LemburController::class, 'getDataPekerja']);
Route::post('/Lembur/AddLembur', [LemburController::class, 'store']);
Route::post('/Dinas/AddDinas', [DinasController::class, 'store']);
Route::post('/Izin/AddIzin', [IzinController::class, 'store']);
Route::put('/Izin/UpdateStatusIzin', [IzinController::class, 'updatestatus']);
Route::put('/Dinas/UpdateStatusDinas', [DinasController::class, 'updatestatus']);
Route::put('/Lembur/UpdateStatusLembur', [LemburController::class, 'updatestatus']);
Route::put('/Izin/UpdateDataIzin/{id}', [IzinController::class, 'update']);
Route::put('/Dinas/UpdateDataDinas/{id}', [DinasController::class, 'update']);
Route::put('/Lembur/UpdateDataLembur/{id}', [LemburController::class, 'update']);
Route::delete('/Perusahaan/DeletePerusahaan/{id}', [PerusahaanController::class, 'destroy']);
Route::get('/Perusahaan/checkEmail/{email}', [PekerjaController::class, 'checkEmail']);
Route::post('/resetPassword', [PekerjaController::class, 'resetPassword']);
Route::get('/Presensi/getDataAbsenPerusahaan/{nama_perusahaan}', [AbsenController::class, 'getDataPerusahaan']);
Route::get('/Presensi/getDataAbsenPekerja/{nama_perusahaan}/{nama_pekerja}', [AbsenController::class, 'getDataPekerja']);
Route::get('/Perusahaan/decryptLogo/{perusahaanId}', [PerusahaanController::class, 'getDecryptedLogo']);
Route::get('/Pekerja/decryptProfile/{pekerjaId}', [PekerjaController::class, 'getDecryptedProfile']);
Route::get('/Admin/decryptProfile/{adminId}', [AdminController::class, 'getDecryptedProfile']);
Route::get('/Lembur/decryptBukti/{lemburId}', [LemburController::class, 'getDecryptedBukti']);
Route::get('/Dinas/decryptBukti/{dinasId}', [DinasController::class, 'getDecryptedBukti']);
Route::get('/Izin/decryptBukti/{izinId}', [IzinController::class, 'getDecryptedBukti']);
Route::get('/getData', [LoginController::class, 'getData']);
Route::get('/Lembur/GetSession/{lemburId}', [LemburController::class, 'getDataSession']);
