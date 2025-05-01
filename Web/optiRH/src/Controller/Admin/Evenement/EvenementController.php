<?php

namespace App\Controller\Admin\Evenement;
use App\Entity\Evenement\Evenement;
use App\Entity\Evenement\FavorisEvenement;
use App\Form\Evenement\EvenementType;
use App\Repository\Evenement\EvenementRepository;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Evenement\ReservationEvenementRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Twig\Environment;
use App\Service\OpenAiService;

#[Route('/evenement')]
class EvenementController extends AbstractController
{
    /*******************Liste des evenment back********* */
    #[Route('/', name: 'app_evenement_index', methods: ['GET'])]
public function index(EvenementRepository $evenementRepository, Request $request): Response
{
    $searchTerm = $request->query->get('term');
    $evenements = $evenementRepository->findByTitleLieuModalite($searchTerm);

    if ($request->isXmlHttpRequest()) {
        return $this->render('evenement/listevenementadmin.html.twig', [ // Créer un nouveau template pour la liste
            'evenements' => $evenements,
        ]);
    }

    return $this->render('evenement/index.html.twig', [
        'evenements' => $evenements,
    ]);
}

  
    /***************Ajouter evenment*********** */
    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EvenementRepository $evenementRepository): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $evenement->updateStatus();
        
            if ($imageFile) {
                $originalFilename = $imageFile->getClientOriginalName();
                $newFilename = pathinfo($originalFilename, PATHINFO_FILENAME) . '-' . uniqid() . '.' . $imageFile->guessExtension();
        
                try {
                    // Déplacer le fichier dans le répertoire des uploads
                    $imageFile->move(
                        $this->getParameter('evenements_directory'), 
                        $newFilename
                    );
        
                    // Stocker le chemin de l'image dans l'entité Evenement
                    $evenement->setImage('uploads/evenements/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'L\'upload de l\'image a échoué.');
                    return $this->redirectToRoute('app_evenement_new');
                }
            }
        
            $evenementRepository->save($evenement, true);
        
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        
        }

        return $this->renderForm('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }



    #[Route('/generer-description', name: 'app_evenement_generer_description', methods: ['POST'])]
    public function genererDescription(Request $request, OpenAIService $openAIService): JsonResponse
    {
        $titre = $request->request->get('titre');
        $lieu = $request->request->get('lieu');
        $type = $request->request->get('type');
        $modalite = $request->request->get('modalite');
        


        if (!$titre) {
            return $this->json(['error' => 'Le titre de l\'événement est requis pour générer la description.'], Response::HTTP_BAD_REQUEST);
        }

        $prompt = $this->construirePrompt($titre, $lieu, $type, $modalite); // Fonction pour construire le prompt

        try {
            $description = $openAIService->generateDescription($prompt); // Utilise le prompt complet
            return $this->json(['description' => $description]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la génération de la description par l\'IA : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function construirePrompt(string $titre, ?string $lieu, ?string $type, ?string $modalite): string
    {
        $prompt = "Génère une description attrayante et informative pour un événement intitulé \"{$titre}\". ";

        if ($lieu) {
            $prompt .= "L'événement se déroulera à {$lieu}. ";
        }

        if ($type) {
            $prompt .= "C'est un événement de type {$type}. ";
        }

        if ($modalite) {
            $prompt .= "La modalité de l'événement est {$modalite}. ";
        }

        $prompt .= "La description doit être concise et captiver l'intérêt des participants potentiels.";

        return $prompt;
    }










    /******************EDIT********* */

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EvenementRepository $evenementRepository): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $evenement->updateStatus();
    
            if ($imageFile instanceof UploadedFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
    
                try {
                    $imageFile->move(
                        $this->getParameter('evenements_directory'), 
                        $newFilename
                    );
                    $evenement->setImage('uploads/evenements/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Échec de l\'upload de l\'image : ' . $e->getMessage());
                    return $this->redirectToRoute('app_evenement_edit', ['id' => $evenement->getId()]);
                }
            }
    
            // Pas besoin de setImage() si aucun fichier n'a été uploadé, l'ancien est gardé
    
            $evenementRepository->save($evenement, true);
    
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->renderForm('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
            'current_image' => $evenement->getImage(),
        ]);
    }


    
    

    /************Supprimer evenement************** */

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EvenementRepository $evenementRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            $evenementRepository->remove($evenement, true);
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }

    /*****************Afficher les reservation du un evenement******* */
    #[Route('/evenement/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement,ReservationEvenementRepository $reservationRepo): Response {
        $reservations = $reservationRepo->findByEvenementId($evenement->getId());

        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
            'reservations' => $reservations,
        ]);
    }

