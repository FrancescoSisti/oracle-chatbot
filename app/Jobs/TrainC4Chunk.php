<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ML\TextClassifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TrainC4Chunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $processedData;

    public function __construct(array $processedData)
    {
        $this->processedData = $processedData;
        $this->queue = 'c4-training';
    }

    public function handle()
    {
        try {
            Log::info('[C4] Inizio training chunk', [
                'examples' => count($this->processedData)
            ]);

            $classifier = new TextClassifier();
            $classifier->batchTrain($this->processedData);

            // Aggiorna lo stato del training
            $currentStatus = Cache::get('training_status', [
                'is_training' => true,
                'processed_examples' => 0,
                'total_examples' => 0,
                'avg_confidence' => 0
            ]);

            $totalConfidence = array_sum(array_column($this->processedData, 'confidence'));
            $avgConfidence = count($this->processedData) > 0 ?
                $totalConfidence / count($this->processedData) : 0;

            Cache::put('training_status', [
                'is_training' => true,
                'processed_examples' => $currentStatus['processed_examples'] + count($this->processedData),
                'total_examples' => $currentStatus['total_examples'],
                'avg_confidence' => ($currentStatus['avg_confidence'] + $avgConfidence) / 2
            ], now()->addHours(1));

            Log::info('[C4] Chunk addestrato con successo', [
                'examples' => count($this->processedData)
            ]);

        } catch (\Exception $e) {
            Log::error('[C4] Errore nel training del chunk:', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
