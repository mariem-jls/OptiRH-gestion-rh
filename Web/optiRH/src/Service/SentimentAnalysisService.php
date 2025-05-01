<?php

// src/Service/SentimentAnalysisService.php


namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SentimentAnalysisService
{
    private const MODEL = 'nlptown/bert-base-multilingual-uncased-sentiment';
    private const TIMEOUT = 30;
    private const MAX_RETRIES = 3;

    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey,
        private LoggerInterface $logger
    ) {}

    public function analyze(string $text): array
    {
        if (empty(trim($text))) {
            return ['score' => 0, 'label' => 'neutral', 'error' => 'Empty text'];
        }

        $preprocessedText = $this->preprocessText($text);

        for ($retry = 0; $retry < self::MAX_RETRIES; $retry++) {
            try {
                $response = $this->client->request(
                    'POST',
                    'https://api-inference.huggingface.co/models/'.self::MODEL,
                    [
                        'headers' => [
                            'Authorization' => 'Bearer '.$this->apiKey,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => ['inputs' => $preprocessedText],
                        'timeout' => self::TIMEOUT,
                    ]
                );

                $statusCode = $response->getStatusCode();

                if ($statusCode === 200) {
                    $data = $response->toArray();
                    return $this->processResponse($data);
                }

                if ($statusCode === 503) {
                    $this->logger->warning('Model is loading, retrying...', ['attempt' => $retry + 1]);
                    sleep(10);
                    continue;
                }

                throw new \Exception("API returned status $statusCode");

            } catch (\Exception $e) {
                $this->logger->error("Attempt $retry failed: ".$e->getMessage());
                if ($retry < self::MAX_RETRIES - 1) {
                    sleep(5);
                }
            }
        }

        return $this->fallbackAnalysis($preprocessedText);
    }

    private function preprocessText(string $text): string
    {
        // Normalisation du texte
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    private function processResponse(array $data): array
    {
        if (empty($data[0])) {
            return $this->fallbackAnalysis('');
        }

        $result = $data[0];
        
        // Le modèle retourne un score de 1 à 5
        // 1-2: négatif
        // 3: neutre
        // 4-5: positif
        $score = $result[0]['score'] ?? 0.5;
        $rating = $result[0]['label'] ?? '3 stars';
        $numericRating = (int) filter_var($rating, FILTER_SANITIZE_NUMBER_INT);
        
        // Conversion en score de 0 à 1
        $normalizedScore = ($numericRating - 1) / 4;
        
        // Détermination du label
        $label = match(true) {
            $numericRating <= 2 => 'negative',
            $numericRating >= 4 => 'positive',
            default => 'neutral'
        };

        return [
            'score' => round($normalizedScore, 2),
            'label' => $label
        ];
    }

    private function fallbackAnalysis(string $text): array
    {
        // Analyse de secours pour les cas où l'API échoue
        $negativeWords = ['hate', 'disappointed', 'sad', 'angry', 'shame', 'disrespectful', 
                         'déteste', 'déçu', 'triste', 'fâché', 'honte', 'irrespecteux'];
        $positiveWords = ['happy', 'good', 'great', 'excellent', 'awesome', 'love',
                         'heureux', 'bon', 'super', 'excellent', 'génial', 'aime'];

        $score = 0.5;
        $textLower = strtolower($text);

        foreach ($negativeWords as $word) {
            if (str_contains($textLower, $word)) {
                $score -= 0.2;
            }
        }

        foreach ($positiveWords as $word) {
            if (str_contains($textLower, $word)) {
                $score += 0.2;
            }
        }

        $score = max(0, min(1, $score));

        return [
            'score' => round($score, 2),
            'label' => $score > 0.6 ? 'positive' : ($score < 0.4 ? 'negative' : 'neutral'),
            'fallback' => true
        ];
    }
}