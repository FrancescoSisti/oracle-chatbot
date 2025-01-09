<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ML\TextClassifier;
use Illuminate\Support\Facades\Queue;

class PretrainClassifier extends Command
{
    protected $signature = 'classifier:pretrain
        {--limit=1000 : Numero di esempi da caricare dal dataset C4}
        {--chunks=4 : Numero di chunk per il processamento parallelo}
        {--queue=default : Nome della coda da utilizzare}';

    protected $description = 'Esegue il pre-training del classificatore usando il dataset C4 italiano';

    public function handle()
    {
        $limit = $this->option('limit');
        $chunks = $this->option('chunks');
        $queue = $this->option('queue');

        $this->info("Configurazione pre-training:");
        $this->table(
            ['Parametro', 'Valore'],
            [
                ['Limite esempi', $limit],
                ['Numero chunks', $chunks],
                ['Coda', $queue]
            ]
        );

        if (!$this->confirm('Vuoi procedere con il pre-training?', true)) {
            return;
        }

        $this->info("Inizio pre-training...");
        $progress = $this->output->createProgressBar($limit);
        $progress->start();

        try {
            $classifier = new TextClassifier();

            // Configura l'handler per aggiornare la progress bar
            Queue::before(function ($job) use ($progress) {
                $progress->advance();
            });

            $classifier->pretrainWithC4($limit, $chunks);

            $progress->finish();
            $this->newLine();
            $this->info('Pre-training completato con successo!');

        } catch (\Exception $e) {
            $this->error('Errore durante il pre-training:');
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
