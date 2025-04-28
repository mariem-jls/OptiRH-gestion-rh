<?php
// src/Service/ResponseGenerationService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ResponseGenerationService
{
    private $httpClient;
    private $logger;
    private $params;
    private $apiKey;
    private $model;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->params = $params;
        $this->apiKey = $this->params->get('huggingface_api_key');
        $this->model = 'gpt2'; // Modèle par défaut, vous pouvez changer selon vos besoins
    }

    /**
     * Génère une réponse basée sur la description de la réclamation
     */
    public function generateResponse(string $description, string $type): string
    {
        try {
            $prompt = $this->createPrompt($description, $type);
            
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/' . $this->model, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_length' => 150,
                        'temperature' => 0.7,
                        'top_k' => 50,
                        'top_p' => 0.95,
                    ],
                    'options' => [
                        'wait_for_model' => true,
                    ],
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('Erreur lors de la génération de la réponse. Code HTTP: ' . $statusCode);
                return 'Notre système n\'a pas pu générer une réponse appropriée. Veuillez réessayer ultérieurement.';
            }

            $content = $response->toArray();
            
            // Traitement du résultat qui peut varier selon le modèle
            $generatedText = $this->extractGeneratedText($content);
            
            // Nettoyage et mise en forme de la réponse
            return $this->formatResponse($generatedText, $type);

        } catch (\Exception $e) {
            $this->logger->error('Exception lors de la génération de la réponse: ' . $e->getMessage());
            return 'Une erreur est survenue lors de la génération de la réponse automatique. Veuillez rédiger manuellement votre réponse.';
        }
    }

    /**
     * Crée un prompt approprié pour le modèle en fonction du type de réclamation
     */
    private function createPrompt(string $description, string $type): string
    {
        $typePrefix = '';
        switch ($type) {
            case 'Technique':
                $typePrefix = 'Vous êtes un expert technique. Voici une réclamation technique: ';
                break;
            case 'Commercial':
                $typePrefix = 'Vous êtes un spécialiste du service client. Voici une réclamation commerciale: ';
                break;
            case 'Produit':
                $typePrefix = 'Vous êtes un spécialiste produit. Voici une réclamation concernant un produit: ';
                break;
            default:
                $typePrefix = 'Vous êtes un agent de support. Voici une réclamation: ';
        }

        return $typePrefix . $description . "\n\nRéponse professionnelle et empathique:";
    }

    /**
     * Extrait le texte généré du résultat de l'API
     */
    private function extractGeneratedText(array $content): string
    {
        // Adaptation selon le format de la réponse du modèle utilisé
        if (isset($content[0]['generated_text'])) {
            return $content[0]['generated_text'];
        } elseif (isset($content['generated_text'])) {
            return $content['generated_text'];
        } else {
            $this->logger->warning('Format de réponse non reconnu: ' . json_encode($content));
            return json_encode($content);
        }
    }

    /**
     * Nettoie et formate la réponse générée
     */
    private function formatResponse(string $text, string $type): string
    {
        // Extraction de la partie pertinente de la réponse
        $parts = explode('Réponse professionnelle et empathique:', $text);
        $response = count($parts) > 1 ? trim($parts[1]) : trim($text);
        
        // Limiter la longueur de la réponse
        if (strlen($response) > 500) {
            $response = substr($response, 0, 500) . '...';
        }
        
        // Ajouter une formule de politesse si elle n'existe pas
        if (!str_contains(strtolower($response), 'merci') && !str_contains(strtolower($response), 'cordialement')) {
            $response .= "\n\nCordialement,\nL'équipe de support.";
        }
        
        return $response;
    }
}