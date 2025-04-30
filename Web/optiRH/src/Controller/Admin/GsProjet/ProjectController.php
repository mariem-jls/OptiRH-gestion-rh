<?php

namespace App\Controller\Admin\GsProjet;
use Knp\Snappy\Pdf;
use App\Service\GeminiAnalysisService;
use Symfony\Component\Process\Process;
use App\Service\MeetLinkGenerator;
use Twig\Environment;
use Symfony\Component\Form\FormInterface; // Correction de l'import
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\MissionNotificationService;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\GsProjet\Mission;
use App\Entity\GsProjet\Project;
use App\Form\GsProjet\ProjectType;
use App\Form\GsProjet\ProjectFilterType;
use App\Repository\GsProjet\ProjectRepository;
use App\Repository\GsProjet\MissionRepository;

use Psr\Log\LoggerInterface;

#[Route('/gs-projet/project', name: 'gs-projet_project_')]

class ProjectController extends AbstractController {
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status');
    
        $projects = $projectRepository->findWithFilters(
            $search,
            $status,
            $request->query->getInt('page', 1)
        );
    
        if ($request->isXmlHttpRequest()) {
            return $this->render('gs-projet/project/_list.html.twig', [
                'projects' => $projects
            ]);
        }
    
        return $this->render('gs-projet/project/index.html.twig', [
            'projects' => $projects,
            'current_filters' => [
                'search' => $search,
                'status' => $status
            ]
        ]);
    }
    #[Route('/api/mission/chatbot/{id}', name: 'mission_chatbot', methods: ['POST'])]
    public function chatbot(Request $request, GeminiAnalysisService $geminiService, $id): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';
        $missionData = $data['missionData'] ?? [];

        if (empty($question) || empty($missionData)) {
            return $this->json(['error' => 'Question ou données de mission manquantes'], 400);
        }

        try {
            $response = $geminiService->generateMissionChatbotResponse($question, $missionData);
            return $this->json(['response' => $response]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur chatbot Gemini', [
                'error' => $e->getMessage(),
                'mission_id' => $id
            ]);
            return $this->json(['error' => 'Erreur lors de la génération de la réponse'], 500);
        }
    }
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        MeetLinkGenerator $meetGenerator,
        LoggerInterface $logger
    ): Response {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
    
        try {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    // Génération du lien Meet avec validation
                    $meetCode = $meetGenerator->CreateMeetLink($project);
                    $project->setMeetLink($meetCode);
                    
                    // Audit
                    $logger->info('Création projet avec Meet', [
                        'project_id' => $project->getId(),
                        'meet_code' => $meetCode,
                        'user' => $this->getUser()->getUserIdentifier()
                    ]);
    
                    // Persistance
                    $project->setCreatedBy($this->getUser());
                    $entityManager->persist($project);
                    $entityManager->flush();
    
                    // Réponse adaptée au type de requête
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'status' => 'success',
                            'message' => 'Projet créé avec succès !',
                            'meetLink' => $project->getMeetLink(),
                            'redirect' => $this->generateUrl('gs-projet_project_show', ['id' => $project->getId()])
                        ]);
                    }
    
                    $this->addFlash('success', 'Projet créé avec lien Meet généré');
                    return $this->redirectToRoute('gs-projet_project_show', ['id' => $project->getId()]);
                } else {
                    // Gestion des erreurs de formulaire
                    $errors = $this->getFormErrors($form);
                    $logger->error('Erreur validation formulaire projet', ['errors' => $errors]);
    
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'status' => 'error',
                            'errors' => $errors
                        ], 400);
                    }
                }
            }
        } catch (\Exception $e) {
            $logger->critical('Erreur création projet', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Erreur serveur lors de la création'
                ], 500);
            }
    
            $this->addFlash('error', 'Une erreur critique est survenue');
        }
    
        // Rendu pour requête normale
        return $this->render('gs-projet/project/new.html.twig', [
            'form' => $form->createView(),
            'meetPattern' => 'xxx-yyyy-zzz' // Info pour le front
        ]);
    }

    /**
     * Génère un code aléatoire pour Google Meet (format: xxx-yyyy-zzz)
     */
    private function generateMeetCode(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $lengths = [3, 4, 3]; // Format des segments du code
        
        $codeParts = [];
        foreach ($lengths as $length) {
            $codeParts[] = substr(str_shuffle($characters), 0, $length);
        }
        
        return implode('-', $codeParts);
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
    #[IsGranted('ROLE_ADMIN')]

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

    #[Route('/{id}/pdf', name: 'project_pdf')]
    #[IsGranted('ROLE_ADMIN')]

public function generatePdf(Project $project, Environment $twig, Pdf $knpSnappyPdf): Response
{
    $html = $twig->render('gs-projet/project/pdf_template.html.twig', [
        'project' => $project,
        'groupedMissions' => $this->groupMissions($project), // à adapter selon ta logique
    ]);

    $pdfContent = $knpSnappyPdf->getOutputFromHtml($html);

    return new Response(
        $pdfContent,
        200,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rapport.pdf"',
        ]
    );
}

