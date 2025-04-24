<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;
use App\Entity\GsProjet\Project;
use App\Entity\GsProjet\Mission;

class GeminiAnalysisService
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com';
    private const API_VERSION = 'v1beta';
    private const MODEL_NAME = 'gemini-2.0-flash'; // Modèle mis à jour
    private const CACHE_TTL = 30; // 30 minutes


    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private string $apiKey
    ) {}

    public function generateProjectAnalysis(array $projects, array $missions): string
    {
        $cacheKey = $this->generateCacheKey($projects, $missions);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($projects, $missions) {
            $item->expiresAfter(self::CACHE_TTL);
            
            try {
                $response = $this->httpClient->request(
                    'POST',
                    $this->buildApiUrl(),
                    $this->buildRequestOptions($projects, $missions)
                );

                // Debug: Enregistrer la réponse brute
                $content = $response->getContent(false);
                $this->logger->debug('Gemini API Response', ['response' => $content]);

                $data = json_decode($content, true);
                
                if ($response->getStatusCode() !== 200) {
                    throw new \RuntimeException('API returned status: '.$response->getStatusCode());
                }

                return $this->extractContent($data);

            } catch (\Exception $e) {
                $this->logger->error('Gemini API Error', [
                    'error' => $e->getMessage(),
                    'url' => $this->buildApiUrl(),
                    'trace' => $e->getTraceAsString()
                ]);
                return '⚠️ Le service d\'analyse est temporairement indisponible. Code d\'erreur: '.$e->getMessage();
            }
        });
    }

    private function buildApiUrl(): string
    {
        return sprintf('%s/%s/models/%s:generateContent?key=%s',
            self::BASE_URL,
            self::API_VERSION,
            self::MODEL_NAME,
            $this->apiKey
        );
    }

    private function buildRequestOptions(array $projects, array $missions): array
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    'parts' => [
                        ['text' => $this->buildAdminPrompt($projects, $missions)]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048,
                    'topP' => 0.95
                ]
            ],
            'timeout' => 30
        ];
    }

    private function extractContent(array $response): string
    {
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $this->logger->error('Invalid API response structure', ['response' => $response]);
            throw new \RuntimeException('Invalid response from Gemini API');
        }

        return $response['candidates'][0]['content']['parts'][0]['text'];
    }

    private function buildAdminPrompt(array $projectsData, array $missionsData): string
    {
        $projectSummary = "Statistiques des projets:\n";
        foreach ($projectsData as $status => $count) {
            $projectSummary .= sprintf("- %s: %d projets\n", $status, $count);
        }

        $missionSummary = "Statistiques des missions:\n";
        foreach ($missionsData as $status => $count) {
            $missionSummary .= sprintf("- %s: %d missions\n", $status, $count);
        }

        $delayedSummary = sprintf(
            "\nProjets en retard: %d\nMissions en retard: %d",
            $projectsData[Project::STATUS_DELAYED] ?? 0,
            $missionsData['Late'] ?? 0
        );

        return <<<PROMPT
        En tant qu'expert en gestion de projet, analysez ces données pour un tableau de bord administratif.
        Fournissez un rapport concis en français avec:
        
        1. Une analyse globale de l'état des projets et missions
        2. Les points critiques nécessitant une attention immédiate
        3. Trois recommandations d'actions prioritaires
        4. Une estimation des risques à moyen terme
        
        Structurez la réponse avec des titres clairs (##) et des listes à puces.
        
        Données:
        {$projectSummary}
        {$missionSummary}
        {$delayedSummary}
        PROMPT;
    }

    public function prepareProjectsData(array $projects): array
    {
        $projectsByStatus = [
            Project::STATUS_ACTIVE => 0,
            Project::STATUS_INACTIVE => 0,
            Project::STATUS_COMPLETED => 0,
            Project::STATUS_DELAYED => 0
        ];
        
        foreach ($projects as $project) {
            $status = $project->getStatus();
            if (isset($projectsByStatus[$status])) {
                $projectsByStatus[$status]++;
            }
        }
        
        return $projectsByStatus;
    }

    public function prepareMissionsData(array $missions): array
    {
        $missionsByStatus = [
            'To Do' => 0,
            'In Progress' => 0,
            'Done' => 0,
            'Late' => 0
        ];
        
        $now = new \DateTime();
        
        foreach ($missions as $mission) {
            $status = $mission->getStatus();
            
            if (isset($missionsByStatus[$status])) {
                $missionsByStatus[$status]++;
            }
            
            $dueDate = $mission->getDateTerminer();
            if ($dueDate && $dueDate < $now && $status !== 'Done') {
                $missionsByStatus['Late']++;
            }
        }
        
        return $missionsByStatus;
    }

    private function generateCacheKey(array $projects, array $missions): string
    {
        return md5('project_analysis_'.json_encode($projects).'_'.json_encode($missions));
    }
}