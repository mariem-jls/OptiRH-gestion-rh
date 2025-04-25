<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class InfobipSmsSender
{
    private HttpClientInterface $client;
    private string $apiUrl;
    private string $apiKey;
    private string $sender;
    private string $defaultPhoneNumber;

    public function __construct(
        HttpClientInterface $client, 
        string $apiUrl, 
        string $apiKey, 
        string $sender,
        string $defaultPhoneNumber
    ) {
        $this->client = $client;
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->sender = $sender;
        $this->defaultPhoneNumber = $defaultPhoneNumber;
    }

    public function sendSms(string $to, string $text): array
    {
        // Si $to est 'default' ou vide, utiliser le numéro par défaut
        if ($to === 'default' || empty($to)) {
            $to = $this->defaultPhoneNumber;
        }
        
        // Formatage du numéro si nécessaire
        $to = trim($to);
        if (!(str_starts_with($to, '216') || str_starts_with($to, '+216'))) {
            $to = '216' . $to;
        }

        $payload = [
            'messages' => [
                [
                    'destinations' => [
                        ['to' => $to]
                    ],
                    'from' => $this->sender,
                    'text' => $text,
                ]
            ]
        ];

        try {
            $response = $this->client->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'App ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => $payload,
            ]);

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new \Exception("Erreur lors de l'envoi du SMS : " . $e->getMessage());
        }
    }
}