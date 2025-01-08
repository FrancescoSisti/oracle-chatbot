<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\TrainingController;

// Welcome e Chat routes (pubbliche)
Route::get('/', function () {
    return view('welcome');
});

Route::get('/chatbot', [ChatBotController::class, 'index'])->name('chatbot.index');
Route::post('/chatbot/message', [ChatBotController::class, 'processMessage'])->name('chatbot.message');

// Training routes (protette da autenticazione)
Route::middleware(['auth'])->group(function () {
    Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
    Route::post('/training', [TrainingController::class, 'store'])->name('training.store');
    Route::post('/training/{trainingData}/verify', [TrainingController::class, 'verify'])->name('training.verify');
    Route::delete('/training/{trainingData}', [TrainingController::class, 'delete'])->name('training.delete');
    Route::post('/training/import', [TrainingController::class, 'import'])->name('training.import');
    Route::post('/training/import/template', [TrainingController::class, 'importTemplate'])->name('training.import.template');
    Route::post('/training/import/json', [TrainingController::class, 'importJson'])
        ->name('training.import.json');
});

// Auth routes
require __DIR__.'/auth.php';