/****************Affichage liste ds evenement+filtre+recherche pour employee */
#[Route('/evenements/indexfront', name: 'app_evenement_indexfront', methods: ['GET'])]
public function showall(
    EvenementRepository $evenementRepository,
    EntityManagerInterface $em,
    Request $request,
    RecommendationService $recommendationService
): Response {
    $searchTerm = $request->query->get('term');
    $modalite = $request->query->get('modalite');
    $type = $request->query->get('type');
    $recommended = $request->query->getBoolean('recommended', false);
    
    $user = $this->getUser();
    $favorisIds = [];
    
    if ($user) {
        $favoris = $em->getRepository(FavorisEvenement::class)->findBy(['id_user' => $user]);
        foreach ($favoris as $favori) {
            $favorisIds[] = $favori->getIdEvenement()->getId();
        }
    }
    
    // Mode recommandé activé et utilisateur connecté
    if ($recommended && $user) {
        $recommendedEvents = $recommendationService->getRecommendedEvents($user);
        
        // Filtrer les événements recommandés si nécessaire
        if ($searchTerm || $modalite || $type) {
            $filteredRecommendedEvents = [];
            
            foreach ($recommendedEvents as $eventScore) {
                $event = $eventScore['event'];
                $match = true;
                
                if ($searchTerm && stripos($event->getTitre(), $searchTerm) === false) {
                    $match = false;
                }
                
                if ($modalite && $event->getModalite() !== $modalite) {
                    $match = false;
                }
                
                if ($type && $event->getType() !== $type) {
                    $match = false;
                }
                
                if ($match) {
                    $filteredRecommendedEvents[] = $eventScore;
                }
            }
            
            $recommendedEvents = $filteredRecommendedEvents;
        }
        
        // Extraire uniquement les événements pour l'affichage
        $evenements = array_map(fn($item) => $item['event'], $recommendedEvents);
        
        // Créer un tableau des scores pour l'affichage
        $scores = [];
        foreach ($recommendedEvents as $eventScore) {
            $scores[$eventScore['event']->getId()] = $eventScore['score'];
        }
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('evenement/card.html.twig', [
                'evenements' => $evenements,
                'favorisIds' => $favorisIds,
                'scores' => $scores,
                'isRecommended' => true
            ]);
        }
    } else {
        // Mode normal - tous les événements
        $evenements = $evenementRepository->findByCombinedFilters($searchTerm, $modalite, $type);
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('evenement/card.html.twig', [
                'evenements' => $evenements,
                'favorisIds' => $favorisIds,
            ]);
        }
    }
    
    // Tri des événements par date de début
    usort($evenements, function ($a, $b) {
        return $a->getDateDebut() <=> $b->getDateDebut();
    });
    
    $closestEvent = null;
    $now = new \DateTime();
    
    // Trouver le premier événement futur ou en cours
    foreach ($evenements as $evenement) {
        if ($evenement->getDateDebut() >= $now) {
            $closestEvent = $evenement;
            break;
        }
    }
    
    
    $closestEventImage = $closestEvent?->getImage();
    
    return $this->render('evenement/indexfront.html.twig', [
        'evenements' => $evenements,
        'favorisIds' => $favorisIds,
        'closestEventImage' => $closestEventImage,
        'closestEvent' => $closestEvent,
        'scores' => $scores ?? [],
        'isRecommended' => $recommended && $user
    ]);
}
 /**********recom******** */

    #[Route('/evenements/recommandes', name: 'evenement_recommended', methods: ['GET'])]

    public function showRecommendedEvents(Request $request, RecommendationService $recommendationService)
    {
        $user = $this->getUser(); // On récupère l'utilisateur connecté (assure-toi d'avoir un utilisateur authentifié)
        $recommendedEvents = $recommendationService->getRecommendedEvents($user);

        return $this->render('evenement/recommended_events.html.twig', [
            'recommendedEvents' => $recommendedEvents
        ]);
    }


    


/*************detaille d un evenment pour employee*********** */
    #[Route('/event/{id}', name: 'event_details', methods: ['GET'])]
    public function eventDetails($id, EvenementRepository $EvenementRepository): Response
    {
        $evenement = $EvenementRepository->find($id);
        
        if (!$evenement) {
            throw $this->createNotFoundException('L\'événement n\'a pas été trouvé');
        }

        return $this->render('reservation_evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }




    #[Route('evenement/stats', name: 'app_evenement_stats')]
    public function stat(EvenementRepository $evenementRepository): Response
    {
    
        // Requêtes optimisées directement en base de données
        $modaliteStats = $evenementRepository->countByModalite();
        $typeStats = $evenementRepository->countByType();
        $statusStats = $evenementRepository->countByStatus();
        
        // Formatage des données pour Chart.js
        $modaliteData = [
            'labels' => array_column($modaliteStats, 'modalite'),
            'data' => array_column($modaliteStats, 'count')
        ];
        
        $typeData = [
            'labels' => array_column($typeStats, 'type'),
            'data' => array_column($typeStats, 'count')
        ];
        
        $statusData = [
            'labels' => array_column($statusStats, 'status'),
            'data' => array_column($statusStats, 'count')
        ];

        return $this->render('evenement/stat.html.twig', [
            'modaliteData' => $modaliteData,
            'typeData' => $typeData,
            'statusData' => $statusData,
        ]);
    }


    

   

   


}
