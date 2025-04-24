<?php

namespace App\Controller\Admin;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\GeminiAnalysisService;
use App\Repository\GsProjet\MissionRepository;
use App\Repository\NotificationRepository; 
use App\Entity\Notification ; 
use App\Repository\GsProjet\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\Query;

class HomeController extends AbstractController
{
    public function __construct(
        private Security $security,
        private GeminiAnalysisService $geminiAnalysis,
        private LoggerInterface $logger
        
    ) {}

    #[Route('/', name: 'admin_home')]
    public function index(
        ProjectRepository $projectRepository,
        MissionRepository $missionRepository,
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository
    ): Response {
        $user = $this->security->getUser();
        $this->checkLateMissionsForUser($user, $missionRepository, $entityManager, $notificationRepository);
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->renderAdminDashboard($projectRepository, $missionRepository);
        }

        return $this->renderEmployeeDashboard($missionRepository, $user);
    }
    private function checkLateMissionsForUser(
        $user, 
        MissionRepository $missionRepository,
        EntityManagerInterface $entityManager
    ): void {
        $lateMissions = $missionRepository->findOverdueMissionsForUser($user);

        foreach ($lateMissions as $mission) {
            if (!$mission->isNotifiedLate()) {
                $notification = new Notification();
                $notification->setRecipient($user);
                $notification->setMessage(sprintf(
                    'Mission en retard: "%s" devait être terminée le %s (%d jours de retard)',
                    $mission->getTitre(),
                    $mission->getDateTerminer()->format('d/m/Y'),
                    $mission->getDaysLate()
                ));
                $notification->setType(Notification::TYPE_LATE_MISSION);
                $notification->setContext([
                    'mission_id' => $mission->getId(),
                    'project_id' => $mission->getProject()?->getId(),
                    'days_late' => $mission->getDaysLate()
                ]);
                $notification->setRouteName('mission_show'); // Remplacez par votre route réelle
                $notification->setRouteParams(['id' => $mission->getId()]);

                $entityManager->persist($notification);
                $mission->setNotifiedLate(true);
                $entityManager->persist($mission);
            }
        }

        $entityManager->flush();
    }


private function getWorkflowData(
    ProjectRepository $projectRepository,
    MissionRepository $missionRepository
): array {
    $projects = $projectRepository->findAll();
    $workflowData = [];
    
    foreach ($projects as $project) {
        $missionsByStatus = [];
        $allMissions = $project->getMissions();
        
        // Regrouper les missions par statut
        foreach ($allMissions as $mission) {
            $status = $mission->getStatus();
            if (!isset($missionsByStatus[$status])) {
                $missionsByStatus[$status] = 0;
            }
            $missionsByStatus[$status]++;
        }
        
        // Calculer le taux de progression
        $totalMissions = count($allMissions);
        $completedMissions = isset($missionsByStatus['Done']) ? $missionsByStatus['Done'] : 0;
        $progressRate = ($totalMissions > 0) ? round(($completedMissions / $totalMissions) * 100) : 0;
        
        // Définir un statut global du projet
        $status = 'À démarrer';
        if ($progressRate >= 100) {
            $status = 'Terminé';
        } elseif ($progressRate >= 70) {
            $status = 'Avancé';
        } elseif ($progressRate >= 30) {
            $status = 'En cours';
        }
        
        $workflowData[] = [
            'project' => $project,
            'missions_by_status' => $missionsByStatus,
            'total_missions' => $totalMissions,
            'completed_missions' => $completedMissions,
            'progress_rate' => $progressRate,
            'status' => $status
        ];
    }
    
    // Trier les projets par taux de progression
    usort($workflowData, function($a, $b) {
        return $b['progress_rate'] <=> $a['progress_rate'];
    });
    
    return $workflowData;
}