private function groupMissionsByStatus(array $missions): array
{
    $grouped = [];
    foreach ($missions as $mission) {
        $grouped[$mission->getStatus()][] = $mission;
    }
    return $grouped;
}
   
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Project $project, MissionRepository $missionRepository): Response
    {
        $user = $this->getUser();
        if ($project->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException();
        }
    
        $stats = $missionRepository->getProjectStats($project);
        $groupedMissions = $missionRepository->findGroupedByStatus($project);
        $overdueMissions = $missionRepository->findOverdueMissions($project);
    
        return $this->render('gs-projet/project/show.html.twig', [
            'project' => $project,
            'groupedMissions' => $groupedMissions,
            'overdueMissions' => $overdueMissions,
            'totalTasks' => $stats['total'] ?? 0,
            'completedTasks' => $stats['completed'] ?? 0,
            'overdueTasks' => count($overdueMissions),
            'membersCount' => $stats['members'] ?? 0
        ]);
    }
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if ($project->getCreatedBy() !== $user) {
            return $this->handleErrorResponse($request, 'Accès non autorisé', 403);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$project->getId(), $submittedToken)) {
            return $this->handleErrorResponse($request, 'Token CSRF invalide', 400);
        }

        try {
            // 1. Supprimer toutes les missions associées
            $missions = $project->getMissions();
            foreach ($missions as $mission) {
                $entityManager->remove($mission);
            }
            $entityManager->flush();

            // 2. Supprimer le projet
            $entityManager->remove($project);
            $entityManager->flush();

            return $this->handleSuccessResponse($request, 'Projet et toutes ses missions supprimés avec succès');

        } catch (\Exception $e) {
            return $this->handleErrorResponse(
                $request,
                'Erreur lors de la suppression : ' . $e->getMessage(),
                500
            );
        }
    }

    private function handleSuccessResponse(Request $request, string $message): Response
    {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'status' => 'success',
                'message' => $message,
                'redirect' => $this->generateUrl('gs-projet_project_index')
            ]);
        }

        $this->addFlash('success', $message);
        return $this->redirectToRoute('gs-projet_project_index');
    }

    private function handleErrorResponse(Request $request, string $message, int $statusCode): Response
    {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'status' => 'error',
                'message' => $message
            ], $statusCode);
        }

        $this->addFlash('error', $message);
        return $this->redirectToRoute('gs-projet_project_show', ['id' => $request->attributes->get('id')]);
    }

    private function groupMissions(Project $project): array
    {
        $groupedMissions = [];
        foreach ($project->getMissions() as $mission) {
            $status = $mission->getStatus();

            if (!isset($groupedMissions[$status])) {
                $groupedMissions[$status] = [];
            }

            $groupedMissions[$status][] = $mission;
        }
        return $groupedMissions;
    }

    #[Route('/{id}/check-missions', name: 'check_missions', methods: ['GET'])]
    public function checkMissions(Project $project, MissionRepository $missionRepository): JsonResponse
    {
        $activeMissionsCount = $missionRepository->count([
            'project' => $project,
            'status' => ['In Progress', 'To Do']
        ]);

        return $this->json([
            'hasActiveMissions' => $activeMissionsCount > 0,
            'activeMissionsCount' => $activeMissionsCount
        ]);
    }
    #[Route('/mission/{id}/send-invitation', name: 'mission_send_invitation', methods: ['POST'])]
    public function sendInvitation(Mission $mission, MissionNotificationService $notificationService): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }
    
        try {
            // Plus besoin de récupérer le meetLink depuis la requête
            // On utilise directement celui de la mission
            $notificationService->sendMeetInvitation($mission);
    
            return new JsonResponse([
                'success' => true,
                'message' => 'Invitation envoyée avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'invitation: ' . $e->getMessage()
            ], 500);
        }
    }
}