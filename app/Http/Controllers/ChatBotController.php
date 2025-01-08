<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ML\IntentRecognizer;

class ChatBotController extends Controller
{
    private IntentRecognizer $intentRecognizer;

    public function __construct()
    {
        $this->intentRecognizer = new IntentRecognizer();
    }

    public function index()
    {
        return view('chatbot.index');
    }

    public function processMessage(Request $request)
    {
        try {
            $message = $request->input('message');
            $analysis = $this->intentRecognizer->recognize($message);

            $response = $this->generateResponse($analysis);

            return response()->json([
                'message' => $response,
                'analysis' => $analysis,
                'timestamp' => now()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Si è verificato un errore durante l\'elaborazione del messaggio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function generateResponse(array $analysis): string
    {
        $responses = [
            'saluto' => [
                'Ciao! Come posso aiutarti oggi?',
                'Salve! Sono qui per aiutarti.',
                'Buongiorno! In cosa posso esserti utile?',
                'Ciao! È un piacere aiutarti oggi.'
            ],
            'richiesta_info' => [
                'Certamente! Che tipo di informazioni ti servono?',
                'Sono qui per questo! Dimmi pure cosa vuoi sapere.',
                'Ti aiuto volentieri. Su cosa vorresti saperne di più?'
            ],
            'prezzo' => [
                'Il prezzo varia in base alle specifiche. Puoi dirmi più dettagli?',
                'Per darti un prezzo preciso ho bisogno di più informazioni.',
                'Posso aiutarti con i prezzi. Quale prodotto ti interessa?'
            ],
            'richiesta_aiuto' => [
                'Sono qui per aiutarti. Qual è il problema?',
                'Certamente! Dimmi pure cosa ti serve.',
                'Come posso esserti d\'aiuto?'
            ],
            'orario' => [
                'Siamo aperti dal lunedì al venerdì, dalle 9:00 alle 18:00.',
                'Gli orari di apertura sono: 9:00-18:00, dal lunedì al venerdì.',
                'Puoi trovarci dal lunedì al venerdì, 9:00-18:00.'
            ],
            'prenotazione' => [
                'Posso aiutarti con la prenotazione. Che giorno preferisci?',
                'Per prenotare ho bisogno di sapere data e orario che preferisci.',
                'Certamente! Quando vorresti prenotare?'
            ],
            'reclamo' => [
                'Mi dispiace per il disagio. Puoi spiegarmi meglio cosa è successo?',
                'Mi spiace che ci siano stati problemi. Come posso aiutarti a risolverli?',
                'Capisco la tua frustrazione. Aiutami a capire meglio il problema.'
            ],
            'ringraziamento' => [
                'È stato un piacere! Posso aiutarti con altro?',
                'Non c\'è di che! Sono qui se hai bisogno.',
                'Grazie a te! Se hai altre domande, non esitare.'
            ],
            'prodotti' => [
                'Abbiamo una vasta gamma di prodotti. Cosa ti interessa in particolare?',
                'Posso mostrarti il nostro catalogo. Hai qualche preferenza?',
                'Che tipo di prodotto stai cercando?'
            ],
            'spedizione' => [
                'Le spedizioni vengono effettuate in 24-48 ore lavorative.',
                'Consegniamo in tutta Italia con corriere espresso.',
                'Vuoi conoscere i costi di spedizione per la tua zona?'
            ],
            'pagamento' => [
                'Accettiamo carte di credito, PayPal e bonifico bancario.',
                'Puoi pagare con carta, PayPal o bonifico. Quale preferisci?',
                'Abbiamo diverse modalità di pagamento. Vuoi saperne di più?'
            ],
            'default' => [
                'Mi dispiace, non ho capito bene. Puoi riformulare la domanda?',
                'Potresti spiegarti meglio?',
                'Non sono sicuro di aver capito. Puoi essere più specifico?'
            ]
        ];

        $intent = $analysis['intent'];
        $sentiment = $analysis['sentiment'];
        $possibleResponses = $responses[$intent] ?? $responses['default'];

        // Modifica la risposta in base al sentiment
        if ($sentiment === 'negativo' && $intent !== 'reclamo') {
            return 'Mi dispiace se qualcosa non va. ' . $possibleResponses[array_rand($possibleResponses)];
        }

        return $possibleResponses[array_rand($possibleResponses)];
    }
}
