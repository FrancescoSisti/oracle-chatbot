<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MonitorTraining extends Command
{
    protected $signature = 'classifier:monitor';
    protected $description = 'Monitora il progresso del training del classificatore';

    private $lastProcessedCount = 0;
    private $startTime;

    public function handle()
    {
        $this->startTime = now();
        $this->info('Avvio monitoraggio training...');
        $this->info('Premi Ctrl+C per interrompere il monitoraggio');

        while (true) {
            $this->updateProgress();
            sleep(2); // Aggiorna ogni 2 secondi
        }
    }

    private function updateProgress()
    {
        // Statistiche code
        $processingJobs = Queue::size('c4-processing');
        $trainingJobs = Queue::size('c4-training');

        // Job completati
        $completedJobs = DB::table('jobs')
            ->whereIn('queue', ['c4-processing', 'c4-training'])
            ->count();

        // Job falliti
        $failedJobs = DB::table('failed_jobs')
            ->whereIn('queue', ['c4-processing', 'c4-training'])
            ->count();

        // Calcola la velocità di processamento
        $currentTime = now();
        $elapsedMinutes = $currentTime->diffInMinutes($this->startTime) ?: 1;
        $processedPerMinute = round($completedJobs / $elapsedMinutes, 2);

        // Pulisci lo schermo
        $this->output->write("\033[2J\033[H");

        // Mostra statistiche
        $this->info('=== STATO TRAINING ===');
        $this->table(
            ['Metrica', 'Valore'],
            [
                ['Job in coda (processing)', $processingJobs],
                ['Job in coda (training)', $trainingJobs],
                ['Job completati', $completedJobs],
                ['Job falliti', $failedJobs],
                ['Velocità (job/min)', $processedPerMinute],
                ['Tempo trascorso', $this->formatDuration($elapsedMinutes)],
            ]
        );

        // Mostra ultimi log
        $this->info('=== ULTIMI LOG ===');
        $recentLogs = $this->getRecentLogs();
        foreach ($recentLogs as $log) {
            $this->line($log);
        }
    }

    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    private function getRecentLogs(int $lines = 5): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return ['Log file non trovato'];
        }

        $logs = [];
        $file = new \SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();

        $start = max(0, $lastLine - $lines);
        $file->seek($start);

        while (!$file->eof()) {
            $line = $file->current();
            if (strpos($line, '[C4]') !== false ||
                strpos($line, 'training') !== false ||
                strpos($line, 'classifier') !== false) {
                $logs[] = trim($line);
            }
            $file->next();
        }

        return array_slice($logs, -$lines);
    }
}
