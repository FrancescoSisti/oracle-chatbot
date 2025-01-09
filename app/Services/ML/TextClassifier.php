<?php

namespace App\Services\ML;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessC4Chunk;

class TextClassifier
{
    private const C4_ITALIAN_CACHE_KEY = 'c4_italian_processed_data';
    private const C4_ITALIAN_CACHE_TTL = 86400; // 24 ore

    private array $categories = [
        'saluto',
        'richiesta_info',
        'capacita',
        'prezzo',
        'richiesta_aiuto',
        'richiesta_opinione',
        'richiesta_consiglio',
        'richiesta_chiarimento',
        'stato_emotivo',
        'ringraziamento',
        'scusa',
        'congedo'
    ];

    private array $trainingData = [];
    private array $vocabulary = [];
    private array $categoryProbabilities = [];
    private array $wordProbabilities = [];
    private float $lastConfidence = 0;
    private string $dataFile;

    private array $stopWords = [
        'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'uno', 'una',
        'di', 'a', 'da', 'in', 'con', 'su', 'per', 'tra', 'fra',
        'e', 'ed', 'o', 'ma', 'se', 'perché', 'come', 'dove',
        'che', 'chi', 'cui', 'non', 'più', 'quale', 'quanto',
        'quanti', 'quanta', 'quante', 'questo', 'questa', 'questi', 'queste',
        'si', 'no', 'mi', 'ti', 'ci', 'vi', 'tu', 'te', 'lui', 'lei', 'noi', 'voi'
    ];

    public function train(array $data): void
    {
        $this->trainingData = $data;
        $this->buildVocabulary();
        $this->calculateProbabilities();
    }

    public function batchTrain(array $data): void
    {
        foreach ($data as $item) {
            $this->trainingData[] = $item;
        }
        $this->buildVocabulary();
        $this->calculateProbabilities();
    }

    private function buildVocabulary(): void
    {
        $this->vocabulary = [];
        foreach ($this->trainingData as $item) {
            $words = $this->tokenize($item['text']);
            foreach ($words as $word) {
                $this->vocabulary[$word] = true;
            }
        }
    }

    private function calculateProbabilities(): void
    {
        // Calcola le probabilità per ogni categoria
        $categoryCount = array_count_values(array_column($this->trainingData, 'category'));
        $totalDocs = count($this->trainingData);

        foreach ($this->categories as $category) {
            $this->categoryProbabilities[$category] =
                ($categoryCount[$category] ?? 0) / $totalDocs;
        }

        // Calcola le probabilità per ogni parola in ogni categoria
        foreach ($this->categories as $category) {
            $this->wordProbabilities[$category] = [];
            $categoryWords = [];
            $totalWords = 0;

            // Raccoglie tutte le parole per questa categoria
            foreach ($this->trainingData as $item) {
                if ($item['category'] === $category) {
                    $words = $this->tokenize($item['text']);
                    foreach ($words as $word) {
                        $categoryWords[$word] = ($categoryWords[$word] ?? 0) + 1;
                        $totalWords++;
                    }
                }
            }

            // Calcola le probabilità con smoothing di Laplace
            foreach ($this->vocabulary as $word => $value) {
                $this->wordProbabilities[$category][$word] =
                    ($categoryWords[$word] ?? 0 + 1) / ($totalWords + count($this->vocabulary));
            }
        }
    }

    public function predict(string $text): string
    {
        $words = $this->tokenize($text);
        $maxScore = -INF;
        $bestCategory = $this->categories[0];

        $scores = [];
        $totalScore = 0;

        // Calcola gli score per ogni categoria
        foreach ($this->categories as $category) {
            $score = log($this->categoryProbabilities[$category] ?? 0.1);
            $wordMatches = 0;
            $totalWords = count($words);

            foreach ($words as $word) {
                if (isset($this->wordProbabilities[$category][$word])) {
                    $score += log($this->wordProbabilities[$category][$word]);
                    $wordMatches++;
                }
            }

            // Normalizza lo score basandosi sulla lunghezza del testo
            $normalizedScore = $totalWords > 0 ? exp($score) * ($wordMatches / $totalWords) : exp($score);
            $scores[$category] = $normalizedScore;
            $totalScore += $normalizedScore;

            if ($normalizedScore > $maxScore) {
                $maxScore = $normalizedScore;
                $bestCategory = $category;
            }
        }

        // Calcola la confidenza
        $confidence = 0;
        if ($totalScore > 0) {
            arsort($scores);
            $scores = array_values($scores);
            $baseConfidence = $scores[0] / $totalScore;
            $margin = count($scores) > 1 ? ($scores[0] - $scores[1]) / $scores[0] : 1;
            $confidence = $baseConfidence * (0.7 + 0.3 * $margin);
        }

        $this->lastConfidence = $confidence;
        return $bestCategory;
    }

