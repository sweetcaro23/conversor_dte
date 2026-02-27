<?php

use App\Http\Controllers\DteController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect('/dte');
});
Route::get('/dte', [DteController::class, 'form']);
Route::post('/dte/generar', [DteController::class, 'generar']);
