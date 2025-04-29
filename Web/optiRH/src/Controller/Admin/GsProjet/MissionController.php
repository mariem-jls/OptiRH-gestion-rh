<?php

namespace App\Controller\Admin\GsProjet;


use App\Entity\GsProjet\Mission;
use App\Entity\GsProjet\Project;
use App\Entity\User;
use App\Form\GsProjet\MissionType;
use App\Repository\GsProjet\MissionRepository;
use App\Service\MissionNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack; // Importation ajoutée
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use DateTimeInterface;
use DateTime;
use App\Service\NotificationManager;



#[Route('/gs-projet/project', name: 'gs-projet_project_')]
#[IsGranted('ROLE_USER')]
class MissionController extends AbstractController
{
    #[Route('/{id}/missions', name: 'missions_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
  

    public function index(
        User $user, 
        MissionRepository $missionRepository,
        MissionNotificationService $notificationService
    ): Response {
        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
    
        // Récupération des missions avec jointures
        $missions = $missionRepository->createQueryBuilder('m')
            ->leftJoin('m.assignedTo', 'u')
            ->leftJoin('m.project', 'p')
            ->addSelect('u')
            ->addSelect('p')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('m.dateTerminer', 'ASC')
            ->getQuery()
            ->getResult();
    
        // Envoi des notifications
        foreach ($missions as $mission) {
            // Vérifier si la mission est en retard
            if ($this->isMissionLate($mission)) {
                $notificationService->sendLateMissionNotification($mission);
            }
        }
    
        // Formatage des données pour FullCalendar
        $formattedMissions = array_map(function(Mission $mission) {
            $endDate = $mission->getDateTerminer();
            if (!$endDate) return null;
    
            return [
                'id' => $mission->getId(),
                'title' => $mission->getTitre() ?? 'Sans titre',
                'start' => $endDate->format('Y-m-d'),
                'allDay' => true,
                'statut' => $mission->getStatus() ?? 'To Do',
                'description' => $mission->getDescription() ?? 'Aucune description',
                'projectTitle' => $mission->getProject() ? $mission->getProject()->getNom() : 'Aucun projet',
                'isLate' => $this->isMissionLate($mission),
                'meetLink' => $mission->getMeetLink(), // Ajout du champ meetLink
            ];
        }, $missions);
    
        // Filtrage des missions sans date
        $formattedMissions = array_filter($formattedMissions);
    
        return $this->render('gs-projet/project/indexMission.html.twig', [
            'missions' => $formattedMissions,
            'user' => $user
        ]);
    }

  
    #[Route('/missions/{id}/update-status', name: 'missions_update_status', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateStatus(Request $request, Mission $mission, EntityManagerInterface $em): Response
    {
        // Récupération des données JSON
        $data = json_decode($request->getContent(), true);
        
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('mission_status', $data['_token'] ?? '')) {
            return $this->json(['error' => 'Token CSRF invalide'], 403);
        }
    
        // Validation du statut
        $allowedStatuses = ['To Do', 'In Progress', 'Done'];
        if (!in_array($data['status'] ?? null, $allowedStatuses)) {
            return $this->json(['error' => 'Statut invalide'], 400);
        }
    
