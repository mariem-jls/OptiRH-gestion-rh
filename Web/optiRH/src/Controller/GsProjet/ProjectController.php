<?php

namespace App\Controller\GsProjet;

use App\Entity\User;
use App\Entity\GsProjet\Project;
use App\Form\GsProjet\ProjectType;
use App\Form\GsProjet\ProjectFilterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\GsProjet\ProjectRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/gs-projet/project', name: 'gs-projet_project_')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        $filterForm = $this->createForm(ProjectFilterType::class);
        $filterForm->handleRequest($request);

        $status = $filterForm->get('status')->getData();
        $sort = $filterForm->get('sort')->getData();
        
        $projects = $projectRepository->findFiltered($status, $sort);

        return $this->render('gs-projet/project/index.html.twig', [
            'projects' => $projects,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
   // src/Controller/GsProjet/ProjectController.php

// Modifiez la méthode new
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $project = new Project();
    $form = $this->createForm(ProjectType::class, $project);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            // Récupération sécurisée de l'utilisateur
            $user = $entityManager->getRepository(User::class)->find(1);
            
            if (!$user) {
                $this->addFlash('error', 'Aucun utilisateur administrateur trouvé');
                return $this->redirectToRoute('gs-projet_project_index');
            }

            $project->setCreatedBy($user);
            
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet créé avec succès');
            return $this->redirectToRoute('gs-projet_project_index');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur de base de données : ' . $e->getMessage());
        }
    }

    return $this->render('gs-projet/project/new.html.twig', [
        'form' => $form->createView(),
    ]);
}
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Projet mis à jour avec succès');
            return $this->redirectToRoute('gs-projet_project_index');
        }

        return $this->render('gs-projet/project/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        $groupedMissions = [
            'To Do' => [],
            'In Progress' => [],
            'Done' => []
        ];
    
        foreach ($project->getMissions() as $mission) {
            $groupedMissions[$mission->getStatus()][] = $mission;
        }
    
        return $this->render('gs-projet/project/show.html.twig', [
            'project' => $project,
            'groupedMissions' => $groupedMissions,
        ]);
    }
#[Route('/{id}', name: 'delete', methods: ['POST'])]
public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
        $entityManager->remove($project);
        $entityManager->flush();
        $this->addFlash('success', 'Projet supprimé avec succès');
    }

    return $this->redirectToRoute('gs-projet_project_index');
}
}
