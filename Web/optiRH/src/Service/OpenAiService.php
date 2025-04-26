<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OpenAiService
{
    private string $apiKey;
    private Client $client;
    private string $model;

    public function __construct(string $apiKey, string $model = 'gpt-4o-mini')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'verify' => 'C:\wamp64\bin\php\php8.3.0\extras\ssl\cacert.pem',
        ]);
    }

    public function generateDescription(string $prompt): string
    {
        try {
            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 150,
                    'temperature' => 0.7,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return trim($data['choices'][0]['message']['content'] ?? '');

        } catch (GuzzleException $e) {
            // Log l'erreur si besoin ou juste retourner une string vide
            return 'Erreur lors de la génération : ' . $e->getMessage();
        }
    }
}
