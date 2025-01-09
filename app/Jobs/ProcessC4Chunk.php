<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ML\TextClassifier;
use App\Jobs\TrainC4Chunk;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessC4Chunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $offset;
    private $limit;

    public function __construct(int $offset, int $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        $this->queue = 'c4-processing';
    }

    public function handle()
    {
        try {
            Log::info('[C4] Inizio processing chunk', [
                'offset' => $this->offset,
                'limit' => $this->limit
            ]);

            $url = "https://datasets-server.huggingface.co/rows";
            $response = Http::post($url, [
                'dataset' => 'allenai/c4',
                'config' => 'it',
                'split' => 'train',
                'offset' => $this->offset,
                'length' => $this->limit
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rows']) && is_array($data['rows'])) {
                    $classifier = new TextClassifier();
                    $processedData = $classifier->processC4Data($data['rows']);

                    $currentStatus = Cache::get('training_status', [
                        'is_training' => true,
                        'processed_examples' => 0,
                        'total_examples' => $this->limit,
                        'avg_confidence' => 0
                    ]);

                    Log::debug('[C4] Stato corrente:', $currentStatus);

                    $totalConfidence = 0;
                    foreach ($processedData as $example) {
                        $totalConfidence += $example['confidence'];
                    }

                    $newStatus = [
                        'is_training' => true,
                        'processed_examples' => $currentStatus['processed_examples'] + count($processedData),
                        'total_examples' => $currentStatus['total_examples'],
                        'avg_confidence' => count($processedData) > 0 ?
                            $totalConfidence / count($processedData) : 0
                    ];

                    Log::debug('[C4] Nuovo stato:', $newStatus);

                    Cache::put('training_status', $newStatus, now()->addHours(1));

                    if (!empty($processedData)) {
                        TrainC4Chunk::dispatch($processedData)
                            ->onQueue('c4-training');

                        Log::info('[C4] Chunk processato con successo', [
                            'processed_examples' => count($processedData),
                            'total_processed' => $newStatus['processed_examples'],
                            'total_examples' => $newStatus['total_examples']
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('[C4] Errore nel processing del chunk:', [
                'error' => $e->getMessage(),
                'offset' => $this->offset,
                'limit' => $this->limit
            ]);
            throw $e;
        }
    }
}
