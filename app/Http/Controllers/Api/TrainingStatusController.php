<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrainingStatusController extends Controller
{
    public function status(): JsonResponse
    {
        $trainingStatus = Cache::get('training_status', [
            'is_training' => false,
            'processed_examples' => 0,
            'total_examples' => 0,
            'avg_confidence' => 0
        ]);

        Log::debug('Stato training richiesto', $trainingStatus); // Debug log

        return response()->json($trainingStatus);
    }
}