    public function getLastConfidence(): float
    {
        return $this->lastConfidence;
    }

    private function tokenize(string $text): array
    {
        // Converti in minuscolo
        $text = mb_strtolower($text);

        // Rimuovi punteggiatura e caratteri speciali ma mantieni le lettere accentate
        $text = preg_replace('/[^\p{L}\s]/u', ' ', $text);

        // Dividi in token
        $tokens = array_filter(explode(' ', $text), function($token) {
            return strlen($token) > 1 && !in_array($token, $this->stopWords);
        });

        // Lemmatizzazione base per alcune forme comuni in italiano
        $lemmatization = [
            'sono' => 'essere',
            'sei' => 'essere',
            'è' => 'essere',
            'siamo' => 'essere',
            'siete' => 'essere',
            'ho' => 'avere',
            'hai' => 'avere',
            'ha' => 'avere',
            'abbiamo' => 'avere',
            'avete' => 'avere',
            'hanno' => 'avere',
            'vorrei' => 'volere',
            'vorresti' => 'volere',
            'vorrebbe' => 'volere',
            'vorremmo' => 'volere',
            'vorreste' => 'volere',
            'vorrebbero' => 'volere'
        ];

        return array_map(function($token) use ($lemmatization) {
            return $lemmatization[$token] ?? $token;
        }, $tokens);
    }

    private function calculateCategoryScore(string $text, array $trainingExample): float
    {
        $text = strtolower($text);
        $exampleText = strtolower($trainingExample['text']);

        // Pattern comuni per ogni categoria
        $categoryPatterns = [
            'richiesta_stato' => [
                '/come\s+stai/i',
                '/come\s+va/i',
                '/come\s+procede/i',
                '/tutto\s+bene/i',
                '/stai\s+bene/i'
            ],
            'saluto' => [
                '/^(ciao|salve|buongiorno|buonasera|hey)/i',
                '/^saluti/i'
            ]
        ];

        $score = 0.0;

        // Verifica pattern specifici della categoria
        if (isset($categoryPatterns[$trainingExample['category']])) {
            foreach ($categoryPatterns[$trainingExample['category']] as $pattern) {
                if (preg_match($pattern, $text)) {
                    $score += 0.8;
                    if ($trainingExample['category'] === 'richiesta_stato' &&
                        (preg_match('/come\s+stai/i', $text) || preg_match('/come\s+va/i', $text))) {
                        $score += 0.2;
                    }
                }
            }
        }

        return min(1.0, $score);
    }

