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
    private const MODEL_NAME = 'gemini-2.0-flash';
    private const CACHE_TTL = 1800; // 30 minutes
    private const REQUEST_TIMEOUT = 30; // Seconds
    private const MAX_RETRIES = 2;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private string $apiKey
    ) {}

    /**
     * Génère une réponse de chatbot pour une question basée sur les données d'une mission.
     *
     * @param string $question La question posée
     * @param array $missionData Données de la mission
     * @return string Réponse générée ou message d'erreur formaté
     */
    public function generateMissionChatbotResponse(string $question, array $missionData): string
    {
        $cacheKey = $this->generateChatbotCacheKey($question, $missionData);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($question, $missionData) {
            $item->expiresAfter(self::CACHE_TTL);

            $retryCount = 0;
            $lastException = null;

            while ($retryCount <= self::MAX_RETRIES) {
                try {
                    $response = $this->httpClient->request(
                        'POST',
                        $this->buildApiUrl(),
                        $this->buildChatbotRequestOptions($question, $missionData)
                    );

                    $content = $response->getContent(false);
                    $this->logger->debug('Gemini API chatbot response', ['response' => substr($content, 0, 300)]);

                    $data = json_decode($content, true);
                    $this->validateResponse($response, $data);

                    return $this->extractContent($data);
                } catch (\Exception $e) {
                    $lastException = $e;
                    $this->logger->error('Gemini API chatbot attempt failed', [
                        'attempt' => $retryCount + 1,
                        'error' => $e->getMessage(),
                    ]);

                    $retryCount++;
                    if ($retryCount <= self::MAX_RETRIES) {
                        sleep($retryCount); // Backoff exponentiel
                    }
                }
            }

            return $this->handleFinalError($lastException);
        });
    }

    /**
     * Génère une analyse détaillée des projets et missions.
     *
     * @param array $projects Liste des projets
     * @param array $missions Liste des missions
     * @return string Analyse formatée ou message d'erreur
     */
    public function generateProjectAnalysis(array $projects, array $missions): string
    {
        $cacheKey = $this->generateCacheKey($projects, $missions);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($projects, $missions) {
            $item->expiresAfter(self::CACHE_TTL);

            $retryCount = 0;
            $lastException = null;

            while ($retryCount <= self::MAX_RETRIES) {
                try {
                    $response = $this->httpClient->request(
                        'POST',
                        $this->buildApiUrl(),
                        $this->buildRequestOptions($projects, $missions)
                    );

                    $content = $response->getContent(false);
                    $this->logger->debug('Gemini API raw response', ['response' => substr($content, 0, 300)]);

                    $data = json_decode($content, true);
                    $this->validateResponse($response, $data);

                    return $this->formatApiResponse($this->extractContent($data));
                } catch (\Exception $e) {
                    $lastException = $e;
                    $this->logger->error('Gemini API attempt failed', [
                        'attempt' => $retryCount + 1,
                        'error' => $e->getMessage(),
                    ]);

                    $retryCount++;
                    if ($retryCount <= self::MAX_RETRIES) {
                        sleep($retryCount);
                    }
                }
            }

            return $this->handleFinalError($lastException);
        });
    }

    /**
     * Prépare les données des projets pour l'analyse.
     *
     * @param array $projects Liste des projets
     * @return array Projets regroupés par statut
     */
    public function prepareProjectsData(array $projects): array
    {
        $projectsByStatus = [
            Project::STATUS_ACTIVE => 0,
            Project::STATUS_INACTIVE => 0,
            Project::STATUS_COMPLETED => 0,
            Project::STATUS_DELAYED => 0,
        ];

        foreach ($projects as $project) {
            $status = $project->getStatus();
            if (isset($projectsByStatus[$status])) {
                $projectsByStatus[$status]++;
            }
        }

        return $projectsByStatus;
    }

    /**
     * Prépare les données des missions pour l'analyse.
     *
     * @param array $missions Liste des missions
     * @return array Missions regroupées par statut
     */
    public function prepareMissionsData(array $missions): array
    {
        $missionsByStatus = [
            'To Do' => 0,
            'In Progress' => 0,
            'Done' => 0,
            'Late' => 0,
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

    /**
     * Construit l'URL de l'API Gemini.
     *
     * @return string URL complète avec clé API
     */
    private function buildApiUrl(): string
    {
        return sprintf(
            '%s/%s/models/%s:generateContent?key=%s',
            self::BASE_URL,
            self::API_VERSION,
            self::MODEL_NAME,
            $this->apiKey
        );
    }

    /**
     * Prépare les options de requête pour l'analyse de projets/missions.
     *
     * @param array $projects Données des projets
     * @param array $missions Données des missions
     * @return array Options de requête HTTP
     */
    private function buildRequestOptions(array $projects, array $missions): array
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    'parts' => [
                        ['text' => $this->buildAdminPrompt($projects, $missions)],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048,
                    'topP' => 0.95,
                    'topK' => 40,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                ],
            ],
            'timeout' => self::REQUEST_TIMEOUT,
        ];
    }

    /**
     * Prépare les options de requête pour le chatbot.
     *
     * @param string $question La question posée
     * @param array $missionData Données de la mission
     * @return array Options de requête HTTP
     */
    private function buildChatbotRequestOptions(string $question, array $missionData): array
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    'parts' => [
                        ['text' => $this->buildChatbotPrompt($question, $missionData)],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 500,
                    'topP' => 0.95,
                    'topK' => 40,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                ],
            ],
            'timeout' => self::REQUEST_TIMEOUT,
        ];
    }

    /**
     * Construit le prompt pour le chatbot.
     *
     * @param string $question La question posée
     * @param array $missionData Données de la mission
     * @return string Prompt formaté
     */
    private function buildChatbotPrompt(string $question, array $missionData): string
    {
        $missionSummary = sprintf(
            "Mission ID: %s\nTitre: %s\nDescription: %s\nStatut: %s\nDate d'échéance: %s\nProjet associé: %s\nRetard: %s",
            $missionData['id'] ?? 'N/A',
            $missionData['title'] ?? 'N/A',
            $missionData['description'] ?? 'Aucune description',
            $missionData['statut'] ?? 'Non spécifié',
            isset($missionData['start']) ? date('d/m/Y', strtotime($missionData['start'])) : 'N/A',
            $missionData['projectTitle'] ?? 'Aucun projet associé',
            $missionData['isLate'] ? 'Oui' : 'Non'
        );

        return <<<PROMPT
Vous êtes un assistant de gestion de projet. Répondez à la question suivante en français, en vous basant sur les données de la mission ci-dessous. Fournissez une réponse claire, concise et professionnelle, limitée à 2-3 phrases. Si la question ne peut pas être répondue avec les données fournies, dites-le poliment.

**Question**: {$question}

**Données de la mission**:
{$missionSummary}
PROMPT;
    }

    /**
     * Construit le prompt pour l'analyse administrative.
     *
     * @param array $projectsData Données des projets
     * @param array $missionsData Données des missions
     * @return string Prompt formaté
     */
    private function buildAdminPrompt(array $projectsData, array $missionsData): string
    {
        $totalProjects = array_sum($projectsData);
        $delayedProjects = $projectsData[Project::STATUS_DELAYED] ?? 0;
        $completedProjects = $projectsData[Project::STATUS_COMPLETED] ?? 0;
        $inProgressProjects = $projectsData[Project::STATUS_ACTIVE] ?? 0;

        $projectCompletionRate = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 1) : 0;
        $projectDelayRate = $totalProjects > 0 ? round(($delayedProjects / $totalProjects) * 100, 1) : 0;

        $totalMissions = array_sum($missionsData);
        $delayedMissions = $missionsData['Late'] ?? 0;
        $completedMissions = $missionsData['Done'] ?? 0;
        $inProgressMissions = $missionsData['In Progress'] ?? 0;

        $missionCompletionRate = $totalMissions > 0 ? round(($completedMissions / $totalMissions) * 100, 1) : 0;
        $missionDelayRate = $totalMissions > 0 ? round(($delayedMissions / $totalMissions) * 100, 1) : 0;

        $missionsPerProject = $totalProjects > 0 ? round($totalMissions / $totalProjects, 1) : 0;
        $combinedDelayRate = round(($projectDelayRate + $missionDelayRate) / 2, 1);
        $inProgressProjectsPercent = $totalProjects > 0 ? round(($inProgressProjects / $totalProjects) * 100, 1) : 0;
        $inProgressMissionsPercent = $totalMissions > 0 ? round(($inProgressMissions / $totalMissions) * 100, 1) : 0;

        $projectSummary = "## Détail des Projets (Total: {$totalProjects})\n";
        foreach ($projectsData as $status => $count) {
            $percentage = $totalProjects > 0 ? round(($count / $totalProjects) * 100, 1) : 0;
            $projectSummary .= sprintf("- %s: %d (%.1f%%)\n", $status, $count, $percentage);
        }
        $projectSummary .= "- Taux d'achèvement: {$projectCompletionRate}%\n";
        $projectSummary .= "- Taux de retard: {$projectDelayRate}%\n";

        $missionSummary = "## Détail des Missions (Total: {$totalMissions})\n";
        foreach ($missionsData as $status => $count) {
            $percentage = $totalMissions > 0 ? round(($count / $totalMissions) * 100, 1) : 0;
            $missionSummary .= sprintf("- %s: %d (%.1f%%)\n", $status, $count, $percentage);
        }
        $missionSummary .= "- Taux d'achèvement: {$missionCompletionRate}%\n";
        $missionSummary .= "- Taux de retard: {$missionDelayRate}%\n";

        return <<<PROMPT
