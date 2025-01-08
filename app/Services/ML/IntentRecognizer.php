<?php

namespace App\Services\ML;

use App\Models\TrainingData;

class IntentRecognizer
{
    private TextClassifier $classifier;
    private array $patterns;
    private array $entities;

    public function __construct()
    {
        $this->classifier = new TextClassifier();
        $this->loadTrainingData();
        $this->initializePatterns();
    }

    private function loadTrainingData()
    {
        // Carica i dati iniziali dal database
        $dbData = TrainingData::where('is_verified', true)->get();

        // Combina i dati del database con quelli predefiniti
        $trainingData = array_merge(
            $this->getDefaultTrainingData(),
            $dbData->map(function ($data) {
                return [
                    'text' => $data->text,
                    'category' => $data->category
                ];
            })->toArray()
        );

        $this->classifier->train($trainingData);
    }

    private function getDefaultTrainingData()
    {
        // Sposta qui i dati di training predefiniti
        return [
            ['text' => 'Ciao come stai', 'category' => 'saluto'],
            // ... altri dati predefiniti ...
        ];
    }

    private function initializePatterns()
    {
        $this->patterns = [
            'prezzo' => '/(?:cost[ao]|prezz[oi])\s+(?:del|della|dello|dei|delle|di)\s+(\w+)/i',
            'orario' => '/(?:orar[io]|apert[oi]|chius[oi])/i',
            'contatto' => '/(?:telefon[oi]|email|contatt[oi]|chiamare)/i',
            'data' => '/(?:luned[iì]|marted[iì]|mercoled[iì]|gioved[iì]|venerd[iì]|sabato|domenica)\s+(?:prossim[oa])?/i',
            'quantità' => '/(?:\d+|un[oa]?|due|tre|quattro|cinque|sei|sette|otto|nove|dieci)\s+(?:pezz[oi]|unit[àa])/i',
            'prodotto' => '/(?:prodott[oi]|articol[oi]|servizi[oi])\s+(?:per|di|da)\s+(\w+)/i',
            'luogo' => '/(?:a|in|presso|verso|da)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/i',
            'urgenza' => '/(?:urgent[ei]|immediat[oa]|veloce|rapid[oa]|prest[oa])/i',
            'problema' => '/(?:problem[ai]|difficolt[àa]|guast[oi]|non\s+funzion[ai])/i',
            'soddisfazione' => '/(?:soddisfatt[oa]|content[oa]|felice|delus[oa]|arabbiat[oa])/i',
            'disponibilità' => '/(?:disponibil[ei]|in\s+stock|esaurit[oa]|terminat[oa])/i',
            'pagamento' => '/(?:cart[ae]|bonifico|paypal|contant[ei]|pagament[oi])/i',
            'spedizione' => '/(?:spedizion[ei]|consegn[ae]|corriere|tracking)/i',
            'dimensione' => '/(?:piccol[oa]|medi[oa]|grand[ei]|enorme|minuscol[oa])/i',
            'colore' => '/(?:ross[oa]|blu|verde|giall[oa]|ner[oa]|bianc[oa])/i',
        ];
    }

    public function recognize(string $text): array
    {
        $intent = $this->classifier->classify($text);
        $entities = $this->extractEntities($text);
        $confidence = $this->calculateConfidence($text, $intent);

        return [
            'intent' => $intent,
            'entities' => $entities,
            'confidence' => $confidence,
            'sentiment' => $this->analyzeSentiment($text)
        ];
    }

    private function extractEntities(string $text): array
    {
        $entities = [];

        foreach ($this->patterns as $type => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $entities[] = [
                    'type' => $type,
                    'value' => $matches[1] ?? null,
                    'raw' => $matches[0]
                ];
            }
        }

        return $entities;
    }

    private function calculateConfidence(string $text, string $intent): float
    {
        // Implementazione base della confidenza
        $words = explode(' ', strtolower($text));
        $relevantWords = 0;

        foreach ($words as $word) {
            if (isset($this->wordFrequencies[$intent][$word])) {
                $relevantWords++;
            }
        }

        return $relevantWords / count($words);
    }

    private function analyzeSentiment(string $text): string
    {
        $positiveWords = ['grazie', 'piacere', 'ottimo', 'perfetto', 'fantastico', 'eccellente'];
        $negativeWords = ['problema', 'male', 'pessimo', 'terribile', 'deluso', 'arrabbiato'];

        $text = strtolower($text);
        $positiveScore = 0;
        $negativeScore = 0;

        foreach ($positiveWords as $word) {
            if (strpos($text, $word) !== false) {
                $positiveScore++;
            }
        }

        foreach ($negativeWords as $word) {
            if (strpos($text, $word) !== false) {
                $negativeScore++;
            }
        }

        if ($positiveScore > $negativeScore) {
            return 'positivo';
        } elseif ($negativeScore > $positiveScore) {
            return 'negativo';
        }

        return 'neutro';
    }

    public function getClassifier(): TextClassifier
    {
        return $this->classifier;
    }
}
