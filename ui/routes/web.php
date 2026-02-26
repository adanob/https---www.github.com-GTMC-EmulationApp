<?php

use App\Http\Controllers\EmulationController;
use Illuminate\Support\Facades\Route;

Route::get('/',                     [EmulationController::class, 'index']);
Route::post('/payload',             [EmulationController::class, 'store'])->name('payload.store');
Route::post('/payload/upload',      [EmulationController::class, 'upload'])->name('payload.upload');
Route::get('/script/{name}/tokens', [EmulationController::class, 'scriptTokens'])->name('script.tokens');
Route::get('/payload/{name}',       [EmulationController::class, 'show'])->name('payload.show');
Route::delete('/payload/{name}',    [EmulationController::class, 'destroy'])->name('payload.destroy');
Route::post('/payload/{name}/run',  [EmulationController::class, 'run'])->name('payload.run');
Route::post('/settings',            [EmulationController::class, 'saveSettings'])->name('settings.save');
