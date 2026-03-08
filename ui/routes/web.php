<?php

use App\Http\Controllers\EmulationController;
use App\Http\Controllers\PageCastRecorderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Emulation Application Routes
|--------------------------------------------------------------------------
|
| Your routes are at ROOT level, which means the /emulation prefix comes
| from your server configuration (Apache/Nginx virtual host).
|
| Routes defined as /recorder will become /emulation/recorder when accessed.
|
*/

// ========================================
// Main Emulation Routes
// ========================================

Route::get('/',                      [EmulationController::class, 'index']);
Route::post('/payload',              [EmulationController::class, 'store'])->name('payload.store');
Route::post('/payload/upload',       [EmulationController::class, 'upload'])->name('payload.upload');
Route::get('/script/{name}/tokens',  [EmulationController::class, 'scriptTokens'])->name('script.tokens');
Route::get('/script/{name}/content', [EmulationController::class, 'scriptContent'])->name('script.content');
Route::get('/payload/{name}',        [EmulationController::class, 'show'])->name('payload.show');
Route::delete('/payload/{name}',     [EmulationController::class, 'destroy'])->name('payload.destroy');
Route::post('/payload/{name}/run',   [EmulationController::class, 'run'])->name('payload.run');
Route::get('/payload/{name}/launch', [EmulationController::class, 'launch'])->name('payload.launch');
Route::post('/settings',             [EmulationController::class, 'saveSettings'])->name('settings.save');


// ========================================
// PageCast Recorder Routes
// ========================================

// Recorder UI Routes
Route::get('/recorder',          [PageCastRecorderController::class, 'index'])->name('recorder.index');
Route::get('/recorder/sessions', [PageCastRecorderController::class, 'sessions'])->name('recorder.sessions');
Route::get('/recorder/scripts',  [PageCastRecorderController::class, 'scripts'])->name('recorder.scripts');

// Recorder API Routes
Route::post('/recorder/start-session',      [PageCastRecorderController::class, 'startSession'])->name('recorder.start-session');
Route::post('/recorder/save-actions',       [PageCastRecorderController::class, 'saveActions'])->name('recorder.save-actions');
Route::post('/recorder/save-tokens',        [PageCastRecorderController::class, 'saveTokens'])->name('recorder.save-tokens');
Route::post('/recorder/save-credentials',   [PageCastRecorderController::class, 'saveCredentials'])->name('recorder.save-credentials');
Route::post('/recorder/stop-session',       [PageCastRecorderController::class, 'stopSession'])->name('recorder.stop-session');
Route::post('/recorder/generate-script',    [PageCastRecorderController::class, 'generateScript'])->name('recorder.generate-script');
Route::get('/recorder/session/{id}',        [PageCastRecorderController::class, 'getSession'])->name('recorder.get-session');
Route::delete('/recorder/session/{id}',     [PageCastRecorderController::class, 'deleteSession'])->name('recorder.delete-session');
