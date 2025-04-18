<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $weatherApiKey)
    {
        $this->client = $client;
        $this->apiKey = $weatherApiKey;
    }

    public function getWeatherByCoordinates(string $lat, string $lon): array
    {
        $response = $this->client->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
            'query' => [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'fr',
            ]
        ]);

        $data = $response->toArray(false);

        if (!($data['cod'] === 200)) {
            return ['error' => $data['message'] ?? 'Erreur inconnue'];
        }

        return $data;
    }
}
