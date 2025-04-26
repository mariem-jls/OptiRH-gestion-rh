<?php
// src/Service/TranslationService.php

namespace App\Service;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

class TranslationService
{
    private $logger;
    private $defaultTargetLanguage;
    private $client;
    
    public function __construct(LoggerInterface $logger, string $defaultTargetLanguage = 'fr')
    {
        $this->logger = $logger;
        $this->defaultTargetLanguage = $defaultTargetLanguage;
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false
        ]);
    }
    
// In TranslationService.php - modify the translate method

public function translate(string $text, string $targetLang = 'fr', string $sourceLang = 'auto'): string
{
    if (empty(trim($text))) {
        return '';
    }
    
    // Add retry mechanism
    $maxRetries = 2;
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            // Add slight delay between retries
            if ($attempt > 0) {
                sleep(1);
            }
            
            $url = 'https://translate.terraprint.co/translate';
            
            $response = $this->client->post($url, [
                'json' => [
                    'q' => $text,
                    'source' => $sourceLang === 'auto' ? 'auto' : $sourceLang,
                    'target' => $targetLang
                ],
                'timeout' => 5 // Reduced timeout
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['translatedText'])) {
                $this->logger->info('Traduction réussie via Libre Translate', [
                    'source' => $sourceLang,
                    'target' => $targetLang
                ]);
                
                return $result['translatedText'];
            }
            
            throw new \Exception('Réponse de traduction invalide');
            
        } catch (\Exception $e) {
            $attempt++;
            $this->logger->warning("Erreur de traduction (tentative {$attempt}/{$maxRetries}): " . $e->getMessage());
            
            // If we've exhausted all retries, try the fallback
            if ($attempt >= $maxRetries) {
                try {
                    return $this->fallbackTranslate($text, $targetLang, $sourceLang);
                } catch (\Exception $fallbackEx) {
                    $this->logger->error('Échec du service de secours: ' . $fallbackEx->getMessage());
                    return "[Traduction échouée] " . $text;
                }
            }
        }
    }
    
    // Should never reach here, but just in case
    return "[Traduction échouée] " . $text;
}
                

    
    private function fallbackTranslate(string $text, string $targetLang, string $sourceLang): string
    {
        // Si le texte est court, utilisons une solution simple basée sur un dictionnaire
        if ($sourceLang === 'fr' && $targetLang === 'en') {
            $translations = [
                'je suis' => 'I am',
                'bonjour' => 'hello',
                'merci' => 'thank you',
                'au revoir' => 'goodbye'
                // Ajouter d'autres traductions courantes
            ];
            
            // Vérifier si nous avons une traduction exacte
            if (isset($translations[strtolower($text)])) {
                return $translations[strtolower($text)];
            }
        }
        
        // Utiliser un autre service de traduction comme backup
        $url = 'https://api.mymemory.translated.net/get';
        $response = $this->client->get($url, [
            'query' => [
                'q' => $text,
                'langpair' => ($sourceLang === 'auto' ? 'fr' : $sourceLang) . '|' . $targetLang
            ]
        ]);
        
        $result = json_decode($response->getBody()->getContents(), true);
        
        if (isset($result['responseData']['translatedText'])) {
            return $result['responseData']['translatedText'];
        }
        
        throw new \Exception('Aucune traduction disponible');
    }
    
    public function getAvailableLanguages(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'Anglais',
            'es' => 'Espagnol',
            'ar' => 'Arabe',
            'de' => 'Allemand',
            'it' => 'Italien',
            'zh' => 'Chinois',
            'ru' => 'Russe',
            'pt' => 'Portugais',
            'ja' => 'Japonais'
        ];
    }
}