<?php

namespace App\Services\ML;

class EntityExtractor
{
    private array $patterns = [
        'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
        'phone' => '/(\+\d{1,3}[- ]?)?\d{10}/',
        'date' => '/\d{1,2}\/\d{1,2}\/\d{4}|\d{4}-\d{2}-\d{2}/',
        'time' => '/([01]?[0-9]|2[0-3]):[0-5][0-9]/',
        'price' => '/€\s*\d+(?:[.,]\d{2})?|\d+(?:[.,]\d{2})?\s*€/',
        'url' => '/https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/',
        'product_code' => '/[A-Z]{2,3}-\d{3,6}/'
    ];

    private array $keywords = [
        'location' => [
            'via', 'piazza', 'corso', 'viale', 'strada', 'vicolo',
            'roma', 'milano', 'napoli', 'torino', 'palermo', 'genova',
            'bologna', 'firenze', 'bari', 'catania'
        ],
        'product_type' => [
            'smartphone', 'tablet', 'laptop', 'computer', 'monitor',
            'stampante', 'scanner', 'router', 'mouse', 'tastiera',
            'cuffie', 'speaker', 'microfono', 'webcam'
        ],
        'service_type' => [
            'assistenza', 'supporto', 'consulenza', 'installazione',
            'configurazione', 'riparazione', 'manutenzione', 'aggiornamento',
            'backup', 'recovery'
        ]
    ];

    public function extract(string $text): array
    {
        $entities = [];

        // Estrai entità basate su pattern
        foreach ($this->patterns as $type => $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $entities[$type] = array_unique($matches[0]);
            }
        }

        // Estrai entità basate su keywords
        $words = explode(' ', strtolower($text));
        foreach ($this->keywords as $type => $keywords) {
            $found = array_intersect($words, $keywords);
            if (!empty($found)) {
                $entities[$type] = array_values($found);
            }
        }

        // Estrai numeri
        if (preg_match_all('/\b\d+\b/', $text, $matches)) {
            $entities['numbers'] = array_unique($matches[0]);
        }

        return $this->cleanEntities($entities);
    }

    private function cleanEntities(array $entities): array
    {
        // Rimuovi array vuoti e valori duplicati
        return array_filter($entities, function($value) {
            return !empty($value);
        });
    }

    public function addPattern(string $type, string $pattern): void
    {
        $this->patterns[$type] = $pattern;
    }

    public function addKeywords(string $type, array $keywords): void
    {
        if (!isset($this->keywords[$type])) {
            $this->keywords[$type] = [];
        }
        $this->keywords[$type] = array_merge($this->keywords[$type], $keywords);
    }
}
