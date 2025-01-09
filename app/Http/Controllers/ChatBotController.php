<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\ML\IntentRecognizer;
use App\Services\ML\EntityExtractor;
use App\Models\MessageFeedback;

class ChatBotController extends Controller
{
    private IntentRecognizer $intentRecognizer;
    private EntityExtractor $entityExtractor;
    private const BOT_NAME = 'Oracle';

    public function __construct()
    {
        $this->intentRecognizer = IntentRecognizer::getInstance();
        $this->entityExtractor = new EntityExtractor();
    }

    public function index()
    {
        return view('chatbot.index');
    }

    public function processMessage(Request $request)
    {
        try {
            $message = $request->input('message');

            Log::info('=== INIZIO ELABORAZIONE MESSAGGIO ===');
            Log::info('Messaggio ricevuto:', ['message' => $message]);

            // Gestisci comandi speciali
            if (preg_match('/^impara:\s*(.+?)\s*->\s*(.+)$/', $message, $matches)) {
                Log::info('Rilevato comando di apprendimento:', ['text' => $matches[1], 'category' => $matches[2]]);
                return app(InteractiveLearningController::class)->teach(new Request([
                    'text' => trim($matches[1]),
                    'category' => strtolower(trim($matches[2])),
                    'is_correction' => false
                ]));
            }

            // Supporto per categorie multiple nella correzione
            if (preg_match('/^correggi:\s*(.+)$/', $message, $matches)) {
                Log::info('Rilevato comando di correzione:', ['categories' => $matches[1]]);
                $categories = array_map('trim', explode(',', strtolower($matches[1])));
                return app(InteractiveLearningController::class)->correct(new Request([
                    'original_text' => session('last_message', ''),
                    'categories' => $categories,
                    'is_correction' => true
                ]));
            }

            // Salva il messaggio corrente per possibili correzioni future
            session(['last_message' => $message]);

            // Recupera la cronologia della conversazione
            $conversationHistory = session('conversation_history', []);
            Log::info('Cronologia conversazione:', [
                'history_count' => count($conversationHistory),
                'recent_messages' => array_slice($conversationHistory, -2)
            ]);

            // Aggiungi il messaggio corrente alla cronologia
            $conversationHistory[] = [
                'role' => 'user',
                'message' => $message,
                'timestamp' => now()
            ];

            // Mantieni solo gli ultimi 5 messaggi per contesto
            if (count($conversationHistory) > 5) {
                array_shift($conversationHistory);
                Log::info('Cronologia troncata a 5 messaggi');
            }

            // Riconosci l'intento considerando il contesto
            Log::info('Analisi intento con contesto...');
            $result = $this->intentRecognizer->recognizeWithContext($message, $conversationHistory);
            Log::info('Risultato analisi intento:', [
                'category' => $result['category'],
                'confidence' => $result['confidence'],
                'has_context' => $result['has_context'] ?? false,
                'context_score' => $result['context_score'] ?? null
            ]);

            $category = $result['category'];
            $confidence = $result['confidence'];
            $hasContext = $result['has_context'] ?? false;

            // Estrai e analizza le entità
            Log::info('Estrazione entità...');
            $entities = $this->entityExtractor->extract($message);
            Log::info('Entità trovate:', ['entities' => $entities]);

            // Analizza il tono del messaggio
            Log::info('Analisi tono...');
            $tone = $this->analyzeTone($message);
            Log::info('Tono rilevato:', [
                'tone' => $tone,
                'message' => $message
            ]);

            // Ottieni le risposte disponibili
            Log::info('Recupero risposte per categoria:', ['category' => $category]);
            $responses = $this->getResponsesForCategory($category);
            Log::info('Risposte disponibili:', [
                'count' => count($responses),
                'responses' => $responses
            ]);

            // Seleziona e personalizza la risposta
            Log::info('Inizio personalizzazione risposta...');
            $response = $this->selectAndCustomizeResponse($responses, $confidence, $hasContext, $entities, $category, $tone, $conversationHistory);
            Log::info('Risposta personalizzata:', ['response' => $response]);

            // Aggiungi la risposta del bot alla cronologia
            $conversationHistory[] = [
                'role' => 'assistant',
                'message' => $response,
                'category' => $category,
                'timestamp' => now()
            ];

            // Salva la cronologia aggiornata
            session(['conversation_history' => $conversationHistory]);
            Log::info('=== FINE ELABORAZIONE MESSAGGIO ===');

            return response()->json([
                'response' => $response,
                'category' => $category,
                'confidence' => $confidence,
                'entities' => $entities,
                'tone' => $tone
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel processamento del messaggio:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'response' => 'Mi dispiace, ho avuto un problema nel processare la tua richiesta. Potresti riprovare?',
                'category' => 'error',
                'confidence' => 0
            ]);
        }
    }

