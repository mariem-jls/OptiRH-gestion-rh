<?php

namespace App\Controller\Admin;

use Doctrine\ORM\Query;
use App\Entity\Reclamation;
use Psr\Log\LoggerInterface;

use App\Entity\Demande;
use App\Entity\Offre;
use App\Repository\DemandeRepository;
use App\Repository\OffreRepository;

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
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/', name: 'admin_home')]
    public function index(
        ProjectRepository $projectRepository,
        MissionRepository $missionRepository,
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        DemandeRepository $demandeRepository,
        OffreRepository $offreRepository
    ): Response {
        $user = $this->security->getUser();

        $this->checkLateMissionsForUser($user, $missionRepository, $entityManager, $notificationRepository);

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->renderAdminDashboard($projectRepository, $missionRepository,$demandeRepository,$offreRepository);
        }

        return $this->renderEmployeeDashboard($missionRepository, $user);
    }

    private function checkLateMissionsForUser(
        $user, 
        MissionRepository $missionRepository,
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository
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
                $notification->setRouteName('mission_show');
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
            
            foreach ($allMissions as $mission) {
                $status = $mission->getStatus();
                if (!isset($missionsByStatus[$status])) {
                    $missionsByStatus[$status] = 0;
                }
                $missionsByStatus[$status]++;
            }
            
            $totalMissions = count($allMissions);
            $completedMissions = $missionsByStatus['Done'] ?? 0;
            $progressRate = ($totalMissions > 0) ? round(($completedMissions / $totalMissions) * 100) : 0;
            
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
        
        usort($workflowData, function($a, $b) {
            return $b['progress_rate'] <=> $a['progress_rate'];
        });
        
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
    MissionRepository $missionRepository,
    DemandeRepository $demandeRepository,
    OffreRepository $offreRepository
): Response {
    // Get all projects with their statistics
    $projects = $projectRepository->findAllWithObjectsAndStats();
    
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
        return $workflowData;
    }

    private function renderAdminDashboard(
        ProjectRepository $projectRepository,
        MissionRepository $missionRepository
    ): Response {
        // Get all projects with their statistics
        $projects = $projectRepository->findAllWithObjectsAndStats();
        $missionTimelineData = $this->getMissionTimelineData($missionRepository);

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

// Recruitment statistics
// 1. Demande statistics
    $demandeStats = $demandeRepository->createQueryBuilder('d')
        ->select('d.statut, COUNT(d.id) as count')
        ->groupBy('d.statut')
        ->getQuery()
        ->getResult();

    $totalDemandes = $demandeRepository->createQueryBuilder('d')
        ->select('COUNT(d.id) as count')
        ->getQuery()
        ->getSingleScalarResult();

    $acceptedDemandes = $demandeRepository->createQueryBuilder('d')
        ->select('COUNT(d.id) as count')
        ->where('d.statut = :statut')
        ->setParameter('statut', Demande::STATUT_ACCEPTEE)
        ->getQuery()
        ->getSingleScalarResult();

// Average processing time (from creation date to current date for completed demands)
    $avgProcessingTime = $demandeRepository->createQueryBuilder('d')
        ->select('AVG(DATE_DIFF(CURRENT_DATE(), d.date)) as avg_days')
        ->where('d.statut IN (:statuts)')
        ->setParameter('statuts', [Demande::STATUT_ACCEPTEE, Demande::STATUT_REFUSEE])
        ->getQuery()
        ->getSingleScalarResult() ?? 0;

// Demandes over time (dynamic range based on data)
    $demandeTimeline = [];
    $minMaxDates = $demandeRepository->createQueryBuilder('d')
        ->select('MIN(d.date) as min_date, MAX(d.date) as max_date')
        ->getQuery()
        ->getSingleResult();

    $minDate = $minMaxDates['min_date'] ? new \DateTime($minMaxDates['min_date']) : new \DateTime('first day of -1 month');
    $maxDate = $minMaxDates['max_date'] ? new \DateTime($minMaxDates['max_date']) : new \DateTime();

// Extend range slightly for better visualization
    $startDate = (clone $minDate)->modify('first day of -1 month');
    $endDate = (clone $maxDate)->modify('last day of +1 month');

// Generate monthly data points
    $current = (clone $startDate);
    while ($current <= $endDate) {
        $monthStart = (clone $current)->setTime(0, 0, 0);
        $monthEnd = (clone $current)->modify('last day of this month')->setTime(23, 59, 59);
        $count = $demandeRepository->createQueryBuilder('d')
            ->select('COUNT(d.id) as count')
            ->where('d.date BETWEEN :start AND :end')
            ->setParameter('start', $monthStart)
            ->setParameter('end', $monthEnd)
            ->getQuery()
            ->getSingleScalarResult();
        $demandeTimeline[] = [
            'month' => $monthStart->format('M Y'),
            'count' => (int)$count
        ];
        $current->modify('+1 month');
    }

// Ensure at least two data points
    if (count($demandeTimeline) < 2) {
        $demandeTimeline[] = [
            'month' => (clone $current)->modify('first day of this month')->format('M Y'),
            'count' => 0
        ];
    }

// 2. Offre statistics
    $offreStats = $offreRepository->createQueryBuilder('o')
        ->select('o.typeContrat, COUNT(o.id) as count')
        ->groupBy('o.typeContrat')
        ->getQuery()
        ->getResult();

    $activeOffres = $offreRepository->createQueryBuilder('o')
        ->select('COUNT(o.id) as count')
        ->where('o.statut = :statut')
        ->setParameter('statut', 'Active')
        ->getQuery()
        ->getSingleScalarResult();

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
        $resolutionRate = 0;
        
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

        // Get dynamic reclamation statistics
        $statusData = $this->getReclamationStatusData();
        $sentimentData = $this->getReclamationSentimentData();
        $typeData = $this->getReclamationTypeData();
        $timelineData = $this->getReclamationTimelineData();

        // Format project and mission stats as associative arrays
        $formattedProjectStats = [];
        foreach ($projectStats as $stat) {
            $formattedProjectStats[$stat['status']] = $stat['count'];
        }

        $formattedMissionStats = [];
        foreach ($missionStats as $stat) {
            $formattedMissionStats[$stat['status']] = $stat['count'];
        }

    // Recruitment stats array for summary cards
    $recruitmentStats = [
        'total_demandes' => $totalDemandes,
        'accepted_demandes' => $acceptedDemandes,
        'active_offres' => $activeOffres,
        'avg_processing_time' => round($avgProcessingTime, 1)
    ];

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
            'statusData' => $statusData,
            'sentimentData' => $sentimentData,
            'typeData' => $typeData,
            'timelineData' => $timelineData,
            'statusDataJson' => json_encode($statusData),
            'sentimentDataJson' => json_encode($sentimentData),
            'typeDataJson' => json_encode($typeData),
            'timelineDataJson' => json_encode($timelineData),
            'adminStats' => $adminStats,
            'mission_timeline_data' => $missionTimelineData,
        ]);
    }

    private function getReclamationStatusData(): array
    {
        $statusStats = $this->entityManager->createQueryBuilder()
            ->select('r.status as status, COUNT(r.id) as count')
            ->from(Reclamation::class, 'r')
            ->groupBy('r.status')
            ->getQuery()
            ->getArrayResult();
        
        return $this->formatChartData($statusStats, 'status', 'count');
    }

    private function getReclamationSentimentData(): array
    {
        $sentimentStats = $this->entityManager->createQueryBuilder()
            ->select('r.sentimentLabel as sentiment, COUNT(r.id) as count')
            ->from(Reclamation::class, 'r')
            ->where('r.sentimentLabel IS NOT NULL')
            ->groupBy('r.sentimentLabel')
            ->getQuery()
            ->getArrayResult();
        
        return $this->formatChartData($sentimentStats, 'sentiment', 'count');
    }

    private function getReclamationTypeData(): array
    {
        $typeStats = $this->entityManager->createQueryBuilder()
            ->select('r.type as type, COUNT(r.id) as count')
            ->from(Reclamation::class, 'r')
            ->groupBy('r.type')
            ->getQuery()
            ->getArrayResult();
        
        return $this->formatChartData($typeStats, 'type', 'count');
    }

    private function getReclamationTimelineData(): array
    {
        // Correction de la requête pour utiliser les fonctions Doctrine
        $timelineStats = $this->entityManager->createQueryBuilder()
            ->select("SUBSTRING(r.date, 1, 7) as month, COUNT(r.id) as count")
            ->from(Reclamation::class, 'r')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getArrayResult();
        
        return $this->formatTimelineData($timelineStats);
    }

    private function formatChartData(array $data, string $labelKey, string $valueKey): array
    {
        $formattedData = [];
        $formattedData[] = [$labelKey, 'Nombre'];
        
        foreach ($data as $item) {
            $formattedData[] = [$item[$labelKey] ?? 'Non défini', (int)$item[$valueKey]];
        }
        
        return $formattedData;
    }

    private function formatTimelineData(array $data): array
    {
        $formattedData = [];
        $formattedData[] = ['Mois', 'Nombre de réclamations'];
        
        foreach ($data as $item) {
            $parts = explode('-', $item['month']);
            $year = $parts[0];
            $month = $parts[1];
            $dateObj = new \DateTime("$year-$month-01");
            $formattedMonth = $dateObj->format('M Y');
            
            $formattedData[] = [$formattedMonth, (int)$item['count']];
        }
        
        return $formattedData;
    }

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
        'demande_stats' => $demandeStats,
        'offre_stats' => $offreStats,
        'recruitment_stats' => $recruitmentStats,
        'demande_timeline' => $demandeTimeline,
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