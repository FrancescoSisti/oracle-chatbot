<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ML\IntentRecognizer;
use App\Models\TrainingData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class InteractiveLearningController extends Controller
{
    private IntentRecognizer $intentRecognizer;

    public function __construct()
    {
        $this->intentRecognizer = IntentRecognizer::getInstance();
    }

    public function teach(Request $request)
    {
        try {
            $validated = $request->validate([
                'text' => 'required|string',
                'category' => 'required|string'
            ]);

            // Controlla se esiste già un esempio simile
            $existingExample = TrainingData::where('text', 'LIKE', $validated['text'])
                ->where('category', $validated['category'])
                ->first();

            if ($existingExample) {
                return response()->json([
                    'success' => true,
                    'message' => "Grazie, ma conosco già questo esempio! So che '{$validated['text']}' è un esempio di '{$validated['category']}'."
                ]);
            }

            // Aggiorna il classificatore con il nuovo esempio
            $this->intentRecognizer->addTrainingData([[
                'text' => $validated['text'],
                'category' => $validated['category']
            ]]);

            // Calcola la confidenza reale
            $classifier = $this->intentRecognizer->getClassifier();
            $classifier->predict($validated['text']);
            $confidence = $classifier->getLastConfidence();

            // Salva il nuovo esempio di training con la confidenza reale
            $trainingData = TrainingData::create([
                'text' => $validated['text'],
                'category' => $validated['category'],
                'is_verified' => true,
                'confidence_score' => $confidence,
                'source' => $request->input('is_correction', false) ? 'correction' : 'interactive'
            ]);

            // Aggiorna il contatore nella cache
            $this->updateTrainingCount();

            $isCorrection = $request->input('is_correction', false);

            // Messaggi più naturali
            if ($isCorrection) {
                $messages = [
                    "Ah, ora ho capito! '{$validated['text']}' è un esempio di '{$validated['category']}'. Grazie per avermi corretto.",
                    "Grazie della correzione! Ho imparato che '{$validated['text']}' si riferisce a '{$validated['category']}'.",
                    "Ok, ho capito il mio errore. Ora so che quando dici '{$validated['text']}' intendi '{$validated['category']}'."
                ];
            } else {
                $messages = [
                    "Interessante! Ho imparato qualcosa di nuovo su '{$validated['category']}'. Grazie per l'esempio!",
                    "Grazie per avermi insegnato questo! Ho salvato '{$validated['text']}' come esempio di '{$validated['category']}'.",
                    "Ho capito! Quando qualcuno dice '{$validated['text']}', si riferisce a '{$validated['category']}'. Lo terrò a mente!"
                ];
            }

            return response()->json([
                'success' => true,
                'message' => $messages[array_rand($messages)]
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nell\'apprendimento:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ops, ho avuto qualche difficoltà a memorizzare questo esempio. Potresti riprovare?'
            ], 500);
        }
    }

    public function correct(Request $request)
    {
        try {
            $validated = $request->validate([
                'original_text' => 'required|string',
                'categories' => 'required|array',
                'categories.*' => 'string'
            ]);

            $successMessages = [];

            // Aggiungi la correzione per ogni categoria
            foreach ($validated['categories'] as $category) {
                // Controlla se esiste già un esempio simile per questa categoria
                $existingExample = TrainingData::where('text', 'LIKE', $validated['original_text'])
                    ->where('category', $category)
                    ->first();

                if (!$existingExample) {
                    // Calcola la confidenza reale
                    $classifier = $this->intentRecognizer->getClassifier();
                    $classifier->predict($validated['original_text']);
                    $confidence = $classifier->getLastConfidence();

                    // Salva il nuovo esempio di training
                    TrainingData::create([
                        'text' => $validated['original_text'],
                        'category' => $category,
                        'is_verified' => true,
                        'confidence_score' => $confidence,
                        'source' => 'correction'
                    ]);

                    // Aggiorna il classificatore con il nuovo esempio
                    $this->intentRecognizer->addTrainingData([[
                        'text' => $validated['original_text'],
                        'category' => $category
                    ]]);

                    $successMessages[] = "'{$category}'";
                }
            }

            // Aggiorna il contatore nella cache
            $this->updateTrainingCount();

            if (empty($successMessages)) {
                return response()->json([
                    'success' => true,
                    'message' => "Grazie, ma conosco già questi significati per '{$validated['original_text']}'!"
                ]);
            }

            $categories = implode(', ', $successMessages);
            $messages = [
                "Ho imparato che '{$validated['original_text']}' può significare: {$categories}. Grazie per avermi insegnato queste sfumature!",
                "Grazie della correzione! Ora so che '{$validated['original_text']}' può essere interpretato come: {$categories}.",
                "Capisco! '{$validated['original_text']}' ha diversi significati: {$categories}. Lo terrò a mente!"
            ];

            return response()->json([
                'success' => true,
                'message' => $messages[array_rand($messages)]
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nella correzione:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Scusa, non sono riuscito a memorizzare la correzione. Puoi riprovare?'
            ], 500);
        }
    }

    private function updateTrainingCount(): void
    {
        try {
            $count = TrainingData::count();
            Cache::put('training_data_count', $count, now()->addDay());
            Log::info('Contatore dati di training aggiornato:', ['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento del contatore:', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
