<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\BoletinController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Rutas públicas (SIN login)
|--------------------------------------------------------------------------
*/

// Login
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

/*
|--------------------------------------------------------------------------
| Rutas protegidas (CON login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Home → clientes
    Route::get('/', function () {
        return redirect()->route('clientes.index');
    })->name('home');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Clientes
    Route::resource('clientes', ClienteController::class);

    // Boletines
    Route::resource('boletines', BoletinController::class)->parameters([
        'boletines' => 'boletin',
    ]);

    Route::get('boletines/{boletin}/oficial', [BoletinController::class, 'pdfOficial'])
        ->name('boletines.pdf.oficial');

    Route::get('boletines/{boletin}/memoria-tecnica', [BoletinController::class, 'pdfMemoriaTecnica'])
        ->name('boletines.pdf.memoria');
});
