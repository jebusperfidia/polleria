<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProviderController;

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

//Ruta para realizar el login de usuarios
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    
    //Rutas para el controlador de usuarios
    Route::post('user-register', [AuthController::class, 'store']);
    Route::get('users', [AuthController::class, 'index']);
    Route::get('user-show/{id}', [AuthController::class, 'show']);
    Route::put('user-update/{id}', [AuthController::class, 'update']);
    Route::delete('user-delete/{id}', [Authcontroller::class, 'destroy']);
    Route::get('user-valid/{user}', [AuthController::class, 'validUser']);
    Route::get('valid-token', [AuthController::class, 'validToken']);
    Route::get('logout', [AuthController::class, 'logout']);

    //Rutas para el controlador de proveedores
    Route::get('providers', [ProviderController::class, 'index']);
    Route::post('provider-register', [ProviderController::class, 'store']);
    Route::get('provider-show/{id}', [ProviderController::class, 'show']);
    Route::put('provider-update/{id}', [ProviderController::class, 'update']);
    Route::delete('provider-delete/{id}', [Providercontroller::class, 'destroy']);
    Route::get('rfc-valid/{rfc}', [Providercontroller::class, 'validRFC']);
    Route::get('search/{search}', [Providercontroller::class, 'search']);


    //Rutas para el controlador de productos
    Route::get('products', [ProductController::class, 'index']);
    Route::post('product-register', [ProductController::class, 'store']);
    Route::get('product-show/{id}', [ProductController::class, 'show']);
    Route::put('product-update/{id}', [ProductController::class, 'update']);
    Route::delete('product-delete/{id}', [ProductController::class, 'destroy']);
    Route::get('barcode-valid/{barcode}', [ProductController::class, 'validBarcode']);

    
    //Rutas para el controlador de tickets
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('ticket-register', [TicketController::class, 'store']);
    Route::get('ticket-show/{id}', [TicketController::class, 'show']);
    Route::delete('ticket-delete/{id}', [TicketController::class, 'destroy']);
    Route::get('product-detect/{bardcode}', [TicketController::class, 'detect']);
    
});