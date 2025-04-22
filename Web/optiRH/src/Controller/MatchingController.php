<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Entity\DemandeMatching;
use App\Service\MatchingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class MatchingController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/matching/{offreId}', name: 'matching', methods: ['GET'])]
    public function match(
        int $offreId,
        MatchingService $matchingService,
        EntityManagerInterface $entityManager
    ): Response {
        $this->logger->debug('Starting matching process', ['offreId' => $offreId]);

        $offre = $entityManager->getRepository(Offre::class)->find($offreId);
        if (!$offre) {
            $this->logger->error('Offre not found', ['offreId' => $offreId]);
            throw $this->createNotFoundException('Offre non trouvée');
        }

        $demandes = $offre->getDemandes();
        $matchingResults = [];

        foreach ($demandes as $demande) {
            $cvPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $demande->getFichierPieceJointe();
            if (!file_exists($cvPath)) {
                $this->logger->warning('CV file not found', [
                    'demandeId' => $demande->getId(),
                    'cvPath' => $cvPath
                ]);
                continue;
            }

            try {
                $demandeMatching = $matchingService->calculateMatchingScore($demande, $offre, $cvPath);
                $matchingResults[] = $demandeMatching;
                $this->logger->info('Matching completed', [
                    'demandeId' => $demande->getId(),
                    'score' => $demandeMatching->getMatchingScore()
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Matching failed for demande', [
                    'demandeId' => $demande->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->render('matching/matching.html.twig', [
            'offre' => $offre,
            'matchingResults' => $matchingResults,
        ]);
    }
}