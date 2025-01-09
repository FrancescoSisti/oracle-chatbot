<?php

namespace App\Services\ML;

use Illuminate\Support\Facades\Log;
use App\Models\TrainingData;
use App\Services\ML\TrainingTemplates;

class IntentRecognizer
{
    private TextClassifier $classifier;
    private array $trainingData = [];
    private static $instance = null;
    private array $conversationContext = [];
    private int $maxContextSize = 5;

    private function __construct()
    {
        $this->classifier = new TextClassifier();

        // Controlla se il classifier è già addestrato in cache
        if (cache()->has('intent_recognizer.trained') && cache()->has('intent_recognizer.data')) {
            Log::debug('Caricamento classifier dalla cache');
            $this->trainingData = cache()->get('intent_recognizer.data', []);
            if (!empty($this->trainingData)) {
                $this->classifier->train($this->trainingData);
            }
        } else {
            Log::info('Primo addestramento del classifier');
            $this->loadTrainingData();
            $this->trainClassifier();

            // Salva in cache per 24 ore
            cache()->put('intent_recognizer.trained', true, now()->addDay());
            cache()->put('intent_recognizer.data', $this->trainingData, now()->addDay());
        }

        // Carica il contesto della conversazione dal cache se esiste
        $this->conversationContext = cache()->get('conversation_context', []);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function recognize(string $text): array
    {
        try {
            // Predici la categoria
            $category = $this->classifier->predict($text);
            $confidence = $this->classifier->getLastConfidence();

            // Aggiorna il contesto della conversazione
            $this->updateContext([
                'text' => $text,
                'category' => $category,
                'confidence' => $confidence,
                'timestamp' => now()->timestamp
            ]);

            // Migliora la predizione basandosi sul contesto
            $enhancedCategory = $this->enhancePredictionWithContext($category, $confidence);

            // Assicurati che la risposta sia nel formato corretto
            $result = [
                'category' => $enhancedCategory,
                'confidence' => (float) $confidence,
                'has_context' => !empty($this->conversationContext)
            ];

            Log::info('Risultato recognizer:', $result);

            return $result;

        } catch (\Exception $e) {
            Log::error('Errore nella predizione:', [
                'text' => $text,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Formato di risposta di fallback
            return [
                'category' => 'default',
                'confidence' => 0.0,
                'has_context' => false
            ];
        }
    }

    private function updateContext(array $interaction): void
    {
        // Assicurati che l'interazione abbia il formato corretto
        if (!isset($interaction['text']) || !isset($interaction['category'])) {
            Log::warning('Formato interazione non valido:', $interaction);
            return;
        }

        array_push($this->conversationContext, $interaction);
        if (count($this->conversationContext) > $this->maxContextSize) {
            array_shift($this->conversationContext);
        }
        cache()->put('conversation_context', $this->conversationContext, now()->addHours(1));
    }

    private function enhancePredictionWithContext(string $currentCategory, float $confidence): string
    {
        if (empty($this->conversationContext) || $confidence > 0.8) {
            return $currentCategory;
        }

        try {
            // Analizza il contesto recente
            $recentCategories = array_column(array_slice($this->conversationContext, -3), 'category');
            $categoryCount = array_count_values($recentCategories);

            // Regole di miglioramento basate sul contesto
            if ($confidence < 0.5 && !empty($categoryCount)) {
                // Se la confidenza è bassa, considera il contesto recente
                $mostFrequentCategory = array_search(max($categoryCount), $categoryCount);
                if ($mostFrequentCategory && $categoryCount[$mostFrequentCategory] >= 2) {
                    return $mostFrequentCategory;
                }
            }

            // Gestione specifica per alcune categorie
            $lastCategory = end($this->conversationContext)['category'] ?? null;
            if ($currentCategory === 'richiesta_chiarimento' && $lastCategory === 'richiesta_info') {
                return 'richiesta_info';
            }

            // Gestione specifica per "Cosa puoi fare?"
            if (stripos($this->conversationContext[count($this->conversationContext)-1]['text'], 'cosa puoi fare') !== false) {
                return 'capacita';
            }

        } catch (\Exception $e) {
            Log::error('Errore nell\'analisi del contesto:', [
                'error' => $e->getMessage()
            ]);
        }

        return $currentCategory;
    }

    public function getContext(): array
    {
        return $this->conversationContext;
    }

    public function clearContext(): void
    {
        $this->conversationContext = [];
        cache()->forget('conversation_context');
    }

    private function loadTrainingData(): void
    {
        try {
            $this->trainingData = [];  // Reset dei dati

            // Carica i dati dal database
            $dbData = TrainingData::where('is_verified', true)->get();
            foreach ($dbData as $data) {
                $this->trainingData[] = [
                    'text' => $data->text,
                    'category' => $data->category
                ];
            }

            // Carica i template predefiniti
            $templates = TrainingTemplates::getTemplates();
            foreach ($templates as $templateSet) {
                foreach ($templateSet as $template) {
                    $this->trainingData[] = [
                        'text' => $template['text'],
                        'category' => $template['category']
                    ];
                }
            }

            Log::info('Dati di training caricati:', [
                'count' => count($this->trainingData)
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento dei dati di training:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Carica almeno i dati base per evitare errori
            $this->loadFallbackData();
        }
    }

    private function loadFallbackData(): void
    {
        // Dati minimi di fallback per garantire il funzionamento
        $this->trainingData = [
            ['text' => 'ciao', 'category' => 'saluto'],
            ['text' => 'come stai', 'category' => 'saluto'],
            ['text' => 'grazie', 'category' => 'ringraziamento'],
            ['text' => 'aiuto', 'category' => 'richiesta_aiuto'],
            ['text' => 'non capisco', 'category' => 'richiesta_chiarimento']
        ];
    }

    private function trainClassifier(): void
    {
        if (!empty($this->trainingData)) {
            Log::info('Inizio addestramento classifier con ' . count($this->trainingData) . ' esempi');
            $this->classifier->train($this->trainingData);
            Log::info('Classifier addestrato con successo');
        } else {
            Log::warning('Nessun dato di training disponibile per il classifier');
        }
    }

    public function getClassifier(): TextClassifier
    {
        return $this->classifier;
    }

    public function addTrainingData(array $data): void
    {
        foreach ($data as $item) {
            $this->trainingData[] = [
                'text' => $item['text'],
                'category' => $item['category']
            ];
        }
        $this->trainClassifier();
        // Aggiorna la cache
        cache()->put('intent_recognizer.data', $this->trainingData, now()->addDay());
        cache()->put('intent_recognizer.trained', true, now()->addDay());
    }

    public function recognizeWithContext(string $message, array $conversationHistory): array
    {
        Log::info('=== RICONOSCIMENTO INTENTO CON CONTESTO ===');

        // Prima ottieni il risultato base
        Log::info('Analisi base del messaggio...');
        $baseResult = $this->recognize($message);
        Log::info('Risultato base:', [
            'message' => $message,
            'category' => $baseResult['category'],
            'confidence' => $baseResult['confidence']
        ]);

        // Se non c'è storia della conversazione, ritorna il risultato base
        if (empty($conversationHistory)) {
            Log::info('Nessuna cronologia conversazione disponibile, uso risultato base');
            return $baseResult;
        }

        // Analizza il contesto della conversazione
        Log::info('Analisi contesto conversazione...');
        $contextualScore = $this->analyzeConversationContext($message, $conversationHistory);
        Log::info('Score contestuale:', $contextualScore);

        // Combina il risultato base con l'analisi contestuale
        $finalCategory = $baseResult['category'];
        $finalConfidence = $baseResult['confidence'];

        // Se c'è un forte contesto che suggerisce una categoria diversa
        if ($contextualScore['confidence'] > $baseResult['confidence'] + 0.2) {
            Log::info('Contesto forte rilevato, override categoria:', [
                'old_category' => $finalCategory,
                'new_category' => $contextualScore['category'],
                'context_confidence' => $contextualScore['confidence'],
                'base_confidence' => $baseResult['confidence']
            ]);
            $finalCategory = $contextualScore['category'];
            $finalConfidence = $contextualScore['confidence'];
        }

        $result = [
            'category' => $finalCategory,
            'confidence' => $finalConfidence,
            'has_context' => true,
            'context_score' => $contextualScore
        ];

        Log::info('=== FINE RICONOSCIMENTO INTENTO ===', $result);
        return $result;
    }

    private function analyzeConversationContext(string $currentMessage, array $history): array
    {
        Log::info('=== ANALISI CONTESTO CONVERSAZIONE ===');
        $contextualScores = [];
        $recentMessages = array_slice($history, -3);
        Log::info('Analisi ultimi messaggi:', ['messages' => $recentMessages]);

        foreach ($recentMessages as $message) {
            if ($message['role'] === 'user') {
                // Calcola la similarità semantica
                $similarity = $this->calculateSimilarity($currentMessage, $message['message']);
                Log::info('Similarità calcolata:', [
                    'current' => $currentMessage,
                    'previous' => $message['message'],
                    'similarity' => $similarity
                ]);

                // Se il messaggio è molto simile, dai più peso alla sua categoria
                if ($similarity > 0.7) {
                    $prediction = $this->recognize($message['message']);
                    $category = $prediction['category'];
                    $contextualScores[$category] = ($contextualScores[$category] ?? 0) + $similarity;
                    Log::info('Similarità alta rilevata:', [
                        'category' => $category,
                        'score' => $contextualScores[$category]
                    ]);
                }
            }
        }

        // Analizza pattern di conversazione comuni
        Log::info('Analisi pattern conversazione...');
        $patterns = $this->analyzeConversationPatterns($recentMessages);
        foreach ($patterns as $category => $score) {
            $contextualScores[$category] = ($contextualScores[$category] ?? 0) + $score;
        }
        Log::info('Pattern rilevati:', ['patterns' => $patterns]);

        // Trova la categoria con il punteggio più alto
        $maxScore = 0;
        $bestCategory = null;
        foreach ($contextualScores as $category => $score) {
            if ($score > $maxScore) {
                $maxScore = $score;
                $bestCategory = $category;
            }
        }

        $result = [
            'category' => $bestCategory ?? $this->recognize($currentMessage)['category'],
            'confidence' => $maxScore > 0 ? min($maxScore / 2, 1.0) : 0.3
        ];

        Log::info('Risultato analisi contesto:', [
            'scores' => $contextualScores,
            'best_category' => $result['category'],
            'confidence' => $result['confidence']
        ]);

        return $result;
    }

    private function calculateSimilarity(string $text1, string $text2): float
    {
        // Tokenizzazione semplice
        $words1 = array_filter(explode(' ', strtolower($text1)));
        $words2 = array_filter(explode(' ', strtolower($text2)));

        // Calcola l'intersezione delle parole
        $intersection = array_intersect($words1, $words2);

        // Calcola il coefficiente di Jaccard
        $union = array_unique(array_merge($words1, $words2));

        $similarity = count($intersection) / count($union);

        Log::info('Calcolo similarità:', [
            'text1' => $text1,
            'text2' => $text2,
            'words1' => $words1,
            'words2' => $words2,
            'intersection' => $intersection,
            'union_count' => count($union),
            'similarity' => $similarity
        ]);

        return $similarity;
    }

    private function analyzeConversationPatterns(array $recentMessages): array
    {
        $patterns = [];

        // Pattern 1: Dopo un saluto, è probabile una richiesta di stato
        if ($this->matchesPattern($recentMessages, ['saluto' => 1])) {
            $patterns['richiesta_stato'] = 0.4;
        }

        // Pattern 2: Dopo una richiesta di stato, potrebbero seguire smalltalk o richieste di info
        if ($this->matchesPattern($recentMessages, ['richiesta_stato' => 1])) {
            $patterns['smalltalk'] = 0.3;
            $patterns['richiesta_info'] = 0.3;
        }

        // Pattern 3: Dopo una richiesta di aiuto, probabilmente seguiranno altre richieste correlate
        if ($this->matchesPattern($recentMessages, ['richiesta_aiuto' => 1])) {
            $patterns['richiesta_aiuto'] = 0.5;
            $patterns['richiesta_info'] = 0.3;
        }

        return $patterns;
    }

    private function matchesPattern(array $messages, array $expectedPattern): bool
    {
        $matchCount = 0;
        foreach ($messages as $message) {
            if ($message['role'] === 'assistant') {
                continue;
            }

            $prediction = $this->recognize($message['message']);
            if (isset($expectedPattern[$prediction['category']])) {
                $matchCount++;
            }
        }

        return $matchCount >= array_sum($expectedPattern);
    }
}
