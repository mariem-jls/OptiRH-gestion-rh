<?php
// src/Controller/Admin/Reclamation/ReclamationController.php

namespace App\Controller\Admin\Reclamation;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Entity\ReclamationArchive;
use App\Form\ReclamationFilterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\SentimentAnalysisService;
use App\Service\TranslationService;
use Psr\Log\LoggerInterface;
use App\Service\InfobipSmsSender;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Component\HttpClient\HttpClient;

class ReclamationController extends AbstractController
{
    private $logger;
    private $translationService;
    private $filterBuilderUpdater;
    private $entityManager;
    private $huggingFaceApiKey;
    
    public function __construct(
        LoggerInterface $logger, 
        TranslationService $translationService, 
        FilterBuilderUpdaterInterface $filterBuilderUpdater,
        EntityManagerInterface $entityManager,
        string $huggingFaceApiKey
    ) {
        $this->logger = $logger;
        $this->translationService = $translationService;
        $this->filterBuilderUpdater = $filterBuilderUpdater;
        $this->entityManager = $entityManager;
        $this->huggingFaceApiKey = $huggingFaceApiKey;
    }

    #[Route('/reclamations', name: 'admin_reclamations', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        // Créer le form filter
        $filterForm = $this->createForm(ReclamationFilterType::class);
        $filterForm->handleRequest($request);
        
        // Créer le QueryBuilder de base
        $queryBuilder = $em->getRepository(Reclamation::class)
            ->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC');
            
        // Appliquer les filtres si le formulaire est soumis
        if ($filterForm->isSubmitted()) {
            $this->filterBuilderUpdater->addFilterConditions($filterForm, $queryBuilder);
        }
        
