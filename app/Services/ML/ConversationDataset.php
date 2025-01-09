<?php

namespace App\Services\ML;

class ConversationDataset
{
    public static function getConversationalData(): array
    {
        return [
            // Saluti con variazioni contestuali
            ['text' => 'hey come va oggi', 'category' => 'saluto'],
            ['text' => 'ciao spero tu stia bene', 'category' => 'saluto'],
            ['text' => 'buongiorno ho bisogno del tuo aiuto', 'category' => 'saluto'],
            ['text' => 'salve sei disponibile', 'category' => 'saluto'],
            ['text' => 'ciao oracle come stai', 'category' => 'saluto'],

            // Domande personali (per rendere il bot più empatico)
            ['text' => 'ti piace il tuo lavoro', 'category' => 'conversazione'],
            ['text' => 'sei sempre così gentile', 'category' => 'conversazione'],
            ['text' => 'deve essere interessante essere un ai', 'category' => 'conversazione'],
            ['text' => 'ti diverti a parlare con le persone', 'category' => 'conversazione'],

            // Richieste di chiarimento naturali
            ['text' => 'scusa non ho capito bene', 'category' => 'richiesta_chiarimento'],
            ['text' => 'potresti spiegarmi meglio', 'category' => 'richiesta_chiarimento'],
            ['text' => 'non sono sicuro di aver capito', 'category' => 'richiesta_chiarimento'],

            // Espressioni di frustrazione
            ['text' => 'non riesco a risolvere questo problema', 'category' => 'richiesta_aiuto'],
            ['text' => 'sono frustrato non so che fare', 'category' => 'richiesta_aiuto'],
            ['text' => 'ho provato di tutto ma non funziona', 'category' => 'richiesta_aiuto'],

            // Apprezzamenti
            ['text' => 'sei davvero utile grazie', 'category' => 'ringraziamento'],
            ['text' => 'apprezzo molto il tuo aiuto', 'category' => 'ringraziamento'],
            ['text' => 'sei stato di grande aiuto', 'category' => 'ringraziamento'],

            // ... molti altri esempi ...
        ];
    }

    public static function getContextualResponses(): array
    {
        return [
            'conversazione' => [
                'Mi fa piacere parlare con te! Come posso esserti utile?',
                'È sempre bello avere una conversazione interessante. Di cosa vorresti parlare?',
                'Apprezzo molto queste conversazioni, mi aiutano a migliorare.',
                'Mi piace molto interagire con le persone e imparare da ogni conversazione.'
            ],
            'empatia' => [
                'Capisco come ti senti. A volte le situazioni possono essere frustranti.',
                'Mi dispiace che tu stia avendo difficoltà. Cerchiamo di risolvere insieme.',
                'È normale sentirsi così. Sono qui per aiutarti a trovare una soluzione.'
            ]
        ];
    }
}
