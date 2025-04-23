<?php
// src/Controller/FrontOffice/Reclamation/ReclamationController.php

namespace App\Controller\Admin\ReclamationEMP;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Entity\ReclamationArchive;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\SentimentAnalysisService;
use Psr\Log\LoggerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\InfobipSmsSender;

#[Route('/list')]
class ReclamationController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'front_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('front_reclamations');
    }

    #[Route('/reclamations', name: 'front_reclamations')]
    public function list(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $searchTerm = $request->query->get('search', '');
        $typeFilter = $request->query->get('type', '');
    
        $queryBuilder = $em->getRepository(Reclamation::class)
            ->createQueryBuilder('r')
            ->where('r.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('r.date', 'DESC');
    
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('r.description LIKE :searchTerm')
                ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }
    
        if (!empty($typeFilter)) {
            $queryBuilder->andWhere('r.type = :type')
                ->setParameter('type', $typeFilter);
        }
    
        $reclamations = $queryBuilder->getQuery()->getResult();
    
        return $this->render('front/reclamation/list.html.twig', [
            'reclamations' => $reclamations,
            'searchTerm' => $searchTerm,
            'selectedType' => $typeFilter,
            'typeChoices' => Reclamation::getTypeChoices(),
        ]);
    }

    #[Route('/reclamations/pdf', name: 'front_reclamations_pdf')]
    public function generatePdf(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $reclamations = $em->getRepository(Reclamation::class)
            ->findBy(['utilisateur' => $user], ['date' => 'DESC']);
        
        // Configure Dompdf
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        
        // Générer le HTML pour le PDF
        $html = $this->renderView('front/reclamation/pdf_list.html.twig', [
            'reclamations' => $reclamations,
            'user' => $user,
            'title' => 'Mes réclamations'
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
            
           /* // Envoi du SMS de confirmation - version sans getTelephone()
            $user = $this->getUser();
            $smsMessage = "Bonjour " . ", une nouvelle réclamation a été ajoutée.";
            
            try {
                // Utiliser le numéro par défaut configuré dans le service
                $smsResponse = $smsSender->sendSms('default', $smsMessage);
                $this->addFlash('success', 'Votre réclamation a été enregistrée et un SMS de confirmation a été envoyé.');
                
                // Pour déboguer, décommentez la ligne suivante :
                // dd($smsResponse);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'envoi du SMS: ' . $e->getMessage());
                $this->addFlash('success', 'Votre réclamation a été enregistrée avec succès.');
                $this->addFlash('warning', "Le SMS de notification n'a pas pu être envoyé : " . $e->getMessage());
            }*/
            
            return $this->redirectToRoute('front_reclamations');
        }
        
        return $this->render('front/reclamation/add.html.twig', [
            'form' => $form->createView(),
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
}