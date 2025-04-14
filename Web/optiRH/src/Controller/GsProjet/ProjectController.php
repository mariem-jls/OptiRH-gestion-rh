<?php

namespace App\Controller\GsProjet;
use Symfony\Component\Form\FormInterface; // Correction de l'import
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\GsProjet\Project;
use App\Form\GsProjet\ProjectType;
use App\Form\GsProjet\ProjectFilterType;
use App\Repository\GsProjet\ProjectRepository;
use App\Repository\GsProjet\MissionRepository;

#[Route('/gs-projet/project', name: 'gs-projet_project_')]
class ProjectController extends AbstractController {
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        $filterForm = $this->createForm(ProjectFilterType::class);
        $filterForm->handleRequest($request);
    
        // Récupération indépendante des paramètres
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort');
    
        // Appel indépendant au repository
        $projects = $projectRepository->findByIndependentFilters(
            $search,
            $status,
            $sort,
            $request->query->getInt('page', 1)
        );
    
        if ($request->isXmlHttpRequest()) {
            return $this->render('gs-projet/project/_list.html.twig', ['projects' => $projects]);
        }
    
        return $this->render('gs-projet/project/index.html.twig', [
            'projects' => $projects,
            'filterForm' => $filterForm->createView()
        ]);
    }
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
    
        try {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $project->setCreatedBy($this->getUser());
                    $entityManager->persist($project);
                    $entityManager->flush();
    
                    return $this->json([
                        'status' => 'success',
                        'message' => 'Projet créé avec succès !'
                    ]);
                } else {
                    return new JsonResponse([
                        'status' => 'error',
                        'errors' => $this->getFormErrors($form)
                    ], 400);
                }
            }
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    
        return $this->render('gs-projet/project/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    private function getFormErrors(FormInterface $form): array
    {
        $errors = [];
    
        foreach ($form->getErrors(true, true) as $error) {
            $formField = $error->getOrigin();
            $fieldName = $formField ? $formField->getName() : 'form';
    
            if (!isset($errors[$fieldName])) {
                $errors[$fieldName] = [];
            }
    
            $errors[$fieldName][] = $error->getMessage();
        }
    
        return $errors;
    }
    
    
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($project->getCreatedBy() !== $user) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Accès refusé'], 403);
            }
            throw $this->createAccessDeniedException();
        }
    
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $entityManager->flush();
    
                    return $this->json([
                        'status' => 'success',
                        'message' => 'Projet mis à jour avec succès'
                    ]);
                } catch (\Exception $e) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Erreur : ' . $e->getMessage()
                    ], 500);
                }
            } else {
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'status' => 'form_error',
                        'formHtml' => $this->renderView('gs-projet/project/_form.html.twig', [
                            'form' => $form->createView()
                        ])
                    ], 400);
                }
            }
        }
    
        return $this->render('gs-projet/project/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }
    
   
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Project $project, MissionRepository $missionRepository): Response
    {
        $user = $this->getUser();
        if ($project->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $stats = $missionRepository->getProjectStats($project);
        $groupedMissions = $missionRepository->findGroupedByStatus($project);

        return $this->render('gs-projet/project/show.html.twig', [
            'project' => $project,
            'groupedMissions' => $groupedMissions,
            'totalTasks' => $stats['total'] ?? 0,
            'completedTasks' => $stats['completed'] ?? 0,
            'overdueTasks' => $stats['overdue'] ?? 0,
            'membersCount' => $stats['members'] ?? 0
        ]);
    }
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($project->getCreatedBy() !== $user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Accès non autorisé'
            ], 403);
        }
    
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($project);
                $entityManager->flush();
    
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'status' => 'success',
                        'message' => 'Projet supprimé avec succès'
                    ]);
                }
    
                $this->addFlash('success', 'Projet supprimé avec succès');
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Erreur de suppression : ' . $e->getMessage()
                    ], 500);
                }
                $this->addFlash('error', 'Erreur lors de la suppression');
            }
        }
    
        return $this->redirectToRoute('gs-projet_project_index');
    }
 

}