<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ML\WikiEventsService;
use App\Models\TrainingData;
use Illuminate\Support\Facades\DB;

class ImportHistoricalEvents extends Command
{
    protected $signature = 'events:import
                          {--year=2000 : Anno di inizio}
                          {--limit=100 : Numero di eventi da importare}';

    protected $description = 'Importa eventi storici da Wikipedia per il training del chatbot';

    private WikiEventsService $wikiEvents;

    public function __construct(WikiEventsService $wikiEvents)
    {
        parent::__construct();
        $this->wikiEvents = $wikiEvents;
    }

    public function handle()
    {
        $year = $this->option('year');
        $limit = $this->option('limit');

        $this->info("Importazione eventi storici dell'anno $year...");

        try {
            $events = $this->wikiEvents->getEvents([
                'begin_date' => $year . '0101',
                'end_date' => $year . '1231',
                'limit' => $limit
            ]);

            DB::beginTransaction();
            $imported = 0;

            foreach ($events as $event) {
                $category = $this->wikiEvents->categorizeEvent($event['description']);

                TrainingData::create([
                    'text' => $event['description'],
                    'category' => $category,
                    'is_verified' => true,
                    'confidence_score' => 1.0
                ]);

                $imported++;
            }

            DB::commit();
            $this->info("Importati con successo $imported eventi.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Errore durante l\'importazione: ' . $e->getMessage());
        }
    }
}
