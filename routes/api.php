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
Route::post('/DaftarPekerja', [PekerjaController::class, 'store']);
Route::post('/DaftarAdmin', [AdminController::class, 'store']);
Route::post('/messages', [MessageController::class, 'store']);
Route::get('/getPerusahaan/{nama_perusahaan}', [PerusahaanController::class, 'show']);
Route::get('/getLocation/{nama_perusahaan}', [AbsenController::class, 'getPekerjaLocation']);
Route::post('/UpdateLocation', [AbsenController::class, 'updateLocation']);
Route::post('/Absensi', [AbsenController::class, 'absen']);
Route::get('/getPekerja/{nama_perusahaan}', [PekerjaController::class, 'getPekerja']);
Route::put('/editPasswordAdmin', [AdminController::class, 'editPassword']);
Route::get('/getAnggota/{nama_perusahaan}', [PerusahaanController::class, 'showAnggota']);
Route::put('/EditPekerjaData', [PekerjaController::class, 'updateData']);
Route::get('/getAllSecretKeys', [PerusahaanController::class, 'index']);
Route::get('/getDataDinasPerusahaan/{nama_perusahaan}', [DinasController::class, 'getDataPerusahaan']);
Route::get('/getDataDinasPekerja/{nama_perusahaan}/{nama_pekerja}', [DinasController::class, 'getDataPekerja']);
Route::get('/getDataIzinPerusahaan/{nama_perusahaan}', [IzinController::class, 'getDataPerusahaan']);
Route::get('/getDataIzinPekerja/{nama_perusahaan}/{nama_pekerja}', [IzinController::class, 'getDataPekerja']);
Route::get('/getDataLemburPerusahaan/{nama_perusahaan}', [LemburController::class, 'getDataPerusahaan']);
Route::get('/getDataLemburPekerja/{nama_perusahaan}/{nama_pekerja}', [LemburController::class, 'getDataPekerja']);