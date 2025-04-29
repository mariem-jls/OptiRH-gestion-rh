<?php
// src/Controller/Admin/Transport/MapController.php
namespace App\Controller\Admin\Transport;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/map')]
class MapController extends AbstractController
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/geocode', name: 'map_geocode', methods: ['GET'])]
    public function geocode(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        if (empty($query)) {
            return new JsonResponse([], 400);
        }

        $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
            'query' => [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'tn', // Limite à la Tunisie
                'addressdetails' => 1
            ],
            'headers' => [
                'User-Agent' => 'YourAppName/1.0 (contact@yourdomain.com)'
            ]
        ]);

        return new JsonResponse($response->toArray());
    }
}