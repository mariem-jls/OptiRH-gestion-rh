<?php

namespace App\Controller\Admin\Dashboard;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class WeatherController extends AbstractController
{
    private WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    #[Route('/weather', name: 'weather', methods: ['GET'])]
    public function getWeather(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');
        $lon = $request->query->get('lon');

        if (!$lat || !$lon) {
            return $this->json(['error' => 'Latitude et longitude requises'], 400);
        }

        $data = $this->weatherService->getWeatherByCoordinates($lat, $lon);

        return $this->json($data);
    }
}