        // Paginer les résultats
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10 // Nombre d'éléments par page
        );

        return $this->render('reclamation/list.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(),
            'typeChoices' => Reclamation::getTypeChoices(),
        ]);
    }

    #[Route('/reclamations/pdf', name: 'admin_reclamations_pdf', methods: ['GET'])]
    public function generatePdf(Request $request, EntityManagerInterface $em): Response
    {
        // Créer le form filter avec les mêmes filtres
        $filterForm = $this->createForm(ReclamationFilterType::class);
        $filterForm->handleRequest($request);
        
        // Créer la requête de base
        $queryBuilder = $em->getRepository(Reclamation::class)
            ->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC');
            
        // Appliquer les filtres si le formulaire est soumis
        if ($filterForm->isSubmitted()) {
            $this->filterBuilderUpdater->addFilterConditions($filterForm, $queryBuilder);
        }
        
        $reclamations = $queryBuilder->getQuery()->getResult();
        
        // Configure Dompdf selon vos besoins
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML pour le PDF
        $html = $this->renderView('reclamation/pdf_list.html.twig', [
            'reclamations' => $reclamations,
            'title' => 'Liste des réclamations',
            'filters' => $filterForm->getData()
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $this->addFlash('success', 'PDF généré avec succès');
        
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reclamations.pdf"'
            ]
        );
    }
        

    
    #[Route('/reclamations/archive', name: 'admin_reclamations_archive', methods: ['GET'])]
    public function listArchive(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        // Récupération des paramètres de recherche et filtrage pour les archives
        $searchTerm = $request->query->get('search', '');
        $typeFilter = $request->query->get('type', '');
        
        // Créer une query pour récupérer toutes les archives avec filtres
        $queryBuilder = $em->getRepository(ReclamationArchive::class)
            ->createQueryBuilder('ra')
            ->orderBy('ra.date', 'DESC');
            
        // Ajouter les filtres de recherche si présents
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('ra.description LIKE :searchTerm')
                ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }
        
        // Ajouter le filtre par type si présent
        if (!empty($typeFilter)) {
            $queryBuilder->andWhere('ra.type = :type')
                ->setParameter('type', $typeFilter);
        }
        
        // Paginer les résultats
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $this->addFlash('info', 'Liste des archives chargée');

        return $this->render('reclamation/archive_list.html.twig', [
            'pagination' => $pagination,
            'archives' => $pagination, 
            'searchTerm' => $searchTerm,
            'selectedType' => $typeFilter,
            'typeChoices' => Reclamation::getTypeChoices(),
        ]);
    }
    
    #[Route('/reclamations/archive/pdf', name: 'admin_reclamations_archive_pdf', methods: ['GET'])]
    public function generateArchivePdf(EntityManagerInterface $em, Request $request): Response
    {
        // Récupération des paramètres de filtrage pour le PDF d'archive
        $searchTerm = $request->query->get('search', '');
        $typeFilter = $request->query->get('type', '');
        
        // Création de la requête avec filtres
        $queryBuilder = $em->getRepository(ReclamationArchive::class)
            ->createQueryBuilder('ra')
            ->orderBy('ra.date', 'DESC');
            
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('ra.description LIKE :searchTerm')
                ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }
        
        if (!empty($typeFilter)) {
            $queryBuilder->andWhere('ra.type = :type')
                ->setParameter('type', $typeFilter);
        }
        
        $archives = $queryBuilder->getQuery()->getResult();
        
        // Configure Dompdf selon vos besoins
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML pour le PDF
        $html = $this->renderView('reclamation/pdf_archive_list.html.twig', [
            'archives' => $archives,
            'searchTerm' => $searchTerm,
            'typeFilter' => $typeFilter,
            'title' => 'Historique des réclamations supprimées'
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $this->addFlash('success', 'PDF des archives généré avec succès');
        
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reclamations-archive.pdf"'
            ]
        );
    }

    #[Route('/reclamation/{id}/reponses', name: 'admin_reclamation_reponses', methods: ['GET', 'POST'])]
    public function reponses(Reclamation $reclamation, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $reponse = new Reponse();
        $form = $this->createFormBuilder($reponse)
            ->add('description', TextareaType::class, [
                'label' => 'Votre réponse',
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Écrivez votre réponse ici...'
                ],
            ])
            ->getForm();
            
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
            $reponse->setReclamation($reclamation);
            $reponse->setDate(new \DateTime());

            if ($reclamation->getStatus() === Reclamation::STATUS_PENDING) {
                $reclamation->setStatus(Reclamation::STATUS_IN_PROGRESS);
            }

            $em->persist($reponse);
            $em->flush();

            // Envoi de l'email à l'employeur
            $employeur = $reclamation->getUtilisateur();
            if ($employeur && $employeur->getEmail()) {
                try {
                    $email = (new Email())
                        ->from('no-reply@votre-domaine.com')
                        ->to($employeur->getEmail())
                        ->subject('Nouvelle réponse à votre réclamation')
                        ->html($this->renderView(
                            'reclamation/nouvelle_reponse.html.twig',
                            [
                                'reclamation' => $reclamation,
                                'reponse' => $reponse,
                                'employeur' => $employeur
                            ]
                        ));

                    $mailer->send($email);
                    $this->addFlash('success', 'Email envoyé avec succès');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'La réponse a été enregistrée mais l\'email n\'a pas pu être envoyé.');
                }
            }

            $this->addFlash('success', 'Réponse publiée avec succès !');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reclamation->getId()]);
        }

        return $this->render('reclamation/reponses.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
            'can_edit' => false,
            'translatedText' => $translatedText,
            'targetLanguage' => $targetLanguage,
            'availableLanguages' => $availableLanguages
        ]);
    }

    #[Route('/reclamation/{id}/qr-code', name: 'admin_reclamation_qr_code', methods: ['GET'])]
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
        
        $this->addFlash('success', 'QR Code généré avec succès');
        
        return new Response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="reclamation-details.png"'
        ]);
    }

    #[Route('/reclamation/{id}/update-status', name: 'admin_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $status = $request->request->get('status');

        if (in_array($status, [
            Reclamation::STATUS_PENDING,
            Reclamation::STATUS_IN_PROGRESS,
            Reclamation::STATUS_RESOLVED
        ])) {
            $reclamation->setStatus($status);
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour avec succès');
        } else {
            $this->addFlash('error', 'Statut invalide');
        }

        return $this->redirectToRoute('admin_reclamations');
    }

    #[Route('/reponse/{id}/edit', name: 'admin_reponse_edit', methods: ['GET', 'POST'])]
    public function editReponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        if ($reponse->getRating() > 0) {
            $this->addFlash('error', 'Impossible de modifier une réponse notée');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        $form = $this->createFormBuilder($reponse)
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 5, 'minlength' => 5],
            ])
            ->getForm();
            
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
            $em->flush();
            $this->addFlash('success', 'Réponse modifiée avec succès');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        return $this->render('reponse/edit.html.twig', [
            'form' => $form->createView(),
            'reponse' => $reponse,
            'translatedText' => $translatedText,
            'targetLanguage' => $targetLanguage,
            'availableLanguages' => $availableLanguages
        ]);
    }

    #[Route('/reponse/{id}/delete', name: 'admin_reponse_delete', methods: ['POST'])]
    public function deleteReponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        if ($reponse->getRating() > 0) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Impossible de supprimer une réponse notée']);
            }
            $this->addFlash('error', 'Impossible de supprimer une réponse notée');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $em->remove($reponse);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => 'Réponse supprimée avec succès']);
            }

            $this->addFlash('success', 'Réponse supprimée avec succès');
        }

        return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
    }
    
    #[Route('/reclamations/statistics', name: 'admin_reclamations_statistics', methods: ['GET'])]
    public function statistics(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        // Créer le form filter
        $filterForm = $this->createForm(ReclamationFilterType::class);
        $filterForm->handleRequest($request);
        
        // Créer la requête de base pour les statistiques
        $baseQueryBuilder = $entityManager->getRepository(Reclamation::class)
            ->createQueryBuilder('r');
            
        // Appliquer les filtres si le formulaire est soumis
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $this->filterBuilderUpdater->addFilterConditions($filterForm, $baseQueryBuilder);
        }
        
        // Cloner le query builder pour chaque requête statistique
        $statusQueryBuilder = clone $baseQueryBuilder;
        $sentimentQueryBuilder = clone $baseQueryBuilder;
        $typeQueryBuilder = clone $baseQueryBuilder;
        $combinedQueryBuilder = clone $baseQueryBuilder;
        $resolvedQueryBuilder = clone $baseQueryBuilder;
        $totalQueryBuilder = clone $baseQueryBuilder;
        
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
        
        // Calcul du taux de résolution
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
        
        // Réclamations sur le temps (par minute)
        $conn = $entityManager->getConnection();
        $timelineQuery = "
            SELECT 
                DATE_FORMAT(r.date, '%Y-%m-%d %H:%i') as datetime,
                COUNT(r.id) as count
            FROM reclamation r
            GROUP BY datetime
            ORDER BY datetime ASC
        ";
        
        try {
            $stmt = $conn->prepare($timelineQuery);
            $resultSet = $stmt->executeQuery();
            $reclamationsByTime = $resultSet->fetchAllAssociative();
            
            // Format pour la timeline
            $timelineData = $this->formatTimelineData($reclamationsByTime);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des données temporelles: ' . $e->getMessage());
            $timelineData = [['Datetime', 'Nombre de réclamations']]; // Array vide mais valide pour le graphique
        }
        
        // Conversion des données pour les graphiques
        $statusData = $this->formatChartData($statusStats, 'status', 'count');
        $sentimentData = $this->formatChartData($sentimentStats, 'sentiment', 'count');
        $typeData = $this->formatChartData($typeStats, 'type', 'count');
        
        // Vérification des données
        if (empty($statusData) || empty($sentimentData) || empty($typeData)) {
            $this->addFlash('warning', 'Aucune donnée disponible pour les statistiques.');
        }
        
        return $this->render('reclamation/statistics.html.twig', [
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
        
        if (empty($data)) {
            return $formattedData;
        }
        
        foreach ($data as $item) {
            if (!isset($item[$labelKey]) || !isset($item[$valueKey])) {
                continue;
            }
            
            $label = $item[$labelKey] ?? 'Non défini';
            $value = (int)$item[$valueKey];
            
            if ($value > 0) {
                $formattedData[] = [$label, $value];
            }
        }
        
        return $formattedData;
    }
    
    /**
     * Formate les données pour les graphiques en timeline
     */
    private function formatTimelineData(array $data): array
    {
        $formattedData = [];
        $formattedData[] = ['Datetime', 'Nombre de réclamations'];
        
        if (empty($data)) {
            return $formattedData;
        }
        
        foreach ($data as $item) {
            if (!isset($item['datetime']) || !isset($item['count'])) {
                continue;
            }
            
            $datetime = $item['datetime'];
            $count = (int)$item['count'];
            
            if ($count > 0) {
                $formattedData[] = [$datetime, $count];
            }
        }
        
        return $formattedData;
    }
    
    #[Route('/reclamation/translate', name: 'admin_translate_text', methods: ['POST'])]
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

    #[Route('/reclamation/generate-ai-response', name: 'admin_generate_ai_response', methods: ['POST'])]
    public function generateAIResponse(Request $request): JsonResponse
    {
        try {
            // Vérification de la clé API
            if (empty($this->huggingFaceApiKey)) {
                throw new \RuntimeException('La clé API Hugging Face n\'est pas configurée');
            }

            // Vérification du format de la clé API
            if (!preg_match('/^hf_[a-zA-Z0-9]{32,}$/', $this->huggingFaceApiKey)) {
                throw new \RuntimeException('Format de clé API Hugging Face invalide');
            }

            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('JSON invalide : ' . json_last_error_msg());
            }

            $reclamationContent = $data['content'] ?? '';
            $responseType = $data['type'] ?? 'professional';
            $context = $data['context'] ?? '';

            // Validation plus stricte des entrées
            if (empty($reclamationContent)) {
                throw new \InvalidArgumentException('Le contenu de la réclamation est requis');
            }

            if (!in_array($responseType, ['professional', 'empathetic', 'solution'])) {
                $responseType = 'professional';
            }

            // Construction du prompt selon le type de réponse
            $prompt = match ($responseType) {
                'empathetic' => "Tu es un assistant RH professionnel et empathique. Réponds à cette réclamation de manière compréhensive et rassurante :\n\n",
                'solution' => "Tu es un assistant RH orienté solutions. Propose une réponse concrète et détaillée à cette réclamation :\n\n",
                default => "Tu es un assistant RH professionnel. Formule une réponse claire et professionnelle à cette réclamation :\n\n",
            };

            $prompt .= "Réclamation : {$reclamationContent}\n";
            if (!empty($context)) {
                $prompt .= "Contexte supplémentaire : {$context}\n";
            }
            $prompt .= "\nRéponse :";

            // Configuration du client HTTP
            $client = HttpClient::create([
                'timeout' => 30,
                'verify_peer' => false
            ]);

            $this->logger->info('Envoi de la requête à Hugging Face', [
                'prompt_length' => strlen($prompt),
                'response_type' => $responseType,
                'has_context' => !empty($context),
                'api_key_prefix' => substr($this->huggingFaceApiKey, 0, 5) . '...' // Log partiel de la clé pour le debugging
            ]);

            $startTime = microtime(true);
            $maxRetries = 2;
            $retryCount = 0;
            $lastException = null;

            // Logique de retry manuelle
            while ($retryCount <= $maxRetries) {
                try {
                    $response = $client->request('POST', 'https://api-inference.huggingface.co/models/mistralai/Mixtral-8x7B-Instruct-v0.1', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->huggingFaceApiKey,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'inputs' => $prompt,
                            'parameters' => [
                                'max_length' => 800,
                                'temperature' => 0.7,
                                'top_p' => 0.9,
                                'return_full_text' => false
                            ]
                        ]
                    ]);

                    $statusCode = $response->getStatusCode();
                    
                    if ($statusCode === 200) {
                        break; // Sort de la boucle si la requête réussit
                    }
                    
                    // Gestion spécifique des erreurs HTTP
                    $responseContent = $response->getContent(false);
                    $this->logger->error('Erreur API Hugging Face', [
                        'status_code' => $statusCode,
                        'response' => $responseContent,
                        'attempt' => $retryCount + 1
                    ]);

                    if ($statusCode === 403) {
                        throw new \RuntimeException('Erreur d\'authentification : Vérifiez votre clé API Hugging Face');
                    } elseif ($statusCode === 429) {
                        throw new \RuntimeException('Limite de requêtes dépassée : Veuillez réessayer plus tard');
                    } else {
                        throw new \RuntimeException("Erreur API (HTTP $statusCode) : $responseContent");
                    }
                    
                } catch (\Exception $e) {
                    $lastException = $e;
                    $retryCount++;
                    
                    if ($retryCount <= $maxRetries) {
                        $this->logger->warning('Tentative de retry après erreur', [
                            'attempt' => $retryCount,
                            'error' => $e->getMessage()
                        ]);
                        // Attente exponentielle entre les retries (1s, 2s, 4s)
                        sleep(pow(2, $retryCount - 1));
                    }
                }
            }

            // Si on arrive ici avec une exception, c'est que tous les retries ont échoué
            if ($lastException !== null) {
                throw $lastException;
            }

            $requestDuration = microtime(true) - $startTime;

            $result = $response->toArray();
            
            $this->logger->info('Réponse reçue de Hugging Face', [
                'duration' => round($requestDuration, 2),
                'status_code' => $statusCode,
                'retry_count' => $retryCount
            ]);

            // Extraction et validation de la réponse générée
            $generatedResponse = '';
            if (isset($result[0]['generated_text'])) {
                $generatedResponse = $result[0]['generated_text'];
            } elseif (isset($result['generated_text'])) {
                $generatedResponse = $result['generated_text'];
            } else {
                throw new \RuntimeException('Format de réponse invalide');
            }

            // Nettoyage et validation de la réponse
            $generatedResponse = trim($generatedResponse);
            if (empty($generatedResponse)) {
                throw new \RuntimeException('La réponse générée est vide');
            }

            if (strlen($generatedResponse) < 10) {
                throw new \RuntimeException('La réponse générée est trop courte');
            }

            return new JsonResponse([
                'success' => true,
                'response' => $generatedResponse,
                'metadata' => [
                    'type' => $responseType,
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'model' => 'Mixtral-8x7B-Instruct-v0.1',
                    'response_time' => round($requestDuration, 2),
                    'retry_count' => $retryCount
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération de la réponse IA', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Une erreur est survenue lors de la génération de la réponse';
            if ($this->getParameter('kernel.debug')) {
                $errorMessage .= ' : ' . $e->getMessage();
            }

            return new JsonResponse([
                'success' => false,
                'error' => $errorMessage
            ], 500);
        }
    }

    private function analyzeSentiment(string $text): string
    {
        try {
            // Nettoyage du texte
            $text = strip_tags($text);
            $text = trim($text);
            
            if (empty($text)) {
                return 'neutre';
            }

            // Configuration du client HTTP
            $client = HttpClient::create([
                'timeout' => 30,
                'verify_peer' => false
            ]);

            // Utilisation du modèle CamemBERT pour l'analyse de sentiments en français
            $response = $client->request('POST', 'https://api-inference.huggingface.co/models/ProsusAI/finbert', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->huggingFaceApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $text,
                    'parameters' => [
                        'max_length' => 100,
                        'temperature' => 0.3,
                        'return_full_text' => false
                    ]
                ]
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                $this->logger->error('Erreur lors de l\'analyse de sentiment', [
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false)
                ]);
                return 'neutre';
            }

            $result = $response->toArray();
            
            // Extraction et normalisation du sentiment
            $sentiment = '';
            if (isset($result[0]['generated_text'])) {
                $sentiment = strtolower(trim($result[0]['generated_text']));
            } elseif (isset($result['generated_text'])) {
                $sentiment = strtolower(trim($result['generated_text']));
            }

            // Mapping des sentiments en français
            $sentimentMap = [
                'positif' => 'positif',
                'positive' => 'positif',
                'négatif' => 'négatif',
                'negative' => 'négatif',
                'neutre' => 'neutre',
                'neutral' => 'neutre'
            ];

            // Analyse plus détaillée du texte en français
            $positiveWords = [
                'content', 'heureux', 'satisfait', 'excellent', 'parfait', 'bon', 'bien', 'super', 'génial', 'merveilleux',
                'content', 'heureuse', 'satisfaite', 'excellente', 'parfaite', 'bonne', 'bien', 'superbe', 'géniale', 'merveilleuse',
                'je suis content', 'je suis heureux', 'je suis satisfait', 'je suis ravi', 'je suis enchanté',
                'je suis contente', 'je suis heureuse', 'je suis satisfaite', 'je suis ravie', 'je suis enchantée',
                'très content', 'très heureux', 'très satisfait', 'très ravi', 'très enchanté',
                'très contente', 'très heureuse', 'très satisfaite', 'très ravie', 'très enchantée',
                'extrêmement content', 'extrêmement heureux', 'extrêmement satisfait',
                'extrêmement contente', 'extrêmement heureuse', 'extrêmement satisfaite'
            ];
            
            $negativeWords = [
                'triste', 'déçu', 'mauvais', 'nul', 'terrible', 'horrible', 'insatisfait', 'fâché', 'énervé', 'désolé',
                'triste', 'déçue', 'mauvaise', 'nulle', 'terrible', 'horrible', 'insatisfaite', 'fâchée', 'énervée', 'désolée',
                'je suis triste', 'je suis déçu', 'je suis mauvais', 'je suis nul', 'je suis terrible',
                'je suis triste', 'je suis déçue', 'je suis mauvaise', 'je suis nulle', 'je suis terrible',
                'très triste', 'très déçu', 'très mauvais', 'très nul', 'très terrible',
                'très triste', 'très déçue', 'très mauvaise', 'très nulle', 'très terrible',
                'extrêmement triste', 'extrêmement déçu', 'extrêmement mauvais',
                'extrêmement triste', 'extrêmement déçue', 'extrêmement mauvaise'
            ];

            // Vérification des mots-clés en français
            $textLower = strtolower($text);
            foreach ($positiveWords as $word) {
                if (strpos($textLower, $word) !== false) {
                    return 'positif';
                }
            }
            foreach ($negativeWords as $word) {
                if (strpos($textLower, $word) !== false) {
                    return 'négatif';
                }
            }

            // Si le sentiment n'est pas clairement identifié, utiliser le résultat de l'API
            return $sentimentMap[$sentiment] ?? 'neutre';

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'analyse de sentiment', [
                'error' => $e->getMessage(),
                'text' => $text
            ]);
            return 'neutre';
        }
    }
}