    public function processC4Data(array $rows): array
    {
        $processedData = [];
        $minConfidence = 0.6;

        foreach ($rows as $row) {
            try {
                if (!isset($row['text']) || empty($row['text'])) {
                    continue;
                }

                $text = $row['text'];

                // Dividi il testo in frasi
                $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($sentences as $sentence) {
                    // Pulisci la frase
                    $sentence = trim($sentence);
                    if (strlen($sentence) < 10 || strlen($sentence) > 200) {
                        continue;
                    }

                    // Pattern per le diverse categorie
                    $patterns = [
                        'saluto' => [
                            '/\b(ciao|salve|buongiorno|buonasera|hey|ehil[aà])\b/i',
                            '/\b(come stai|come va|come andiamo)\b/i'
                        ],
                        'richiesta_info' => [
                            '/\b(puoi dirmi|mi dici|vorrei sapere|sai dirmi|potresti dirmi)\b/i',
                            '/\b(che cos[\'è]|cos[\'è]|chi [èe]|dove [èe]|quando [èe]|perch[èé])\b/i'
                        ],
                        'capacita' => [
                            '/\b(cosa sai fare|cosa puoi fare|quali sono le tue capacit[àa])\b/i',
                            '/\b(come funzioni|come ti comporti|cosa sei capace di fare)\b/i'
                        ],
                        'richiesta_aiuto' => [
                            '/\b(aiuto|aiutami|ho bisogno|mi serve una mano|puoi aiutarmi)\b/i',
                            '/\b(non riesco|non capisco|sono in difficolt[àa])\b/i'
                        ],
                        'ringraziamento' => [
                            '/\b(grazie|ti ringrazio|molto gentile|apprezzo)\b/i',
                            '/\b(sei stato|[èe] stato) (utile|d\'aiuto|gentile)\b/i'
                        ],
                        'scusa' => [
                            '/\b(scusa|scusami|mi dispiace|perdonami)\b/i',
                            '/\b(non volevo|non intendevo)\b/i'
                        ],
                        'congedo' => [
                            '/\b(ciao|arrivederci|a presto|a dopo|ci vediamo)\b/i',
                            '/\b(buona giornata|buona serata|buona notte)\b/i'
                        ],
                        'richiesta_stato' => [
                            '/\b(come stai|come ti senti|tutto bene|come va)\b/i',
                            '/\b(sei (stanco|felice|triste|contento))\b/i'
                        ]
                    ];

                    // Trova la migliore corrispondenza
                    $bestMatch = ['category' => null, 'confidence' => 0];
                    foreach ($patterns as $category => $categoryPatterns) {
                        foreach ($categoryPatterns as $pattern) {
                            $confidence = $this->calculatePatternConfidence($sentence, $pattern, $category);
                            if ($confidence > $bestMatch['confidence']) {
                                $bestMatch = [
                                    'category' => $category,
                                    'confidence' => $confidence
                                ];
                            }
                        }
                    }

                    // Aggiungi solo se la confidenza è sufficientemente alta
                    if ($bestMatch['confidence'] >= $minConfidence && $bestMatch['category'] !== null) {
                        $processedData[] = [
                            'text' => $sentence,
                            'category' => $bestMatch['category'],
                            'confidence' => $bestMatch['confidence']
                        ];

                        Log::debug('Frase categorizzata:', [
                            'text' => $sentence,
                            'category' => $bestMatch['category'],
                            'confidence' => $bestMatch['confidence']
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Errore nel processamento di un item:', [
                    'error' => $e->getMessage(),
                    'item' => $row
                ]);
                continue;
            }
        }

        Log::info('Processamento completato:', [
            'input_size' => count($rows),
            'output_size' => count($processedData)
        ]);

        return $processedData;
    }

    private function calculatePatternConfidence(string $sentence, string $pattern, string $category): float
    {
        $confidence = 0.0;

        // Match esatto del pattern
        if (preg_match($pattern, $sentence)) {
            $confidence += 0.6;
        }

        // Bonus per frasi più corte (più probabilmente pertinenti)
        $wordCount = str_word_count($sentence);
        if ($wordCount <= 5) {
            $confidence += 0.2;
        } elseif ($wordCount <= 10) {
            $confidence += 0.1;
        }

        // Bonus per pattern all'inizio della frase
        if (preg_match('/^' . substr($pattern, 1), $sentence)) {
            $confidence += 0.2;
        }

        // Penalità per frasi troppo lunghe
        if ($wordCount > 20) {
            $confidence -= 0.2;
        }

        // Bonus per categorie specifiche con pattern più precisi
        $highPrecisionCategories = ['saluto', 'ringraziamento', 'congedo'];
        if (in_array($category, $highPrecisionCategories)) {
            $confidence += 0.1;
        }

        return min(1.0, max(0.0, $confidence));
    }

    public function pretrainWithC4(int $limit = 1000, int $chunks = 4): void
    {
        try {
            Log::info('[C4] Inizio pre-training');

            // Carica i dati in chunks e li invia alla coda di processing
            $chunkSize = ceil($limit / $chunks);
            for ($i = 0; $i < $chunks; $i++) {
                $offset = $i * $chunkSize;
                $currentLimit = min($chunkSize, $limit - $offset);

                // Dispatch del job di processing
                ProcessC4Chunk::dispatch($offset, $currentLimit)
                    ->onQueue('c4-processing');

                Log::info('[C4] Job di processing dispatched', [
                    'chunk' => $i + 1,
                    'offset' => $offset,
                    'limit' => $currentLimit
                ]);
            }

            Log::info('[C4] Tutti i job di processing sono stati dispatched', [
                'total_chunks' => $chunks
            ]);

        } catch (\Exception $e) {
            Log::error('[C4] Errore durante il pre-training:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
