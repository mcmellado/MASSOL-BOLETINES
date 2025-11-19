<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\BoletinController;
use setasign\Fpdi\Fpdi;



Route::get('/', function () {
    return redirect()->route('clientes.index');
});

Route::resource('clientes', ClienteController::class);

Route::resource('boletines', BoletinController::class)->parameters([
    'boletines' => 'boletin',
]);

Route::get('boletines/{boletin}/oficial', [BoletinController::class, 'pdfOficial'])
    ->name('boletines.pdf.oficial');

Route::get('/boletines/{boletin}/memoria-tecnica', [BoletinController::class, 'pdfMemoriaTecnica'])
    ->name('boletines.pdf.memoria');