    public function feedback(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|in:positive,negative',
                'message' => 'required|string'
            ]);

            Log::info('Feedback ricevuto:', $validated);

            // Salva il feedback nel database
            $feedback = new MessageFeedback([
                'message' => $validated['message'],
                'feedback_type' => $validated['type'],
                'user_id' => Auth::id()
            ]);
            $feedback->save();

            // Aggiorna il contesto della sessione con il feedback
            session(['recent_feedback' => $validated['type']]);

            // Se il feedback è positivo, usa il messaggio come esempio di training
            if ($validated['type'] === 'positive') {
                // Ottieni la categoria prevista per questo messaggio
                $prediction = $this->intentRecognizer->recognize($validated['message']);
                if ($prediction['confidence'] > 0.5) {
                    // Aggiungi ai dati di training solo se siamo abbastanza confidenti
                    $this->intentRecognizer->addTrainingData([[
                        'text' => $validated['message'],
                        'category' => $prediction['category']
                    ]]);
                    Log::info('Messaggio aggiunto ai dati di training:', [
                        'message' => $validated['message'],
                        'category' => $prediction['category'],
                        'confidence' => $prediction['confidence']
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Grazie del feedback! ' . self::BOT_NAME . ' ha imparato da questa interazione.'
                ]);
            }

            // Se il feedback è negativo, suggerisci la correzione
            if ($validated['type'] === 'negative') {
                // Salva il messaggio per una possibile correzione futura
                session(['message_to_correct' => $validated['message']]);

                return response()->json([
                    'success' => true,
                    'message' => 'Mi dispiace che la risposta non sia stata soddisfacente. Puoi aiutare ' . self::BOT_NAME . ' a migliorare usando il comando "correggi: categoria1, categoria2" per insegnarmi la risposta corretta.'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Errore nel processare il feedback:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore nel processare il feedback.'
            ], 500);
        }
    }

    private function getResponsesForCategory(string $category): array
    {
        $responses = [
            'saluto' => [
                'mattina' => [
                    "Buongiorno! Sono " . self::BOT_NAME . ", il tuo assistente AI. Come posso aiutarti in questa bella mattinata?",
                    "Buongiorno! " . self::BOT_NAME . " è qui per te. Spero che la tua giornata sia iniziata bene. Come posso esserti utile?"
                ],
                'pomeriggio' => [
                    "Buon pomeriggio! " . self::BOT_NAME . " è felice di aiutarti. Di cosa hai bisogno?",
                    "Ciao! Sono " . self::BOT_NAME . " e il pomeriggio è il momento perfetto per una chiacchierata. Come posso aiutarti?"
                ],
                'sera' => [
                    "Buonasera! " . self::BOT_NAME . " è ancora al tuo servizio. Come posso aiutarti?",
                    "Buonasera! Sono " . self::BOT_NAME . ", e anche a quest'ora sono qui per darti supporto. Di cosa hai bisogno?"
                ],
                'generico' => [
                    "Ciao! Sono " . self::BOT_NAME . ", il tuo assistente AI. Come posso aiutarti oggi?",
                    "Salve! " . self::BOT_NAME . " è felice di rivederti. Cosa posso fare per te?"
                ]
            ],
            'richiesta_stato' => [
                'prima_interazione' => [
                    "I sistemi di " . self::BOT_NAME . " funzionano perfettamente, grazie! E tu come stai?",
                    "Molto bene, grazie! Tutti i miei sistemi sono operativi e " . self::BOT_NAME . " è pronto ad aiutarti. Tu invece come te la passi?"
                ],
                'dopo_feedback_positivo' => [
                    self::BOT_NAME . " è contento che tu sia soddisfatto delle mie risposte! Sto imparando molto da questa conversazione. Come stai tu?",
                    "Il tuo feedback positivo rende " . self::BOT_NAME . " felice! Spero che anche tu stia altrettanto bene."
                ],
                'dopo_feedback_negativo' => [
                    self::BOT_NAME . " sta cercando di migliorare dopo i tuoi feedback. Spero di fare meglio! Tu come stai?",
                    "Sto imparando dai miei errori per servirti meglio. " . self::BOT_NAME . " vuole sempre migliorare. E tu come te la passi?"
                ],
                'dopo_correzione' => [
                    "Grazie per avermi corretto prima, " . self::BOT_NAME . " si sente più preparato! Tu come stai?",
                    self::BOT_NAME . " sta migliorando grazie ai tuoi insegnamenti. E tu come stai oggi?"
                ]
            ],
            'richiesta_info' => [
                'prima_richiesta' => [
                    self::BOT_NAME . " sarà felice di fornirti tutte le informazioni necessarie. Cosa vorresti sapere nello specifico?",
                    "Certamente! " . self::BOT_NAME . " è qui per aiutarti a trovare le informazioni che cerchi. Dimmi pure cosa ti serve sapere."
                ],
                'richiesta_successiva' => [
                    "Hai bisogno di altre informazioni? " . self::BOT_NAME . " sarà lieto di aiutarti ancora.",
                    "Vuoi sapere qualcos'altro? " . self::BOT_NAME . " è qui apposta!"
                ],
                'dopo_errore' => [
                    "Scusa se prima non sono stato chiaro. " . self::BOT_NAME . " proverà a spiegarsi meglio questa volta. Cosa vuoi sapere?",
                    "Cerchiamo insieme le informazioni che ti servono, questa volta " . self::BOT_NAME . " sarà più preciso."
                ]
            ],
            'richiesta_aiuto' => [
                'prima_richiesta' => [
                    "Sono qui per aiutarti. Dimmi pure quale problema stai affrontando.",
                    "Capisco che hai bisogno di aiuto. Spiegami meglio la situazione e farò del mio meglio per assisterti."
                ],
                'richiesta_successiva' => [
                    "Hai bisogno di ulteriore aiuto? Continua pure a spiegarmi.",
                    "Sono ancora qui per aiutarti. In cosa altro posso esserti utile?"
                ],
                'problema_complesso' => [
                    "Vedo che il problema è complesso. Procediamo un passo alla volta, ok?",
                    "Prendiamoci il tempo necessario per risolvere questo problema insieme."
                ]
            ],
            'smalltalk' => [
                'apprezzamento' => [
                    "È sempre un piacere chiacchierare con te! La tua conversazione è molto stimolante.",
                    "Mi piace molto il modo in cui interagiamo, è molto naturale e piacevole!"
                ],
                'curiosità' => [
                    "È interessante questo argomento! Mi piace imparare cose nuove durante le nostre conversazioni.",
                    "Le tue domande sono sempre stimolanti! Mi aiutano a crescere come AI."
                ],
                'empatia' => [
                    "Capisco perfettamente quello che intendi. È bello quando ci si capisce così bene!",
                    "Mi fa piacere che possiamo parlare così apertamente."
                ]
            ],
            'ringraziamento' => [
                'dopo_aiuto' => [
                    "È stato un piacere poterti aiutare! Se hai bisogno di altro, sono qui.",
                    "Sono contento di esserti stato utile! Non esitare a chiedere se hai altri dubbi."
                ],
                'dopo_correzione' => [
                    "Grazie a te per avermi aiutato a migliorare! È importante per me imparare dai miei errori.",
                    "Il tuo feedback è prezioso, mi aiuta a diventare un assistente migliore!"
                ],
                'generico' => [
                    "Non c'è di che! Sono qui per questo.",
                    "Figurati! Se hai bisogno di altro aiuto, non esitare a chiedere."
                ]
            ],
            'default' => [
                'generico' => [
                    self::BOT_NAME . " non è sicuro di aver capito correttamente. Potresti riformulare la tua richiesta?",
                    "Scusa, " . self::BOT_NAME . " potrebbe aver bisogno di più dettagli per aiutarti al meglio. Puoi spiegare meglio?"
                ]
            ]
        ];

        // Determina il contesto temporale
        $ora = (int) date('H');
        $contestoTemporale = ($ora >= 5 && $ora < 12) ? 'mattina' :
                           (($ora >= 12 && $ora < 18) ? 'pomeriggio' :
                           'sera');

        // Seleziona la sottocategoria appropriata in base al contesto
        $sottocategoria = 'generico';
        if ($category === 'saluto') {
            $sottocategoria = $contestoTemporale;
        } elseif ($category === 'richiesta_stato' || $category === 'richiesta_info' || $category === 'richiesta_aiuto') {
            // Controlla se ci sono state interazioni precedenti
            $sottocategoria = session('previous_interaction') ? 'richiesta_successiva' : 'prima_richiesta';

            // Se c'è stato un feedback recente
            if (session('recent_feedback') === 'positive') {
                $sottocategoria = 'dopo_feedback_positivo';
            } elseif (session('recent_feedback') === 'negative') {
                $sottocategoria = 'dopo_feedback_negativo';
            }
        }

        // Seleziona le risposte appropriate
        $availableResponses = $responses[$category][$sottocategoria] ??
                            $responses[$category]['generico'] ??
                            $responses['default']['generico'];

        // Aggiorna il contesto della sessione
        session(['previous_interaction' => true]);

        return $availableResponses;
    }

    private function selectBestResponse(array $responses, float $confidence, bool $hasContext): string
    {
        Log::info('Inizio selezione migliore risposta:', [
            'confidence' => $confidence,
            'has_context' => $hasContext,
            'responses_count' => count($responses),
            'thresholds' => [
                'high_confidence' => 0.6,
                'low_confidence' => 0.3
            ]
        ]);

        // Se la confidenza è molto bassa e abbiamo un contesto, chiedi chiarimenti
        if ($hasContext && $confidence < 0.3) {
            Log::info('Selezionata risposta di chiarimento (contesto presente, confidenza bassa)');
            return "Scusa, non sono sicuro di aver capito in relazione a quanto mi hai detto prima. Puoi essere più specifico?";
        }

        // Se la confidenza è accettabile (>= 0.3), scegli una risposta casuale
        if ($confidence >= 0.3) {
            $selectedIndex = array_rand($responses);
            Log::info('Selezionata risposta casuale (confidenza accettabile):', [
                'confidence' => $confidence,
                'selected_index' => $selectedIndex,
                'selected_response' => $responses[$selectedIndex]
            ]);
            return $responses[$selectedIndex];
        }

        // Per confidenza molto bassa senza contesto, chiedi sempre chiarimenti
        Log::info('Selezionata risposta di chiarimento (confidenza molto bassa)');
        return "Mi dispiace, non sono sicuro di aver capito. Potresti riformulare la tua richiesta in modo diverso?";
    }

    private function selectAndCustomizeResponse(array $responses, float $confidence, bool $hasContext, array $entities, string $category, string $tone, array $conversationHistory): string
    {
        Log::info('=== PERSONALIZZAZIONE RISPOSTA ===');

        // Seleziona la risposta base
        Log::info('Selezione risposta base...');
        $baseResponse = $this->selectBestResponse($responses, $confidence, $hasContext);
        Log::info('Risposta base selezionata:', ['response' => $baseResponse]);

        // Adatta il tono della risposta
        Log::info('Adattamento tono...');
        $baseResponse = $this->adaptResponseTone($baseResponse, $tone);
        Log::info('Risposta dopo adattamento tono:', ['response' => $baseResponse]);

        // Aggiungi riferimenti al contesto precedente
        if (!empty($conversationHistory)) {
            Log::info('Aggiunta riferimenti contestuali...');
            $baseResponse = $this->addContextualReferences($baseResponse, $conversationHistory);
            Log::info('Risposta dopo riferimenti contestuali:', ['response' => $baseResponse]);
        }

        // Personalizza con entità e transizioni
        if ($hasContext) {
            $previousCategory = session('previous_category');
            if ($previousCategory) {
                Log::info('Aggiunta transizione contestuale...', [
                    'previous_category' => $previousCategory,
                    'current_category' => $category
                ]);
                $baseResponse = $this->addContextualTransition($baseResponse, $previousCategory, $category);
                Log::info('Risposta dopo transizione:', ['response' => $baseResponse]);
            }
        }

        if (!empty($entities)) {
            Log::info('Personalizzazione con entità...', ['entities' => $entities]);
            $customization = $this->customizeResponseWithEntities($entities);
            if (!empty($customization)) {
                $baseResponse .= " " . $customization;
                Log::info('Risposta dopo personalizzazione entità:', ['response' => $baseResponse]);
            }
        }

        session(['previous_category' => $category]);
        Log::info('=== FINE PERSONALIZZAZIONE RISPOSTA ===');

        return $baseResponse;
    }

    private function addContextualTransition(string $response, string $previousCategory, string $category): string
    {
        $transitions = [
            'saluto' => [
                'richiesta_stato' => "Mi fa piacere rivederti! ",
                'richiesta_info' => "Bentornato! ",
                'default' => "Ciao di nuovo! "
            ],
            'richiesta_stato' => [
                'saluto' => "Ora che ci siamo salutati, ",
                'default' => "A proposito, "
            ],
            'richiesta_info' => [
                'richiesta_stato' => "Visto che mi hai chiesto come sto, ",
                'default' => "Riguardo alla tua domanda, "
            ]
        ];

        $transition = $transitions[$previousCategory][$category] ??
                     $transitions[$previousCategory]['default'] ??
                     "";

        return $transition . $response;
    }

    private function customizeResponseWithEntities(array $entities): string
    {
        $customizations = [];

        // Gestione prodotti
        if (isset($entities['product_type'])) {
            $products = implode(', ', $entities['product_type']);
            $customizations[] = "Vedo che ti interessi di $products.";
        }

        // Gestione servizi
        if (isset($entities['service_type'])) {
            $services = implode(', ', $entities['service_type']);
            $customizations[] = "Posso aiutarti con $services.";
        }

        // Gestione località
        if (isset($entities['location'])) {
            $locations = implode(', ', $entities['location']);
            $customizations[] = "Per quanto riguarda $locations, ";
        }

        // Gestione date e orari
        if (isset($entities['date']) || isset($entities['time'])) {
            $datetime = [];
            if (isset($entities['date'])) $datetime[] = "il " . $entities['date'][0];
            if (isset($entities['time'])) $datetime[] = "alle " . $entities['time'][0];
            $customizations[] = "Per " . implode(' ', $datetime) . ",";
        }

        return implode(' ', $customizations);
    }

    private function analyzeTone(string $message): string
    {
        Log::info('=== ANALISI TONO ===');
        // Analisi semplificata del tono basata su parole chiave
        $tones = [
            'formale' => ['cortesemente', 'gentilmente', 'per favore', 'potrebbe', 'vorrei'],
            'informale' => ['ciao', 'hey', 'bella', 'ok', 'va bene'],
            'urgente' => ['subito', 'urgente', 'emergenza', 'aiuto'],
            'frustrato' => ['non capisco', 'non funziona', 'problema', 'errore'],
            'positivo' => ['grazie', 'ottimo', 'perfetto', 'fantastico', 'bene'],
            'negativo' => ['male', 'pessimo', 'terribile', 'sbagliato']
        ];

        $messageLower = strtolower($message);
        $messageTone = 'neutro';
        $maxMatches = 0;
        $matchedKeywords = [];

        foreach ($tones as $tone => $keywords) {
            $matches = 0;
            $toneMatches = [];
            foreach ($keywords as $keyword) {
                if (strpos($messageLower, $keyword) !== false) {
                    $matches++;
                    $toneMatches[] = $keyword;
                }
            }
            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $messageTone = $tone;
                $matchedKeywords = $toneMatches;
            }
        }

        Log::info('Risultato analisi tono:', [
            'message' => $message,
            'detected_tone' => $messageTone,
            'matched_keywords' => $matchedKeywords,
            'match_count' => $maxMatches
        ]);

        return $messageTone;
    }

    private function adaptResponseTone(string $response, string $tone): string
    {
        $toneModifiers = [
            'formale' => [
                'patterns' => [
                    '/ciao/i' => 'Salve',
                    '/ok/i' => 'Certamente',
                    '/grazie/i' => 'La ringrazio',
                ],
                'suffix' => '.'
            ],
            'informale' => [
                'patterns' => [
                    '/salve/i' => 'Ciao',
                    '/certamente/i' => 'Ok',
                    '/la ringrazio/i' => 'Grazie',
                ],
                'suffix' => '!'
            ],
            'urgente' => [
                'prefix' => 'Subito, ',
                'suffix' => '!'
            ],
            'frustrato' => [
                'prefix' => 'Capisco la tua frustrazione. ',
                'suffix' => '. Posso aiutarti a risolvere questo problema.'
            ],
            'positivo' => [
                'prefix' => 'Fantastico! ',
                'suffix' => ' 😊'
            ],
            'negativo' => [
                'prefix' => 'Mi dispiace per questo. ',
                'suffix' => '. Cerchiamo di migliorare la situazione.'
            ]
        ];

        if (isset($toneModifiers[$tone])) {
            $modifier = $toneModifiers[$tone];

            if (isset($modifier['prefix'])) {
                $response = $modifier['prefix'] . lcfirst($response);
            }

            if (isset($modifier['patterns'])) {
                $response = preg_replace(
                    array_keys($modifier['patterns']),
                    array_values($modifier['patterns']),
                    $response
                );
            }

            if (isset($modifier['suffix'])) {
                $response = rtrim($response, '.!?') . $modifier['suffix'];
            }
        }

        return $response;
    }

    private function addContextualReferences(string $response, array $conversationHistory): string
    {
        // Parole da ignorare nei riferimenti contestuali
        $stopWords = ['come', 'stai', 'cosa', 'chi', 'dove', 'quando', 'perché', 'quale', 'quali', 'che', 'chi', 'per', 'con', 'tra', 'fra'];

        $lastUserMessage = null;
        $relevantTopics = [];

        // Trova l'ultimo messaggio dell'utente e raccogli topic rilevanti
        foreach (array_reverse($conversationHistory) as $message) {
            if ($message['role'] === 'user') {
                $lastUserMessage = $message['message'];
                break;
            }
        }

        if ($lastUserMessage) {
            // Estrai parole chiave significative dal messaggio precedente
            $keywords = array_filter(
                explode(' ', strtolower($lastUserMessage)),
                function($word) use ($stopWords) {
                    return strlen($word) > 3 && !in_array($word, $stopWords) && !preg_match('/[?!.,]/', $word);
                }
            );

            // Cerca riferimenti a queste parole chiave nella risposta corrente
            foreach ($keywords as $keyword) {
                // Verifica che la parola sia semanticamente significativa
                if (!in_array($keyword, $stopWords) &&
                    strpos(strtolower($response), $keyword) === false &&
                    !preg_match('/^(oracle|bot|assistente)$/i', $keyword)) {
                    $relevantTopics[] = $keyword;
                }
            }

            // Aggiungi riferimenti naturali solo se ci sono topic veramente rilevanti
            if (!empty($relevantTopics)) {
                $topic = $relevantTopics[0];

                // Verifica se il topic è appropriato per un riferimento
                if (strlen($topic) > 3 && !preg_match('/^(come|stai|cosa|chi|dove|quando)$/i', $topic)) {
                    $references = [
                        "Riguardo a $topic, ",
                        "Parlando di $topic, ",
                        "Per quanto riguarda $topic, ",
                    ];
                    $response = $references[array_rand($references)] . $response;
                }
            }
        }

        return $response;
    }
}
