<?php

namespace App\Controller\Admin;

use App\Repository\GsProjet\MissionRepository;
use App\Repository\GsProjet\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class HomeController extends AbstractController
{
    public function __construct(
        private Security $security
    ) {}

    #[Route('/', name: 'home')]
    public function index(
        ProjectRepository $projectRepository,
        MissionRepository $missionRepository
    ): Response {
        $user = $this->security->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->renderAdminDashboard($projectRepository, $missionRepository);
        }

        return $this->renderEmployeeDashboard($missionRepository, $user);
    }

    private function renderAdminDashboard(
        ProjectRepository $projectRepository,
        MissionRepository $missionRepository
    ): Response {
        // Statistiques globales pour l'admin
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

        return $this->render('admin/index.html.twig', [
            'project_stats' => $projectStats,
            'mission_stats' => $missionStats,
            'delayed_projects' => $delayedProjects,
            'delayed_missions' => $delayedMissions,
            'is_admin' => true
        ]);
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