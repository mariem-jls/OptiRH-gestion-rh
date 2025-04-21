<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EmbeddingService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private FilesystemAdapter $cache;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        string $huggingFaceApiKey,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $huggingFaceApiKey;
        $this->cache = new FilesystemAdapter();
        $this->logger = $logger;
    }

    private function sanitizeText(string $text): string
    {
        // Remove sensitive info
        $text = preg_replace('/\S+@\S+\.\S+/', '', $text); // Emails
        $text = preg_replace('/\b\d{10,}\b/', '', $text); // Very long numbers (keep short ones like "6 mois")

        // Normalize spacing
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove special characters, keep periods and hyphens
        $text = trim(preg_replace('/[^\w\s.-]/', ' ', $text));

        // Boost key skills relevant to the offer
        $keySkills = [
            'Python', 'Pandas', 'Scikit-learn', 'TensorFlow', 'Power BI', 'SQL',
            'machine learning', 'data science', 'visualization', 'predictive models'
        ];
        foreach ($keySkills as $skill) {
            // Repeat skill twice to increase weight
            $text = str_replace($skill, "$skill $skill", $text, $count);
            if ($count > 0) {
                $this->logger->debug('Boosted skill', ['skill' => $skill, 'count' => $count]);
            }
        }

        $this->logger->debug('Sanitized text', ['text' => substr($text, 0, 200) . '...']);
        return $text;
    }

    public function generateEmbedding(string $text): array
    {
        $cleanText = $this->sanitizeText($text);
        $cacheKey = 'embedding_' . md5($cleanText);

        $this->logger->debug('Cache key', ['key' => $cacheKey]);

        return $this->cache->get($cacheKey, function () use ($cleanText) {
            $retryCount = 0;
            $maxRetries = 3;

            while ($retryCount <= $maxRetries) {
                try {
                    $response = $this->httpClient->request(
                        'POST',
                        'https://api-inference.huggingface.co/pipeline/feature-extraction/sentence-transformers/all-MiniLM-L6-v2',
                        [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $this->apiKey,
                                'Content-Type' => 'application/json',
                            ],
                            'json' => [
                                'inputs' => $cleanText,
                                'options' => [
                                    'wait_for_model' => true,
                                    'use_cache' => true
                                ]
                            ],
                            'timeout' => 30
                        ]
                    );

                    $statusCode = $response->getStatusCode();
                    $content = $response->getContent(false);

                    $this->logger->debug('Hugging Face API response', [
                        'status' => $statusCode,
                        'content' => substr($content, 0, 500)
                    ]);

                    if ($statusCode === 200) {
                        $result = $response->toArray();
                        // Handle single embedding or array of embeddings
                        $embedding = is_array($result[0]) ? $result[0] : $result;
                        $this->logger->debug('Generated embedding', ['first_10' => array_slice($embedding, 0, 10)]);
                        return $embedding;
                    }

                    $errorData = json_decode($content, true);
                    if (str_contains($content, 'loading')) {
                        $this->logger->info('Model loading, retrying', ['retry' => $retryCount + 1]);
                        sleep(10);
                        $retryCount++;
                        continue;
                    }

                    throw new \RuntimeException("Erreur HTTP $statusCode: " . $content);
                } catch (\Exception $e) {
                    $this->logger->error('Embedding generation failed', [
                        'retry' => $retryCount,
                        'error' => $e->getMessage()
                    ]);
                    if ($retryCount === $maxRetries) {
                        throw new \RuntimeException('Échec après ' . $maxRetries . ' tentatives: ' . $e->getMessage());
                    }
                    sleep(pow(2, $retryCount));
                    $retryCount++;
                }
            }
        });
    }
}