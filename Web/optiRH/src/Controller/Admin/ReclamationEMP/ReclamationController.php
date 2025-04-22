<?php
// src/Controller/FrontOffice/Reclamation/ReclamationController.php

namespace App\Controller\Admin\ReclamationEMP;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\SentimentAnalysisService;
use Psr\Log\LoggerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
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
            
            // Envoi du SMS de confirmation - version sans getTelephone()
            $user = $this->getUser();
            $smsMessage = "Bonjour "   . ", une nouvelle réclamation a été ajoutée.";
            
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
            }
            
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
        $url = $this->generateUrl(
            'front_reclamation_reponses', 
            ['id' => $reclamation->getId()], 
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(20)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();
    
        return new Response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-code.png"'
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