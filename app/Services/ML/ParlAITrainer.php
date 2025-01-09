<?php

namespace App\Services\ML;

class ParlAITrainer
{
    private TextClassifier $classifier;
    private array $trainingData = [];
    private array $metrics = [];

    public function __construct()
    {
        $this->classifier = new TextClassifier();
    }

    public function train(array $data, array $options = [])
    {
        $this->metrics = [
            'total_examples' => 0,
            'correct_predictions' => 0,
            'accuracy' => 0,
            'loss' => 0,
            'epochs' => 0,
        ];

        $batchSize = $options['batch_size'] ?? 32;
        $epochs = $options['epochs'] ?? 10;
        $validationSplit = $options['validation_split'] ?? 0.2;

        // Divide i dati in training e validation
        $splitIndex = (int)(count($data) * (1 - $validationSplit));
        $trainData = array_slice($data, 0, $splitIndex);
        $validData = array_slice($data, $splitIndex);

        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            $this->metrics['epochs']++;

            // Training
            foreach (array_chunk($trainData, $batchSize) as $batch) {
                $this->trainBatch($batch);
            }

            // Validation
            $validationMetrics = $this->validate($validData);

            // Aggiorna le metriche
            $this->metrics['accuracy'] = $validationMetrics['accuracy'];
            $this->metrics['loss'] = $validationMetrics['loss'];
        }

        return $this->metrics;
    }

    private function trainBatch(array $batch)
    {
        $this->classifier->train($batch);
        foreach ($batch as $example) {
            $this->metrics['total_examples']++;

            $prediction = $this->classifier->predict($example['text']);
            if ($prediction === $example['category']) {
                $this->metrics['correct_predictions']++;
            }
        }
    }

    private function validate(array $validData): array
    {
        $correct = 0;
        $total = count($validData);
        $loss = 0;

        foreach ($validData as $example) {
            $prediction = $this->classifier->predict($example['text']);
            if ($prediction === $example['category']) {
                $correct++;
            }
            // Calcolo loss semplificato
            $loss += ($prediction !== $example['category']) ? 1 : 0;
        }

        return [
            'accuracy' => $total > 0 ? ($correct / $total) : 0,
            'loss' => $total > 0 ? ($loss / $total) : 0
        ];
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }
}
