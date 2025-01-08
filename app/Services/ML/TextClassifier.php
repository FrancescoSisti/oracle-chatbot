<?php

namespace App\Services\ML;

class TextClassifier
{
    private array $vocabulary = [];
    private array $categories = [];
    private array $wordFrequencies = [];
    private array $categoryProbabilities = [];
    private const BATCH_SIZE = 100;

    public function train(array $trainingData)
    {
        // Resetta i dati di training
        $this->vocabulary = [];
        $this->categories = [];
        $this->wordFrequencies = [];

        // Analizza i dati di training
        foreach ($trainingData as $item) {
            $this->processTrainingItem($item);
        }

        $this->updateModel();
    }

    public function classify(string $text): string
    {
        $words = $this->tokenize($text);
        $scores = [];

        foreach ($this->categories as $category => $count) {
            $scores[$category] = log($this->categoryProbabilities[$category]);

            foreach ($words as $word) {
                if (isset($this->wordFrequencies[$category][$word])) {
                    $wordFreq = $this->wordFrequencies[$category][$word];
                    $scores[$category] += log(($wordFreq + 1) / ($count + count($this->vocabulary)));
                }
            }
        }

        arsort($scores);
        return key($scores);
    }

    private function tokenize(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/i', '', $text);
        return array_filter(explode(' ', $text));
    }

    public function batchTrain(array $trainingData)
    {
        $batches = array_chunk($trainingData, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            $this->trainBatch($batch);
        }
    }

    private function trainBatch(array $batch)
    {
        foreach ($batch as $item) {
            $this->processTrainingItem($item);
        }

        $this->updateModel();
    }

    private function processTrainingItem(array $item)
    {
        $category = $item['category'];
        $text = $this->tokenize($item['text']);

        if (!isset($this->categories[$category])) {
            $this->categories[$category] = 0;
        }
        $this->categories[$category]++;

        foreach ($text as $word) {
            if (!isset($this->vocabulary[$word])) {
                $this->vocabulary[$word] = true;
            }

            if (!isset($this->wordFrequencies[$category][$word])) {
                $this->wordFrequencies[$category][$word] = 0;
            }
            $this->wordFrequencies[$category][$word]++;
        }
    }

    private function updateModel()
    {
        // Calcola le probabilità per categoria
        $totalDocs = array_sum($this->categories);
        foreach ($this->categories as $category => $count) {
            $this->categoryProbabilities[$category] = $count / $totalDocs;
        }
    }
}