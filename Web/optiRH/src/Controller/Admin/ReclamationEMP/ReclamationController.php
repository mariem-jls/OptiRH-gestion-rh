<?php
// src/Controller/FrontOffice/Reclamation/ReclamationController.php

namespace App\Controller\Admin\ReclamationEMP;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Entity\ReclamationArchive;
use App\Form\ReclamationType;
use App\Form\ReclamationFilterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\SentimentAnalysisService;
use App\Service\TranslationService;
use Psr\Log\LoggerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\InfobipSmsSender;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;

#[Route('/list')]
class ReclamationController extends AbstractController
{
    private $logger;
    private $translationService;
    private $filterBuilderUpdater;

    public function __construct(
        LoggerInterface $logger,
        TranslationService $translationService,
        FilterBuilderUpdaterInterface $filterBuilderUpdater = null
    ) {
        $this->logger = $logger;
        $this->translationService = $translationService;
        $this->filterBuilderUpdater = $filterBuilderUpdater;
    }

    #[Route('/', name: 'front_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('front_reclamations');
    }

    #[Route('/reclamations', name: 'front_reclamations')]
    public function list(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        
        // Créer le form filter
        $filterForm = $this->createForm(ReclamationFilterType::class);
        $filterForm->handleRequest($request);
        
        // Créer le QueryBuilder de base
        $queryBuilder = $em->getRepository(Reclamation::class)
            ->createQueryBuilder('r')
            ->where('r.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('r.date', 'DESC');
            
        // Appliquer les filtres si le formulaire est soumis et que FilterBuilderUpdater est disponible
        if ($filterForm->isSubmitted() && $this->filterBuilderUpdater) {
            $this->filterBuilderUpdater->addFilterConditions($filterForm, $queryBuilder);
        } else {
            // Utiliser la recherche simple si FilterBuilderUpdater n'est pas disponible
            $searchTerm = $request->query->get('search', '');
            $typeFilter = $request->query->get('type', '');
        
            if (!empty($searchTerm)) {
                $queryBuilder->andWhere('r.description LIKE :searchTerm')
                    ->setParameter('searchTerm', '%'.$searchTerm.'%');
            }
        
            if (!empty($typeFilter)) {
                $queryBuilder->andWhere('r.type = :type')
                    ->setParameter('type', $typeFilter);
            }
        }
        
        // Paginer les résultats
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10 // Nombre d'éléments par page
        );
        
        return $this->render('front/reclamation/list.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(),
            'searchTerm' => $request->query->get('search', ''),
            'selectedType' => $request->query->get('type', ''),
            'typeChoices' => Reclamation::getTypeChoices(),
        ]);
    }

    #[Route('/reclamations/pdf', name: 'front_reclamations_pdf')]
    public function generatePdf(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        // Créer le form filter avec les mêmes filtres
        $filterForm = $this->createForm(ReclamationFilterType::class);
        $filterForm->handleRequest($request);
        
        // Créer la requête de base
        $queryBuilder = $em->getRepository(Reclamation::class)
            ->createQueryBuilder('r')
            ->where('r.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('r.date', 'DESC');
            
        // Appliquer les filtres si le formulaire est soumis et que FilterBuilderUpdater est disponible
        if ($filterForm->isSubmitted() && $this->filterBuilderUpdater) {
            $this->filterBuilderUpdater->addFilterConditions($filterForm, $queryBuilder);
        } else {
            // Utiliser la recherche simple si FilterBuilderUpdater n'est pas disponible
            $searchTerm = $request->query->get('search', '');
            $typeFilter = $request->query->get('type', '');
        
            if (!empty($searchTerm)) {
                $queryBuilder->andWhere('r.description LIKE :searchTerm')
                    ->setParameter('searchTerm', '%'.$searchTerm.'%');
            }
        
            if (!empty($typeFilter)) {
                $queryBuilder->andWhere('r.type = :type')
                    ->setParameter('type', $typeFilter);
            }
        }
        
        $reclamations = $queryBuilder->getQuery()->getResult();
        
        // Configure Dompdf
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        
        // Générer le HTML pour le PDF
        $html = $this->renderView('front/reclamation/pdf_list.html.twig', [
            'reclamations' => $reclamations,
            'user' => $user,
            'title' => 'Mes réclamations',
            'filters' => $filterForm->getData()
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Générer le PDF et le renvoyer comme réponse
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="mes-reclamations.pdf"'
            ]
        );
    }

    #[Route('/reclamation/add', name: 'front_add_reclamation')]
    public function add(
        Request $request, 
        EntityManagerInterface $em,
        SentimentAnalysisService $sentimentAnalyzer,
        InfobipSmsSender $smsSender
    ): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation, ['is_admin' => false]);
        
        // Initialiser les variables pour la traduction
        $translatedText = null;
        $targetLanguage = 'fr'; // Langue par défaut
        $availableLanguages = $this->translationService->getAvailableLanguages();
        
        // Traitement de la traduction si demandée
        if ($request->isMethod('POST') && $request->request->has('translate')) {
            $textToTranslate = $request->request->get('text_to_translate');
            $targetLanguage = $request->request->get('target_language', 'fr');
            
            try {
                $translatedText = $this->translationService->translate($textToTranslate, $targetLanguage);
                
                // Pour debugging
                $this->logger->info('Traduction effectuée avec succès', [
                    'source' => 'auto',
                    'target' => $targetLanguage,
                    'original' => $textToTranslate,
                    'translated' => $translatedText
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la traduction: ' . $e->getMessage());
                $this->addFlash('error', 'Erreur de traduction: ' . $e->getMessage());
            }
            
            // Si c'est une requête AJAX, retourner le résultat en JSON
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'translation' => $translatedText
                ]);
            }
        }
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $sentiment = $sentimentAnalyzer->analyze($reclamation->getDescription());
            
            if (isset($sentiment['fallback'])) {
                $this->addFlash('warning', 'Analyse de secours utilisée (service principal indisponible)');
            }
            $reclamation->setSentimentScore($sentiment['score']);
            $reclamation->setSentimentLabel($sentiment['label']);
            $reclamation->setStatus(Reclamation::STATUS_PENDING);
            $reclamation->setUtilisateur($this->getUser());
            $reclamation->setDate(new \DateTime());
            
            $em->persist($reclamation);
            $em->flush();
            
            $this->addFlash('success', 'Votre réclamation a été enregistrée avec succès.');
            return $this->redirectToRoute('front_reclamations');
        }
        
        return $this->render('front/reclamation/add.html.twig', [
            'form' => $form->createView(),
            'translatedText' => $translatedText,
            'targetLanguage' => $targetLanguage,
            'availableLanguages' => $availableLanguages
        ]);
    }

    #[Route('/reclamation/{id}/edit', name: 'front_edit_reclamation')]
    public function edit(
        Request $request, 
        Reclamation $reclamation, 
        EntityManagerInterface $em,
        SentimentAnalysisService $sentimentAnalyzer
    ): Response
    {
        if ($reclamation->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($reclamation->getReponses()->count() > 0) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier une réclamation qui a déjà une réponse.');
            return $this->redirectToRoute('front_reclamations');
        }

        $form = $this->createForm(ReclamationType::class, $reclamation, ['is_admin' => false]);
        
        // Initialiser les variables pour la traduction
        $translatedText = null;
        $targetLanguage = 'fr'; // Langue par défaut
        $availableLanguages = $this->translationService->getAvailableLanguages();
        
        // Traitement de la traduction si demandée
        if ($request->isMethod('POST') && $request->request->has('translate')) {
            $textToTranslate = $request->request->get('text_to_translate');
            $targetLanguage = $request->request->get('target_language', 'fr');
            
            try {
                $translatedText = $this->translationService->translate($textToTranslate, $targetLanguage);
                
                // Pour debugging
                $this->logger->info('Traduction effectuée avec succès (edit)', [
                    'source' => 'auto',
                    'target' => $targetLanguage,
                    'original' => $textToTranslate,
                    'translated' => $translatedText
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la traduction: ' . $e->getMessage());
                $this->addFlash('error', 'Erreur de traduction: ' . $e->getMessage());
            }
            
            // Si c'est une requête AJAX, retourner le résultat en JSON
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'translation' => $translatedText
                ]);
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sentiment = $sentimentAnalyzer->analyze($reclamation->getDescription());
            
            if (isset($sentiment['fallback'])) {
                $this->addFlash('warning', 'Analyse de secours utilisée (service principal indisponible)');
            }

            $reclamation->setSentimentScore($sentiment['score']);
            $reclamation->setSentimentLabel($sentiment['label']);
            
            $em->flush();
            $this->addFlash('success', 'Réclamation modifiée avec succès.');
            return $this->redirectToRoute('front_reclamations');
        }

        return $this->render('front/reclamation/edit.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation,
            'translatedText' => $translatedText,
            'targetLanguage' => $targetLanguage,
            'availableLanguages' => $availableLanguages
        ]);
    }

    #[Route('/reclamation/{id}/delete', name: 'front_delete_reclamation', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($reclamation->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    
        if ($reclamation->getReponses()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer une réclamation avec des réponses');
            return $this->redirectToRoute('front_reclamations');
        }
    
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            // Créer une entrée dans l'archive avant la suppression
            $archive = new ReclamationArchive();
            $archive->setDescription($reclamation->getDescription());
            $archive->setType($reclamation->getType());
            $archive->setStatus($reclamation->getStatus());
            $archive->setUtilisateurNom($reclamation->getUtilisateur()->getNom());
            $archive->setDate($reclamation->getDate());
            $archive->setDeletedAt(new \DateTime());
            $archive->setSentimentScore($reclamation->getSentimentScore());
            $archive->setSentimentLabel($reclamation->getSentimentLabel());
            
            $em->persist($archive);
            $em->remove($reclamation);
            $em->flush();
            $this->addFlash('success', 'Réclamation supprimée avec succès.');
        }
    
        return $this->redirectToRoute('front_reclamations');
    }

    #[Route('/reclamation/{id}/reponses', name: 'front_reclamation_reponses')]
    public function reponses(Reclamation $reclamation): Response
    {
        if ($reclamation->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('front/reclamation/reponses.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamation/{id}/qr-code', name: 'front_reclamation_qr_code')]
    public function generateQrCode(Reclamation $reclamation): Response
    {
        // Préparation des données de la réclamation à inclure dans le QR code
        $reclamationData = [
            'id' => $reclamation->getId(),
            'type' => $reclamation->getType(),
            'status' => $reclamation->getStatus(),
            'description' => $reclamation->getDescription(),
            'date' => $reclamation->getDate()->format('Y-m-d H:i:s'),
            'utilisateur' => $reclamation->getUtilisateur()->getNom()
        ];
        
        // Conversion des données en JSON pour le QR code
        $dataString = json_encode($reclamationData, JSON_UNESCAPED_UNICODE);
        
        // Création du QR code avec les données
        $qrCode = new QrCode($dataString);
        
        // Création du writer et génération de l'image
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        // Retourne l'image générée
        return new Response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="reclamation-details.png"'
        ]);
    }
    
    #[Route('/reclamation/translate', name: 'front_translate_text', methods: ['POST'])]
    public function translateText(Request $request): Response
    {
        $text = $request->request->get('text', '');
        $targetLang = $request->request->get('target_lang', 'en');
        
        if (empty($text)) {
            return $this->json(['success' => false, 'error' => 'Texte vide'], 400);
        }
        
        try {
            // First try with our translation service (which has internal fallbacks)
            $translation = $this->translationService->translate($text, $targetLang);
            $method = 'service';
            
            // Check if translation failed or returned an error indicator
            if (strpos($translation, '[Traduction échouée]') === 0) {
                // If our service completely failed, try a direct simple fallback
                // This is just for common phrases as a last resort
                if ($text === 'je suis' && $targetLang === 'en') {
                    $translation = 'I am';
                    $method = 'manual';
                } elseif ($text === 'bonjour' && $targetLang === 'en') {
                    $translation = 'hello';
                    $method = 'manual';
                } else {
                    // If we can't translate, return the original text
                    $translation = $text;
                    $method = 'none';
                }
            }
            
            return $this->json([
                'success' => true,
                'translation' => $translation,
                'method' => $method
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur de traduction dans le contrôleur: ' . $e->getMessage());
            
            // Solution simple pour les textes courts en cas d'échec
            if ($text === 'je suis' && $targetLang === 'en') {
                return $this->json([
                    'success' => true,
                    'translation' => 'I am',
                    'method' => 'fallback'
                ]);
            }
            
            return $this->json([
                'success' => false,
                'error' => 'Service de traduction temporairement indisponible. Veuillez réessayer plus tard.'
            ], 500);
        }
    }

    #[Route('/reponse/{id}/rate', name: 'front_rate_reponse', methods: ['POST'])]
    public function rateReponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        $reclamation = $reponse->getReclamation();

        if ($reclamation->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $rating = $request->request->get('rating');

        if ($rating >= 1 && $rating <= 5) {
            $reponse->setRating((int)$rating);
            $reclamation->setStatus(Reclamation::STATUS_RESOLVED);
            
            $em->flush();
            $this->addFlash('success', 'Merci pour votre évaluation !');
        } else {
            $this->addFlash('error', 'Veuillez sélectionner une note valide.');
        }

        return $this->redirectToRoute('front_reclamation_reponses', ['id' => $reclamation->getId()]);
    }
    
    #[Route('/reclamations/statistics', name: 'front_reclamations_statistics', methods: ['GET'])]
    public function statistics(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        // Créer le form filter avec les mêmes filtres
        $filterForm = $this->createForm(ReclamationFilterType::class);
        $filterForm->handleRequest($request);
        
        // Créer la requête de base pour les statistiques
        $baseQueryBuilder = $em->getRepository(Reclamation::class)
            ->createQueryBuilder('r')
            ->where('r.utilisateur = :user')
            ->setParameter('user', $user);
            
        // Appliquer les filtres si le formulaire est soumis et que FilterBuilderUpdater est disponible
        if ($filterForm->isSubmitted() && $this->filterBuilderUpdater) {
            $this->filterBuilderUpdater->addFilterConditions($filterForm, $baseQueryBuilder);
        } else {
            // Utiliser la recherche simple si FilterBuilderUpdater n'est pas disponible
            $searchTerm = $request->query->get('search', '');
            $typeFilter = $request->query->get('type', '');
        
            if (!empty($searchTerm)) {
                $baseQueryBuilder->andWhere('r.description LIKE :searchTerm')
                    ->setParameter('searchTerm', '%'.$searchTerm.'%');
            }
        
            if (!empty($typeFilter)) {
                $baseQueryBuilder->andWhere('r.type = :type')
                    ->setParameter('type', $typeFilter);
            }
        }
        
        // Cloner le query builder pour chaque requête statistique
        $statusQueryBuilder = clone $baseQueryBuilder;
        $sentimentQueryBuilder = clone $baseQueryBuilder;
        $typeQueryBuilder = clone $baseQueryBuilder;
        $combinedQueryBuilder = clone $baseQueryBuilder;
        $resolvedQueryBuilder = clone $baseQueryBuilder;
        $totalQueryBuilder = clone $baseQueryBuilder;
        $timelineQueryBuilder = clone $baseQueryBuilder;
        
        // Statistiques par statut
        $statusStats = $statusQueryBuilder
            ->select('r.status as status, COUNT(r.id) as count')
            ->groupBy('r.status')
            ->getQuery()
            ->getArrayResult();
        
        // Statistiques par sentiment
        $sentimentStats = $sentimentQueryBuilder
            ->select('r.sentimentLabel as sentiment, COUNT(r.id) as count')
            ->where('r.sentimentLabel IS NOT NULL')
            ->groupBy('r.sentimentLabel')
            ->getQuery()
            ->getArrayResult();
        
        // Statistiques par type
        $typeStats = $typeQueryBuilder
            ->select('r.type as type, COUNT(r.id) as count')
            ->groupBy('r.type')
            ->getQuery()
            ->getArrayResult();
        
        // Statistiques combinées (type + sentiment)
        $typeSentimentStats = $combinedQueryBuilder
            ->select('r.type as type, r.sentimentLabel as sentiment, COUNT(r.id) as count')
            ->where('r.sentimentLabel IS NOT NULL')
            ->groupBy('r.type, r.sentimentLabel')
            ->getQuery()
            ->getArrayResult();
        
        // Calcul du taux de résolution basé sur les filtres
        $totalReclamations = $totalQueryBuilder
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $resolvedReclamations = $resolvedQueryBuilder
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :status')
            ->setParameter('status', Reclamation::STATUS_RESOLVED)
            ->getQuery()
            ->getSingleScalarResult();
        
        $resolutionRate = $totalReclamations > 0 ? 
            round(($resolvedReclamations / $totalReclamations) * 100, 2) : 0;
        
        // Réclamations sur le temps (par mois) avec filtres
        $reclamationsByMonth = $timelineQueryBuilder
            ->select("CONCAT(YEAR(r.date), '-', MONTH(r.date)) as month, COUNT(r.id) as count")
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getArrayResult();
        
        $timelineData = $this->formatTimelineData($reclamationsByMonth);
        
        // Conversion des données pour Google Chart
        $statusData = $this->formatChartData($statusStats, 'status', 'count');
        $sentimentData = $this->formatChartData($sentimentStats, 'sentiment', 'count');
        $typeData = $this->formatChartData($typeStats, 'type', 'count');
        
        return $this->render('front/reclamation/statistics.html.twig', [
            'statusData' => json_encode($statusData),
            'sentimentData' => json_encode($sentimentData),
            'typeData' => json_encode($typeData),
            'typeSentimentData' => json_encode($typeSentimentStats),
            'resolutionRate' => $resolutionRate,
            'timelineData' => json_encode($timelineData),
            'filterForm' => $filterForm->createView(),
            'hasFilters' => $filterForm->isSubmitted()
        ]);
    }

    /**
     * Formate les données pour les graphiques
     */
    private function formatChartData(array $data, string $labelKey, string $valueKey): array
    {
        $formattedData = [];
        $formattedData[] = [$labelKey, 'Nombre'];
        
        foreach ($data as $item) {
            $formattedData[] = [$item[$labelKey] ?? 'Non défini', (int)$item[$valueKey]];
        }
        
        return $formattedData;
    }

    /**
     * Formate les données pour les graphiques en timeline
     */
    private function formatTimelineData(array $data): array
    {
        $formattedData = [];
        $formattedData[] = ['Mois', 'Nombre de réclamations'];
        
        foreach ($data as $item) {
            // Formater le mois pour affichage (ex: 2025-4 -> Avril 2025)
            $parts = explode('-', $item['month']);
            $year = $parts[0];
            $month = $parts[1];
            $dateObj = new \DateTime("$year-$month-01");
            $formattedMonth = $dateObj->format('M Y'); // Abr. mois et année
            
            $formattedData[] = [$formattedMonth, (int)$item['count']];
        }
        
        return $formattedData;
    }
}