<?php

use App\Http\Controllers\BoxController;
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
Route::post('ticket-print', [TicketController::class, 'ticketPrint']);


Route::middleware(['auth:sanctum'])->group(function () {

    //Rutas para el controlador de usuarios
    Route::get('users', [AuthController::class, 'index']);
    Route::post('user-register', [AuthController::class, 'store']);
    Route::get('user-show/{id}', [AuthController::class, 'show']);
    Route::put('user-update/{id}', [AuthController::class, 'update']);
    Route::put('user-update-password/{id}', [AuthController::class, 'updatePassword']);
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
    Route::get('rfc-valid-update/{rfc}/{id}', [Providercontroller::class, 'validRfcUpdate']);
    Route::get('search/{search}', [Providercontroller::class, 'search']);


    //Rutas para el controlador de productos
    Route::get('products', [ProductController::class, 'index']);
    Route::post('product-register', [ProductController::class, 'store']);
    Route::get('product-show/{id}', [ProductController::class, 'show']);
    Route::get('product-provider/{id}', [ProductController::class, 'productsByProvider']);
    Route::get('product-show-barcode/{barcode}', [ProductController::class, 'showBarcode']);
    Route::put('product-update/{id}', [ProductController::class, 'update']);
    Route::delete('product-delete/{id}', [ProductController::class, 'destroy']);
    Route::get('barcode-valid/{barcode}', [ProductController::class, 'validBarcode']);
    Route::get('barcode-valid-update/{barcode}/{id}', [ProductController::class, 'validBarcodeUpdate']);
    Route::get('search-product/{search}', [ProductController::class, 'search']);



    //Rutas para el controlador de tickets
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('ticket-register', [TicketController::class, 'store']);
    Route::get('ticket-show/{id}', [TicketController::class, 'show']);
    Route::delete('ticket-delete/{id}', [TicketController::class, 'destroy']);
    Route::get('product-detect/{bardcode}', [TicketController::class, 'detect']);


    //Rutas para el controlador cajas o boxes
    Route::get('boxes', [BoxController::class, 'index']);
    Route::post('box-register', [BoxController::class, 'store']);
    Route::get('box-show/{id}', [BoxController::class, 'show']);
    Route::get('box-show-barcode/{barcode}', [BoxController::class, 'showBarcode']);
    Route::put('box-update/{id}', [BoxController::class, 'update']);
    Route::delete('box-delete/{id}', [BoxController::class, 'destroy']);
    Route::get('box-barcode-valid/{barcode}', [BoxController::class, 'validBarcode']);
    Route::get('boxbarcode-valid-update/{barcode}/{id}', [BoxController::class, 'validBarcodeUpdate']);


});