En tant qu'expert en gestion de projet, produisez un rapport analytique détaillé en français pour un tableau de bord administratif. Le rapport doit être structuré, clair et limité à 50 lignes, avec un ton professionnel et orienté action.

## Analyse Globale
- **Portefeuille actuel**: {$totalProjects} projets et {$totalMissions} missions (ratio: {$missionsPerProject} missions/projet)
- **Performance globale**: 
  - Projets achevés: {$completedProjects} ({$projectCompletionRate}%)
  - Missions achevées: {$completedMissions} ({$missionCompletionRate}%)
- **Retards**: 
  - {$delayedProjects} projets en retard ({$projectDelayRate}% du total)
  - {$delayedMissions} missions en retard ({$missionDelayRate}% du total)

## Analyse Comparative
1. **Progression**:
   - {$inProgressProjects} projets en cours ({$inProgressProjectsPercent}%)
   - {$inProgressMissions} missions en cours ({$inProgressMissionsPercent}%)
2. **Efficacité opérationnelle**:
   - Ratio missions/projets: {$missionsPerProject}
   - Taux de retard combiné: {$combinedDelayRate}%

## Points Critiques
- **Problèmes immédiats**:
  - Top 3 des statuts les plus problématiques
  - Projets bloqués ou nécessitant une attention urgente
