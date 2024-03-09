<?php

use App\Http\Controllers\TipoCambioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/variables-disponibles', [TipoCambioController::class, 'obtenerVariablesDisponibles']);
Route::get('/tasas-cambio', [TipoCambioController::class, 'obtenerTasasCambio']);
Route::post('/tasas-cambio', [TipoCambioController::class, 'store']);
Route::delete('/tasas-cambio/{id}', [TipoCambioController::class, 'destroy']);
