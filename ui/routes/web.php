<?php

use App\Http\Controllers\EmulationController;
use Illuminate\Support\Facades\Route;

Route::get('/',                     [EmulationController::class, 'index']);
Route::post('/payload',             [EmulationController::class, 'store'])->name('payload.store');
Route::post('/payload/upload',      [EmulationController::class, 'upload'])->name('payload.upload');
Route::get('/script/{name}/tokens',  [EmulationController::class, 'scriptTokens'])->name('script.tokens');
Route::get('/script/{name}/content', [EmulationController::class, 'scriptContent'])->name('script.content');
Route::get('/payload/{name}',       [EmulationController::class, 'show'])->name('payload.show');
Route::delete('/payload/{name}',    [EmulationController::class, 'destroy'])->name('payload.destroy');
Route::post('/payload/{name}/run',  [EmulationController::class, 'run'])->name('payload.run');
Route::get('/payload/{name}/launch', [EmulationController::class, 'launch'])->name('payload.launch');
Route::post('/settings',            [EmulationController::class, 'saveSettings'])->name('settings.save');

/**
 * PageCast Recorder Routes
 */

// PageCast Recorder Routes
Route::prefix('emulation/recorder')->middleware(['auth'])->group(function() {

    // ========================================
    // UI Routes (Blade Views)
    // ========================================

    // Main recorder interface
    Route::get('/', 'PageCastRecorderController@index')
        ->name('recorder.index');

    // Session management page
    Route::get('/sessions', 'PageCastRecorderController@sessions')
        ->name('recorder.sessions');

    // Generated scripts page
    Route::get('/scripts', 'PageCastRecorderController@scripts')
        ->name('recorder.scripts');

    // ========================================
    // API Routes (JSON Responses)
    // ========================================

    // Start new recording session
    Route::post('/start-session', 'PageCastRecorderController@startSession')
        ->name('recorder.start-session');

    // Save recorded actions to session
    Route::post('/save-actions', 'PageCastRecorderController@saveActions')
        ->name('recorder.save-actions');

    // Save tokens for session
    Route::post('/save-tokens', 'PageCastRecorderController@saveTokens')
        ->name('recorder.save-tokens');

    // Save credentials for session (encrypted)
    Route::post('/save-credentials', 'PageCastRecorderController@saveCredentials')
        ->name('recorder.save-credentials');

    // Stop recording session
    Route::post('/stop-session', 'PageCastRecorderController@stopSession')
        ->name('recorder.stop-session');

    // Generate Python script from session
    Route::post('/generate-script', 'PageCastRecorderController@generateScript')
        ->name('recorder.generate-script');

    // Get session data by ID
    Route::get('/session/{id}', 'PageCastRecorderController@getSession')
        ->name('recorder.get-session');

    // Delete session by ID
    Route::delete('/session/{id}', 'PageCastRecorderController@deleteSession')
        ->name('recorder.delete-session');
});

// ========================================
// Optional: Public API Routes (if needed)
// ========================================

// Uncomment if you need external access to recorder API
/*
Route::prefix('api/recorder')->middleware(['api', 'api_key'])->group(function() {
    Route::post('/start', 'PageCastRecorderController@startSession');
    Route::post('/actions', 'PageCastRecorderController@saveActions');
    Route::post('/generate', 'PageCastRecorderController@generateScript');
});
*/
