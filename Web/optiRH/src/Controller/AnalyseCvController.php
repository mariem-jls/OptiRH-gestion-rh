<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Entity\Demande;
use App\Service\MatchingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class AnalyseCvController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    #[Route('/analyse-cv', name: 'admin_analyse_cv', methods: ['GET'])]
    public function index(): Response
    {
        $this->logger->debug('Accessing Analyse CV page');

        $offres = $this->entityManager->getRepository(Offre::class)
            ->findBy(['statut' => 'Active'], ['poste' => 'ASC']);

        return $this->render('matching/analyse_cv.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/analyse-cv/demandes/{offreId}', name: 'admin_analyse_cv_demandes', methods: ['GET'])]
    public function getDemandes(int $offreId, Request $request): JsonResponse
    {
        $this->logger->debug('Fetching demandes for offre', ['offreId' => $offreId]);

        $offre = $this->entityManager->getRepository(Offre::class)->find($offreId);
        if (!$offre) {
            $this->logger->error('Offre not found', ['offreId' => $offreId]);
            return new JsonResponse(['error' => 'Offre non trouvée'], 404);
        }

        $demandes = $offre->getDemandes()->toArray();
        $demandeData = array_map(function (Demande $demande) {
            return [
                'id' => $demande->getId(),
                'nomComplet' => $demande->getNomComplet() ?? 'Non spécifié',
                'email' => $demande->getEmail() ?? 'Non spécifié',
                'fichierPieceJointe' => $demande->getFichierPieceJointe() ?? 'Aucun CV',
            ];
        }, $demandes);

        $this->logger->debug('Fetched demande IDs', ['ids' => array_column($demandeData, 'id'), 'offreId' => $offreId]);
        return new JsonResponse(['demandes' => $demandeData]);
    }

    #[Route('/analyse-cv/results/{offreId}', name: 'admin_analyse_cv_results', methods: ['GET'])]
    public function getResults(int $offreId, MatchingService $matchingService): JsonResponse
    {
        $this->logger->debug('Fetching analysis results for offre', ['offreId' => $offreId]);

        $offre = $this->entityManager->getRepository(Offre::class)->find($offreId);
        if (!$offre) {
            $this->logger->error('Offre not found', ['offreId' => $offreId]);
            return new JsonResponse(['error' => 'Offre non trouvée'], 404);
        }

        $demandes = $offre->getDemandes()->toArray();
        if (empty($demandes)) {
            return new JsonResponse(['results' => [], 'nbPostes' => 0]);
        }

        try {
            $results = [];
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/Uploads/';
            foreach ($demandes as $demande) {
                $cvFilePath = $demande->getFichierPieceJointe() && !str_contains($demande->getFichierPieceJointe(), 'Aucun CV')
                    ? $uploadsDir . $demande->getFichierPieceJointe()
                    : null;

                if ($cvFilePath && file_exists($cvFilePath)) {
                    $demandeMatching = $matchingService->calculateMatchingScore($demande, $offre, $cvFilePath);
                    $results[] = [
                        'demandeId' => $demandeMatching->getDemande()->getId(),
                        'score' => $demandeMatching->getMatchingScore() / 100,
                    ];
                } else {
                    $this->logger->warning('CV file not found for demande', ['demandeId' => $demande->getId()]);
                    $results[] = [
                        'demandeId' => $demande->getId(),
                        'score' => 0.0,
                    ];
                }
            }

            // Sort results by score in descending order
            usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

            return new JsonResponse([
                'results' => $results,
                'nbPostes' => $offre->getNbPostes() ?? 1, // Default to 1 if nbPostes is null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error during analysis', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Erreur lors de l\'analyse: ' . $e->getMessage()], 500);
        }
    }
}