- **Risques potentiels**:
  - Analyse des causes racines des retards
  - Impact sur les délais globaux

## Recommandations Stratégiques
1. **Actions prioritaires**:
   - Identifier les {$delayedProjects} projets en retard et établir un plan de rattrapage
   - Réallocation des ressources pour les missions critiques
   - Mettre en place des revues hebdomadaires pour les projets à risque
2. **Améliorations processus**:
   - Optimiser le suivi des dépendances entre projets/missions
   - Automatiser le reporting des retards
   - Former les équipes sur la gestion des risques

## Perspectives à 6 mois
- **Projections**:
  - Tendance d'achèvement actuelle: {$projectCompletionRate}% (objectif: 85%)
  - Estimation des retards futurs basée sur les tendances actuelles
- **Opportunités**:
  - Analyse des meilleures pratiques des projets réussis
  - Potentiel d'automatisation des missions répétitives

Données complètes:
{$projectSummary}
{$missionSummary}

Format attendu:
- Structure claire avec titres ## et sous-titres ###
- Listes à puces pour les points clés
- Chiffres clés en évidence
- Maximum 50 lignes au total
PROMPT;
    }

    /**
     * Valide la réponse de l'API.
     *
     * @param mixed $response Réponse HTTP
     * @param array $data Données décodées
     * @throws \RuntimeException Si la réponse est invalide
     */
    private function validateResponse($response, array $data): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf(
                'API returned HTTP %d: %s',
                $response->getStatusCode(),
                $data['error']['message'] ?? 'No error details'
            ));
        }

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $this->logger->error('Invalid API response structure', ['response' => $data]);
            throw new \RuntimeException('Invalid response structure from Gemini API');
        }
    }

    /**
     * Extrait le contenu texte de la réponse API.
     *
     * @param array $response Réponse décodée
     * @return string Contenu texte
     */
    private function extractContent(array $response): string
    {
        return $response['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Formate la réponse API pour l'affichage.
     *
     * @param string $content Contenu brut
     * @return string Contenu formaté en HTML
     */
    private function formatApiResponse(string $content): string
    {
        $content = nl2br(htmlspecialchars($content));
        $replacements = [
            '## ' => '</div><div class="analysis-section"><h3>',
            "\n- " => '</li><li class="analysis-item">',
            "\n* " => '</li><li class="analysis-item">',
            "\n" => '<br>',
        ];

        $formatted = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );

        return '<div class="gemini-analysis-container">'
            . '<div class="analysis-section"><h3>Analyse des projets</h3>'
            . $formatted
            . '</div></div>';
    }

    /**
     * Gère les erreurs finales après épuisement des tentatives.
     *
     * @param \Exception $e Exception capturée
     * @return string Message d'erreur formaté
     */
    private function handleFinalError(\Exception $e): string
    {
        $this->logger->critical('Gemini API final failure', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->formatErrorMessage($e);
    }

    /**
     * Formate un message d'erreur pour l'affichage.
     *
     * @param \Exception $e Exception capturée
     * @return string Message d'erreur en HTML
     */
    private function formatErrorMessage(\Exception $e): string
    {
        $errorCode = $e instanceof \Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface
            ? $e->getCode()
            : 500;

        $errorMap = [
            429 => 'Service surchargé - Veuillez réessayer plus tard',
            500 => 'Erreur interne du service',
            503 => 'Service temporairement indisponible',
        ];

        $message = $errorMap[$errorCode] ?? 'Erreur lors de la génération de l\'analyse';

        return '<div class="analysis-error alert alert-danger">'
            . '<h4>⚠️ ' . htmlspecialchars($message) . '</h4>'
            . '<p>Détails techniques : ' . htmlspecialchars($e->getMessage()) . '</p>'
            . '<small>Code erreur : ' . $errorCode . '</small>'
            . '</div>';
    }

    /**
     * Génère une clé de cache pour le chatbot.
     *
     * @param string $question La question posée
     * @param array $missionData Données de la mission
     * @return string Clé de cache unique
     */
    private function generateChatbotCacheKey(string $question, array $missionData): string
    {
        return md5(sprintf('chatbot_%s_%s', $question, json_encode($missionData)));
    }

    /**
     * Génère une clé de cache pour l'analyse.
     *
     * @param array $projects Liste des projets
     * @param array $missions Liste des missions
     * @return string Clé de cache unique
     */
    private function generateCacheKey(array $projects, array $missions): string
    {
        $projectHash = md5(json_encode($projects));
        $missionHash = md5(json_encode($missions));
        return sprintf('gemini_analysis_%s_%s', $projectHash, $missionHash);
    }
}