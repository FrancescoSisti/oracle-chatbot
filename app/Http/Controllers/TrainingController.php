<?php

namespace App\Http\Controllers;

use App\Models\TrainingData;
use Illuminate\Http\Request;
use App\Services\ML\IntentRecognizer;
use Illuminate\Support\Facades\DB;
use App\Services\ML\TrainingTemplates;

class TrainingController extends Controller
{
    private IntentRecognizer $intentRecognizer;

    public function __construct(IntentRecognizer $intentRecognizer)
    {
        $this->intentRecognizer = $intentRecognizer;
    }

    public function index()
    {
        $trainingData = TrainingData::orderBy('created_at', 'desc')->paginate(20);
        return view('training.index', compact('trainingData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'category' => 'required|string',
        ]);

        $trainingData = TrainingData::create([
            'text' => $validated['text'],
            'category' => $validated['category'],
            'is_verified' => true,
            'confidence_score' => 1.0
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
            'json_file' => 'required|file|mimes:json'
        ]);

        try {
            $jsonContent = file_get_contents($request->file('json_file')->getPathname());
            $data = json_decode($jsonContent, true);

            // Se il JSON ha una struttura con "training_data"
            if (isset($data['training_data'])) {
                $data = $data['training_data'];
            }

            if (!is_array($data)) {
                throw new \Exception('Formato JSON non valido');
            }

            DB::beginTransaction();

            $imported = 0;
            foreach ($data as $item) {
                if (!isset($item['text']) || !isset($item['category'])) {
                    continue;
                }

                TrainingData::create([
                    'text' => $item['text'],
                    'category' => $item['category'],
                    'is_verified' => true,
                    'confidence_score' => 1.0
                ]);
                $imported++;
            }

            DB::commit();
            $this->retrainModel();

            return back()->with('success', "Importati con successo $imported esempi di training");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Errore durante l\'importazione: ' . $e->getMessage());
        }
    }
}