// Modifier la méthode renderAdminDashboard pour inclure les données de workflow
private function renderAdminDashboard(
    ProjectRepository $projectRepository,
    MissionRepository $missionRepository
): Response {
    // Récupérer tous les projets avec leurs statistiques
    $projects = $projectRepository->findAllWithObjectsAndStats();
    
    // Préparer les données pour les sparklines
    $projectsWithStats = [];
    foreach ($projects as $project) {
        $projectData = [
            'entity' => $project,
            'total_missions' => $project->getMissions()->count(),
            'done_missions' => $project->getMissions()->filter(
                fn($m) => $m->getStatus() === 'Done'
            )->count(),
            'sparkline_data' => $this->getMissionCompletionTrend(
                $project, 
                $missionRepository
            )
        ];
        $projectsWithStats[] = $projectData;
    }

    // Récupérer les données de workflow
    $workflowData = $this->getWorkflowData($projectRepository, $missionRepository);
    
    return $this->render('admin/index.html.twig', [
        'project_stats' => $this->prepareProjectsData($projectRepository),
        'mission_stats' => $this->prepareMissionsData($missionRepository),
        'delayed_projects' => $projectRepository->findBy(['status' => 'delayed']),
        'delayed_missions' => $missionRepository->findOverdueMissions2(),
        'projects' => $projectsWithStats,
        'workflow_data' => $workflowData,
        'is_admin' => true
    ]);
}
    private function getMissionCompletionTrend(
        $project, 
        MissionRepository $missionRepository,
        int $months = 12
    ): array {
        $completionData = [];
        $now = new \DateTime();
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = (new \DateTime("first day of -$i months"))->setTime(0, 0, 0);
            $endDate = (clone $startDate)->modify('+1 month');
            
            // Assure que nous ne dépassons pas la date actuelle
            if ($endDate > $now) {
                $endDate = clone $now;
            }
    
            try {
                $total = $missionRepository->countMissionsByProjectAndDateRange(
                    $project,
                    $startDate,
                    $endDate
                );
                
                $completed = $missionRepository->countMissionsByProjectAndDateRange(
                    $project,
                    $startDate,
                    $endDate,
                    'Done'
                );
                
                $rate = ($total > 0) ? round(($completed / $total) * 100) : 0;
                $completionData[] = $rate;
            } catch (\Exception $e) {
                $this->logger->error('Erreur dans getMissionCompletionTrend', [
                    'project' => $project->getId(),
                    'month' => $startDate->format('Y-m'),
                    'error' => $e->getMessage()
                ]);
                $completionData[] = 0;
            }
        }
        
        return $completionData;
    }
    #[Route('/generate-analysis', name: 'admin_generate_analysis', methods: ['POST'])]
    public function generateAnalysis(
        Request $request,
        ProjectRepository $projectRepository,
        MissionRepository $missionRepository
    ): JsonResponse {
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            if (!$request->isXmlHttpRequest()) {
                throw new \RuntimeException('Cette route nécessite une requête AJAX');
            }

            $analysisData = [
                'projects' => $this->prepareProjectsData($projectRepository),
                'missions' => $this->prepareMissionsData($missionRepository),
                'late_missions' => count($missionRepository->findOverdueMissions2())
            ];

            $report = $this->geminiAnalysis->generateProjectAnalysis(
                $analysisData['projects'],
                array_merge($analysisData['missions'], ['Late' => $analysisData['late_missions']])
            );

            return $this->json([
                'status' => 'success',
                'report' => $report,
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $this->getParameter('kernel.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function prepareProjectsData(ProjectRepository $repository): array
    {
        $results = $repository->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        return array_reduce($results, function($carry, $item) {
            $carry[$item['status']] = (int)$item['count'];
            return $carry;
        }, []);
    }

    private function prepareMissionsData(MissionRepository $repository): array
    {
        $results = $repository->createQueryBuilder('m')
            ->select('m.status, COUNT(m.id) as count')
            ->groupBy('m.status')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        return array_reduce($results, function($carry, $item) {
            $carry[$item['status']] = (int)$item['count'];
            return $carry;
        }, []);
    }

    private function prepareUserMissionsData(MissionRepository $repository, $user): array
    {
        $results = $repository->createQueryBuilder('m')
            ->select('m.status, COUNT(m.id) as count')
            ->where('m.assignedTo = :user')
            ->setParameter('user', $user)
            ->groupBy('m.status')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        return array_reduce($results, function($carry, $item) {
            $carry[$item['status']] = (int)$item['count'];
            return $carry;
        }, []);
    }

    private function renderEmployeeDashboard(
        MissionRepository $missionRepository,
        $user
    ): Response {
        // Données spécifiques à l'employé
        $userMissions = $missionRepository->findBy(['assignedTo' => $user]);
        
        $lateMissions = array_filter($userMissions, function($mission) {
            return $mission->getDateTerminer() < new \DateTime() 
                && $mission->getStatus() !== 'Done';
        });

        $missionStats = $missionRepository->createQueryBuilder('m')
            ->select('m.status, COUNT(m.id) as count')
            ->where('m.assignedTo = :user')
            ->setParameter('user', $user)
            ->groupBy('m.status')
            ->getQuery()
            ->getResult();

        return $this->render('employee/index.html.twig', [
            'mission_stats' => $missionStats,
            'late_missions' => $lateMissions,
            'total_missions' => count($userMissions),
            'is_admin' => false
        ]);
    }
}