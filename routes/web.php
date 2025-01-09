<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\InteractiveLearningController;
use App\Http\Controllers\Api\TrainingStatusController;

// Welcome e Chat routes (pubbliche)
Route::get('/', function () {
    return view('welcome');
});

Route::get('/chatbot', [ChatBotController::class, 'index'])->name('chatbot.index');
Route::post('/chatbot/message', [ChatBotController::class, 'processMessage'])->name('chatbot.message');
Route::post('/chatbot/feedback', [ChatBotController::class, 'feedback'])->name('chatbot.feedback');
Route::post('/chatbot/teach', [InteractiveLearningController::class, 'teach'])->name('chatbot.teach');
Route::post('/chatbot/correct', [InteractiveLearningController::class, 'correct'])->name('chatbot.correct');

// Training routes (protette da autenticazione)
Route::middleware(['auth'])->group(function () {
    Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
    Route::post('/training', [TrainingController::class, 'store'])->name('training.store');
    Route::post('/training/import/wiki', [TrainingController::class, 'importWikiEvents'])
        ->name('training.import.wiki');
    Route::post('/training/{trainingData}/verify', [TrainingController::class, 'verify'])
        ->name('training.verify');
    Route::delete('/training/{trainingData}', [TrainingController::class, 'delete'])
        ->name('training.delete');
    Route::post('/training/import/daily', [TrainingController::class, 'importDailyConversation'])
        ->name('training.import.daily');
    Route::post('/training/train-model', [TrainingController::class, 'trainModel'])
        ->name('training.train-model');
    Route::post('/training/pretrain', [TrainingController::class, 'pretrain'])
        ->name('training.pretrain');
});

Route::get('/training/status', [TrainingStatusController::class, 'status']);

// Auth routes
require __DIR__.'/auth.php';
