<?php

use App\Http\Controllers\Api\FiestaController;
use App\Http\Controllers\Api\MunicipioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal JSON API (consumed by the calendar, map and search modules)
|--------------------------------------------------------------------------
*/

Route::name('api.')->middleware('throttle:120,1')->group(function () {
    Route::get('fiestas', [FiestaController::class, 'index'])->name('fiestas.index');
    Route::get('fiestas/proximas', [FiestaController::class, 'proximas'])->name('fiestas.proximas');
    Route::get('fiestas/cercanas', [FiestaController::class, 'cercanas'])->name('fiestas.cercanas');
    Route::post('fiestas/{fiesta}/share', [FiestaController::class, 'share'])
        ->middleware('throttle:20,1')
        ->name('fiestas.share');

    Route::get('municipios', [MunicipioController::class, 'search'])->name('municipios.search');
});
