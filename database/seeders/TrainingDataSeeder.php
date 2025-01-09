<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainingData;
use App\Services\ML\TextClassifier;
use Faker\Factory as Faker;

class TrainingDataSeeder extends Seeder
{
    private $faker;
    private $baseTrainingData = [];
    private $trainingData = [];

    public function __construct()
    {
        $this->faker = Faker::create('it_IT');
    }

    public function run(): void
    {
        $this->initializeBaseData();
        $this->generateVariants();

        $classifier = new TextClassifier();
        $classifier->train($this->trainingData);

        foreach ($this->trainingData as $data) {
            $classifier->predict($data['text']);
            $confidence = $classifier->getLastConfidence();

            TrainingData::create([
                'text' => $data['text'],
                'category' => $data['category'],
                'is_verified' => true,
                'confidence_score' => $confidence
            ]);
        }
    }

    private function initializeBaseData(): void
    {
        $this->baseTrainingData = [
            'saluto' => [
                'base' => ['ciao', 'buongiorno', 'salve', 'hey'],
                'prefixes' => ['ehi', 'oh', 'scusa'],
                'suffixes' => ['come va', 'tutto bene', 'come stai']
            ],
            'richiesta_stato' => [
                'base' => ['come stai', 'come va', 'tutto bene'],
                'prefixes' => ['dimmi', 'senti'],
                'suffixes' => ['oggi', 'adesso', 'in questo momento', 'ultimamente']
            ],
            'ringraziamento' => [
                'base' => ['grazie', 'ti ringrazio', 'grazie mille'],
                'prefixes' => ['davvero', 'molto', 'tanto'],
                'suffixes' => ['sei gentile', 'sei stato utile', 'mi hai aiutato molto']
            ],
            'richiesta_info' => [
                'base' => ['mi puoi dire', 'vorrei sapere', 'puoi spiegarmi'],
                'prefixes' => ['scusa', 'per favore', 'gentilmente'],
                'suffixes' => ['se possibile', 'quando puoi', 'se non ti dispiace']
            ],
            'richiesta_aiuto' => [
                'base' => ['aiuto', 'ho bisogno di aiuto', 'mi serve una mano'],
                'prefixes' => ['per favore', 'potresti', 'mi servirebbe'],
                'suffixes' => ['con questo', 'per favore', 'se puoi']
            ]
        ];
    }

    private function generateVariants(): void
    {
        foreach ($this->baseTrainingData as $category => $data) {
            // Aggiungi frasi base
            foreach ($data['base'] as $base) {
                $this->trainingData[] = ['text' => $base, 'category' => $category];
            }

            // Genera varianti con prefissi
            foreach ($data['base'] as $base) {
                foreach ($data['prefixes'] as $prefix) {
                    $this->trainingData[] = [
                        'text' => trim($prefix . ' ' . $base),
                        'category' => $category
                    ];
                }
            }

            // Genera varianti con suffissi
            foreach ($data['base'] as $base) {
                foreach ($data['suffixes'] as $suffix) {
                    $this->trainingData[] = [
                        'text' => trim($base . ' ' . $suffix),
                        'category' => $category
                    ];
                }
            }

            // Genera combinazioni casuali
            for ($i = 0; $i < 100; $i++) {
                $base = $this->faker->randomElement($data['base']);
                $prefix = $this->faker->optional(0.3)->randomElement($data['prefixes']);
                $suffix = $this->faker->optional(0.3)->randomElement($data['suffixes']);

                $text = trim(
                    ($prefix ? $prefix . ' ' : '') .
                    $base .
                    ($suffix ? ' ' . $suffix : '')
                );

                $this->trainingData[] = ['text' => $text, 'category' => $category];
            }
        }

        // Genera frasi complesse con multiple categorie
        for ($i = 0; $i < 100; $i++) {
            $categories = $this->faker->randomElements(
                array_keys($this->baseTrainingData),
                $this->faker->numberBetween(2, 3)
            );

            $text = '';
            foreach ($categories as $category) {
                $data = $this->baseTrainingData[$category];
                $base = $this->faker->randomElement($data['base']);
                $prefix = $this->faker->optional(0.3)->randomElement($data['prefixes']);
                $suffix = $this->faker->optional(0.3)->randomElement($data['suffixes']);

                $part = trim(
                    ($prefix ? $prefix . ' ' : '') .
                    $base .
                    ($suffix ? ' ' . $suffix : '')
                );

                $text .= ($text ? ' ' : '') . $part;
            }

            foreach ($categories as $category) {
                $this->trainingData[] = ['text' => $text, 'category' => $category];
            }
        }

        // Aggiungi varianti con errori comuni di battitura
        foreach ($this->trainingData as $data) {
            if ($this->faker->boolean(20)) { // 20% di probabilità di aggiungere una variante con errore
                $text = $this->introduceTypos($data['text']);
                $this->trainingData[] = [
                    'text' => $text,
                    'category' => $data['category']
                ];
            }
        }
    }

    private function introduceTypos(string $text): string
    {
        $words = explode(' ', $text);
        foreach ($words as &$word) {
            if (strlen($word) > 3 && $this->faker->boolean(30)) {
                switch ($this->faker->numberBetween(1, 3)) {
                    case 1: // Scambia due lettere
                        $pos = $this->faker->numberBetween(0, strlen($word) - 2);
                        $chars = str_split($word);
                        [$chars[$pos], $chars[$pos + 1]] = [$chars[$pos + 1], $chars[$pos]];
                        $word = implode('', $chars);
                        break;
                    case 2: // Raddoppia una lettera
                        $pos = $this->faker->numberBetween(0, strlen($word) - 1);
                        $word = substr_replace($word, $word[$pos], $pos, 0);
                        break;
                    case 3: // Ometti una lettera
                        $pos = $this->faker->numberBetween(0, strlen($word) - 1);
                        $word = substr_replace($word, '', $pos, 1);
                        break;
                }
            }
        }
        return implode(' ', $words);
    }
}