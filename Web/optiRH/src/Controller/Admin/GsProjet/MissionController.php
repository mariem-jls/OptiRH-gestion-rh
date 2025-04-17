<?php

namespace App\Controller\Admin\GsProjet;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\GsProjet\Mission;
use App\Entity\GsProjet\Project;
use App\Entity\User;
use App\Form\GsProjet\MissionType;
use App\Repository\UserRepository;
use App\Repository\GsProjet\MissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gs-projet/project', name: 'gs-projet_project_')]
#[IsGranted('ROLE_USER')]
class MissionController extends AbstractController
{
    #[Route('/{id}/missions', name: 'missions_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(User $user, MissionRepository $missionRepository): Response
    {
        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
    
        // Récupération des missions avec jointure
        $missions = $missionRepository->createQueryBuilder('m')
            ->leftJoin('m.assignedTo', 'u')
            ->addSelect('u')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();
    
        // Formatage des données
        $formattedMissions = [];
        foreach ($missions as $mission) {
            $endDate = $mission->getDateTerminer();
            if (!$endDate) continue;
    
            $formattedMissions[] = [
                'id' => $mission->getId(),
                'title' => $mission->getTitre() ?? 'Sans titre',
                'start' => $endDate->format('Y-m-d'),
                'allDay' => true,
                'statut' => $mission->getStatus() ?? 'To Do',
                'description' => $mission->getDescription() ?? 'Aucune description'
            ];
        }
    
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
    
    private function getStatusColor(string $status): string
    {
        return match($status) {
            'Done' => '#28a745',
            'In Progress' => '#ffc107',
            default => '#dc3545'
        };
    }
    #[Route('/{id}/missions/new', name: 'mission_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, Project $project, EntityManagerInterface $em): Response
    {
        $mission = new Mission();
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $mission->setProject($project);
                $em->persist($mission);
                $em->flush();
    
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
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['error' => $e->getMessage()], 500);
                }
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }
    
        return $this->render('gs-projet/project/newMiss.html.twig', [
            'form' => $form->createView(),
            'project' => $project
        ]);
    }
    
    private function getFormErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[$error->getOrigin()->getName()][] = $error->getMessage();
        }
        return $errors;
    }
    
    #[Route('/mission/{id}/edit', name: 'mission_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Mission $mission, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
    
            // Retourner une réponse JSON pour SweetAlert
            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('gs-projet_project_show', ['id' => $mission->getProject()->getId()]),
                'message' => 'Les modifications ont été enregistrées avec succès.'
            ]);
        }
    
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
}