<?php

namespace App\Service;

use App\Entity\Demande;
use App\Entity\Offre;
use App\Entity\DemandeMatching;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MatchingService
{
    private PdfParserService $pdfParserService;
    private EmbeddingService $embeddingService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        PdfParserService $pdfParserService,
        EmbeddingService $embeddingService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->pdfParserService = $pdfParserService;
        $this->embeddingService = $embeddingService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function calculateMatchingScore(Demande $demande, Offre $offre, string $cvFilePath): DemandeMatching
    {
        $this->logger->debug('Calculating matching score', [
            'demandeId' => $demande->getId(),
            'offreId' => $offre->getId(),
            'cvPath' => $cvFilePath
        ]);

        $demandeMatching = $this->entityManager->getRepository(DemandeMatching::class)
            ->findByDemandeAndOffre($demande->getId(), $offre->getId());

        if (!$demandeMatching) {
            $demandeMatching = new DemandeMatching();
            $demandeMatching->setDemande($demande);
            $demandeMatching->setOffre($offre);
        }

        try {
            $cvText = $this->pdfParserService->extract($cvFilePath);
            $this->logger->debug('Extracted CV text', ['text' => substr($cvText, 0, 100) . '...']);
        } catch (\Exception $e) {
            $this->logger->error('CV extraction failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur lors de l\'extraction du CV : ' . $e->getMessage());
        }

        if (!$demandeMatching->getCvEmbedding()) {
            try {
                $cvEmbedding = $this->embeddingService->generateEmbedding($cvText);
                $demandeMatching->setCvEmbedding($cvEmbedding);
                $this->logger->debug('CV embedding', ['first_10' => array_slice($cvEmbedding, 0, 10)]);
            } catch (\Exception $e) {
                $this->logger->error('CV embedding generation failed', ['error' => $e->getMessage()]);
                throw new \RuntimeException('Erreur lors de la génération de l\'embedding CV : ' . $e->getMessage());
            }
        }

        $offerText = $offre->getDescription() . ' ' . $offre->getPoste();
        if (!$demandeMatching->getOffreEmbedding()) {
            try {
                $offerEmbedding = $this->embeddingService->generateEmbedding($offerText);
                $demandeMatching->setOffreEmbedding($offerEmbedding);
                $this->logger->debug('Offer embedding', ['first_10' => array_slice($offerEmbedding, 0, 10)]);
            } catch (\Exception $e) {
                $this->logger->error('Offer embedding generation failed', ['error' => $e->getMessage()]);
                throw new \RuntimeException('Erreur lors de la génération de l\'embedding offre : ' . $e->getMessage());
            }
        }

        $rawScore = $this->cosineSimilarity($demandeMatching->getCvEmbedding(), $demandeMatching->getOffreEmbedding());
        $this->logger->debug('Raw cosine similarity', ['score' => $rawScore]);

        // Non-linear scaling to spread scores
        $percentage = $this->scaleScore($rawScore);
        $demandeMatching->setMatchingScore($percentage);

        $this->logger->info('Matching score calculated', [
            'demandeId' => $demande->getId(),
            'offreId' => $offre->getId(),
            'percentage' => $percentage
        ]);

        $this->entityManager->persist($demandeMatching);
        $this->entityManager->flush();

        return $demandeMatching;
    }

    private function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($vectorA as $i => $valueA) {
            $valueB = $vectorB[$i] ?? 0;
            $dotProduct += $valueA * $valueB;
            $normA += $valueA * $valueA;
            $normB += $valueB * $valueB;
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            $this->logger->warning('Zero norm detected in cosine similarity', [
                'normA' => $normA,
                'normB' => $normB
            ]);
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    private function scaleScore(float $rawScore): float
    {
        // Map cosine similarity (-1 to 1) to 0-100% with non-linear scaling
        $normalized = ($rawScore + 1) / 2; // 0 to 1
        // Square to amplify high scores and penalize low ones
        $adjusted = pow($normalized, 2);
        return $adjusted * 100; // 0 to 100%
    }
}