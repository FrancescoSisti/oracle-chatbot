<?php

namespace App\Http\Controllers;

use App\Models\TrainingData;
use Illuminate\Http\Request;
use App\Services\ML\IntentRecognizer;
use Illuminate\Support\Facades\DB;
use App\Services\ML\TrainingTemplates;
use App\Services\ML\JsonHandler;
use JsonStreamingParser\Parser as JsonParser;
use App\Services\ML\WikiEventsService;
use App\Services\ML\ParlAITrainer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class TrainingController extends Controller
{
    private IntentRecognizer $intentRecognizer;
    private WikiEventsService $wikiEvents;
    private ParlAITrainer $trainer;

    public function __construct(WikiEventsService $wikiEvents)
    {
        $this->intentRecognizer = IntentRecognizer::getInstance();
        $this->wikiEvents = $wikiEvents;
        $this->trainer = new ParlAITrainer();
    }

    public function index()
    {
        $trainingData = TrainingData::orderBy('created_at', 'desc')->paginate(20);
        $metrics = $this->trainer->getMetrics();

        return view('training.index', compact('trainingData', 'metrics'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'category' => 'required|string',
        ]);

        // Predici la categoria per calcolare la confidenza
        $classifier = $this->intentRecognizer->getClassifier();
        $classifier->predict($validated['text']);
        $confidence = $classifier->getLastConfidence();

        $trainingData = TrainingData::create([
            'text' => $validated['text'],
            'category' => $validated['category'],
            'is_verified' => true,
            'confidence_score' => $confidence * 100 // Converti in percentuale
        ]);

        // Riaddestra il modello dopo l'aggiunta di nuovi dati
        $this->retrainModel();

        return redirect()->back()->with('success', 'Dato di training aggiunto con successo');
    }

    public function verify(TrainingData $trainingData)
    {
        $trainingData->update(['is_verified' => true]);

        // Riaddestra il modello dopo la verifica
        $this->retrainModel();

        return redirect()->back()->with('success', 'Dato verificato e modello riaddestrato');
    }

    public function delete(TrainingData $trainingData)
    {
        $trainingData->delete();

        // Riaddestra il modello dopo l'eliminazione
        $this->retrainModel();

        return redirect()->back()->with('success', 'Dato di training eliminato');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,json'
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        switch ($extension) {
            case 'csv':
                $data = $this->parseCSV($file);
                break;
            case 'json':
                $data = json_decode(file_get_contents($file->getPathname()), true);
                break;
            default:
                return back()->with('error', 'Formato file non supportato');
        }

        DB::beginTransaction();
        try {
            foreach ($data as $item) {
                TrainingData::create([
                    'text' => $item['text'],
                    'category' => $item['category'],
                    'is_verified' => true,
                    'confidence_score' => 1.0
                ]);
            }
            DB::commit();
            $this->retrainModel();

            return back()->with('success', 'Dati importati con successo');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Errore durante l\'importazione: ' . $e->getMessage());
        }
    }

    private function parseCSV($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = [
                'text' => $row[0],
                'category' => $row[1]
            ];
        }

        fclose($handle);
        return $data;
    }

    /**
     * Riaddestra il modello utilizzando i dati verificati
     */
    private function retrainModel()
    {
        $trainingData = TrainingData::where('is_verified', true)
            ->get()
            ->map(function ($data) {
                return [
                    'text' => $data->text,
                    'category' => $data->category
                ];
            })
            ->toArray();

        $this->intentRecognizer->getClassifier()->train($trainingData);
    }

    public function importTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required|string|in:customer_service,e_commerce,support_tech'
        ]);

        $templateName = $request->input('template');
        $templates = TrainingTemplates::getTemplates();

        if (!isset($templates[$templateName])) {
            return back()->with('error', 'Template non trovato');
        }

        DB::beginTransaction();
        try {
            foreach ($templates[$templateName] as $item) {
                TrainingData::create([
                    'text' => $item['text'],
                    'category' => $item['category'],
                    'is_verified' => true,
                    'confidence_score' => 1.0
                ]);
            }
            DB::commit();

            // Riaddestra il modello con i nuovi dati
            $this->retrainModel();

            return back()->with('success', 'Template importato con successo');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Errore durante l\'importazione del template: ' . $e->getMessage());
        }
    }

    public function importJson(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json|max:512000' // 500MB in kilobytes
        ]);

        try {
            // Leggiamo il file in modo più efficiente usando uno stream
            $stream = fopen($request->file('json_file')->getPathname(), 'r');
            $parser = new JsonParser($stream, new JsonHandler());
            $parser->parse();
            fclose($stream);

            // Processa i dati in batch più piccoli
            $batchSize = 500;
            $imported = 0;
            $currentBatch = [];

            DB::beginTransaction();

            foreach (JsonHandler::getData() as $item) {
                if (!isset($item['text']) || !isset($item['category'])) {
                    continue;
                }

                $currentBatch[] = [
                    'text' => $item['text'],
                    'category' => $item['category'],
                    'is_verified' => true,
                    'confidence_score' => 1.0,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                if (count($currentBatch) >= $batchSize) {
                    TrainingData::insert($currentBatch);
                    $imported += count($currentBatch);
                    $currentBatch = [];
                }
            }

            // Inserisci gli ultimi record rimanenti
            if (!empty($currentBatch)) {
                TrainingData::insert($currentBatch);
                $imported += count($currentBatch);
            }

            DB::commit();

            // Riaddestra il modello usando batch
            $this->retrainModelInBatches();

            return back()->with('success', "Importati con successo $imported esempi di training");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Errore durante l\'importazione: ' . $e->getMessage());
        }
    }

    private function retrainModelInBatches()
    {
        $batchSize = 1000;
        $page = 1;

        do {
            $trainingData = TrainingData::where('is_verified', true)
                ->forPage($page, $batchSize)
                ->get()
                ->map(function ($data) {
                    return [
                        'text' => $data->text,
                        'category' => $data->category
                    ];
                })
                ->toArray();

            if (!empty($trainingData)) {
                $this->intentRecognizer->getClassifier()->batchTrain($trainingData);
            }

            $page++;
        } while (!empty($trainingData));
    }

    public function importWikiEvents(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'limit' => 'required|integer|min:1|max:1000'
        ]);

        try {
            $events = $this->wikiEvents->getEvents([
                'begin_date' => $request->year . '0101',
                'end_date' => $request->year . '1231',
                'limit' => $request->limit
            ]);

            if (empty($events)) {
                return back()->with('error', 'Nessun evento trovato per l\'anno selezionato');
            }

            DB::beginTransaction();
            $imported = 0;
            $existingTexts = TrainingData::pluck('text')->toArray();

            foreach ($events as $event) {
                // Salta eventi duplicati
                if (in_array($event['description'], $existingTexts)) {
                    continue;
                }

                $category = $this->wikiEvents->categorizeEvent($event['description']);

                TrainingData::create([
                    'text' => $event['description'],
                    'category' => $category,
                    'is_verified' => true,
                    'confidence_score' => 1.0
                ]);

                $imported++;
                $existingTexts[] = $event['description'];
            }

            if ($imported > 0) {
                $this->retrainModel();
                DB::commit();
                return back()->with('success', "Importati con successo $imported nuovi eventi storici.");
            } else {
                DB::rollBack();
                return back()->with('info', 'Nessun nuovo evento da importare per l\'anno selezionato.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Errore durante l\'importazione: ' . $e->getMessage());
        }
    }

    public function importDailyConversation()
    {
        try {
            Log::info('Inizio importazione conversazioni quotidiane');
            $templates = TrainingTemplates::getDailyConversationTemplates();
            Log::info('Template caricati', ['count' => count($templates)]);

            DB::beginTransaction();
            $imported = 0;

            foreach ($templates as $template) {
                if (!TrainingData::where('text', $template['text'])->exists()) {
                    TrainingData::create([
                        'text' => $template['text'],
                        'category' => $template['category'],
                        'is_verified' => true,
                        'confidence_score' => 1.0
                    ]);
                    $imported++;
                }
            }

            Log::info('Importazione completata', ['imported' => $imported]);

            if ($imported > 0) {
                $this->retrainModel();
                DB::commit();
                return back()->with('success', "Importati $imported nuovi esempi di conversazione quotidiana");
            }

            DB::rollBack();
            return back()->with('info', 'Nessun nuovo esempio da importare');

        } catch (\Exception $e) {
            Log::error('Errore importazione', ['error' => $e->getMessage()]);
            DB::rollBack();
            return back()->with('error', 'Errore durante l\'importazione: ' . $e->getMessage());
        }
    }

    public function trainModel(Request $request)
    {
        $options = [
            'batch_size' => $request->input('batch_size', 32),
            'epochs' => $request->input('epochs', 10),
            'validation_split' => $request->input('validation_split', 0.2)
        ];

        $trainingData = TrainingData::where('is_verified', true)
            ->get()
            ->map(fn($item) => [
                'text' => $item->text,
                'category' => $item->category
            ])
            ->toArray();

        $metrics = $this->trainer->train($trainingData, $options);

        return response()->json([
            'success' => true,
            'metrics' => $metrics
        ]);
    }

    public function pretrain(Request $request)
    {
        $request->validate([
            'examples' => 'required|integer|min:1000|max:50000',
            'chunks' => 'required|integer|min:10|max:80',
        ]);

        // Resetta lo stato del training
        Cache::put('training_status', [
            'is_training' => true,
            'processed_examples' => 0,
            'total_examples' => $request->examples,
            'avg_confidence' => 0
        ], now()->addHours(1));

        // Avvia il comando in background
        Artisan::queue('classifier:pretrain', [
            '--limit' => $request->examples,
            '--chunks' => $request->chunks
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pre-training avviato con successo!'
            ]);
        }

        return redirect()->route('training.index')
            ->with('success', 'Pre-training avviato con successo! Puoi monitorare il progresso qui.');
    }
}
