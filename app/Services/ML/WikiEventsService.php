<?php

namespace App\Services\ML;

use Illuminate\Support\Facades\Http;

class WikiEventsService
{
    private const API_URL = 'http://www.vizgr.org/historical-events/search.php';

    /**
     * Lingue supportate dall'API
     */
    public const SUPPORTED_LANGUAGES = ['en', 'de', 'it', 'es', 'pt', 'ca', 'id', 'ro', 'tr'];

    /**
     * Granularità supportate
     */
    public const SUPPORTED_GRANULARITIES = ['all', 'year', 'month'];

    /**
     * Ordini supportati
     */
    public const SUPPORTED_ORDERS = ['asc', 'desc'];

    public function getEvents(array $params = []): array
    {
        $defaultParams = [
            'format' => 'json',
            'lang' => 'it',
            'limit' => 100,
            'html' => 'false',
            'links' => 'false'
        ];

        $params = array_merge($defaultParams, $params);

        try {
            $response = Http::get(self::API_URL, $params);
            $data = $response->json();
        } catch (\Exception $e) {
            return [];
        }

        if (!isset($data['result']) || empty($data['result'])) {
            return [];
        }

        $events = [];

        // Estrai gli eventi dalla risposta
        foreach ($data['result'] as $key => $item) {
            // Salta l'elemento 'count' che non è un evento
            if ($key === 'count') continue;

            // Ogni evento è contenuto in una chiave 'event'
            if (isset($item['event'])) {
                $event = $item['event'];

                if (isset($event['description']) && !empty($event['description'])) {
                    $events[] = [
                        'description' => $event['description'],
                        'date' => $event['date'] ?? '',
                        'category' => $event['category1'] ?? ''
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Valida e formatta i parametri della richiesta
     */
    private function validateParams(array $params): array
    {
        // Validazione date
        if (isset($params['begin_date'])) {
            $params['begin_date'] = $this->formatDate($params['begin_date']);
        }
        if (isset($params['end_date'])) {
            $params['end_date'] = $this->formatDate($params['end_date']);
        }

        // Validazione lingua
        if (isset($params['lang']) && !in_array($params['lang'], self::SUPPORTED_LANGUAGES)) {
            $params['lang'] = 'it';
        }

        // Validazione granularità
        if (isset($params['granularity']) && !in_array($params['granularity'], self::SUPPORTED_GRANULARITIES)) {
            $params['granularity'] = 'year';
        }

        // Validazione ordine
        if (isset($params['order']) && !in_array($params['order'], self::SUPPORTED_ORDERS)) {
            $params['order'] = 'desc';
        }

        // Validazione booleani
        foreach (['html', 'links', 'related'] as $boolParam) {
            if (isset($params[$boolParam])) {
                $params[$boolParam] = filter_var($params[$boolParam], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            }
        }

        // Validazione limit
        if (isset($params['limit'])) {
            $params['limit'] = min(max(1, (int)$params['limit']), 1000);
        }

        return $params;
    }

    /**
     * Formatta una data nel formato richiesto dall'API (YYYYMMDD)
     */
    private function formatDate($date): string
    {
        if (is_numeric($date) && strlen($date) === 8) {
            return $date;
        }

        try {
            return date('Ymd', strtotime($date));
        } catch (\Exception $e) {
            return date('Ymd');
        }
    }

    public function categorizeEvent(string $description): string
    {
        $categoryKeywords = [
            'politica' => ['governo', 'presidente', 'ministro', 'elezioni', 'parlamento', 'politica'],
            'guerra' => ['guerra', 'battaglia', 'conflitto', 'militare', 'esercito', 'combattimento'],
            'scienza' => ['scoperta', 'invenzione', 'scienziato', 'ricerca', 'tecnologia', 'scientifico'],
            'cultura' => ['arte', 'musica', 'letteratura', 'film', 'teatro', 'culturale', 'libro'],
            'sport' => ['olimpiadi', 'campionato', 'torneo', 'gara', 'atleta', 'sportivo'],
            'tecnologia' => ['computer', 'internet', 'software', 'tecnologico', 'digitale', 'innovazione'],
            'economia' => ['economia', 'mercato', 'finanziario', 'borsa', 'commercio', 'industria']
        ];

        $description = strtolower($description);
        foreach ($categoryKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($description, $keyword)) {
                    return $category;
                }
            }
        }

        return 'altro';
    }
}
