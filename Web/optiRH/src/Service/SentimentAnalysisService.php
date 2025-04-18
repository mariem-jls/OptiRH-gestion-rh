<?php

// src/Service/SentimentAnalysisService.php

// src/Service/SentimentAnalysisService.php

namespace App\Service;
// src/Service/SentimentAnalysisService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SentimentAnalysisService
{
    private const MODEL = 'finiteautomata/bertweet-base-sentiment-analysis';
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
            return ['score' => 0, 'label' => 'error', 'error' => 'Empty text'];
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
                    sleep(10); // Attendre que le modèle se charge
                    continue;
                }

                throw new \Exception("API returned status $statusCode");

            } catch (\Exception $e) {
                $this->logger->error("Attempt $retry failed: ".$e->getMessage());
                sleep(5);
            }
        }

        return $this->fallbackAnalysis($text);
    }

    private function preprocessText(string $text): string
    {
        // Normalisation du texte
        $replacements = [
            'dispointed' => 'disappointed',
            'sadd' => 'sad',
            'angryyy' => 'angry',
            ' u ' => ' you ',
            ' ur ' => ' your ',
            ' im ' => ' i am '
        ];

        $text = strtolower($text);
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        
        return $text;
    }

    private function processResponse(array $data): array
    {
        if (!isset($data[0])) {
            return $this->fallbackAnalysis('');
        }

        $result = $data[0];
        $label = $result['label'] ?? 'neutral';
        $score = $result['score'] ?? 0.5;

        return [
            'score' => round($score, 2),
            'label' => $label
        ];
    }

    private function fallbackAnalysis(string $text): array
    {
        // Analyse de secours pour les cas où l'API échoue
        $negativeWords = ['hate', 'disappointed', 'sad', 'angry', 'shame', 'disrespectful'];
        $positiveWords = ['happy', 'good', 'great', 'excellent', 'awesome'];

        $score = 0.5;
        $textLower = strtolower($text);

        foreach ($negativeWords as $word) {
            if (str_contains($textLower, $word)) {
                $score -= 0.3;
            }
        }

        foreach ($positiveWords as $word) {
            if (str_contains($textLower, $word)) {
                $score += 0.3;
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