        try {
            // Mise à jour et sauvegarde
            $mission->setStatus($data['status']);
            $em->flush();
    
            return $this->json([
                'success' => true,
                'newStatus' => $mission->getStatus(),
                'newColor' => $this->getStatusColor($mission->getStatus())
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur de base de données : ' . $e->getMessage()], 500);
        }
    }
    private function isMissionLate(Mission $mission): bool
    {
        if ($mission->getStatus() === 'Done') return false;
        
        $today = new \DateTime();
        $deadline = $mission->getDateTerminer();
        
        return $deadline && $deadline < $today;
    }

    /**
     * Retourne la couleur associée à un statut
     */
   
    
    private function getStatusColor(string $status): string
    {
        return match($status) {
            'Done' => '#28a745',
            'In Progress' => '#ffc107',
            default => '#dc3545'
        };
    }
    #[Route('/{id}/missions/new', name: 'mission_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        Project $project, 
        EntityManagerInterface $em,
        MissionNotificationService $notificationService
    ): Response {
        $mission = new Mission();
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Associer la mission au projet
                $mission->setProject($project);
                // Copier le lien Meet du projet vers la mission
                $mission->setMeetLink($project->getMeetLink());
                
                // Persister la mission pour obtenir un ID
                $em->persist($mission);
                $em->flush();
    
                // Envoyer la notification seulement après la persistance
                if ($mission->getAssignedTo()) {
                    $notificationService->sendNewMissionNotification($mission);
                }
    
                // Réponse pour requêtes AJAX
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => true,
                        'message' => 'Mission créée avec succès!',
                        'redirect' => $this->generateUrl('gs-projet_project_show', ['id' => $project->getId()])
                    ]);
                }
    
                $this->addFlash('success', 'Mission créée avec succès!');
                return $this->redirectToRoute('gs-projet_project_show', ['id' => $project->getId()]);
                
            } catch (\Exception $e) {
                // Gestion des erreurs
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['error' => $e->getMessage()], 500);
                }
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }
    
        // Affichage du formulaire
        return $this->render('gs-projet/project/newMiss.html.twig', [
            'form' => $form->createView(),
            'project' => $project
        ]);
    }
    #[Route('/mission/{id}/edit', name: 'mission_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Mission $mission, 
        EntityManagerInterface $em,
        MissionNotificationService $notificationService
    ): Response {
        // Sauvegarder l'assigné original pour comparaison
        $originalAssignee = $mission->getAssignedTo();
        
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'assigné a changé
            $newAssignee = $mission->getAssignedTo();
            
            // Envoyer une notification si l'assigné a changé et n'est pas null
            if ($newAssignee && $originalAssignee !== $newAssignee) {
                $notificationService->sendNewMissionNotification($mission);
            }

            $em->flush();

            // Réponse JSON pour les requêtes AJAX
            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('gs-projet_project_show', ['id' => $mission->getProject()->getId()]),
                'message' => 'Les modifications ont été enregistrées avec succès.'
            ]);
        }

        // Affichage du formulaire d'édition
        return $this->render('gs-projet/project/editMission.html.twig', [
            'form' => $form->createView(),
            'mission' => $mission,
        ]);
    }
    #[Route('/mission/{id}', name: 'mission_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Mission $mission): Response
    {
        return $this->render('gs-projet/project/showMission.html.twig', [
            'mission' => $mission,
            'project' => $mission->getProject()
        ]);
    }
   
    #[Route('/mission/{id}/delete', name: 'mission_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Mission $mission, EntityManagerInterface $em): Response
    {
        $projectId = $mission->getProject()?->getId();
    
        if (!$projectId) {
            throw $this->createNotFoundException('Projet introuvable');
        }
    
        if ($this->isCsrfTokenValid('delete' . $mission->getId(), $request->request->get('_token'))) {
            try {
                $em->remove($mission);
                $em->flush();
                $this->addFlash('success', 'Mission supprimée avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }
    
        return $this->redirectToRoute('gs-projet_project_show', [
            'id' => $projectId
        ]);
    }
   

#[Route('/mission/{id}/late-notify', name: 'mission_late_notify', methods: ['POST'])]
public function sendLateNotification(
    Mission $mission,
    NotificationManager $notificationManager,
    Request $request
): Response {
    if (!$this->isCsrfTokenValid('late_notify'.$mission->getId(), $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide');
    }

    $daysLate = $mission->getDaysLate();
    $notificationManager->createLateMissionNotification(
        $mission->getAssignedTo(),
        $mission->getTitre(),
        $mission->getId(),
        $mission->getProject()->getId(),
        $daysLate
    );

    $this->addFlash('success', 'Notification envoyée avec succès');
    return $this->redirectToRoute('mission_show', ['id' => $mission->getId()]);
}
#[Route('/calendar', name: 'app_calendar', methods: ['GET'])]
public function calendar(MissionRepository $missionRepository): Response
{
    $user = $this->getUser();
    if (!$user) {
        throw $this->createNotFoundException('Utilisateur non trouvé');
    }

    $missions = $missionRepository->createQueryBuilder('m')
        ->leftJoin('m.assignedTo', 'u')
        ->leftJoin('m.project', 'p')
        ->addSelect('u')
        ->addSelect('p')
        ->where('u.id = :userId')
        ->orderBy('m.dateTerminer', 'ASC')
        ->getQuery()
        ->getResult();

    $formattedMissions = array_map(function(Mission $mission) {
        $endDate = $mission->getDateTerminer();
        if (!$endDate) return null;

        return [
            'id' => $mission->getId(),
            'title' => $mission->getTitre() ?? 'Sans titre',
            'start' => $endDate->format('Y-m-d'),
            'allDay' => true,
            'statut' => $mission->getStatus() ?? 'To Do',
            'description' => $mission->getDescription() ?? 'Aucune description',
            'projectTitle' => $mission->getProject() ? $mission->getProject()->getNom() : 'Aucun projet',
            'isLate' => $this->isMissionLate($mission),
            'meetLink' => $mission->getMeetLink(),
        ];
    }, $missions);

    $formattedMissions = array_filter($formattedMissions);

    return $this->render('calendrier.html.twig', [
        'missions' => $formattedMissions,
        'user' => $user
    ]);
}
#[Route('/missions/{id}/generate-meet-link', name: 'gs-projet_generate_meet_link', methods: ['POST'])]
public function generateMeetLink(
    Mission $mission,
    Request $request,
    EntityManagerInterface $em,
    LoggerInterface $logger,
    RequestStack $requestStack
): JsonResponse
{
    if (!$this->isCsrfTokenValid('generate_meet', $request->headers->get('X-CSRF-Token'))) {
        return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
    }

    $session = $requestStack->getSession();
    if (!$session->has('google_calendar_token')) {
        return new JsonResponse([
            'error' => 'Authentification Google requise',
            'authUrl' => $this->generateUrl('google_calendar_connect')
        ], 401);
    }

    try {
        $client = new Client();
        $client->setApplicationName('Optirh');
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
        $client->addScope(Calendar::CALENDAR);
        $client->setAccessType('offline');

        $token = $session->get('google_calendar_token');
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $token['refresh_token'] ?? null;
            if (!$refreshToken) {
                throw new \Exception('No refresh token available');
            }
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (isset($newToken['error'])) {
                throw new \Exception('Token refresh error: ' . $newToken['error']);
            }
            $session->set('google_calendar_token', $newToken);
            $client->setAccessToken($newToken);
        }

        $service = new Calendar($client);

        // Ensure $startTime is a DateTime object
        $startTime = $mission->getDateTerminer() ?? new DateTime('+1 hour');
        if ($startTime instanceof \DateTimeImmutable) {
            $startTime = \DateTime::createFromImmutable($startTime);
        }

        $event = new Event([
            'summary' => 'Réunion pour la mission: ' . $mission->getTitre(),
            'description' => $mission->getDescription(),
            'start' => [
                'dateTime' => $startTime->format(DateTimeInterface::RFC3339),
                'timeZone' => 'Europe/Paris',
            ],
          
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ],
            'attendees' => [
                ['email' => $mission->getAssignedTo()->getEmail()]
            ],
        ]);

        $event = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);
        $meetLink = $event->getHangoutLink();

        $mission->setMeetLink($meetLink);
        $em->persist($mission);
        $em->flush();

        return new JsonResponse(['meetLink' => $meetLink]);
    } catch (\Exception $e) {
        $logger->error('Generate Meet link error: ' . $e->getMessage());
        return new JsonResponse(['error' => $e->getMessage()], 500);
    }
}
}