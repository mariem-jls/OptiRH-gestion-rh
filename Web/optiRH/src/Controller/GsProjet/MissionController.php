<?php

namespace App\Controller\GsProjet;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\GsProjet\Mission;
use App\Entity\GsProjet\Project;
use App\Repository\GsProjet\UserRepository;
use App\Form\GsProjet\MissionType;

use App\Repository\GsProjet\MissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gs-projet/project', name: 'gs-projet_project_')]
class MissionController extends AbstractController
{
   // Dans MissionController.php
#[Route('/missions', name: 'app_missions_index', methods: ['GET'])]
public function index(UserRepository $userRepository, MissionRepository $missionRepository): Response
{
    $user = $userRepository->find(1);
    $missions = $missionRepository->findBy(['assignedTo' => $user]);

    $formattedMissions = [];
    foreach ($missions as $mission) {
        $formattedMissions[] = [
            'id' => $mission->getId(), // Ajout de l'ID
            'titre' => $mission->getTitre(),
            'start' => $mission->getDateTerminer()->format('Y-m-d'),
            'allDay' => true,
            'statut' => $mission->getStatus(),
            'description' => $mission->getDescription()
        ];
    }

    return $this->render('gs-projet/Project/indexMission.html.twig', [
        'missions' => $formattedMissions,
        'user' => $user
    ]);
}

#[Route('/missions/{id}/update-status', name: 'app_missions_update_status', methods: ['POST'])]
public function updateStatus(Request $request, Mission $mission, EntityManagerInterface $em): Response
{
    $newStatus = $request->request->get('status');
    $submittedToken = $request->request->get('_token');

    if (!$this->isCsrfTokenValid('mission_status', $submittedToken)) {
        return $this->json(['error' => 'Token CSRF invalide'], 403);
    }

    $allowedStatuses = ['To Do', 'In Progress', 'Done'];
    
    if (in_array($newStatus, $allowedStatuses)) {
        $mission->setStatus($newStatus);
        $em->flush();
        return $this->json(['success' => true, 'newStatus' => $newStatus]);
    }
    
    return $this->json(['error' => 'Statut invalide'], 400);
}
    #[Route('/{projectId}/missions/new', name: 'app_missions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, int $projectId): Response
    {
        $project = $em->getRepository(Project::class)->find($projectId);
        $mission = (new Mission())->setProject($project);
        
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($mission);
            $em->flush();
            return $this->redirectToRoute('gs-projet_project_show', [
                'id' => $project->getId() // Redirection vers la page du projet
            ]);
        }

        return $this->render('gs-projet/Project/newMiss.html.twig', [
            'form' => $form->createView(),
            'project' => $project
        ]);
    }


   
    #[Route('/missions/{id}/edit', name: 'app_missions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mission $mission, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('gs-projet_project_show', [
                'id' => $mission->getProject()->getId() // Correction ici
            ]);
        }
    
        return $this->render('gs-projet/Project/editMission.html.twig', [
            'form' => $form->createView(),
            'mission' => $mission,
            'project' => $mission->getProject() // Ajout crucial
        ]);
    }

    #[Route('/missions/{id}', name: 'app_missions_show', methods: ['GET'])]
    public function show(Mission $mission): Response
    {
        return $this->render('gs-projet/Project/showMission.html.twig', [
            'mission' => $mission,
            'project' => $mission->getProject()
        ]);
    }

    #[Route('/missions/{id}/delete', name: 'app_missions_delete', methods: ['POST'])]
    public function delete(Request $request, Mission $mission, EntityManagerInterface $em): Response
    {
        $project = $mission->getProject(); // Récupération du projet avant suppression
        
        if ($this->isCsrfTokenValid('delete'.$mission->getId(), $request->request->get('_token'))) {
            $em->remove($mission);
            $em->flush();
        }
    
        return $this->redirectToRoute('gs-projet_project_show', [
            'id' => $project->getId() // Redirection vers la page du projet
        ]);
    }

    #[Route('/{id}/missions', name: 'app_project_missions', methods: ['GET'])]
    public function projectMissions(Project $project, MissionRepository $repo): Response
    {
        return $this->render('gs-projet/Project/project_missions.html.twig', [
            'project' => $project,
            'missions' => $repo->findBy(['project' => $project])
        ]);
    }
  
}