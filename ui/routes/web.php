<?php

use App\Http\Controllers\EmulationController;
use Illuminate\Support\Facades\Route;

Route::get('/',               [EmulationController::class, 'index']);
Route::post('/payload',       [EmulationController::class, 'store'])->name('payload.store');
Route::get('/payload/{name}', [EmulationController::class, 'show'])->name('payload.show');
Route::delete('/payload/{name}', [EmulationController::class, 'destroy'])->name('payload.destroy');
