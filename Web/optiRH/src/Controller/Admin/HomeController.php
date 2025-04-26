<?php

namespace App\Controller\Admin;
use Doctrine\ORM\Query;
use App\Entity\Reclamation;
use Psr\Log\LoggerInterface;


use App\Entity\Notification ; 
use App\Repository\UserRepository;
use App\Service\GeminiAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository; 
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use App\Repository\GsProjet\MissionRepository;
use App\Repository\GsProjet\ProjectRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    private $entityManager;
    private UserRepository $userRepository;
    public function __construct(
        private Security $security,
        private GeminiAnalysisService $geminiAnalysis,
        private LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
        
    ) {$this->entityManager = $entityManager;
    $this->userRepository = $userRepository;
    }

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
private function renderAdminDashboard(
    ProjectRepository $projectRepository,
    MissionRepository $missionRepository
): Response {
    // Get all projects with their statistics
    $projects = $projectRepository->findAllWithObjectsAndStats();
    $missionTimelineData = $this->getMissionTimelineData($missionRepository);

    // Prepare data for sparklines
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

    // Get workflow data
    $workflowData = $this->getWorkflowData($projectRepository, $missionRepository);

    // Global statistics for admin
    $projectStats = $projectRepository->createQueryBuilder('p')
        ->select('p.status, COUNT(p.id) as count')
        ->groupBy('p.status')
        ->getQuery()
        ->getResult();

    $missionStats = $missionRepository->createQueryBuilder('m')
        ->select('m.status, COUNT(m.id) as count')
        ->groupBy('m.status')
        ->getQuery()
        ->getResult();

    $delayedProjects = $projectRepository->findBy(['status' => 'delayed']);
    $delayedMissions = $missionRepository->findOverdueMissions2();

    // Calculate reclamation resolution rate
    $resolutionRate = 0; // Default value
    
    try {
        if (class_exists('App\Entity\Reclamation')) {
            $totalReclamations = $this->entityManager->createQueryBuilder()
                ->select('COUNT(r.id)')
                ->from(Reclamation::class, 'r')
                ->getQuery()
                ->getSingleScalarResult();
            
            $resolvedReclamations = $this->entityManager->createQueryBuilder()
                ->select('COUNT(r.id)')
                ->from(Reclamation::class, 'r')
                ->where('r.status = :status')
                ->setParameter('status', Reclamation::STATUS_RESOLVED)
                ->getQuery()
                ->getSingleScalarResult();
            
            if ($totalReclamations > 0) {
                $resolutionRate = round(($resolvedReclamations / $totalReclamations) * 100, 2);
            }
        }
    } catch (\Exception $e) {
        $resolutionRate = 0;
    }

    // Prepare data for statistics charts
    $statusData = [['Statut', 'Nombre'], ['En attente', 5], ['En cours', 8], ['Résolue', 12]];
    $sentimentData = [['Sentiment', 'Nombre'], ['Négatif', 7], ['Neutre', 10], ['Positif', 8]];
    $typeData = [['Type', 'Nombre'], ['Technique', 10], ['Commercial', 8], ['Facturation', 7]];
    $timelineData = [
        ['Mois', 'Nombre de réclamations'],
        ['Jan 2025', 8],
        ['Fév 2025', 10],
        ['Mar 2025', 12],
        ['Avr 2025', 9]
    ];

    // Format project and mission stats as associative arrays
    $formattedProjectStats = [];
    foreach ($projectStats as $stat) {
        $formattedProjectStats[$stat['status']] = $stat['count'];
    }

    $formattedMissionStats = [];
    foreach ($missionStats as $stat) {
        $formattedMissionStats[$stat['status']] = $stat['count'];
    }

        $adminStats = [
            'total_users' => $this->userRepository->count([]),
            'verified_users' => $this->userRepository->count(['isVerified' => true]),
            'pending_verification' => $this->userRepository->count(['isVerified' => false]),
            'admin_users' => $this->userRepository->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'standard_users' => $this->userRepository->createQueryBuilder('u')
                ->where('u.roles NOT LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'recent_users' => $this->userRepository->createQueryBuilder('u')
                ->where('u.createdAt >= :date')
                ->setParameter('date', new \DateTime('-30 days'))
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'recently_updated' => $this->userRepository->createQueryBuilder('u')
                ->where('u.updatedAt >= :date')
                ->setParameter('date', new \DateTime('-30 days'))
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'average_account_age' => $this->userRepository->createQueryBuilder('u')
                ->select('AVG(DATE_DIFF(CURRENT_DATE(), u.createdAt))')
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
        ];


    return $this->render('admin/index.html.twig', [
        'project_stats' => $formattedProjectStats,
        'mission_stats' => $formattedMissionStats,
        'delayed_projects' => $delayedProjects,
        'delayed_missions' => $delayedMissions,
        'projects' => $projectsWithStats,
        'workflow_data' => $workflowData,
        'is_admin' => true,
        'resolutionRate' => $resolutionRate,
        'statusData' => $statusData,  // Pass array directly for Twig
        'sentimentData' => $sentimentData,
        'typeData' => $typeData,
        'timelineData' => $timelineData,
        'statusDataJson' => json_encode($statusData),  // JSON version for JS
        'sentimentDataJson' => json_encode($sentimentData),
        'typeDataJson' => json_encode($typeData),
        'timelineDataJson' => json_encode($timelineData),
        'adminStats' => $adminStats,
        'mission_timeline_data' => $missionTimelineData,
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
    private function getMissionTimelineData(MissionRepository $missionRepository, int $months = 6): array
    {
        $timelineData = [];
        $now = new \DateTime();
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = (new \DateTime("first day of -$i months"))->setTime(0, 0, 0);
            $endDate = (clone $startDate)->modify('+1 month');
            $monthKey = $startDate->format('M Y');
            
            if ($endDate > $now) {
                $endDate = clone $now;
            }
            
            $created = $missionRepository->countMissionsByDateRange($startDate, $endDate);
            $completed = $missionRepository->countMissionsByDateRange($startDate, $endDate, 'Done');
            
            $timelineData[$monthKey] = [
                'created' => $created,
                'completed' => $completed
            ];
        }
        
        return $timelineData;
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